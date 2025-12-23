<?php

namespace Icebox;

class Config
{
    private static array $config = [];

    /**
     * Load and merge configuration settings
     *
     * @param array $settings Configuration array to merge
     * @return void
     */
    public static function set(array $settings): void
    {
        self::$config = array_merge(self::$config, $settings);
    }

    /**
     * Get a configuration value by key
     *
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Support dot notation: 'database.host'
        if (str_contains($key, '.')) {
            return self::getNestedValue($key, $default);
        }

        return self::$config[$key] ?? $default;
    }

    /**
     * Check if a configuration key exists
     *
     * @param string $key Configuration key
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (str_contains($key, '.')) {
            return self::getNestedValue($key) !== null;
        }

        return isset(self::$config[$key]);
    }

    /**
     * Remove a configuration key.
     *
     * Useful for tests, feature flags, or runtime overrides.
     * Silently ignores missing keys.
     *
     * @param string $key Configuration key to remove
     */
    public static function unset(string $key): void
    {
        unset(self::$config[$key]);
    }

    /**
     * Get all configuration values
     *
     * @return array
     */
    public static function all(): array
    {
        return self::$config;
    }

    /**
     * Clear all configuration (useful for testing)
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$config = [];
    }

    /**
     * Get nested configuration value using dot notation
     *
     * @param string $key Dot notation key
     * @param mixed $default Default value
     * @return mixed
     */
    private static function getNestedValue(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}