<?php
/**
 * CEMS - List Categories Endpoint
 * GET /backend/categories/list.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed. Use GET.', [], 405);
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->query('
        SELECT 
            c.category_id,
            c.category_name,
            LOWER(c.category_name) AS slug,
            COUNT(ec.event_id) AS events_count
        FROM category c
        LEFT JOIN event_category ec ON c.category_id = ec.category_id
        GROUP BY c.category_id, c.category_name
        ORDER BY c.category_id ASC
    ');

    $categories = $stmt->fetchAll();

    sendSuccess('Categories retrieved successfully.', $categories);
} catch (PDOException $e) {
    error_log('Categories list error: ' . $e->getMessage());
    sendError('Failed to retrieve categories.', ['database' => 'Query execution failed.'], 500);
}
