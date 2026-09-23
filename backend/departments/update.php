<?php
/**
 * CEMS - Update Department Endpoint (Admin Only)
 * POST /backend/departments/update.php
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
$id   = $input['department_id'] ?? null;
$name = sanitizeString($input['department_name'] ?? null);

if (!isValidId($id)) {
    sendError('Valid department ID is required.', ['department_id' => 'Invalid ID.'], 422);
}
if (empty($name)) {
    sendError('Department name is required.', ['department_name' => 'Name cannot be blank.'], 422);
}

try {
    $pdo = Database::getConnection();

    // Check duplicate name for another department
    $check = $pdo->prepare('SELECT department_id FROM department WHERE department_name = ? AND department_id != ?');
    $check->execute([$name, $id]);
    if ($check->fetch()) {
        sendError('Another department with this name already exists.', ['department_name' => 'Must be unique.'], 409);
    }

    $stmt = $pdo->prepare('UPDATE department SET department_name = ? WHERE department_id = ?');
    $stmt->execute([$name, $id]);

    sendSuccess('Department updated successfully.', [
        'department_id'   => (int)$id,
        'department_name' => $name
    ]);
} catch (PDOException $e) {
    error_log('Update department error: ' . $e->getMessage());
    sendError('Failed to update department record.', ['database' => 'Query execution failed.'], 500);
}
