<?php
/**
 * CEMS - Delete Department Endpoint (Admin Only)
 * POST /backend/departments/delete.php
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
$id = $input['department_id'] ?? null;

if (!isValidId($id)) {
    sendError('Valid department ID is required.', ['department_id' => 'Invalid ID.'], 422);
}

try {
    $pdo = Database::getConnection();

    // Check if students are associated with this department (Enforcing Foreign Key integrity)
    $check = $pdo->prepare('SELECT COUNT(*) AS total FROM student WHERE department_id = ?');
    $check->execute([$id]);
    $count = (int)$check->fetchColumn();

    if ($count > 0) {
        sendError("Cannot delete department. {$count} student(s) are currently enrolled under it.", [
            'referential_integrity' => 'Foreign key constraint prevents deletion of department with active students.'
        ], 409);
    }

    $stmt = $pdo->prepare('DELETE FROM department WHERE department_id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        sendError('Department not found.', ['department_id' => 'No matching record.'], 404);
    }

    sendSuccess('Department deleted successfully.', ['department_id' => (int)$id]);
} catch (PDOException $e) {
    error_log('Delete department error: ' . $e->getMessage());
    sendError('Failed to delete department record due to database constraint.', ['database' => 'Integrity violation.'], 500);
}
