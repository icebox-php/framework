<?php

namespace Icebox\Tests;

use Icebox\ActiveRecord\Config;
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
        Config::initialize(function() {
            return [
                'driver' => 'sqlite',
                'host' => ':memory:',
                'database' => ':memory:',
                'username' => '',
                'password' => '',
                'charset' => 'utf8'
            ];
        });

        // Create test tables
        $pdo = Config::getConnection();
        
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
        if (Config::isInitialized()) {
            $pdo = Config::getConnection();
            $pdo->exec("DROP TABLE IF EXISTS users");
            $pdo->exec("DROP TABLE IF EXISTS posts");
            Config::closeConnection();
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
        $pdo = Config::getConnection();
        
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
        $pdo = Config::getConnection();
        $pdo->exec("DELETE FROM users");
        $pdo->exec("DELETE FROM posts");
    }
}
