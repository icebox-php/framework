<?php

namespace Icebox\Generator;

/**
 * MigrationGenerator - Handles generation of database migration files
 */
class MigrationGenerator
{
    /**
     * Extract table name from migration name
     *
     * @param string $migrationName
     * @return string
     */
    public static function extractTableName(string $migrationName): string
    {
        // Extract table name from migration name patterns
        // create_posts_table -> posts
        // add_user_id_to_posts -> posts
        // remove_title_from_posts -> posts
        // create_join_table_for_posts_and_tags -> posts_tags
        
        if (preg_match('/^create_(.+)_table$/', $migrationName, $matches)) {
            return $matches[1];
        } elseif (preg_match('/^(add|remove|change)_(.+)_(?:to|from)_(.+)$/', $migrationName, $matches)) {
            return $matches[3];
        } elseif (preg_match('/^create_join_table_for_(.+)_and_(.+)$/', $migrationName, $matches)) {
            return $matches[1] . '_' . $matches[2];
        } elseif (preg_match('/^create_(.+)$/', $migrationName, $matches)) {
            return $matches[1];
        }
        
        // Default: use the migration name as table name
        return $migrationName;
    }

    /**
     * Parse column attributes into associative array
     *
     * @param array $attrs Array of column definitions like ['title:string', 'content:text']
     * @return array Associative array of column name => type
     */
    public static function parseColumnAttributes(array $attrs): array
    {
        $result = [];
        foreach ($attrs as $attr) {
            $temp = explode(':', $attr);
            $result[$temp[0]] = isset($temp[1]) ? $temp[1] : 'string';
        }
        return $result;
    }

    /**
     * Map PHP column types to SQL types
     *
     * @param string $phpType
     * @return string
     */
    public static function mapColumnTypeToSql(string $phpType): string
    {
        $typeMap = [
            'string' => 'VARCHAR(255)',
            'text' => 'TEXT',
            'integer' => 'INT',
            'bigint' => 'BIGINT',
            'float' => 'FLOAT',
            'decimal' => 'DECIMAL(10,2)',
            'boolean' => 'BOOLEAN',
            'date' => 'DATE',
            'datetime' => 'DATETIME',
            'timestamp' => 'TIMESTAMP',
            'time' => 'TIME',
            'binary' => 'BLOB',
        ];
        
        return isset($typeMap[$phpType]) ? $typeMap[$phpType] : $typeMap['string'];
    }

    /**
     * Generate SQL from migration name and columns
     *
     * @param string $migrationName
     * @param array $columns Array of column definitions
     * @return array [upSql, downSql]
     */
    public static function generateSql(string $migrationName, array $columns): array
    {
        $tableName = self::extractTableName($migrationName);
        $columnDefs = self::parseColumnAttributes($columns);
        
        // Determine migration type
        if (strpos($migrationName, 'create') === 0) {
            // Create table migration
            $sql = "CREATE TABLE {$tableName} (\n";
            $sql .= "  id INT AUTO_INCREMENT PRIMARY KEY";
            
            $columnSql = [];
            foreach ($columnDefs as $columnName => $columnType) {
                $sqlType = self::mapColumnTypeToSql($columnType);
                $columnSql[] = "  {$columnName} {$sqlType}";
            }
            
            if (!empty($columnSql)) {
                $sql .= ",\n" . implode(",\n", $columnSql);
            }
            
            $sql .= "\n)";
            
            $upSql = $sql;
            $downSql = "DROP TABLE {$tableName}";
            
        } elseif (strpos($migrationName, 'add') === 0) {
            // Add column migration
            $upSql = "ALTER TABLE {$tableName} ADD COLUMN ";
            $downSql = "ALTER TABLE {$tableName} DROP COLUMN ";
            
            foreach ($columnDefs as $columnName => $columnType) {
                $sqlType = self::mapColumnTypeToSql($columnType);
                $upSql .= "{$columnName} {$sqlType}";
                $downSql .= "{$columnName}";
                break; // Only handle first column for add/remove migrations
            }
            
        } elseif (strpos($migrationName, 'remove') === 0) {
            // Remove column migration (reverse of add)
            $upSql = "ALTER TABLE {$tableName} DROP COLUMN ";
            $downSql = "ALTER TABLE {$tableName} ADD COLUMN ";
            
            foreach ($columnDefs as $columnName => $columnType) {
                $sqlType = self::mapColumnTypeToSql($columnType);
                $upSql .= "{$columnName}";
                $downSql .= "{$columnName} {$sqlType}";
                break; // Only handle first column for add/remove migrations
            }
            
        } else {
            // Default: empty migration
            $upSql = "-- Migration: {$migrationName}";
            $downSql = "-- Rollback: {$migrationName}";
        }
        
        return [$upSql, $downSql];
    }

    /**
     * Generate migration class name from migration name
     *
     * @param string $migrationName
     * @param string $timestamp
     * @return string
     */
    public static function generateClassName(string $migrationName, string $timestamp): string
    {
        return 'Migration' . $timestamp . '_' . str_replace(' ', '_', ucwords(preg_replace('/[^a-zA-Z0-9]/', ' ', $migrationName)));
    }

    /**
     * Generate migration file content
     *
     * @param string $className
     * @param string $upSql
     * @param string $downSql
     * @return string
     */
    public static function generateFileContent(string $className, string $upSql, string $downSql): string
    {
        $template = <<<PHP
<?php

use Icebox\ActiveRecord\Connection;

class {$className} 
{
    public function up()
    {
        \$sql = "{$upSql}";
        Connection::query(\$sql);
    }

    public function down()
    {
        \$sql = "{$downSql}";
        Connection::query(\$sql);
    }
}

PHP;
        return $template;
    }

    /**
     * Create migration file
     *
     * @param string $migrationName
     * @param string $upSql
     * @param string $downSql
     * @param string $migrationsDir
     * @return string Path to created file
     */
    public static function createMigrationFile(string $migrationName, string $upSql, string $downSql, string $migrationsDir): string
    {
        $timestamp = date('YmdHis');
        $className = self::generateClassName($migrationName, $timestamp);
        $content = self::generateFileContent($className, $upSql, $downSql);
        
        // Ensure migrations directory exists
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
        }
        
        $fileName = "{$timestamp}_{$migrationName}.php";
        $filePath = $migrationsDir . '/' . $fileName;
        
        if (file_put_contents($filePath, $content)) {
            return $filePath;
        }
        
        throw new \RuntimeException("Failed to create migration file: {$filePath}");
    }

    /**
     * Generate HTML input type for form generation
     *
     * @param string $columnType
     * @return array ['html_tag' => 'input|textarea|select', 'type' => 'text|number|date|...']
     */
    public static function getHtmlInputType(string $columnType): array
    {
        $supportedTypes = [
            'boolean' => ['html_tag' => 'checkbox', 'type' => ''],
            'date' => ['html_tag' => 'input', 'type' => 'date'],
            'datetime' => ['html_tag' => 'input', 'type' => 'datetime-local'],
            'decimal' => ['html_tag' => 'input', 'type' => 'number'],
            'float' => ['html_tag' => 'input', 'type' => 'number'],
            'integer' => ['html_tag' => 'input', 'type' => 'number'],
            'string' => ['html_tag' => 'input', 'type' => 'text'],
            'text' => ['html_tag' => 'textarea', 'type' => ''],
            'time' => ['html_tag' => 'input', 'type' => 'time'],
            'select' => ['html_tag' => 'select', 'type' => ''],
        ];

        return isset($supportedTypes[$columnType]) ? $supportedTypes[$columnType] : $supportedTypes['string'];
    }
}
