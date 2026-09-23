<?php
/**
 * CEMS - DBMS Demonstration: Predefined Safe Academic Queries Endpoint
 * GET /backend/database-demo/queries.php?type=...
 * Conforms to Requirement 16 (B, C, D, E, F, G, H, I, Views)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed. Use GET.', [], 405);
}

requireAdminAuth();

$type = trim($_GET['type'] ?? 'multi_join');

try {
    $pdo = Database::getConnection();

    $queryMap = [
        // C & F: Multi-table relational JOIN
        'multi_join' => [
            'title'       => 'Multi-Table INNER JOIN (4 Entities)',
            'description' => 'Visualizes referential integrity: Student -> Department -> Registration -> Event -> Venue.',
            'sql'         => 'SELECT 
    r.registration_id,
    s.name AS student_name,
    s.email AS student_email,
    d.department_name,
    e.event_name,
    e.event_date,
    v.venue_name,
    v.location AS venue_location,
    r.status AS registration_status
FROM registration r
INNER JOIN student s ON r.student_id = s.student_id
INNER JOIN department d ON s.department_id = d.department_id
INNER JOIN event e ON r.event_id = e.event_id
INNER JOIN venue v ON e.venue_id = v.venue_id
ORDER BY e.event_date ASC, s.name ASC'
        ],

        // F: LEFT JOIN
        'left_join' => [
            'title'       => 'LEFT OUTER JOIN (All Venues & Assigned Events)',
            'description' => 'Shows all campus venues including facilities with zero scheduled events.',
            'sql'         => 'SELECT 
    v.venue_id,
    v.venue_name,
    v.capacity AS venue_capacity,
    e.event_id,
    COALESCE(e.event_name, "(No Event Scheduled)") AS event_name,
    COALESCE(e.event_date, "—") AS event_date
FROM venue v
LEFT JOIN event e ON v.venue_id = e.venue_id
ORDER BY v.venue_name ASC'
        ],

        // G: Many-to-Many via Junction Table
        'many_to_many' => [
            'title'       => 'Many-to-Many Relational Resolution (EVENT M:M CATEGORY)',
            'description' => 'Retrieves events with their comma-aggregated categories through the junction table EVENT_CATEGORY.',
            'sql'         => 'SELECT 
    e.event_id,
    e.event_name,
    GROUP_CONCAT(c.category_name ORDER BY c.category_name SEPARATOR ", ") AS categories
FROM event e
INNER JOIN event_category ec ON e.event_id = ec.event_id
INNER JOIN category c ON ec.category_id = c.category_id
GROUP BY e.event_id, e.event_name'
        ],

        // D: Aggregate Queries
        'aggregate' => [
            'title'       => 'SQL Aggregate Functions (COUNT, SUM, AVG, MIN, MAX)',
            'description' => 'Executes statistical aggregation across student, event, and venue entities.',
            'sql'         => 'SELECT 
    (SELECT COUNT(*) FROM student) AS total_students,
    (SELECT COUNT(*) FROM event) AS total_events,
    (SELECT COUNT(*) FROM registration WHERE status = "CONFIRMED") AS total_confirmed_registrations,
    (SELECT ROUND(AVG(max_capacity), 1) FROM event) AS average_event_capacity,
    (SELECT MAX(capacity) FROM venue) AS max_venue_capacity,
    (SELECT MIN(capacity) FROM venue) AS min_venue_capacity,
    (SELECT SUM(capacity) FROM venue) AS total_campus_seats'
        ],

        // E: GROUP BY & HAVING
        'group_by_event' => [
            'title'       => 'GROUP BY with Aggregation & Arithmetic (Enrolled vs Remaining Seats)',
            'description' => 'Groups registrations by event, calculates enrolled totals and computes available seat quota.',
            'sql'         => 'SELECT 
    e.event_id,
    e.event_name,
    e.max_capacity,
    COUNT(CASE WHEN r.status = "CONFIRMED" THEN 1 END) AS total_confirmed,
    (e.max_capacity - COUNT(CASE WHEN r.status = "CONFIRMED" THEN 1 END)) AS available_seats
FROM event e
LEFT JOIN registration r ON e.event_id = r.event_id
GROUP BY e.event_id, e.event_name, e.max_capacity
ORDER BY total_confirmed DESC'
        ],

        'group_by_dept' => [
            'title'       => 'GROUP BY Academic Department',
            'description' => 'Summarizes student distribution grouped by departmental entity.',
            'sql'         => 'SELECT 
    d.department_id,
    d.department_name,
    COUNT(s.student_id) AS student_count
FROM department d
LEFT JOIN student s ON d.department_id = s.department_id
GROUP BY d.department_id, d.department_name
ORDER BY student_count DESC'
        ],

        'group_by_status' => [
            'title'       => 'GROUP BY Registration Status',
            'description' => 'Aggregates registration counts across CONFIRMED, PENDING, CANCELLED, and COMPLETED.',
            'sql'         => 'SELECT 
    status,
    COUNT(*) AS total_count
FROM registration
GROUP BY status
ORDER BY total_count DESC'
        ],

        // G: Subquery
        'subquery_capacity' => [
            'title'       => 'Nested Scalar Subquery (Events Capacity > Average)',
            'description' => 'Demonstrates subquery in WHERE clause comparing each event capacity against the overall average.',
            'sql'         => 'SELECT 
    event_id,
    event_name,
    max_capacity,
    event_date
FROM event
WHERE max_capacity > (
    SELECT AVG(max_capacity) FROM event
)
ORDER BY max_capacity DESC'
        ],

        'subquery_students' => [
            'title'       => 'Correlated Subquery (Active Multiprogram Students)',
            'description' => 'Identifies students registered in more than one active event using correlated subquery logic.',
            'sql'         => 'SELECT 
    s.student_id,
    s.name,
    s.email,
    (
        SELECT COUNT(*) 
        FROM registration r 
        WHERE r.student_id = s.student_id AND r.status = "CONFIRMED"
    ) AS active_registrations
FROM student s
WHERE (
    SELECT COUNT(*) 
    FROM registration r 
    WHERE r.student_id = s.student_id AND r.status = "CONFIRMED"
) > 1
ORDER BY active_registrations DESC'
        ],

        // H & I: Filtering & Ordering
        'order_by_date' => [
            'title'       => 'ORDER BY Chronological Schedule',
            'description' => 'Sorts upcoming events by scheduled date and time.',
            'sql'         => 'SELECT event_id, event_name, event_date, event_time, status 
FROM event 
ORDER BY event_date ASC, event_time ASC'
        ],

        'where_like' => [
            'title'       => 'Pattern Matching with LIKE Operator',
            'description' => 'Filters students with .edu institutional email addresses and names starting with "A".',
            'sql'         => 'SELECT student_id, name, email, phone 
FROM student 
WHERE email LIKE "%.edu" AND name LIKE "A%"'
        ],

        'between_dates' => [
            'title'       => 'Range Filtering with BETWEEN Operator',
            'description' => 'Selects campus events scheduled throughout October 2026.',
            'sql'         => 'SELECT event_id, event_name, event_date, max_capacity 
FROM event 
WHERE event_date BETWEEN "2026-10-01" AND "2026-10-31" 
ORDER BY event_date ASC'
        ],

        // Views (Requirement 17)
        'view_summary' => [
            'title'       => 'Database View: view_event_registration_summary',
            'description' => 'Reads directly from the compiled SQL View summarizing capacities and occupancy percentages.',
            'sql'         => 'SELECT 
    event_id,
    event_name,
    event_date,
    venue_name,
    max_capacity,
    enrolled_count,
    available_seats,
    occupancy_percentage
FROM view_event_registration_summary
ORDER BY enrolled_count DESC'
        ],

        'view_student' => [
            'title'       => 'Database View: view_student_registrations',
            'description' => 'Reads directly from the compiled virtual View joining 5 relational entities.',
            'sql'         => 'SELECT 
    registration_id,
    student_name,
    department_name,
    event_name,
    event_date,
    venue_name,
    registration_status
FROM view_student_registrations
ORDER BY registration_date DESC
LIMIT 10'
        ]
    ];

    if (!isset($queryMap[$type])) {
        sendError('Invalid query demonstration type requested.', ['available_types' => array_keys($queryMap)], 400);
    }

    $selected = $queryMap[$type];
    $stmt = $pdo->query($selected['sql']);
    $results = $stmt->fetchAll();

    sendSuccess("Executed {$selected['title']} successfully.", [
        'type'        => $type,
        'title'       => $selected['title'],
        'description' => $selected['description'],
        'sql'         => $selected['sql'],
        'row_count'   => count($results),
        'results'     => $results
    ]);

} catch (PDOException $e) {
    error_log('DBMS Demo Query error: ' . $e->getMessage());
    sendError('Query benchmark execution failed.', ['database' => 'Execution error.'], 500);
}
