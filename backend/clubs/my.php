<?php
/**
 * CEMS - Get Logged-in Student's Joined Clubs
 * GET /backend/clubs/my.php
 * Returns array of clubs joined by the current authenticated student.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

requireRole('student');

try {
    $pdo = Database::getConnection();
    $studentId = (int)$_SESSION['user_id'];

    $sql = '
        SELECT 
            cl.club_id,
            cl.club_name,
            cl.slug,
            cl.category,
            cl.tagline,
            cl.cover_image,
            cl.logo_icon,
            cl.coordinator_name,
            cl.meeting_schedule,
            cm.role AS membership_role,
            cm.joined_at,
            COUNT(DISTINCT e.event_id) AS upcoming_event_count
        FROM club_membership cm
        INNER JOIN club cl ON cm.club_id = cl.club_id
        LEFT JOIN event e ON cl.club_id = e.club_id AND e.event_date >= CURDATE() AND e.status != "CANCELLED"
        WHERE cm.student_id = ?
        GROUP BY cl.club_id, cl.club_name, cl.slug, cl.category, cl.tagline, cl.cover_image, cl.logo_icon, cl.coordinator_name, cl.meeting_schedule, cm.role, cm.joined_at
        ORDER BY cm.joined_at DESC
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$studentId]);
    $myClubs = $stmt->fetchAll();

    sendSuccess('Joined clubs retrieved successfully.', $myClubs);

} catch (PDOException $e) {
    error_log('Database error in clubs/my.php: ' . $e->getMessage());
    sendError('Database error occurred while fetching your clubs.', 500);
} catch (Exception $e) {
    error_log('General error in clubs/my.php: ' . $e->getMessage());
    sendError('An unexpected server error occurred.', 500);
}
