<?php
/**
 * CEMS - Department Statistics Report Endpoint
 * GET /backend/reports/department_statistics.php
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
            d.department_id,
            d.department_name,
            COUNT(DISTINCT s.student_id) AS student_count,
            COUNT(DISTINCT r.registration_id) AS total_registrations,
            COUNT(DISTINCT CASE WHEN r.status = "CONFIRMED" THEN r.registration_id END) AS confirmed_passes,
            ROUND((COUNT(DISTINCT s.student_id) / NULLIF((SELECT COUNT(*) FROM student), 0)) * 100, 1) AS student_percentage
        FROM department d
        LEFT JOIN student s ON d.department_id = s.department_id
        LEFT JOIN registration r ON s.student_id = r.student_id
        GROUP BY d.department_id, d.department_name
        ORDER BY student_count DESC
    ');

    $report = $stmt->fetchAll();

    sendSuccess('Department statistics retrieved.', $report);
} catch (PDOException $e) {
    error_log('Department stats error: ' . $e->getMessage());
    sendError('Failed to query department statistics.', ['database' => 'Query execution failed.'], 500);
}
