<?php
/**
 * CEMS - Full-Stack Automated Verification Test Suite
 * Executes live tests against running MySQL 8.0 and PHP PDO backend.
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/shared/auth.php';
require_once __DIR__ . '/../backend/shared/validation.php';

$passed = 0;
$failed = 0;

function assertTest(string $title, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$title}" . ($details ? " ({$details})" : "") . PHP_EOL;
        $passed++;
    } else {
        echo "  [FAIL] {$title}" . ($details ? " - {$details}" : "") . PHP_EOL;
        $failed++;
    }
}

echo PHP_EOL . "============================================================" . PHP_EOL;
echo "  CEMS FULL-STACK DATABASE & PHP BACKEND TEST SUITE" . PHP_EOL;
echo "============================================================" . PHP_EOL . PHP_EOL;

// 1. Connection Test
echo "--- 1. Testing PDO Database Connection ---" . PHP_EOL;
try {
    $pdo = Database::getConnection();
    assertTest("PDO Connection to cems_db", $pdo !== null);
    $ver = $pdo->query("SELECT VERSION()")->fetchColumn();
    assertTest("MySQL Engine Version", !empty($ver), "MySQL {$ver}");
} catch (Exception $e) {
    assertTest("PDO Connection", false, $e->getMessage());
}

// 2. Student Authentication Test
echo PHP_EOL . "--- 2. Testing Authentication ---" . PHP_EOL;
try {
    $stmt = $pdo->prepare("SELECT password FROM student WHERE email = ?");
    $stmt->execute(['aarav.sharma@campus.edu']);
    $hash = $stmt->fetchColumn();
    assertTest("Student password_verify('Student@123')", password_verify('Student@123', $hash));

    $admStmt = $pdo->prepare("SELECT password FROM admin WHERE username = ?");
    $admStmt->execute(['admin']);
    $admHash = $admStmt->fetchColumn();
    assertTest("Admin password_verify('Admin@123')", password_verify('Admin@123', $admHash));
} catch (Exception $e) {
    assertTest("Auth tests", false, $e->getMessage());
}

// 3. Foreign Key & Relational JOIN Test
echo PHP_EOL . "--- 3. Testing Relational JOINs ---" . PHP_EOL;
try {
    $joinSql = "
        SELECT r.registration_id, s.name, d.department_name, e.event_name, v.venue_name
        FROM registration r
        INNER JOIN student s ON r.student_id = s.student_id
        INNER JOIN department d ON s.department_id = d.department_id
        INNER JOIN event e ON r.event_id = e.event_id
        INNER JOIN venue v ON e.venue_id = v.venue_id
        LIMIT 5
    ";
    $rows = $pdo->query($joinSql)->fetchAll();
    assertTest("4-Table Relational JOIN", count($rows) > 0, count($rows) . " rows traversed");
} catch (Exception $e) {
    assertTest("Relational JOIN", false, $e->getMessage());
}

// 4. ACID Transaction Test: Registration & Capacity
echo PHP_EOL . "--- 4. Testing ACID Transaction & Duplicate Prevention ---" . PHP_EOL;
try {
    // Pick an existing student and event
    $testStudentId = 7; // Vikram Singh
    $testEventId   = 1; // National Collegiate Hackathon 2026

    // Clean if already there
    $pdo->prepare("DELETE FROM registration WHERE student_id = ? AND event_id = ?")->execute([$testStudentId, $testEventId]);

    // Initial capacity
    $capBefore = (int)$pdo->query("SELECT COUNT(*) FROM registration WHERE event_id = {$testEventId} AND status = 'CONFIRMED'")->fetchColumn();

    // Begin Transaction
    $pdo->beginTransaction();
    $ins = $pdo->prepare("INSERT INTO registration (student_id, event_id, status) VALUES (?, ?, 'CONFIRMED')");
    $ins->execute([$testStudentId, $testEventId]);
    $pdo->commit();

    $capAfter = (int)$pdo->query("SELECT COUNT(*) FROM registration WHERE event_id = {$testEventId} AND status = 'CONFIRMED'")->fetchColumn();
    assertTest("Atomic Registration INSERT & COMMIT", $capAfter === ($capBefore + 1), "Confirmed count: {$capBefore} -> {$capAfter}");

    // Test Duplicate Registration Prevention (UNIQUE constraint)
    $duplicateCaught = false;
    try {
        $pdo->beginTransaction();
        $insDup = $pdo->prepare("INSERT INTO registration (student_id, event_id, status) VALUES (?, ?, 'CONFIRMED')");
        $insDup->execute([$testStudentId, $testEventId]);
        $pdo->commit();
    } catch (PDOException $dupEx) {
        $pdo->rollBack();
        $duplicateCaught = true;
    }
    assertTest("UNIQUE(student_id, event_id) Rejected Duplicate", $duplicateCaught);

    // Test Transaction ROLLBACK
    $pdo->beginTransaction();
    $tempEmail = 'rollback.test@campus.edu';
    $pdo->prepare("INSERT INTO student (department_id, name, email, phone, semester, password) VALUES (1, 'Rollback Test', ?, '123', 1, 'x')")->execute([$tempEmail]);
    $pdo->rollBack();

    $checkRollback = $pdo->query("SELECT * FROM student WHERE email = '{$tempEmail}'")->fetch();
    assertTest("ACID ROLLBACK Restores Table State", $checkRollback === false);

} catch (Exception $e) {
    assertTest("Transaction test", false, $e->getMessage());
}

// 5. Database Views & Aggregates
echo PHP_EOL . "--- 5. Testing Database Views & Aggregates ---" . PHP_EOL;
try {
    $v1 = $pdo->query("SELECT * FROM view_event_registration_summary LIMIT 1")->fetch();
    assertTest("View: view_event_registration_summary", !empty($v1['event_name']));

    $v2 = $pdo->query("SELECT * FROM view_student_registrations LIMIT 1")->fetch();
    assertTest("View: view_student_registrations", !empty($v2['student_name']));

    $subquery = $pdo->query("
        SELECT COUNT(*) FROM event WHERE max_capacity > (SELECT AVG(max_capacity) FROM event)
    ")->fetchColumn();
    assertTest("Nested Subquery (Capacity > AVG)", (int)$subquery > 0, "{$subquery} events found");

} catch (Exception $e) {
    assertTest("Views & Aggregates", false, $e->getMessage());
}

// Summary
echo PHP_EOL . "============================================================" . PHP_EOL;
echo "  TEST RESULTS: {$passed} PASSED, {$failed} FAILED" . PHP_EOL;
echo "============================================================" . PHP_EOL . PHP_EOL;

if ($failed === 0) {
    exit(0);
} else {
    exit(1);
}
