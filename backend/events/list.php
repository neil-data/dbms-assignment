<?php
/**
 * CEMS - List Events Catalog Endpoint
 * GET /backend/events/list.php
 * Supports Search, Category Filter, Status Filter, and Sorting.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

try {
    $pdo = Database::getConnection();

    $search   = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? 'all');
    $status   = trim($_GET['status'] ?? 'all');
    $sort     = trim($_GET['sort'] ?? 'date_asc');
    $featured = isset($_GET['featured']) && $_GET['featured'] === '1';

    // Base query joining EVENT with VENUE and aggregating confirmed REGISTRATION count
    $sql = '
        SELECT 
            e.event_id,
            e.event_name,
            e.event_name AS title,
            e.description,
            e.event_date,
            e.event_date AS date,
            DATE_FORMAT(e.event_date, "%W, %M %e, %Y") AS display_date,
            e.event_time,
            e.event_time AS time,
            e.max_capacity,
            e.status,
            v.venue_id,
            v.venue_name,
            v.location AS venue_location,
            v.capacity AS venue_capacity,
            COUNT(CASE WHEN r.status = "CONFIRMED" THEN 1 END) AS registered_count,
            GREATEST(0, e.max_capacity - COUNT(CASE WHEN r.status = "CONFIRMED" THEN 1 END)) AS available_seats
        FROM event e
        INNER JOIN venue v ON e.venue_id = v.venue_id
        LEFT JOIN registration r ON e.event_id = r.event_id
    ';

    $whereClauses = [];
    $params = [];

    // Search filter
    if (!empty($search)) {
        $whereClauses[] = '(e.event_name LIKE ? OR e.description LIKE ? OR v.venue_name LIKE ?)';
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Category filter
    if (!empty($category) && $category !== 'all') {
        $whereClauses[] = 'e.event_id IN (
            SELECT ec.event_id 
            FROM event_category ec 
            INNER JOIN category c ON ec.category_id = c.category_id 
            WHERE LOWER(c.category_name) = LOWER(?) OR c.category_id = ?
        )';
        $params[] = $category;
        $params[] = $category;
    }

    // Status filter
    if (!empty($status) && $status !== 'all') {
        if ($status === 'Open' || $status === 'UPCOMING') {
            $whereClauses[] = 'e.status = "UPCOMING"';
        } elseif ($status === 'Closing Soon' || $status === 'ONGOING') {
            $whereClauses[] = 'e.status = "ONGOING"';
        } elseif ($status === 'Closed' || $status === 'COMPLETED' || $status === 'CANCELLED') {
            $whereClauses[] = 'e.status IN ("COMPLETED", "CANCELLED")';
        } else {
            $whereClauses[] = 'e.status = ?';
            $params[] = $status;
        }
    }

    if (!empty($whereClauses)) {
        $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
    }

    $sql .= ' GROUP BY e.event_id, e.event_name, e.description, e.event_date, e.event_time, e.max_capacity, e.status, v.venue_id, v.venue_name, v.location, v.capacity';

    // Sorting
    switch ($sort) {
        case 'date_desc':
            $sql .= ' ORDER BY e.event_date DESC, e.event_name ASC';
            break;
        case 'seats_left':
            $sql .= ' ORDER BY available_seats ASC, e.event_date ASC';
            break;
        case 'title_asc':
            $sql .= ' ORDER BY e.event_name ASC';
            break;
        case 'date_asc':
        default:
            $sql .= ' ORDER BY e.event_date ASC, e.event_name ASC';
            break;
    }

    if ($featured) {
        $sql .= ' LIMIT 3';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    // Fetch categories for all returned events efficiently
    if (!empty($events)) {
        $eventIds = array_column($events, 'event_id');
        $inPlaceholders = implode(',', array_fill(0, count($eventIds), '?'));

        $catStmt = $pdo->prepare("
            SELECT ec.event_id, c.category_id, c.category_name, LOWER(c.category_name) AS slug
            FROM event_category ec
            INNER JOIN category c ON ec.category_id = c.category_id
            WHERE ec.event_id IN ({$inPlaceholders})
            ORDER BY c.category_name ASC
        ");
        $catStmt->execute($eventIds);
        $categoriesRows = $catStmt->fetchAll();

        $eventCatMap = [];
        foreach ($categoriesRows as $cr) {
            $eventCatMap[$cr['event_id']][] = [
                'category_id'   => (int)$cr['category_id'],
                'category_name' => $cr['category_name'],
                'slug'          => $cr['slug']
            ];
        }

        // Format and attach nested venue and categories
        foreach ($events as &$ev) {
            $ev['event_id']         = (int)$ev['event_id'];
            $ev['max_capacity']     = (int)$ev['max_capacity'];
            $ev['registered_count'] = (int)$ev['registered_count'];
            $ev['available_seats']  = (int)$ev['available_seats'];
            $ev['categories']       = $eventCatMap[$ev['event_id']] ?? [];
            $ev['venue'] = [
                'venue_id'   => (int)$ev['venue_id'],
                'venue_name' => $ev['venue_name'],
                'location'   => $ev['venue_location'],
                'capacity'   => (int)$ev['venue_capacity']
            ];

            // Computed badge status for the frontend UI
            if ($ev['status'] === 'UPCOMING') {
                $ev['ui_status'] = ($ev['available_seats'] <= 15 && $ev['available_seats'] > 0) ? 'Closing Soon' : 'Open';
            } elseif ($ev['status'] === 'ONGOING') {
                $ev['ui_status'] = 'Ongoing';
            } else {
                $ev['ui_status'] = 'Closed';
            }
        }
        unset($ev);
    }

    sendSuccess('Events retrieved successfully.', $events);
} catch (PDOException $e) {
    error_log('Events list error: ' . $e->getMessage());
    sendError('Failed to retrieve event records.', ['database' => 'Query execution failed.'], 500);
}
