<?php
/**
 * CEMS - Student Logout Endpoint
 * POST /backend/auth/student_logout.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

initSession();

// Clear and destroy session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

sendSuccess('Student session closed successfully.');
