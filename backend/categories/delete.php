<?php
/**
 * CEMS - Delete Category Endpoint (Admin Only)
 * POST /backend/categories/delete.php
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
$id    = $input['category_id'] ?? null;

if (!isValidId($id)) {
    sendError('Valid category ID is required.', ['category_id' => 'Invalid ID.'], 422);
}

try {
    $pdo = Database::getConnection();

    // Remove relations in event_category first
    $delPivot = $pdo->prepare('DELETE FROM event_category WHERE category_id = ?');
    $delPivot->execute([$id]);

    $stmt = $pdo->prepare('DELETE FROM category WHERE category_id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        sendError('Category not found.', ['category_id' => 'No matching record.'], 404);
    }

    sendSuccess('Category deleted successfully.', ['category_id' => (int)$id]);
} catch (PDOException $e) {
    error_log('Delete category error: ' . $e->getMessage());
    sendError('Failed to delete category record.', ['database' => 'Query execution failed.'], 500);
}
