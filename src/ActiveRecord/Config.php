<?php

namespace Icebox\ActiveRecord;

use Closure;
use PDO;
use PDOException;

/**
 * ActiveRecord Configuration
 * 
 * Handles database connection configuration and initialization
 */
class Config
{
    /**
     * @var PDO|null Database connection instance
     */
    private static $connection;

    /**
     * @var array Database configuration
     */
    private static $config = [];

    /**
     * Initialize database configuration
     *
     * @param Closure $initializer Configuration closure
     * @throws PDOException
     */
    public static function initialize(Closure $initializer)
    {
        $config = $initializer();
        
        if (!is_array($config)) {
            throw new \InvalidArgumentException('Configuration must return an array');
        }

        self::$config = $config;
        self::createConnection();
    }

    /**
     * Create database connection
     *
     * @throws PDOException
     */
    private static function createConnection()
    {
        $required = ['driver', 'host', 'database', 'username', 'password'];
        
        foreach ($required as $key) {
            if (!isset(self::$config[$key])) {
                throw new \InvalidArgumentException("Missing required configuration: {$key}");
            }
        }

        $driver = self::$config['driver'];
        $host = self::$config['host'];
        $database = self::$config['database'];
        $username = self::$config['username'];
        $password = self::$config['password'];
        $charset = self::$config['charset'] ?? 'utf8mb4';

        $dsn = "{$driver}:host={$host};dbname={$database};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$connection = new PDO($dsn, $username, $password, $options);
    }

    /**
     * Get database connection
     *
     * @return PDO
     * @throws \RuntimeException If connection not initialized
     */
    public static function getConnection()
    {
        if (!self::$connection) {
            throw new \RuntimeException('Database connection not initialized. Call Config::initialize() first.');
        }

        return self::$connection;
    }

    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }

    /**
     * Set configuration value
     *
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        self::$config[$key] = $value;
    }

    /**
     * Check if configuration is initialized
     *
     * @return bool
     */
    public static function isInitialized()
    {
        return self::$connection !== null;
    }

    /**
     * Close database connection
     */
    public static function closeConnection()
    {
        self::$connection = null;
    }
}
