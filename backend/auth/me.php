<?php
/**
 * CEMS - Active Session & Database Health Endpoint
 * GET /backend/auth/me.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

$dbConnected = Database::isConnected();
$session = getCurrentSession();

sendSuccess('Status check complete.', [
    'database_connected' => $dbConnected,
    'authenticated'      => $session !== null,
    'user'               => $session,
    'role'               => $session['role'] ?? null
]);
