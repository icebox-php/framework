<?php

namespace Icebox\ActiveRecord;

/**
 * Base ActiveRecord Model class
 * 
 * Provides basic CRUD operations and database interaction
 */
abstract class Model
{
    /**
     * @var string The database table name
     */
    protected static $table;

    /**
     * @var string The primary key column name
     */
    protected static $primaryKey = 'id';

    /**
     * @var array Model attributes
     */
    protected $attributes = [];

    /**
     * @var array Original attributes for dirty checking
     */
    protected $original = [];

    /**
     * @var bool Whether the model exists in the database
     */
    protected $exists = false;

    /**
     * Constructor
     *
     * @param array $attributes Initial attributes
     * @param bool $exists Whether the model exists in database
     */
    public function __construct(array $attributes = [], $exists = false)
    {
        $this->attributes = $attributes;
        $this->original = $attributes;
        $this->exists = $exists;
    }

    /**
     * Get the table name for the model
     *
     * @return string
     */
    public static function getTable()
    {
        if (static::$table) {
            return static::$table;
        }

        // Convert class name to table name (plural, snake_case)
        $className = basename(str_replace('\\', '/', static::class));
        $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        return $table . 's'; // Pluralize
    }

    /**
     * Get the primary key name
     *
     * @return string
     */
    public static function getPrimaryKey()
    {
        return static::$primaryKey;
    }

    /**
     * Find a record by primary key
     *
     * @param mixed $id
     * @return static|null
     */
    public static function find($id)
    {
        $primaryKey = static::getPrimaryKey();
        $result = Connection::selectOne(
            static::getTable(),
            [$primaryKey => $id]
        );

        if ($result) {
            return new static($result, true);
        }

        return null;
    }

    /**
     * Create a query builder instance
     *
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return QueryBuilder
     */
    public static function where($column = null, $operator = null, $value = null)
    {
        $builder = new QueryBuilder(static::class);
        
        if ($column !== null) {
            return $builder->where($column, $operator, $value);
        }
        
        return $builder;
    }

    /**
     * Save the model to database
     *
     * @return bool
     */
    public function save()
    {
        if ($this->exists) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    /**
     * Insert new record
     *
     * @return bool
     */
    protected function insert()
    {
        $id = Connection::insert(static::getTable(), $this->attributes);
        
        if ($id) {
            $this->attributes[static::getPrimaryKey()] = $id;
            $this->exists = true;
            $this->original = $this->attributes;
            return true;
        }

        return false;
    }

    /**
     * Update existing record
     *
     * @return bool
     */
    protected function update()
    {
        $primaryKey = static::getPrimaryKey();
        $id = $this->attributes[$primaryKey] ?? null;

        if (!$id) {
            return false;
        }

        $success = Connection::update(
            static::getTable(),
            $this->attributes,
            [$primaryKey => $id]
        );

        if ($success) {
            $this->original = $this->attributes;
        }

        return $success;
    }

    /**
     * Delete the record
     *
     * @return bool
     */
    public function delete()
    {
        if (!$this->exists) {
            return false;
        }

        $primaryKey = static::getPrimaryKey();
        $id = $this->attributes[$primaryKey] ?? null;

        if (!$id) {
            return false;
        }

        $success = Connection::delete(
            static::getTable(),
            [$primaryKey => $id]
        );

        if ($success) {
            $this->exists = false;
        }

        return $success;
    }

    /**
     * Update model attributes
     *
     * @param array $attributes
     * @return bool
     */
    public function updateAttributes(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this->save();
    }

    /**
     * Get attribute value
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set attribute value
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Check if attribute exists
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Convert model to array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Get changed attributes
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Check if model has changes
     *
     * @return bool
     */
    public function isDirty()
    {
        return !empty($this->getDirty());
    }
}
