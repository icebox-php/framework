<?php

namespace Icebox\ActiveRecord\Migration;

use Icebox\ActiveRecord\Connection;

/**
 * Base Migration class providing DSL methods for database migrations
 */
abstract class BaseMigration
{
    /**
     * Create a table
     *
     * @param string $tableName
     * @param callable $callback
     * @return void
     */
    protected function createTable(string $tableName, callable $callback): void
    {
        $sql = SqlGenerator::generateCreateTableSql($tableName, $callback);
        Connection::query($sql);
    }

    /**
     * Drop a table
     *
     * @param string $tableName
     * @return void
     */
    protected function dropTable(string $tableName): void
    {
        $sql = SqlGenerator::generateDropTableSql($tableName);
        Connection::query($sql);
    }

    /**
     * Add a column to an existing table
     *
     * @param string $tableName
     * @param string $columnName
     * @param string $type
     * @param array $options
     * @return void
     */
    protected function addColumn(string $tableName, string $columnName, string $type, array $options = []): void
    {
        $columnDef = new ColumnDefinition($columnName, $type, $options);
        $sql = SqlGenerator::generateAddColumnSql($tableName, $columnDef);
        Connection::query($sql);
    }

    /**
     * Remove a column from a table
     *
     * @param string $tableName
     * @param string $columnName
     * @return void
     */
    protected function removeColumn(string $tableName, string $columnName): void
    {
        $sql = SqlGenerator::generateDropColumnSql($tableName, $columnName);
        Connection::query($sql);
    }

    /**
     * Rename a column
     *
     * @param string $tableName
     * @param string $oldName
     * @param string $newName
     * @return void
     */
    protected function renameColumn(string $tableName, string $oldName, string $newName): void
    {
        // This is database-specific, for now we'll use a generic approach
        // In a real implementation, you'd detect the database type
        $sql = SqlGenerator::generateRenameColumnSql($tableName, $oldName, $newName);
        Connection::query($sql);
    }

    /**
     * Add an index
     *
     * @param string $tableName
     * @param string|array $columns
     * @param array $options
     * @return void
     */
    protected function addIndex(string $tableName, $columns, array $options = []): void
    {
        $sql = SqlGenerator::generateCreateIndexSql($tableName, $columns, $options);
        Connection::query($sql);
    }

    /**
     * Remove an index
     *
     * @param string $tableName
     * @param string|array $columns
     * @param array $options
     * @return void
     */
    protected function removeIndex(string $tableName, $columns, array $options = []): void
    {
        $sql = SqlGenerator::generateDropIndexSql($tableName, $columns, $options);
        Connection::query($sql);
    }

    /**
     * Execute raw SQL
     *
     * @param string $sql
     * @return void
     */
    protected function execute(string $sql): void
    {
        Connection::query($sql);
    }

    /**
     * Abstract methods that must be implemented by migration classes
     */
    abstract public function up();
    abstract public function down();
}
