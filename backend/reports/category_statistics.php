<?php
/**
 * CEMS - Category Statistics Report Endpoint
 * GET /backend/reports/category_statistics.php
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
            c.category_id,
            c.category_name,
            COUNT(DISTINCT ec.event_id) AS events_offered,
            COUNT(DISTINCT CASE WHEN r.status = "CONFIRMED" THEN r.registration_id END) AS confirmed_registrations,
            COUNT(DISTINCT r.registration_id) AS total_registrations
        FROM category c
        LEFT JOIN event_category ec ON c.category_id = ec.category_id
        LEFT JOIN event e ON ec.event_id = e.event_id
        LEFT JOIN registration r ON e.event_id = r.event_id
        GROUP BY c.category_id, c.category_name
        ORDER BY confirmed_registrations DESC
    ');

    $report = $stmt->fetchAll();

    sendSuccess('Category statistics retrieved.', $report);
} catch (PDOException $e) {
    error_log('Category stats error: ' . $e->getMessage());
    sendError('Failed to query category statistics.', ['database' => 'Query execution failed.'], 500);
}
