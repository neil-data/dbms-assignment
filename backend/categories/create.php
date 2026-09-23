<?php
/**
 * CEMS - Create Category Endpoint (Admin Only)
 * POST /backend/categories/create.php
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
$name  = sanitizeString($input['category_name'] ?? null);

if (empty($name)) {
    sendError('Category name is required.', ['category_name' => 'Name cannot be blank.'], 422);
}

try {
    $pdo = Database::getConnection();

    $check = $pdo->prepare('SELECT category_id FROM category WHERE category_name = ?');
    $check->execute([$name]);
    if ($check->fetch()) {
        sendError('A category with this name already exists.', ['category_name' => 'Must be unique.'], 409);
    }

    $stmt = $pdo->prepare('INSERT INTO category (category_name) VALUES (?)');
    $stmt->execute([$name]);

    $id = (int)$pdo->lastInsertId();

    sendSuccess('Category created successfully.', [
        'category_id'   => $id,
        'category_name' => $name,
        'slug'          => strtolower($name)
    ], 201);
} catch (PDOException $e) {
    error_log('Create category error: ' . $e->getMessage());
    sendError('Failed to create category record.', ['database' => 'Query execution failed.'], 500);
}
