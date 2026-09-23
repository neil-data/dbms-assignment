<?php
/**
 * CEMS - Get Single Club Details Endpoint
 * GET /backend/clubs/get.php?id=1 or ?slug=tech-club
 * Returns club profile, host events, and current student membership status.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

try {
    $pdo = Database::getConnection();

    $id   = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $slug = trim($_GET['slug'] ?? '');

    if ($id === null && empty($slug)) {
        sendError('Club ID or slug parameter is required.', 400);
    }

    if ($id !== null) {
        $stmt = $pdo->prepare('SELECT * FROM club WHERE club_id = ?');
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM club WHERE slug = ?');
        $stmt->execute([$slug]);
    }

    $club = $stmt->fetch();
    if (!$club) {
        sendError('Club not found.', 404);
    }

    $clubId = (int)$club['club_id'];

    // 1. Get member counts & student membership status
    $currentStudentId = null;
    $isMember = false;
    $myRole = null;

    if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'student') {
        $currentStudentId = (int)$_SESSION['user_id'];
        $memStmt = $pdo->prepare('SELECT role FROM club_membership WHERE student_id = ? AND club_id = ?');
        $memStmt->execute([$currentStudentId, $clubId]);
        $membership = $memStmt->fetch();
        if ($membership) {
            $isMember = true;
            $myRole = $membership['role'];
        }
    }

    // Count total members
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM club_membership WHERE club_id = ?');
    $countStmt->execute([$clubId]);
    $memberCount = (int)$countStmt->fetchColumn();

    // 2. Fetch upcoming events hosted by this club
    $upEventsStmt = $pdo->prepare('
        SELECT 
            e.event_id,
            e.event_name,
            e.description,
            e.event_date,
            DATE_FORMAT(e.event_date, "%W, %M %e, %Y") AS display_date,
            e.event_time,
            e.max_capacity,
            e.status,
            v.venue_name,
            v.location AS venue_location,
            COUNT(CASE WHEN r.status = "CONFIRMED" THEN 1 END) AS registered_count,
            GREATEST(0, e.max_capacity - COUNT(CASE WHEN r.status = "CONFIRMED" THEN 1 END)) AS available_seats
        FROM event e
        INNER JOIN venue v ON e.venue_id = v.venue_id
        LEFT JOIN registration r ON e.event_id = r.event_id
        WHERE e.club_id = ? AND e.event_date >= CURDATE() AND e.status != "CANCELLED"
        GROUP BY e.event_id, e.event_name, e.description, e.event_date, e.event_time, e.max_capacity, e.status, v.venue_name, v.location
        ORDER BY e.event_date ASC
    ');
    $upEventsStmt->execute([$clubId]);
    $upcomingEvents = $upEventsStmt->fetchAll();

    // 3. Fetch past events hosted by this club
    $pastEventsStmt = $pdo->prepare('
        SELECT 
            e.event_id,
            e.event_name,
            e.description,
            e.event_date,
            DATE_FORMAT(e.event_date, "%M %e, %Y") AS display_date,
            e.event_time,
            e.status,
            v.venue_name,
            COUNT(CASE WHEN r.status = "CONFIRMED" THEN 1 END) AS attendee_count
        FROM event e
        INNER JOIN venue v ON e.venue_id = v.venue_id
        LEFT JOIN registration r ON e.event_id = r.event_id
        WHERE e.club_id = ? AND (e.event_date < CURDATE() OR e.status = "COMPLETED")
        GROUP BY e.event_id, e.event_name, e.description, e.event_date, e.event_time, e.status, v.venue_name
        ORDER BY e.event_date DESC
        LIMIT 6
    ');
    $pastEventsStmt->execute([$clubId]);
    $pastEvents = $pastEventsStmt->fetchAll();

    // 4. Fetch leadership & active members
    $leadersStmt = $pdo->prepare('
        SELECT s.student_id, s.name, s.email, d.department_name, cm.role, cm.joined_at
        FROM club_membership cm
        INNER JOIN student s ON cm.student_id = s.student_id
        INNER JOIN department d ON s.department_id = d.department_id
        WHERE cm.club_id = ?
        ORDER BY FIELD(cm.role, "LEAD", "COORDINATOR", "MEMBER"), cm.joined_at ASC
        LIMIT 10
    ');
    $leadersStmt->execute([$clubId]);
    $membersList = $leadersStmt->fetchAll();

    $payload = [
        'club_id'          => $clubId,
        'club_name'        => $club['club_name'],
        'slug'             => $club['slug'],
        'category'         => $club['category'],
        'tagline'          => $club['tagline'],
        'description'      => $club['description'],
        'mission'          => $club['mission'],
        'cover_image'      => $club['cover_image'] ?: 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200&auto=format&fit=crop',
        'logo_icon'        => $club['logo_icon'] ?: 'groups',
        'coordinator_name' => $club['coordinator_name'],
        'coordinator_email'=> $club['coordinator_email'],
        'meeting_schedule' => $club['meeting_schedule'],
        'member_count'     => $memberCount,
        'is_member'        => $isMember,
        'my_role'          => $myRole,
        'upcoming_events'  => $upcomingEvents,
        'past_events'      => $pastEvents,
        'members'          => $membersList
    ];

    sendSuccess('Club profile loaded successfully.', $payload);

} catch (PDOException $e) {
    error_log('Database error in clubs/get.php: ' . $e->getMessage());
    sendError('Database error occurred while fetching club profile.', 500);
} catch (Exception $e) {
    error_log('General error in clubs/get.php: ' . $e->getMessage());
    sendError('An unexpected server error occurred.', 500);
}
