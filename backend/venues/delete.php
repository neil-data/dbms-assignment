<?php
/**
 * CEMS - Delete Venue Endpoint (Admin Only)
 * POST /backend/venues/delete.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../shared/validation.php';

requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

$input = getRequestData();
$id    = $input['venue_id'] ?? null;

if (!isValidId($id)) {
    sendError('Valid venue ID is required.', ['venue_id' => 'Invalid ID.'], 422);
}

try {
    $pdo = Database::getConnection();

    // Check foreign key constraint with EVENT
    $check = $pdo->prepare('SELECT COUNT(*) AS total FROM event WHERE venue_id = ?');
    $check->execute([$id]);
    $count = (int)$check->fetchColumn();

    if ($count > 0) {
        sendError("Cannot delete venue. It is currently assigned to {$count} event(s).", [
            'referential_integrity' => 'Foreign key constraint prevents deletion of venue with active events.'
        ], 409);
    }

    $stmt = $pdo->prepare('DELETE FROM venue WHERE venue_id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        sendError('Venue not found.', ['venue_id' => 'No matching record.'], 404);
    }

    sendSuccess('Venue record deleted successfully.', ['venue_id' => (int)$id]);
} catch (PDOException $e) {
    error_log('Delete venue error: ' . $e->getMessage());
    sendError('Failed to delete venue record due to database constraint.', ['database' => 'Integrity violation.'], 500);
}
