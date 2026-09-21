<?php
/**
 * CEMS - DBMS Demonstration: Database Overview & Table Metadata Endpoint
 * GET /backend/database-demo/overview.php
 * Conforms to Requirement 16-A
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

requireAdminAuth();

try {
    $pdo = Database::getConnection();

    $tables = [
        'department',
        'student',
        'venue',
        'event',
        'category',
        'registration',
        'event_category',
        'admin'
    ];

    $tableStats = [];

    foreach ($tables as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
        $rowCount = (int)$countStmt->fetchColumn();

        // Get table columns and primary keys
        $colsStmt = $pdo->query("DESCRIBE `{$table}`");
        $columns = $colsStmt->fetchAll();

        $tableStats[] = [
            'table_name' => $table,
            'row_count'  => $rowCount,
            'columns'    => array_map(function ($col) {
                return [
                    'field'   => $col['Field'],
                    'type'    => $col['Type'],
                    'null'    => $col['Null'],
                    'key'     => $col['Key'],
                    'default' => $col['Default'],
                    'extra'   => $col['Extra']
                ];
            }, $columns)
        ];
    }

    // Views overview
    $views = ['view_student_registrations', 'view_event_registration_summary', 'view_department_enrollment_stats'];
    $viewStats = [];
    foreach ($views as $view) {
        $vCount = $pdo->query("SELECT COUNT(*) FROM `{$view}`")->fetchColumn();
        $viewStats[] = [
            'view_name' => $view,
            'row_count' => (int)$vCount
        ];
    }

    // Server metadata
    $versionStmt = $pdo->query('SELECT VERSION() AS mysql_version, DATABASE() AS current_database');
    $dbMeta = $versionStmt->fetch();

    sendSuccess('Database overview retrieved.', [
        'database_name' => $dbMeta['current_database'] ?? 'cems_db',
        'mysql_version' => $dbMeta['mysql_version'] ?? 'MySQL 8.0+',
        'tables'        => $tableStats,
        'views'         => $viewStats
    ]);
} catch (PDOException $e) {
    error_log('DBMS Demo Overview error: ' . $e->getMessage());
    sendError('Failed to inspect relational schema.', ['database' => 'Query execution failed.'], 500);
}
