<?php
/**
 * CEMS - List Campus Clubs Endpoint
 * GET /backend/clubs/list.php
 * Supports keyword search, category filter, and returns member & event metrics.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed. Use GET.', [], 405);
}

try {
    $pdo = Database::getConnection();

    $search   = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? 'all');
    $featured = isset($_GET['featured']) && $_GET['featured'] === '1';

    $sql = '
        SELECT 
            cl.club_id,
            cl.club_name,
            cl.slug,
            cl.category,
            cl.tagline,
            cl.description,
            cl.cover_image,
            cl.logo_icon,
            cl.coordinator_name,
            cl.coordinator_email,
            cl.meeting_schedule,
            cl.created_at,
            COUNT(DISTINCT cm.student_id) AS member_count,
            COUNT(DISTINCT CASE WHEN e.event_date >= CURDATE() AND e.status != "CANCELLED" THEN e.event_id END) AS upcoming_event_count,
            COUNT(DISTINCT e.event_id) AS total_event_count
        FROM club cl
        LEFT JOIN club_membership cm ON cl.club_id = cm.club_id
        LEFT JOIN event e ON cl.club_id = e.club_id
    ';

    $whereClauses = [];
    $params = [];

    // Search filter
    if (!empty($search)) {
        $whereClauses[] = '(cl.club_name LIKE ? OR cl.tagline LIKE ? OR cl.description LIKE ?)';
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Category filter
    if (!empty($category) && strtolower($category) !== 'all') {
        $whereClauses[] = 'LOWER(cl.category) = LOWER(?)';
        $params[] = $category;
    }

    if (!empty($whereClauses)) {
        $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
    }

    $sql .= ' GROUP BY cl.club_id, cl.club_name, cl.slug, cl.category, cl.tagline, cl.description, cl.cover_image, cl.logo_icon, cl.coordinator_name, cl.coordinator_email, cl.meeting_schedule, cl.created_at';

    if ($featured) {
        $sql .= ' ORDER BY upcoming_event_count DESC, member_count DESC LIMIT 4';
    } else {
        $sql .= ' ORDER BY cl.club_name ASC';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clubs = $stmt->fetchAll();

    // Check membership status if student is logged in
    $currentStudentId = null;
    if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'student') {
        $currentStudentId = (int)$_SESSION['user_id'];
    }

    $joinedClubIds = [];
    if ($currentStudentId !== null) {
        $memStmt = $pdo->prepare('SELECT club_id FROM club_membership WHERE student_id = ?');
        $memStmt->execute([$currentStudentId]);
        $joinedClubIds = $memStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $formattedClubs = array_map(function ($club) use ($joinedClubIds) {
        return [
            'club_id'              => (int)$club['club_id'],
            'club_name'            => $club['club_name'],
            'slug'                 => $club['slug'],
            'category'             => $club['category'],
            'tagline'              => $club['tagline'],
            'description'          => $club['description'],
            'cover_image'          => $club['cover_image'] ?: 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200&auto=format&fit=crop',
            'logo_icon'            => $club['logo_icon'] ?: 'groups',
            'coordinator_name'     => $club['coordinator_name'],
            'coordinator_email'    => $club['coordinator_email'],
            'meeting_schedule'     => $club['meeting_schedule'],
            'member_count'         => (int)$club['member_count'],
            'upcoming_event_count' => (int)$club['upcoming_event_count'],
            'total_event_count'    => (int)$club['total_event_count'],
            'is_joined'            => in_array((int)$club['club_id'], $joinedClubIds, true)
        ];
    }, $clubs);

    sendSuccess('Clubs retrieved successfully.', $formattedClubs);

} catch (PDOException $e) {
    error_log('Database error in clubs/list.php: ' . $e->getMessage());
    sendError('Database error occurred while fetching clubs.', 500);
} catch (Exception $e) {
    error_log('General error in clubs/list.php: ' . $e->getMessage());
    sendError('An unexpected server error occurred.', 500);
}
