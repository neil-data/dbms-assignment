<?php
/**
 * CEMS - Get Student Profile Endpoint
 * GET /backend/students/get.php?id=...
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../shared/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed. Use GET.', [], 405);
}

$session = getCurrentSession();
if (!$session) {
    sendError('Authentication required.', [], 401);
}

$idParam = $_GET['id'] ?? null;

// Students can only view their own profile unless admin
if ($session['role'] === 'student') {
    $targetId = (int)$session['user_id'];
} else {
    $targetId = isValidId($idParam) ? (int)$idParam : (int)$session['user_id'];
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare('
        SELECT 
            s.student_id,
            s.department_id,
            s.name,
            s.email,
            s.phone,
            s.semester,
            s.created_at,
            d.department_name,
            COUNT(DISTINCT r.registration_id) AS total_registrations
        FROM student s
        INNER JOIN department d ON s.department_id = d.department_id
        LEFT JOIN registration r ON s.student_id = r.student_id
        WHERE s.student_id = ?
        GROUP BY s.student_id, s.department_id, s.name, s.email, s.phone, s.semester, s.created_at, d.department_name
        LIMIT 1
    ');
    $stmt->execute([$targetId]);
    $student = $stmt->fetch();

    if (!$student) {
        sendError('Student record not found.', [], 404);
    }

    sendSuccess('Student profile retrieved successfully.', $student);
} catch (PDOException $e) {
    error_log('Get student error: ' . $e->getMessage());
    sendError('Failed to retrieve student profile.', ['database' => 'Query execution failed.'], 500);
}
