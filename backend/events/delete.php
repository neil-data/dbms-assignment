<?php
/**
 * CEMS - Delete Event Endpoint (Admin Only)
 * POST /backend/events/delete.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../shared/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

requireAdminAuth();

$input = getRequestData();
$id = $input['event_id'] ?? null;

if (!isValidId($id)) {
    sendError('Valid event ID is required.', ['event_id' => 'Parameter missing or invalid.'], 422);
}

try {
    $pdo = Database::getConnection();

    // Cascades automatically to event_category and registration due to ON DELETE CASCADE
    $stmt = $pdo->prepare('DELETE FROM event WHERE event_id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        sendError('Event record not found.', ['event_id' => 'No matching record.'], 404);
    }

    sendSuccess('Event record and associated registrations deleted successfully.', ['event_id' => (int)$id]);
} catch (PDOException $e) {
    error_log('Delete event error: ' . $e->getMessage());
    sendError('Failed to delete event record.', ['database' => 'Query execution failed.'], 500);
}
