<?php

namespace Icebox\ActiveRecord\Migration;

/**
 * TableBlueprint - Handles column definitions for table creation/alteration
 */
class TableBlueprint
{
    private $tableName;
    private $columns = [];
    private $indexes = [];

    /**
     * Constructor
     *
     * @param string $tableName
     */
    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * Add a string column
     *
     * @param string $name
     * @param int $limit
     * @return ColumnDefinition
     */
    public function string(string $name, int $limit = 255): ColumnDefinition
    {
        return $this->addColumn($name, 'string', ['limit' => $limit]);
    }

    /**
     * Add a text column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'text');
    }

    /**
     * Add an integer column
     *
     * @param string $name
     * @param bool $autoIncrement
     * @return ColumnDefinition
     */
    public function integer(string $name, bool $autoIncrement = false): ColumnDefinition
    {
        return $this->addColumn($name, 'integer', ['auto_increment' => $autoIncrement]);
    }

    /**
     * Add a bigint column
     *
     * @param string $name
     * @param bool $autoIncrement
     * @return ColumnDefinition
     */
    public function bigint(string $name, bool $autoIncrement = false): ColumnDefinition
    {
        return $this->addColumn($name, 'bigint', ['auto_increment' => $autoIncrement]);
    }

    /**
     * Add a float column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function float(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'float');
    }

    /**
     * Add a decimal column
     *
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @return ColumnDefinition
     */
    public function decimal(string $name, int $precision = 10, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, 'decimal', ['precision' => $precision, 'scale' => $scale]);
    }

    /**
     * Add a boolean column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'boolean');
    }

    /**
     * Add a date column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'date');
    }

    /**
     * Add a datetime column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function datetime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'datetime');
    }

    /**
     * Add a timestamp column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'timestamp');
    }

    /**
     * Add a time column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function time(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'time');
    }

    /**
     * Add a binary column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function binary(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'binary');
    }

    /**
     * Add timestamps (created_at and updated_at)
     *
     * @return void
     */
    public function timestamps(): void
    {
        $this->datetime('created_at')->null(false)->default('CURRENT_TIMESTAMP');
        $this->datetime('updated_at')->null(false)->default('CURRENT_TIMESTAMP');
    }

    /**
     * Add a column
     *
     * @param string $name
     * @param string $type
     * @param array $options
     * @return ColumnDefinition
     */
    public function addColumn(string $name, string $type, array $options = []): ColumnDefinition
    {
        $columnDef = new ColumnDefinition($name, $type, $options);
        $this->columns[] = $columnDef;
        return $columnDef;
    }

    /**
     * Add an index
     *
     * @param string|array $columns
     * @param array $options
     * @return void
     */
    public function index($columns, array $options = []): void
    {
        $this->indexes[] = [
            'columns' => $columns,
            'options' => $options
        ];
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
