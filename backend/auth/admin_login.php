<?php
/**
 * CEMS - Admin Authentication Endpoint
 * POST /backend/auth/admin_login.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../shared/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

$input = getRequestData();
$loginIdentifier = trim((string)($input['username'] ?? $input['email'] ?? ''));
$password        = (string)($input['password'] ?? '');

$errors = [];
if (empty($loginIdentifier)) {
    $errors['username'] = 'Administrator username or email is required.';
}
if (empty($password)) {
    $errors['password'] = 'Password is required.';
}

if (!empty($errors)) {
    sendError('Please provide administrative credentials.', $errors, 422);
}

try {
    $pdo = Database::getConnection();

    // Query admin table (supports username or admin email prefix)
    $stmt = $pdo->prepare('
        SELECT admin_id, username, password, created_at
        FROM admin
        WHERE username = ? OR username = SUBSTRING_INDEX(?, "@", 1)
        LIMIT 1
    ');
    $stmt->execute([$loginIdentifier, $loginIdentifier]);
    $admin = $stmt->fetch();

    if (!$admin || !verifyPassword($password, $admin['password'])) {
        sendError('Invalid administrative credentials.', ['auth' => 'Administrative authorization failed.'], 401);
    }

    // Initialize administrator session
    initSession();
    $_SESSION['user_id']  = (int)$admin['admin_id'];
    $_SESSION['role']     = 'admin';
    $_SESSION['username'] = $admin['username'];
    $_SESSION['name']     = 'Administrator (' . $admin['username'] . ')';

    sendSuccess('Administrative authorization granted. Welcome to CEMS Relational Console.', [
        'admin_id' => (int)$admin['admin_id'],
        'username' => $admin['username'],
        'role'     => 'admin'
    ]);

} catch (PDOException $e) {
    error_log('Admin login PDO Error: ' . $e->getMessage());
    sendError('Administrative authentication failure.', ['database' => 'Query execution failed.'], 500);
}
