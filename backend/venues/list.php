<?php
/**
 * CEMS - List Venues Endpoint
 * GET /backend/venues/list.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed. Use GET.', [], 405);
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->query('
        SELECT 
            v.venue_id,
            v.venue_name,
            v.location,
            v.capacity,
            COUNT(DISTINCT e.event_id) AS events_count,
            COUNT(DISTINCT r.registration_id) AS total_attendees
        FROM venue v
        LEFT JOIN event e ON v.venue_id = e.venue_id
        LEFT JOIN registration r ON e.event_id = r.event_id AND r.status = "CONFIRMED"
        GROUP BY v.venue_id, v.venue_name, v.location, v.capacity
        ORDER BY v.venue_name ASC
    ');

    $venues = $stmt->fetchAll();

    sendSuccess('Venues retrieved successfully.', $venues);
} catch (PDOException $e) {
    error_log('Venues list error: ' . $e->getMessage());
    sendError('Failed to retrieve venue records.', ['database' => 'Query execution failed.'], 500);
}
