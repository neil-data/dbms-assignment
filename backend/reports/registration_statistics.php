<?php
/**
 * CEMS - Registration Status Distribution Report Endpoint
 * GET /backend/reports/registration_statistics.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed. Use GET.', [], 405);
}

requireAdminAuth();

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->query('
        SELECT 
            status,
            COUNT(*) AS total_count,
            ROUND((COUNT(*) / NULLIF((SELECT COUNT(*) FROM registration), 0)) * 100, 1) AS percentage
        FROM registration
        GROUP BY status
        ORDER BY total_count DESC
    ');

    $report = $stmt->fetchAll();

    sendSuccess('Registration status distribution retrieved.', $report);
} catch (PDOException $e) {
    error_log('Registration stats error: ' . $e->getMessage());
    sendError('Failed to query registration statistics.', ['database' => 'Query execution failed.'], 500);
}
