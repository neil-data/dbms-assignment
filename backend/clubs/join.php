<?php
/**
 * CEMS - Join / Leave Club Endpoint
 * POST /backend/clubs/join.php
 * Handles student club membership toggle. Requires real student session.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

// Strict Authentication Enforcement
$session = requireStudentAuth();
$studentId = (int)$session['user_id'];

try {
    $pdo = Database::getConnection();

    $input = getRequestData();
    $clubId = isset($input['club_id']) ? (int)$input['club_id'] : 0;
    $action = trim($input['action'] ?? 'toggle'); // 'join', 'leave', or 'toggle'

    if ($clubId <= 0) {
        sendError('Valid Club ID is required.', 400);
    }

    // Verify club exists
    $chkStmt = $pdo->prepare('SELECT club_id, club_name FROM club WHERE club_id = ?');
    $chkStmt->execute([$clubId]);
    $club = $chkStmt->fetch();
    if (!$club) {
        sendError('Club not found.', 404);
    }

    // Check existing membership
    $memStmt = $pdo->prepare('SELECT membership_id FROM club_membership WHERE student_id = ? AND club_id = ?');
    $memStmt->execute([$studentId, $clubId]);
    $existing = $memStmt->fetch();

    $newStatus = false;

    if ($action === 'join' || ($action === 'toggle' && !$existing)) {
        if (!$existing) {
            $insStmt = $pdo->prepare('INSERT INTO club_membership (student_id, club_id, role) VALUES (?, ?, "MEMBER")');
            $insStmt->execute([$studentId, $clubId]);
        }
        $newStatus = true;
        $msg = "Successfully joined {$club['club_name']}!";
    } else {
        if ($existing) {
            $delStmt = $pdo->prepare('DELETE FROM club_membership WHERE student_id = ? AND club_id = ?');
            $delStmt->execute([$studentId, $clubId]);
        }
        $newStatus = false;
        $msg = "You have left {$club['club_name']}.";
    }

    // Return updated member count
    $cntStmt = $pdo->prepare('SELECT COUNT(*) FROM club_membership WHERE club_id = ?');
    $cntStmt->execute([$clubId]);
    $updatedCount = (int)$cntStmt->fetchColumn();

    sendSuccess($msg, [
        'club_id'      => $clubId,
        'club_name'    => $club['club_name'],
        'is_member'    => $newStatus,
        'member_count' => $updatedCount
    ]);

} catch (PDOException $e) {
    error_log('Database error in clubs/join.php: ' . $e->getMessage());
    sendError('Database error occurred while processing membership.', 500);
} catch (Exception $e) {
    error_log('General error in clubs/join.php: ' . $e->getMessage());
    sendError('An unexpected server error occurred.', 500);
}
