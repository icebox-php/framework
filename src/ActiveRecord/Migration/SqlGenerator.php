<?php

namespace Icebox\ActiveRecord\Migration;

/**
 * SqlGenerator - Generates SQL strings for database migration operations
 */
class SqlGenerator
{
    /**
     * Generate CREATE TABLE SQL
     *
     * @param string $tableName
     * @param callable $callback Callback that receives TableBlueprint
     * @return string
     */
    public static function generateCreateTableSql(string $tableName, callable $callback): string
    {
        $blueprint = new TableBlueprint($tableName);
        $callback($blueprint);
        
        $sql = "CREATE TABLE {$tableName} (\n";
        $sql .= "  id INT AUTO_INCREMENT PRIMARY KEY";

        $columnSqls = [];
        foreach ($blueprint->getColumns() as $column) {
            $columnSqls[] = '  ' . $column->toSql();
        }

        if (!empty($columnSqls)) {
            $sql .= ",\n" . implode(",\n", $columnSqls);
        }

        $sql .= "\n)";

        return $sql;
    }

    /**
     * Generate ALTER TABLE ADD COLUMN SQL
     *
     * @param string $tableName
     * @param ColumnDefinition $column
     * @return string
     */
    public static function generateAddColumnSql(string $tableName, ColumnDefinition $column): string
    {
        return "ALTER TABLE {$tableName} ADD COLUMN " . $column->toSql();
    }

    /**
     * Generate ALTER TABLE DROP COLUMN SQL
     *
     * @param string $tableName
     * @param string $columnName
     * @return string
     */
    public static function generateDropColumnSql(string $tableName, string $columnName): string
    {
        return "ALTER TABLE {$tableName} DROP COLUMN {$columnName}";
    }

    /**
     * Generate ALTER TABLE RENAME COLUMN SQL
     *
     * @param string $tableName
     * @param string $oldName
     * @param string $newName
     * @return string
     */
    public static function generateRenameColumnSql(string $tableName, string $oldName, string $newName): string
    {
        return "ALTER TABLE {$tableName} RENAME COLUMN {$oldName} TO {$newName}";
    }

    /**
     * Generate CREATE INDEX SQL
     *
     * @param string $tableName
     * @param string|array $columns
     * @param array $options
     * @return string
     */
    public static function generateCreateIndexSql(string $tableName, $columns, array $options = []): string
    {
        $indexName = $options['name'] ?? self::generateIndexName($tableName, $columns);
        $columnsStr = is_array($columns) ? implode(', ', $columns) : $columns;
        $unique = isset($options['unique']) && $options['unique'] ? 'UNIQUE ' : '';

        return "CREATE {$unique}INDEX {$indexName} ON {$tableName} ({$columnsStr})";
    }

    /**
     * Generate DROP INDEX SQL
     *
     * @param string $tableName
     * @param string|array $columns
     * @param array $options
     * @return string
     */
    public static function generateDropIndexSql(string $tableName, $columns, array $options = []): string
    {
        $indexName = $options['name'] ?? self::generateIndexName($tableName, $columns);
        return "DROP INDEX {$indexName}";
    }

    /**
     * Generate DROP TABLE SQL
     *
     * @param string $tableName
     * @return string
     */
    public static function generateDropTableSql(string $tableName): string
    {
        return "DROP TABLE {$tableName}";
    }

    /**
     * Map PHP column type to SQL type
     *
     * @param string $phpType
     * @param array $options
     * @return string
     */
    public static function mapColumnTypeToSql(string $phpType, array $options = []): string
    {
        $typeMap = [
            'string' => 'VARCHAR',
            'text' => 'TEXT',
            'integer' => 'INT',
            'bigint' => 'BIGINT',
            'float' => 'FLOAT',
            'decimal' => 'DECIMAL',
            'boolean' => 'BOOLEAN',
            'date' => 'DATE',
            'datetime' => 'DATETIME',
            'timestamp' => 'TIMESTAMP',
            'time' => 'TIME',
            'binary' => 'BLOB',
        ];

        $sqlType = isset($typeMap[$phpType]) ? $typeMap[$phpType] : 'VARCHAR(255)';

        // Add length/precision/scale specifications
        if ($phpType === 'string' && isset($options['limit'])) {
            $sqlType .= "({$options['limit']})";
        } elseif ($phpType === 'decimal') {
            $precision = $options['precision'] ?? 10;
            $scale = $options['scale'] ?? 2;
            $sqlType .= "({$precision},{$scale})";
        } elseif ($phpType === 'string' && !isset($options['limit'])) {
            // Default length for string type
            $sqlType .= "(255)";
        }

        return $sqlType;
    }

    /**
     * Generate index name
     *
     * @param string $tableName
     * @param string|array $columns
     * @return string
     */
    private static function generateIndexName(string $tableName, $columns): string
    {
        $columnsStr = is_array($columns) ? implode('_', $columns) : $columns;
        return "index_{$tableName}_on_{$columnsStr}";
    }
}
