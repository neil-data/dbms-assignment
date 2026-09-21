<?php
/**
 * CEMS - Delete Student Endpoint (Admin Only)
 * POST /backend/students/delete.php
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
$id = $input['student_id'] ?? null;

if (!isValidId($id)) {
    sendError('Valid student ID is required.', ['student_id' => 'Invalid ID.'], 422);
}

try {
    $pdo = Database::getConnection();

    // Cascading delete will remove their registrations automatically due to FK ON DELETE CASCADE
    $stmt = $pdo->prepare('DELETE FROM student WHERE student_id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        sendError('Student record not found.', ['student_id' => 'No matching record.'], 404);
    }

    sendSuccess('Student record and associated passes deleted successfully.', ['student_id' => (int)$id]);
} catch (PDOException $e) {
    error_log('Delete student error: ' . $e->getMessage());
    sendError('Failed to delete student record.', ['database' => 'Query execution failed.'], 500);
}
