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
    public static function initialize(PDO $connection)
    {
        // $config = $initializer();
        
        // if (!is_array($config)) {
        //     throw new \InvalidArgumentException('Configuration must return an array');
        // }

        // self::$config = $config;
        // self::createConnection();

        self::$connection = $connection;
    }

    // /**
    //  * Create database connection
    //  *
    //  * @throws PDOException
    //  */
    // private static function createConnection()
    // {
    //     $driver = self::$config['driver'] ?? null;
    //     if (!$driver) {
    //         throw new \InvalidArgumentException("Missing required configuration: driver");
    //     }

    //     $database = self::$config['database'] ?? null;
    //     if (!$database) {
    //         throw new \InvalidArgumentException("Missing required configuration: database");
    //     }

    //     $username = self::$config['username'] ?? '';
    //     $password = self::$config['password'] ?? '';
    //     $host = self::$config['host'] ?? '';
    //     $charset = self::$config['charset'] ?? 'utf8mb4';

    //     // Handle SQLite specially since it has a different DSN format
    //     if ($driver === 'sqlite') {
    //         // For SQLite, database is the path (or :memory: for in-memory)
    //         // Ignore host and charset parameters for SQLite
    //         $dsn = "sqlite:{$database}";
    //     } else {
    //         // For other databases (MySQL, PostgreSQL), require host
    //         if (!$host) {
    //             throw new \InvalidArgumentException("Missing required configuration: host");
    //         }
    //         $dsn = "{$driver}:host={$host};dbname={$database};charset={$charset}";
    //     }

    //     $options = [
    //         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    //         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    //         PDO::ATTR_EMULATE_PREPARES => false,
    //     ];

    //     self::$connection = new PDO($dsn, $username, $password, $options);
    // }

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

    /**
     * Parse a database URL and return PDO components
     * 
     * @param string $url Database URL (e.g., mysql://root:password@127.0.0.1/forge?charset=UTF-8)
     * @return array ['dsn' => string, 'username' => string, 'password' => string, 'options' => array]
     * @throws \InvalidArgumentException
     */
    public static function parseDatabaseUrl($url)
    {
        #---------------------------------
        #--- helper functions ---

        /**
         * Get default port for database scheme
         */
        $getDefaultPort = function($scheme)
        {
            $ports = [
                'mysql' => 3306,
                'pgsql' => 5432,
                'sqlite' => null,
            ];
            return $ports[$scheme] ?? 3306;
        };

        /**
         * Get default PDO options for a scheme
         * Users can override these by passing options to Config::initialize()
         */
        $getDefaultOptions = function($scheme)
        {
            return [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
        };

        #---------------------------------
        #--- function starts from here ---

        $parsed = parse_url($url);

        if (!$parsed || !isset($parsed['scheme'], $parsed['host'])) {
            throw new \InvalidArgumentException('Invalid database URL format');
        }

        $scheme = $parsed['scheme'];
        $host = $parsed['host'];
        $port = $parsed['port'] ?? $getDefaultPort($scheme);
        $database = ltrim($parsed['path'] ?? '', '/');
        $username = $parsed['user'] ?? '';
        $password = $parsed['pass'] ?? '';

        if (empty($database)) {
            throw new \InvalidArgumentException('Database name is required in URL path');
        }

        // Build DSN
        $dsn = "{$scheme}:host={$host};port={$port};dbname={$database}";

        // Parse query parameters for options
        $options = $getDefaultOptions($scheme);
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
            foreach ($queryParams as $key => $value) {
                // Convert string booleans
                if (strtolower($value) === 'true') {
                    $value = true;
                } elseif (strtolower($value) === 'false') {
                    $value = false;
                }
                $options[$key] = $value;
            }
        }

        return [
            'dsn' => $dsn,
            'username' => $username,
            'password' => $password,
            'options' => $options,
        ];

        
    }
}
