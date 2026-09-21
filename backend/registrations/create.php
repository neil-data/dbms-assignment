<?php
/**
 * CEMS - Event Registration Endpoint (ACID Transaction)
 * POST /backend/registrations/create.php
 * Conforms strictly to Requirements 14 & 15:
 * START TRANSACTION -> Lock Event -> Check Capacity -> Check Duplicate -> INSERT -> COMMIT / ROLLBACK
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../shared/validation.php';

// 1. Verify Student Authentication
$session = requireStudentAuth();
$studentId = (int)$session['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

$input   = getRequestData();
$eventId = $input['event_id'] ?? null;

if (!isValidId($eventId)) {
    sendError('Valid event ID is required for registration.', ['event_id' => 'Parameter missing or invalid.'], 422);
}

$eventId = (int)$eventId;

try {
    $pdo = Database::getConnection();

    // -------------------------------------------------------------------------
    // BEGIN ACID TRANSACTION
    // -------------------------------------------------------------------------
    $pdo->beginTransaction();

    // 2. Lock event record and verify existence & status (FOR UPDATE ensures atomicity under concurrency)
    $evtStmt = $pdo->prepare('
        SELECT event_id, event_name, event_date, event_time, venue_id, max_capacity, status
        FROM event
        WHERE event_id = ?
        FOR UPDATE
    ');
    $evtStmt->execute([$eventId]);
    $event = $evtStmt->fetch();

    if (!$event) {
        $pdo->rollBack();
        sendError('Event not found in institutional catalog.', ['event_id' => 'Invalid event.'], 404);
    }

    // 3. Verify event is open for registration
    if ($event['status'] === 'COMPLETED' || $event['status'] === 'CANCELLED') {
        $pdo->rollBack();
        sendError("Registration closed. This event is marked as {$event['status']}.", [
            'status' => 'Event is not accepting registrations.'
        ], 400);
    }

    // 4. Check for duplicate registration by the same student (Enforcing UNIQUE(student_id, event_id))
    $dupStmt = $pdo->prepare('
        SELECT registration_id, status 
        FROM registration 
        WHERE student_id = ? AND event_id = ?
        FOR UPDATE
    ');
    $dupStmt->execute([$studentId, $eventId]);
    $existing = $dupStmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'CONFIRMED' || $existing['status'] === 'PENDING') {
            $pdo->rollBack();
            sendError('Duplicate registration rejected. You have already registered for this event.', [
                'unique_constraint' => 'Enforced by UNIQUE(student_id, event_id) relational constraint.',
                'registration_id'   => (int)$existing['registration_id']
            ], 409);
        } elseif ($existing['status'] === 'CANCELLED') {
            // Re-activate previously cancelled registration
            $reopenStmt = $pdo->prepare('
                UPDATE registration 
                SET status = "CONFIRMED", registration_date = NOW()
                WHERE registration_id = ?
            ');
            $reopenStmt->execute([$existing['registration_id']]);

            $pdo->commit();

            sendSuccess('Registration reinstated successfully.', [
                'registration_id'   => (int)$existing['registration_id'],
                'student_id'        => $studentId,
                'event_id'          => $eventId,
                'event_name'        => $event['event_name'],
                'status'            => 'CONFIRMED',
                'ticket_token'      => 'CEMS-PASS-' . str_pad((string)$existing['registration_id'], 6, '0', STR_PAD_LEFT)
            ], 200);
        }
    }

    // 5. Check available capacity (Count currently CONFIRMED registrations)
    $capStmt = $pdo->prepare('
        SELECT COUNT(*) AS current_confirmed
        FROM registration
        WHERE event_id = ? AND status = "CONFIRMED"
    ');
    $capStmt->execute([$eventId]);
    $currentConfirmed = (int)$capStmt->fetchColumn();

    if ($currentConfirmed >= (int)$event['max_capacity']) {
        // Quota full -> Abort transaction
        $pdo->rollBack();
        sendError('Registration limit reached. This event has reached maximum capacity.', [
            'capacity' => "All {$event['max_capacity']} seats are reserved."
        ], 409);
    }

    // 6. Insert new registration record
    $insStmt = $pdo->prepare('
        INSERT INTO registration (student_id, event_id, registration_date, status)
        VALUES (?, ?, NOW(), "CONFIRMED")
    ');
    $insStmt->execute([$studentId, $eventId]);
    $registrationId = (int)$pdo->lastInsertId();

    // 7. COMMIT transaction: Atomic operation complete
    $pdo->commit();

    // Generate formatted pass token
    $ticketToken = 'CEMS-PASS-' . str_pad((string)$registrationId, 6, '0', STR_PAD_LEFT);

    sendSuccess('Registration confirmed! Digital pass generated.', [
        'registration_id'   => $registrationId,
        'student_id'        => $studentId,
        'event_id'          => $eventId,
        'event_name'        => $event['event_name'],
        'status'            => 'CONFIRMED',
        'registration_date' => date('Y-m-d H:i:s'),
        'ticket_token'      => $ticketToken,
        'seats_remaining'   => ((int)$event['max_capacity'] - ($currentConfirmed + 1))
    ], 201);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Registration transaction error: ' . $e->getMessage());
    sendError('Registration transaction aborted due to database error.', [
        'transaction' => 'Rolled back. No changes were committed.'
    ], 500);
}
