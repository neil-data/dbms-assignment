<?php
/**
 * CEMS - Database Configuration & Central PDO Connection Provider
 * Supports XAMPP default credentials, environment variables, and config fallbacks.
 */

declare(strict_types=1);

class Database {
    private static ?PDO $instance = null;

    // Default configuration for standard local XAMPP / Apache / MySQL environments
    private const DB_HOST = 'localhost';
    private const DB_PORT = '3306';
    private const DB_NAME = 'cems_db';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_CHARSET = 'utf8mb4';

    /**
     * Retrieve the centralized singleton PDO database connection instance.
     *
     * @return PDO
     * @throws PDOException if connection fails
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: self::DB_HOST;
            $primaryPort = getenv('DB_PORT') ?: self::DB_PORT;
            $db   = getenv('DB_NAME') ?: self::DB_NAME;
            $user = getenv('DB_USER') ?: self::DB_USER;
            $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : self::DB_PASS;
            $charset = self::DB_CHARSET;

            $candidatePorts = array_unique([$primaryPort, '3307', '3306']);

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci"
            ];

            $lastException = null;
            foreach ($candidatePorts as $port) {
                try {
                    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
                    self::$instance = new PDO($dsn, $user, $pass, $options);
                    break;
                } catch (PDOException $e) {
                    $lastException = $e;
                }
            }

            if (self::$instance === null) {
                error_log("Database connection error: " . ($lastException ? $lastException->getMessage() : 'Unknown error'));
                throw new PDOException("Database connection failure. Please verify MySQL service and credentials.", (int)($lastException ? $lastException->getCode() : 0));
            }
        }

        return self::$instance;
    }

    /**
     * Check if the database connection is currently alive.
     *
     * @return bool
     */
    public static function isConnected(): bool {
        try {
            $pdo = self::getConnection();
            $pdo->query("SELECT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
