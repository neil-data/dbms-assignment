<?php
/**
 * CEMS - Active Session & Database Health Endpoint
 * GET /backend/auth/me.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed. Use GET.', [], 405);
}

$dbConnected = Database::isConnected();
$session = getCurrentSession();

sendSuccess('Status check complete.', [
    'database_connected' => $dbConnected,
    'authenticated'      => $session !== null,
    'user'               => $session,
    'role'               => $session['role'] ?? null
]);
