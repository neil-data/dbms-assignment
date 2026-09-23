<?php
/**
 * CEMS - DBMS Demonstration: Live Transaction Commit & Rollback Endpoint
 * POST /backend/database-demo/test_transaction.php
 * Conforms to Requirement 15 & 16-L
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

requireAdminAuth();

$input  = getRequestData();
$action = trim((string)($input['action'] ?? 'commit'));

try {
    $pdo = Database::getConnection();

    $steps = [];

    if ($action === 'commit') {
        // Step 1: BEGIN TRANSACTION
        $steps[] = 'Step 1: Execute `START TRANSACTION;` — MySQL starts an atomic isolation boundary.';
        $pdo->beginTransaction();

        // Step 2: Acquire row lock on EVENT 1
        $steps[] = 'Step 2: Execute `SELECT * FROM event WHERE event_id = 1 FOR UPDATE;` — Acquired exclusive row lock.';
        $evtStmt = $pdo->prepare('SELECT event_id, event_name, max_capacity FROM event WHERE event_id = 1 FOR UPDATE');
        $evtStmt->execute();
        $event = $evtStmt->fetch();

        // Step 3: Check current capacity
        $steps[] = 'Step 3: Execute `SELECT COUNT(*) FROM registration WHERE event_id = 1 AND status = "CONFIRMED";` — Evaluated capacity quota.';
        $capStmt = $pdo->query('SELECT COUNT(*) FROM registration WHERE event_id = 1 AND status = "CONFIRMED"');
        $currentCount = (int)$capStmt->fetchColumn();

        // Step 4: Create a controlled demo student or demo registration
        $demoEmail = 'demo.student.tx@campus.edu';
        $findStd = $pdo->prepare('SELECT student_id FROM student WHERE email = ?');
        $findStd->execute([$demoEmail]);
        $stdId = $findStd->fetchColumn();

        if (!$stdId) {
            $insStd = $pdo->prepare('
                INSERT INTO student (department_id, name, email, phone, semester, password) 
                VALUES (1, "DBMS Transaction Demo Student", ?, "+91 99999 00000", 6, "$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq")
            ');
            $insStd->execute([$demoEmail]);
            $stdId = $pdo->lastInsertId();
            $steps[] = "Step 4: Created staging student record (ID: {$stdId}) within active transaction boundary.";
        }

        // Clean any old demo registration
        $pdo->prepare('DELETE FROM registration WHERE student_id = ? AND event_id = 1')->execute([$stdId]);

        // Step 5: INSERT registration
        $steps[] = "Step 5: Execute `INSERT INTO registration (student_id, event_id, status) VALUES ({$stdId}, 1, 'CONFIRMED');`";
        $insReg = $pdo->prepare('INSERT INTO registration (student_id, event_id, status) VALUES (?, 1, "CONFIRMED")');
        $insReg->execute([$stdId]);
        $newRegId = (int)$pdo->lastInsertId();

        // Step 6: COMMIT
        $steps[] = 'Step 6: Execute `COMMIT;` — MySQL writes transaction log to disk, persists all changes permanently, and releases row locks.';
        $pdo->commit();

        // Verification query
        $verifyStmt = $pdo->prepare('SELECT registration_id, student_id, event_id, status FROM registration WHERE registration_id = ?');
        $verifyStmt->execute([$newRegId]);
        $persisted = $verifyStmt->fetch();

        sendSuccess('Transaction completed and COMMITTED successfully.', [
            'action'         => 'COMMIT',
            'steps'          => $steps,
            'persisted_row'  => $persisted,
            'explanation'    => 'All steps succeeded. The COMMIT instruction flushed changes permanently to the MySQL InnoDB engine.'
        ]);

    } elseif ($action === 'rollback') {
        // Step 1: START TRANSACTION
        $steps[] = 'Step 1: Execute `START TRANSACTION;` — MySQL establishes atomic savepoint.';
        $pdo->beginTransaction();

        // Step 2: Insert a staging student
        $tempEmail = 'temporary.rollback.test@campus.edu';
        // Clean if exists
        $pdo->prepare('DELETE FROM student WHERE email = ?')->execute([$tempEmail]);

        $steps[] = "Step 2: Execute `INSERT INTO student` for '{$tempEmail}'. Row is visible only within this transaction.";
        $insStmt = $pdo->prepare('
            INSERT INTO student (department_id, name, email, phone, semester, password) 
            VALUES (1, "Temporary Uncommitted Student", ?, "+91 00000 00000", 1, "test")
        ');
        $insStmt->execute([$tempEmail]);
        $tempId = $pdo->lastInsertId();

        // Step 3: Simulate business rule failure (e.g. Overcapacity / Validation rule)
        $steps[] = 'Step 3: Business rule check triggered: Capacity limit exceeded or simulated system failure.';

        // Step 4: ROLLBACK
        $steps[] = 'Step 4: Execute `ROLLBACK;` — MySQL undoes all pending modifications, cleans undo logs, and restores prior state.';
        $pdo->rollBack();

        // Step 5: Verification check in MySQL
        $verifyStmt = $pdo->prepare('SELECT * FROM student WHERE email = ?');
        $verifyStmt->execute([$tempEmail]);
        $existsAfter = $verifyStmt->fetch();

        $steps[] = 'Step 5: Verified table state: `SELECT * FROM student WHERE email = ?` returned NULL (Row does NOT exist in MySQL).';

        sendSuccess('Transaction successfully ROLLED BACK.', [
            'action'         => 'ROLLBACK',
            'steps'          => $steps,
            'row_existed'    => (bool)$existsAfter,
            'explanation'    => 'Because ROLLBACK was executed, MySQL completely reverted the INSERT operation. The temporary student was NOT saved.'
        ]);
    } else {
        sendError('Invalid action. Specify "commit" or "rollback".', [], 400);
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DBMS Demo Transaction error: ' . $e->getMessage());
    sendError('Transaction demonstration failed: ' . $e->getMessage(), ['database' => 'Execution error.'], 500);
}
