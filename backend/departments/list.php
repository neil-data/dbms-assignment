<?php
/**
 * CEMS - List Departments Endpoint
 * GET /backend/departments/list.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->query('
        SELECT 
            d.department_id,
            d.department_name,
            COUNT(s.student_id) AS student_count
        FROM department d
        LEFT JOIN student s ON d.department_id = s.department_id
        GROUP BY d.department_id, d.department_name
        ORDER BY d.department_id ASC
    ');

    $departments = $stmt->fetchAll();

    sendSuccess('Departments retrieved successfully.', $departments);
} catch (PDOException $e) {
    error_log('Departments list error: ' . $e->getMessage());
    sendError('Failed to retrieve department records.', ['database' => 'Query execution failed.'], 500);
}
