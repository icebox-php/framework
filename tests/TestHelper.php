<?php

namespace Icebox\Tests;

use Icebox\ActiveRecord\Config as ArConfig;
use PDO;

/**
 * Test helper class for common test setup and utilities
 */
class TestHelper
{
    /**
     * Initialize SQLite in-memory database for testing
     */
    public static function initializeTestDatabase(): void
    {
        // Config::initialize(function() {
        //     return [
        //         'driver' => 'sqlite',
        //         'database' => ':memory:',
        //         'username' => '',
        //         'password' => ''
        //         // Note: 'host' and 'charset' are not used for SQLite; if you need this, create PR
        //         // The Config class handles SQLite DSN specially
        //     ];
        // });

        // Create test tables
        $pdo = ArConfig::getConnection();
        
        // Create users table for testing
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255),
                email VARCHAR(255),
                age INTEGER,
                active BOOLEAN DEFAULT 1,
                approved_at DATETIME,
                deleted_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create posts table for testing relationships
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                title VARCHAR(255),
                content TEXT,
                published BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Clean up test database
     */
    public static function cleanupTestDatabase(): void
    {
        if (ArConfig::isInitialized()) {
            $pdo = ArConfig::getConnection();
            $pdo->exec("DROP TABLE IF EXISTS users");
            $pdo->exec("DROP TABLE IF EXISTS posts");
            ArConfig::closeConnection();
        }
    }

    /**
     * Set up HTTP server variables for testing
     */
    public static function setupHttpContext(string $method = 'GET', array $server = []): void
    {
        $_SERVER = array_merge([
            'HTTP_HOST' => 'localhost',
            'SERVER_PORT' => 80,
            'REQUEST_METHOD' => $method,
            'HTTPS' => 'off',
            'SCRIPT_NAME' => '/index.php'
        ], $server);
    }

    /**
     * Reset HTTP context
     */
    public static function resetHttpContext(): void
    {
        $_SERVER = [];
        $_POST = [];
        $_GET = [];
        $_SESSION = [];
    }

    /**
     * Create test data in the database
     */
    public static function createTestData(): void
    {
        $pdo = ArConfig::getConnection();
        
        // Insert test users
        $pdo->exec("INSERT INTO users (name, email, age, active) VALUES 
            ('John Doe', 'john@example.com', 30, 1),
            ('Jane Smith', 'jane@example.com', 25, 1),
            ('Bob Johnson', 'bob@example.com', 40, 0)");
        
        // Insert test posts
        $pdo->exec("INSERT INTO posts (user_id, title, content, published) VALUES
            (1, 'First Post', 'Content of first post', 1),
            (1, 'Second Post', 'Content of second post', 0),
            (2, 'Another Post', 'Content from Jane', 1)");
    }

    /**
     * Clear all test data
     */
    public static function clearTestData(): void
    {
        $pdo = ArConfig::getConnection();
        $pdo->exec("DELETE FROM users");
        $pdo->exec("DELETE FROM posts");
    }

    /**
     * Assert that a table exists in the database
     */
    public static function assertTableExists(string $tableName): void
    {
        $pdo = ArConfig::getConnection();
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$tableName}'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        if (empty($result)) {
            throw new \PHPUnit\Framework\AssertionFailedError("Table '{$tableName}' does not exist in the database");
        }
    }

    /**
     * Assert that a table does not exist in the database
     */
    public static function assertTableDoesNotExist(string $tableName): void
    {
        $pdo = ArConfig::getConnection();
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$tableName}'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        if (!empty($result)) {
            throw new \PHPUnit\Framework\AssertionFailedError("Table '{$tableName}' exists in the database, but should not");
        }
    }

    /**
     * Assert that a column exists in a table
     *
     * @param string $tableName The table name
     * @param string $columnName The column name
     * @param string|null $expectedType Optional expected column type (SQLite type names are flexible)
     */
    public static function assertColumnExists(string $tableName, string $columnName, ?string $expectedType = null): void
    {
        $pdo = ArConfig::getConnection();
        $stmt = $pdo->query("PRAGMA table_info('{$tableName}')");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        $found = false;
        $actualType = null;
        
        foreach ($columns as $column) {
            if ($column['name'] === $columnName) {
                $found = true;
                $actualType = $column['type'];
                break;
            }
        }
        
        if (!$found) {
            throw new \PHPUnit\Framework\AssertionFailedError("Column '{$columnName}' does not exist in table '{$tableName}'");
        }
        
        if ($expectedType !== null && $actualType !== null) {
            // SQLite types are flexible, so we do a case-insensitive contains check
            $expectedTypeLower = strtolower($expectedType);
            $actualTypeLower = strtolower($actualType);
            
            // Check if expected type is contained in actual type (e.g., 'int' in 'INTEGER')
            if (strpos($actualTypeLower, $expectedTypeLower) === false) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    "Column '{$columnName}' in table '{$tableName}' should have type containing '{$expectedType}', got '{$actualType}'"
                );
            }
        }
    }

    /**
     * Get column information for a table
     */
    public static function getTableInfo(string $tableName): array
    {
        $pdo = ArConfig::getConnection();
        $stmt = $pdo->query("PRAGMA table_info('{$tableName}')");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        return $columns;
    }

    /**
     * Get list of all tables in the database
     */
    public static function getAllTables(): array
    {
        $pdo = ArConfig::getConnection();
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();
        
        return $tables;
    }
}
