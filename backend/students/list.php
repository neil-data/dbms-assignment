<?php
/**
 * CEMS - List Students Endpoint (Admin Only)
 * GET /backend/students/list.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

requireAdminAuth();

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->query('
        SELECT 
            s.student_id,
            s.department_id,
            s.name,
            s.email,
            s.phone,
            s.semester,
            s.created_at,
            d.department_name,
            COUNT(DISTINCT CASE WHEN r.status = "CONFIRMED" THEN r.registration_id END) AS confirmed_registrations,
            COUNT(DISTINCT r.registration_id) AS total_registrations
        FROM student s
        INNER JOIN department d ON s.department_id = d.department_id
        LEFT JOIN registration r ON s.student_id = r.student_id
        GROUP BY s.student_id, s.department_id, s.name, s.email, s.phone, s.semester, s.created_at, d.department_name
        ORDER BY s.student_id DESC
    ');

    $students = $stmt->fetchAll();

    sendSuccess('Students retrieved successfully.', $students);
} catch (PDOException $e) {
    error_log('Students list error: ' . $e->getMessage());
    sendError('Failed to retrieve student records.', ['database' => 'Query execution failed.'], 500);
}
