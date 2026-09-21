<?php
/**
 * CEMS - Admin Dashboard Metrics Endpoint
 * GET /backend/reports/dashboard.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

requireAdminAuth();

try {
    $pdo = Database::getConnection();

    $statsStmt = $pdo->query('
        SELECT
            (SELECT COUNT(*) FROM student) AS total_students,
            (SELECT COUNT(*) FROM event) AS total_events,
            (SELECT COUNT(*) FROM registration WHERE status = "CONFIRMED") AS total_active_registrations,
            (SELECT COUNT(*) FROM registration) AS total_all_registrations,
            (SELECT COUNT(*) FROM venue) AS total_venues,
            (SELECT COUNT(*) FROM department) AS total_departments,
            (SELECT COUNT(*) FROM category) AS total_categories,
            (SELECT COALESCE(SUM(capacity), 0) FROM venue) AS total_campus_capacity,
            (SELECT COALESCE(ROUND(AVG(max_capacity), 1), 0) FROM event) AS average_event_capacity
    ');

    $stats = $statsStmt->fetch();

    sendSuccess('Dashboard metrics retrieved successfully.', [
        'totalStudents'             => (int)$stats['total_students'],
        'totalEvents'                => (int)$stats['total_events'],
        'totalRegistrations'         => (int)$stats['total_active_registrations'],
        'totalAllRegistrations'      => (int)$stats['total_all_registrations'],
        'totalVenues'                => (int)$stats['total_venues'],
        'totalDepartments'           => (int)$stats['total_departments'],
        'totalCategories'            => (int)$stats['total_categories'],
        'totalCampusCapacity'        => (int)$stats['total_campus_capacity'],
        'averageEventCapacity'       => (float)$stats['average_event_capacity']
    ]);
} catch (PDOException $e) {
    error_log('Dashboard metrics error: ' . $e->getMessage());
    sendError('Failed to calculate dashboard statistics.', ['database' => 'Query execution failed.'], 500);
}
