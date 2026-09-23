<?php
/**
 * CEMS - Delete Registration Endpoint (Admin Only)
 * POST /backend/registrations/delete.php
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
$regId = $input['registration_id'] ?? null;

if (!isValidId($regId)) {
    sendError('Valid registration ID is required.', ['registration_id' => 'Parameter missing or invalid.'], 422);
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare('DELETE FROM registration WHERE registration_id = ?');
    $stmt->execute([$regId]);

    if ($stmt->rowCount() === 0) {
        sendError('Registration record not found.', [], 404);
    }

    sendSuccess('Registration record permanently deleted from database.', ['registration_id' => (int)$regId]);

} catch (PDOException $e) {
    error_log('Delete registration error: ' . $e->getMessage());
    sendError('Failed to delete registration record.', ['database' => 'Query execution failed.'], 500);
}
