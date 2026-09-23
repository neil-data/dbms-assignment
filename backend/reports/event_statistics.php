<?php
/**
 * CEMS - Event Utilization Statistics Endpoint
 * GET /backend/reports/event_statistics.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed. Use GET.', [], 405);
}

requireAdminAuth();

try {
    $pdo = Database::getConnection();

    // Query view_event_registration_summary or direct query
    $stmt = $pdo->query('
        SELECT 
            e.event_id,
            e.event_name,
            e.event_date,
            e.event_time,
            e.status AS event_status,
            v.venue_name,
            e.max_capacity,
            COUNT(CASE WHEN r.status = "CONFIRMED" THEN 1 END) AS enrolled_count,
            (e.max_capacity - COUNT(CASE WHEN r.status = "CONFIRMED" THEN 1 END)) AS available_seats,
            ROUND((COUNT(CASE WHEN r.status = "CONFIRMED" THEN 1 END) / e.max_capacity) * 100, 1) AS occupancy_percentage
        FROM event e
        INNER JOIN venue v ON e.venue_id = v.venue_id
        LEFT JOIN registration r ON e.event_id = r.event_id
        GROUP BY e.event_id, e.event_name, e.event_date, e.event_time, e.status, v.venue_name, e.max_capacity
        ORDER BY enrolled_count DESC
    ');

    $report = $stmt->fetchAll();

    sendSuccess('Event capacity utilization statistics retrieved.', $report);
} catch (PDOException $e) {
    error_log('Event stats error: ' . $e->getMessage());
    sendError('Failed to query event statistics.', ['database' => 'Query execution failed.'], 500);
}
