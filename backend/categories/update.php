<?php
/**
 * CEMS - Update Category Endpoint (Admin Only)
 * POST /backend/categories/update.php
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
$name  = sanitizeString($input['category_name'] ?? null);

if (!isValidId($id)) {
    sendError('Valid category ID is required.', ['category_id' => 'Invalid ID.'], 422);
}
if (empty($name)) {
    sendError('Category name is required.', ['category_name' => 'Name cannot be blank.'], 422);
}

try {
    $pdo = Database::getConnection();

    $check = $pdo->prepare('SELECT category_id FROM category WHERE category_name = ? AND category_id != ?');
    $check->execute([$name, $id]);
    if ($check->fetch()) {
        sendError('Another category with this name already exists.', ['category_name' => 'Must be unique.'], 409);
    }

    $stmt = $pdo->prepare('UPDATE category SET category_name = ? WHERE category_id = ?');
    $stmt->execute([$name, $id]);

    sendSuccess('Category updated successfully.', [
        'category_id'   => (int)$id,
        'category_name' => $name,
        'slug'          => strtolower($name)
    ]);
} catch (PDOException $e) {
    error_log('Update category error: ' . $e->getMessage());
    sendError('Failed to update category record.', ['database' => 'Query execution failed.'], 500);
}
