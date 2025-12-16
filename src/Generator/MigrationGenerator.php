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

    // /**
    //  * Generate column specifications for DSL migration generation
    //  *
    //  * @param string $migrationName
    //  * @param array $columns Array of column definitions like ['name:string', 'price:decimal']
    //  * @return array Normalized column specifications for DSL
    //  */
    // public static function generateColsForDSL(string $migrationName, array $columns): array
    // {
    //     // Parse column attributes to validate format
    //     $parsed = self::parseColumnAttributes($columns);

    //     // Validate that all columns have valid types
    //     $validTypes = ['string', 'text', 'integer', 'bigint', 'float', 'decimal', 'boolean', 'date', 'datetime', 'timestamp', 'time', 'binary'];

    //     foreach ($parsed as $columnName => $columnType) {
    //         if (!in_array($columnType, $validTypes)) {
    //             throw new \InvalidArgumentException("Invalid column type '$columnType' for column '$columnName'. Valid types: " . implode(', ', $validTypes));
    //         }
    //     }

    //     // For create table migrations, ensure we have basic structure
    //     if (strpos($migrationName, 'create') === 0) {
    //         // Could add default 'id' column or timestamps here if needed
    //         // But for now, just return the validated columns
    //     }

    //     return $columns; // Return original format since it's already correct for DSL
    // }

    /**
     * Generate DSL code from migration name and columns
     *
     * @param string $migrationName
     * @param array $columns Array of column definitions
     * @return array [upCode, downCode]
     */
    public static function generateDsl(string $migrationName, array $columns): array
    {
        $tableName = self::extractTableName($migrationName);
        $columnDefs = self::parseColumnAttributes($columns);

        // Determine migration type
        if (strpos($migrationName, 'create') === 0) {
            // Create table migration
            $upCode = self::generateCreateTableDsl($tableName, $columnDefs);
            $downCode = self::generateDropTableDsl($tableName);

        } elseif (strpos($migrationName, 'add') === 0) {
            // Add column migration
            $upCode = self::generateAddColumnDsl($tableName, $columnDefs);
            $downCode = self::generateRemoveColumnDsl($tableName, $columnDefs);

        } elseif (strpos($migrationName, 'remove') === 0) {
            // Remove column migration (reverse of add)
            $upCode = self::generateRemoveColumnDsl($tableName, $columnDefs);
            $downCode = self::generateAddColumnDsl($tableName, $columnDefs);

        } else {
            // Default: empty migration
            $upCode = "// Migration: {$migrationName}";
            $downCode = "// Rollback: {$migrationName}";
        }

        return [$upCode, $downCode];
    }

    /**
     * Generate create table DSL
     *
     * @param string $tableName
     * @param array $columnDefs
     * @return string
     */
    private static function generateCreateTableDsl(string $tableName, array $columnDefs): string
    {
        $code = "\$this->createTable('{$tableName}', function(\$t) {\n";

        foreach ($columnDefs as $columnName => $columnType) {
            $methodCall = self::generateColumnMethodCall($columnName, $columnType);
            $code .= "    {$methodCall};\n";
        }

        $code .= "});";
        return $code;
    }

    /**
     * Generate drop table DSL
     *
     * @param string $tableName
     * @return string
     */
    private static function generateDropTableDsl(string $tableName): string
    {
        return "\$this->dropTable('{$tableName}');";
    }

    /**
     * Generate add column DSL
     *
     * @param string $tableName
     * @param array $columnDefs
     * @return string
     */
    private static function generateAddColumnDsl(string $tableName, array $columnDefs): string
    {
        $code = "";
        foreach ($columnDefs as $columnName => $columnType) {
            $methodCall = self::generateColumnMethodCall($columnName, $columnType);
            $code .= "\$this->addColumn('{$tableName}', '{$columnName}', '{$columnType}');\n";
            break; // Only handle first column for add/remove migrations
        }
        return trim($code);
    }

    /**
     * Generate remove column DSL
     *
     * @param string $tableName
     * @param array $columnDefs
     * @return string
     */
    private static function generateRemoveColumnDsl(string $tableName, array $columnDefs): string
    {
        $code = "";
        foreach ($columnDefs as $columnName => $columnType) {
            $code .= "\$this->removeColumn('{$tableName}', '{$columnName}');\n";
            break; // Only handle first column for add/remove migrations
        }
        return trim($code);
    }

    /**
     * Generate column method call for create table
     *
     * @param string $columnName
     * @param string $columnType
     * @return string
     */
    private static function generateColumnMethodCall(string $columnName, string $columnType): string
    {
        $methodMap = [
            'string' => 'string',
            'text' => 'text',
            'integer' => 'integer',
            'bigint' => 'bigint',
            'float' => 'float',
            'decimal' => 'decimal',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
            'timestamp' => 'timestamp',
            'time' => 'time',
            'binary' => 'binary',
        ];

        $method = isset($methodMap[$columnType]) ? $methodMap[$columnType] : 'string';
        return "\$t->{$method}('{$columnName}')";
    }

    /**
     * Generate SQL from migration name and columns (backward compatibility)
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
     * Generate migration file content (DSL-based)
     *
     * @param string $className
     * @param string $upCode
     * @param string $downCode
     * @return string
     */
    public static function generateDslFileContent(string $className, string $upCode, string $downCode): string
    {
        $template = <<<PHP
<?php

use Icebox\ActiveRecord\Migration\BaseMigration;

class {$className} extends BaseMigration
{
    public function up()
    {
{$upCode}
    }

    public function down()
    {
{$downCode}
    }
}

PHP;
        return $template;
    }

    /**
     * Generate migration file content (SQL-based for backward compatibility)
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
     * Create migration file (DSL-based)
     *
     * @param string $migrationName
     * @param array $columns
     * @param string $migrationsDir
     * @return string Path to created file
     */
    public static function createDslMigrationFile(string $migrationName, array $columns, string $migrationsDir): string
    {
        $timestamp = date('YmdHis');
        $className = self::generateClassName($migrationName, $timestamp);
        list($upCode, $downCode) = self::generateDsl($migrationName, $columns);
        $content = self::generateDslFileContent($className, $upCode, $downCode);

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
     * Create migration file (smart parameter detection for DSL vs SQL)
     *
     * @param string $migrationName
     * @param array|string $param2 Either column array (DSL mode) or up SQL string (SQL mode)
     * @param string|null $param3 Either migrations dir (DSL mode) or down SQL string (SQL mode)
     * @param string|null $param4 Migrations directory (SQL mode only)
     * @return string Path to created file
     */
    public static function createMigrationFile(string $migrationName, $param2, $param3 = null, $param4 = null): string
    {
        $timestamp = date('YmdHis');
        $className = self::generateClassName($migrationName, $timestamp);

        // Detect mode based on parameter types
        if (is_array($param2)) {
            // DSL mode: createMigrationFile('name', ['col:spec'], 'migrationsDir')
            $columns = $param2;
            $migrationsDir = $param3 ?? 'db/migrations';

            list($upCode, $downCode) = self::generateDsl($migrationName, $columns);
            $content = self::generateDslFileContent($className, $upCode, $downCode);
        } else {
            // SQL mode: createMigrationFile('name', 'upSql', 'downSql', 'migrationsDir')
            $upSql = $param2;
            $downSql = $param3;
            $migrationsDir = $param4 ?? 'db/migrations';

            $content = self::generateFileContent($className, $upSql, $downSql);
        }

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
