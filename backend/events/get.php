<?php
/**
 * CEMS - Get Single Event Details Endpoint
 * GET /backend/events/get.php?id=...
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../shared/validation.php';

$id = $_GET['id'] ?? null;
if (!isValidId($id)) {
    sendError('Valid event ID is required.', ['id' => 'Parameter missing or invalid.'], 422);
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare('
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
        WHERE e.event_id = ?
        GROUP BY e.event_id, e.event_name, e.description, e.event_date, e.event_time, e.max_capacity, e.status, v.venue_id, v.venue_name, v.location, v.capacity
        LIMIT 1
    ');
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if (!$event) {
        sendError('Event record not found in institutional database.', [], 404);
    }

    // Fetch categories
    $catStmt = $pdo->prepare('
        SELECT c.category_id, c.category_name, LOWER(c.category_name) AS slug
        FROM event_category ec
        INNER JOIN category c ON ec.category_id = c.category_id
        WHERE ec.event_id = ?
        ORDER BY c.category_name ASC
    ');
    $catStmt->execute([$id]);
    $event['categories'] = $catStmt->fetchAll();

    $event['venue'] = [
        'venue_id'   => (int)$event['venue_id'],
        'venue_name' => $event['venue_name'],
        'location'   => $event['venue_location'],
        'capacity'   => (int)$event['venue_capacity']
    ];

    $event['event_id']         = (int)$event['event_id'];
    $event['max_capacity']     = (int)$event['max_capacity'];
    $event['registered_count'] = (int)$event['registered_count'];
    $event['available_seats']  = (int)$event['available_seats'];

    if ($event['status'] === 'UPCOMING') {
        $event['ui_status'] = ($event['available_seats'] <= 15 && $event['available_seats'] > 0) ? 'Closing Soon' : 'Open';
    } elseif ($event['status'] === 'ONGOING') {
        $event['ui_status'] = 'Ongoing';
    } else {
        $event['ui_status'] = 'Closed';
    }

    // Check if currently logged in student is already registered
    $session = getCurrentSession();
    $event['is_user_registered'] = false;
    $event['user_registration_id'] = null;

    if ($session && $session['role'] === 'student') {
        $regCheck = $pdo->prepare('
            SELECT registration_id, status 
            FROM registration 
            WHERE student_id = ? AND event_id = ? AND status != "CANCELLED"
            LIMIT 1
        ');
        $regCheck->execute([$session['user_id'], $id]);
        $existingReg = $regCheck->fetch();
        if ($existingReg) {
            $event['is_user_registered'] = true;
            $event['user_registration_id'] = (int)$existingReg['registration_id'];
            $event['user_registration_status'] = $existingReg['status'];
        }
    }

    sendSuccess('Event record retrieved successfully.', $event);
} catch (PDOException $e) {
    error_log('Get event error: ' . $e->getMessage());
    sendError('Failed to retrieve event details.', ['database' => 'Query execution failed.'], 500);
}
