<?php

namespace Icebox\ActiveRecord;

use PDO;
use PDOException;

/**
 * Database Connection Handler
 * 
 * Provides low-level database operations for ActiveRecord
 */
class Connection
{
    /**
     * Execute a SELECT query and return all results
     *
     * @param string $table
     * @param string|array $conditions WHERE clause string or array of conditions
     * @param array $options Query options (order, limit, offset)
     * @param array $params Query parameters for WHERE clause
     * @return array
     */
    public static function select(string $table, $conditions = '', array $options = [], array $params = [])
    {
        $sql = "SELECT * FROM {$table}";

        // Handle array conditions (backward compatibility)
        if (is_array($conditions) && !empty($conditions)) {
            $where = self::buildWhereClause($conditions, $params);
            $sql .= " WHERE {$where}";
        }
        // Handle string WHERE clause
        elseif (is_string($conditions) && $conditions !== '') {
            $sql .= " WHERE {$conditions}";
        }

        if (isset($options['order'])) {
            $sql .= " ORDER BY {$options['order']}";
        }

        if (isset($options['limit'])) {
            $sql .= " LIMIT {$options['limit']}";
        }

        if (isset($options['offset'])) {
            $sql .= " OFFSET {$options['offset']}";
        }

        return self::executeSelect($sql, $params);
    }

    /**
     * Execute a SELECT query and return single result
     *
     * @param string $table
     * @param array $conditions
     * @return array|null
     */
    public static function selectOne(string $table, array $conditions = [])
    {
        $results = self::select($table, $conditions, ['limit' => 1]);
        return $results[0] ?? null;
    }

    /**
     * Insert a record
     *
     * @param string $table
     * @param array $data
     * @return int|false Last insert ID or false on failure
     */
    public static function insert(string $table, array $data)
    {
        if (empty($data)) {
            return false;
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $params = array_values($data);

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        try {
            $pdo = Config::getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            // Log error or handle appropriately
            return false;
        }
    }

    /**
     * Update records
     *
     * @param string $table
     * @param array $data
     * @param array $conditions
     * @return int|false Number of affected rows or false on failure
     */
    public static function update(string $table, array $data, array $conditions = [])
    {
        if (empty($data)) {
            return false;
        }

        $setClause = implode(', ', array_map(function ($column) {
            return "{$column} = ?";
        }, array_keys($data)));

        $sql = "UPDATE {$table} SET {$setClause}";
        $params = array_values($data);

        if (!empty($conditions)) {
            $where = self::buildWhereClause($conditions, $params);
            $sql .= " WHERE {$where}";
        }

        try {
            $pdo = Config::getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            // Log error or handle appropriately
            return false;
        }
    }

    /**
     * Delete records
     *
     * @param string $table
     * @param array $conditions
     * @return int|false Number of affected rows or false on failure
     */
    public static function delete(string $table, array $conditions = [])
    {
        $sql = "DELETE FROM {$table}";
        $params = [];

        if (!empty($conditions)) {
            $where = self::buildWhereClause($conditions, $params);
            $sql .= " WHERE {$where}";
        }

        try {
            $pdo = Config::getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            // Log error or handle appropriately
            return false;
        }
    }

    /**
     * Execute a raw SQL query
     *
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public static function query(string $sql, array $params = [])
    {
        $pdo = Config::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Begin a transaction
     *
     * @return bool
     */
    public static function beginTransaction()
    {
        return Config::getConnection()->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return bool
     */
    public static function commit()
    {
        return Config::getConnection()->commit();
    }

    /**
     * Rollback a transaction
     *
     * @return bool
     */
    public static function rollback()
    {
        return Config::getConnection()->rollBack();
    }

    /**
     * Build WHERE clause from conditions
     *
     * @param array $conditions
     * @param array $params Reference to parameters array
     * @return string
     */
    private static function buildWhereClause(array $conditions, array &$params)
    {
        $whereParts = [];

        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                // IN clause
                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $whereParts[] = "{$column} IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                // Equality
                $whereParts[] = "{$column} = ?";
                $params[] = $value;
            }
        }

        return implode(' AND ', $whereParts);
    }

    /**
     * Execute SELECT query
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    private static function executeSelect(string $sql, array $params = [])
    {
        try {
            $pdo = Config::getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log error or handle appropriately
            return [];
        }
    }
}
