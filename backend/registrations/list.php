<?php
/**
 * CEMS - List Registrations Endpoint
 * GET /backend/registrations/list.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed. Use GET.', [], 405);
}

$session = getCurrentSession();
if (!$session) {
    sendError('Authentication required.', [], 401);
}

$myOnly  = isset($_GET['my']) && $_GET['my'] === '1';
$eventId = $_GET['event_id'] ?? null;

try {
    $pdo = Database::getConnection();

    $sql = '
        SELECT 
            r.registration_id,
            r.student_id,
            r.event_id,
            r.registration_date,
            r.status,
            s.name AS student_name,
            s.email AS student_email,
            s.phone AS student_phone,
            s.semester AS student_semester,
            d.department_id,
            d.department_name,
            e.event_name,
            e.event_date,
            DATE_FORMAT(e.event_date, "%W, %M %e, %Y") AS display_date,
            e.event_time,
            v.venue_name,
            v.location AS venue_location
        FROM registration r
        INNER JOIN student s ON r.student_id = s.student_id
        INNER JOIN department d ON s.department_id = d.department_id
        INNER JOIN event e ON r.event_id = e.event_id
        INNER JOIN venue v ON e.venue_id = v.venue_id
    ';

    $where = [];
    $params = [];

    // Students can only view their own registrations
    if ($session['role'] === 'student' || $myOnly) {
        $where[] = 'r.student_id = ?';
        $params[] = (int)$session['user_id'];
    }

    if ($eventId && isValidId($eventId)) {
        $where[] = 'r.event_id = ?';
        $params[] = (int)$eventId;
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY r.registration_date DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Format structure matching frontend expectations (including nested student & event objects)
    $formatted = array_map(function ($row) {
        $token = 'CEMS-PASS-' . str_pad((string)$row['registration_id'], 6, '0', STR_PAD_LEFT);
        return [
            'registration_id'   => (int)$row['registration_id'],
            'student_id'        => (int)$row['student_id'],
            'event_id'          => (int)$row['event_id'],
            'registration_date' => $row['registration_date'],
            'status'            => $row['status'],
            'ticket_token'      => $token,
            'student' => [
                'student_id'      => (int)$row['student_id'],
                'name'            => $row['student_name'],
                'email'           => $row['student_email'],
                'phone'           => $row['student_phone'],
                'semester'        => (int)$row['student_semester'],
                'department' => [
                    'dept_id'   => (int)$row['department_id'],
                    'dept_name' => $row['department_name'],
                    'code'      => $row['department_name']
                ]
            ],
            'event' => [
                'event_id'     => (int)$row['event_id'],
                'title'        => $row['event_name'],
                'date'         => $row['event_date'],
                'display_date' => $row['display_date'],
                'time'         => $row['event_time'],
                'venue' => [
                    'venue_name' => $row['venue_name'],
                    'location'   => $row['venue_location']
                ]
            ]
        ];
    }, $rows);

    sendSuccess('Registrations retrieved successfully.', $formatted);
} catch (PDOException $e) {
    error_log('List registrations error: ' . $e->getMessage());
    sendError('Failed to retrieve registration records.', ['database' => 'Query execution failed.'], 500);
}
