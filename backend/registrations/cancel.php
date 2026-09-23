<?php
/**
 * CEMS - Cancel Registration Endpoint
 * POST /backend/registrations/cancel.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../shared/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

$session = getCurrentSession();
if (!$session) {
    sendError('Authentication required.', [], 401);
}

$input = getRequestData();
$regId = $input['registration_id'] ?? null;

if (!isValidId($regId)) {
    sendError('Valid registration ID is required.', ['registration_id' => 'Parameter missing or invalid.'], 422);
}

try {
    $pdo = Database::getConnection();

    // Verify registration exists
    $stmt = $pdo->prepare('SELECT registration_id, student_id, event_id, status FROM registration WHERE registration_id = ?');
    $stmt->execute([$regId]);
    $reg = $stmt->fetch();

    if (!$reg) {
        sendError('Registration record not found.', [], 404);
    }

    // Students can only cancel their own registrations
    if ($session['role'] === 'student' && (int)$reg['student_id'] !== (int)$session['user_id']) {
        sendError('Forbidden. You can only cancel your own registrations.', [], 403);
    }

    if ($reg['status'] === 'CANCELLED') {
        sendError('Registration pass is already marked as cancelled.', [], 400);
    }

    $upStmt = $pdo->prepare('UPDATE registration SET status = "CANCELLED" WHERE registration_id = ?');
    $upStmt->execute([$regId]);

    sendSuccess('Registration pass has been cancelled successfully. Released seat back to quota.', [
        'registration_id' => (int)$regId,
        'status'          => 'CANCELLED'
    ]);

} catch (PDOException $e) {
    error_log('Cancel registration error: ' . $e->getMessage());
    sendError('Failed to cancel registration.', ['database' => 'Query execution failed.'], 500);
}
