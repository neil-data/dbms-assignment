<?php
/**
 * CEMS - Central Session & Authentication Manager
 * Implements role-based access control (Student vs Admin) and authorization guards.
 */

declare(strict_types=1);

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Start PHP session safely if not already active.
 */
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        
        session_start();
    }
}

/**
 * Get active authenticated user details.
 *
 * @return array|null
 */
function getCurrentSession(): ?array {
    initSession();
    if (isset($_SESSION['user_id'], $_SESSION['role'])) {
        return [
            'user_id'  => (int)$_SESSION['user_id'],
            'role'     => (string)$_SESSION['role'],
            'username' => $_SESSION['username'] ?? '',
            'name'     => $_SESSION['name'] ?? '',
            'email'    => $_SESSION['email'] ?? '',
            'department_id' => $_SESSION['department_id'] ?? null,
            'department_name' => $_SESSION['department_name'] ?? null,
            'semester' => $_SESSION['semester'] ?? null,
        ];
    }
    return null;
}

/**
 * Guard that enforces Student authentication.
 *
 * @return array Student session details
 */
function requireStudentAuth(): array {
    $session = getCurrentSession();
    if (!$session || $session['role'] !== 'student') {
        sendError('Unauthorized. Active student session required.', ['auth' => 'Student sign-in required.'], 401);
    }
    return $session;
}

/**
 * Guard that enforces Admin authentication.
 * Protects all admin endpoints from student or anonymous access.
 *
 * @return array Admin session details
 */
function requireAdminAuth(): array {
    $session = getCurrentSession();
    if (!$session || $session['role'] !== 'admin') {
        sendError('Forbidden. Administrative privileges required.', ['auth' => 'Administrative access denied.'], 403);
    }
    return $session;
}

/**
 * Verify a password against standard bcrypt hash, with compatibility check.
 *
 * @param string $password
 * @param string $storedHash
 * @return bool
 */
function verifyPassword(string $password, string $storedHash): bool {
    if (password_verify($password, $storedHash)) {
        return true;
    }
    // Fallback for demo convenience if unhashed seed matches
    if ($password === $storedHash) {
        return true;
    }
    return false;
}
