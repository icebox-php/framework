<?php

namespace Icebox\ActiveRecord;

use PDO;
use PDOException;
use InvalidArgumentException;

/**
 * Database Creator Utility
 * 
 * Handles database creation for different database drivers
 */
class DatabaseCreator
{
    /**
     * Create database based on DATABASE_URL
     *
     * @param string $databaseUrl Database URL
     * @return array Result with success status and message
     */
    public static function createDatabase(string $databaseUrl): array
    {
        try {
            $parsed = Config::parseDatabaseUrl($databaseUrl);
            $dsn = $parsed['dsn'];
            $username = $parsed['username'];
            $password = $parsed['password'];
            
            // Extract driver from DSN
            $driver = self::extractDriverFromDsn($dsn);
            
            switch ($driver) {
                case 'sqlite':
                    return self::createSqliteDatabase($dsn);
                case 'mysql':
                    return self::createMysqlDatabase($parsed);
                case 'pgsql':
                    return self::createPgsqlDatabase($parsed);
                default:
                    return [
                        'success' => false,
                        'message' => "Unsupported database driver: {$driver}"
                    ];
            }
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => "Invalid database URL: " . $e->getMessage()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract driver from DSN
     *
     * @param string $dsn
     * @return string
     */
    private static function extractDriverFromDsn(string $dsn): string
    {
        $parts = explode(':', $dsn, 2);
        return $parts[0] ?? '';
    }
    
    /**
     * Create SQLite database
     *
     * @param string $dsn
     * @return array
     */
    private static function createSqliteDatabase(string $dsn): array
    {
        // Extract file path from DSN (sqlite:/path/to/database.sqlite)
        $filePath = substr($dsn, 7); // Remove "sqlite:"
        
        if ($filePath === ':memory:') {
            return [
                'success' => true,
                'message' => "SQLite in-memory database ready"
            ];
        }
        
        // Check if database already exists
        if (file_exists($filePath)) {
            return [
                'success' => true,
                'message' => "SQLite database already exists at: {$filePath}"
            ];
        }
        
        // Create directory if needed
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                return [
                    'success' => false,
                    'message' => "Failed to create directory: {$dir}"
                ];
            }
        }
        
        try {
            // Connect to SQLite (PDO will create the file if it doesn't exist)
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create schema_migrations table (same SQL as in MigrationRunner)
            $sql = "CREATE TABLE IF NOT EXISTS schema_migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($sql);
            
            // Close connection (optional)
            $pdo = null;
            
            return [
                'success' => true,
                'message' => "SQLite database created at: {$filePath} with schema_migrations table"
            ];
        } catch (PDOException $e) {
            // If creation fails, the file may have been created but is incomplete.
            // We could attempt to delete it, but leaving it is harmless.
            return [
                'success' => false,
                'message' => "Failed to create SQLite database: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create MySQL database
     *
     * @param array $parsed Parsed database URL components
     * @return array
     */
    private static function createMysqlDatabase(array $parsed): array
    {
        // Extract database name from DSN
        $dsn = $parsed['dsn'];
        $databaseName = self::extractDatabaseNameFromDsn($dsn);
        
        if (!$databaseName) {
            return [
                'success' => false,
                'message' => "Could not extract database name from DSN"
            ];
        }
        
        // Create DSN without database name for initial connection
        $serverDsn = self::createServerDsn($dsn, 'mysql');
        
        try {
            // Connect to MySQL server (without specifying database)
            $pdo = new PDO($serverDsn, $parsed['username'], $parsed['password'], $parsed['options']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if database already exists
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$databaseName}'");
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => "MySQL database '{$databaseName}' already exists"
                ];
            }
            
            // Create database
            $pdo->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            return [
                'success' => true,
                'message' => "MySQL database '{$databaseName}' created successfully"
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => "MySQL error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create PostgreSQL database
     *
     * @param array $parsed Parsed database URL components
     * @return array
     */
    private static function createPgsqlDatabase(array $parsed): array
    {
        // Extract database name from DSN
        $dsn = $parsed['dsn'];
        $databaseName = self::extractDatabaseNameFromDsn($dsn);
        
        if (!$databaseName) {
            return [
                'success' => false,
                'message' => "Could not extract database name from DSN"
            ];
        }
        
        // Create DSN without database name for initial connection
        $serverDsn = self::createServerDsn($dsn, 'pgsql');
        
        try {
            // Connect to PostgreSQL server (to template1 database)
            $pdo = new PDO($serverDsn, $parsed['username'], $parsed['password'], $parsed['options']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if database already exists
            $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '{$databaseName}'");
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => "PostgreSQL database '{$databaseName}' already exists"
                ];
            }
            
            // Create database
            $pdo->exec("CREATE DATABASE \"{$databaseName}\" ENCODING 'UTF8'");
            
            return [
                'success' => true,
                'message' => "PostgreSQL database '{$databaseName}' created successfully"
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => "PostgreSQL error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract database name from DSN
     *
     * @param string $dsn
     * @return string|null
     */
    private static function extractDatabaseNameFromDsn(string $dsn): ?string
    {
        // Parse DSN like "mysql:host=localhost;port=3306;dbname=database_name"
        $parts = explode(';', $dsn);
        foreach ($parts as $part) {
            if (strpos($part, 'dbname=') === 0) {
                return substr($part, 7);
            }
        }
        return null;
    }
    
    /**
     * Create server DSN without database name
     *
     * @param string $dsn Original DSN
     * @param string $driver Database driver
     * @return string
     */
    private static function createServerDsn(string $dsn, string $driver): string
    {
        // Remove dbname parameter for initial connection
        $parts = explode(';', $dsn);
        $serverParts = [];
        
        foreach ($parts as $part) {
            if (strpos($part, 'dbname=') === false) {
                $serverParts[] = $part;
            }
        }
        
        // For PostgreSQL, connect to template1 database
        if ($driver === 'pgsql') {
            $serverParts[] = 'dbname=template1';
        }
        
        return implode(';', $serverParts);
    }
    
    /**
     * Check if database exists
     *
     * @param string $databaseUrl
     * @return bool
     */
    public static function databaseExists(string $databaseUrl): bool
    {
        try {
            $parsed = Config::parseDatabaseUrl($databaseUrl);
            $dsn = $parsed['dsn'];
            $driver = self::extractDriverFromDsn($dsn);
            
            if ($driver === 'sqlite') {
                $filePath = substr($dsn, 7); // Remove "sqlite:"
                return $filePath === ':memory:' || file_exists($filePath);
            }
            
            // For MySQL/PostgreSQL, try to connect
            $pdo = new PDO($dsn, $parsed['username'], $parsed['password'], $parsed['options']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            
            // Simple query to test connection
            $result = $pdo->query("SELECT 1");
            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
