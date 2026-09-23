<?php
/**
 * CEMS - Create Department Endpoint (Admin Only)
 * POST /backend/departments/create.php
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
$name = sanitizeString($input['department_name'] ?? null);

if (empty($name)) {
    sendError('Department name is required.', ['department_name' => 'Name cannot be blank.'], 422);
}

try {
    $pdo = Database::getConnection();

    // Check duplicate
    $check = $pdo->prepare('SELECT department_id FROM department WHERE department_name = ?');
    $check->execute([$name]);
    if ($check->fetch()) {
        sendError('A department with this name already exists.', ['department_name' => 'Must be unique.'], 409);
    }

    $stmt = $pdo->prepare('INSERT INTO department (department_name) VALUES (?)');
    $stmt->execute([$name]);

    $id = (int)$pdo->lastInsertId();

    sendSuccess('Department created successfully.', [
        'department_id'   => $id,
        'department_name' => $name
    ], 201);
} catch (PDOException $e) {
    error_log('Create department error: ' . $e->getMessage());
    sendError('Failed to create department record.', ['database' => 'Query execution failed.'], 500);
}
