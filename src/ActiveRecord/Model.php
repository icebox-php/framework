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
     * Add OR WHERE conditions
     *
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return QueryBuilder
     */
    public static function orWhere($column, $operator = null, $value = null)
    {
        return (new QueryBuilder(static::class))->orWhere($column, $operator, $value);
    }

    /**
     * Add WHERE NULL condition
     *
     * @param string $column
     * @return QueryBuilder
     */
    public static function whereNull($column)
    {
        return (new QueryBuilder(static::class))->whereNull($column);
    }

    /**
     * Add WHERE NOT NULL condition
     *
     * @param string $column
     * @return QueryBuilder
     */
    public static function whereNotNull($column)
    {
        return (new QueryBuilder(static::class))->whereNotNull($column);
    }

    /**
     * Add WHERE IN condition
     *
     * @param string $column
     * @param array $values
     * @return QueryBuilder
     */
    public static function whereIn($column, array $values)
    {
        return (new QueryBuilder(static::class))->whereIn($column, $values);
    }

    /**
     * Add WHERE NOT IN condition
     *
     * @param string $column
     * @param array $values
     * @return QueryBuilder
     */
    public static function whereNotIn($column, array $values)
    {
        return (new QueryBuilder(static::class))->whereNotIn($column, $values);
    }

    /**
     * Add WHERE BETWEEN condition
     *
     * @param string $column
     * @param array $values [min, max]
     * @return QueryBuilder
     */
    public static function whereBetween($column, array $values)
    {
        return (new QueryBuilder(static::class))->whereBetween($column, $values);
    }

    /**
     * Add ORDER BY clause
     *
     * @param string $column
     * @param string $direction ASC|DESC
     * @return QueryBuilder
     */
    public static function orderBy($column, $direction = 'ASC')
    {
        return (new QueryBuilder(static::class))->orderBy($column, $direction);
    }

    /**
     * Add LIMIT clause
     *
     * @param int $limit
     * @return QueryBuilder
     */
    public static function limit($limit)
    {
        return (new QueryBuilder(static::class))->limit($limit);
    }

    /**
     * Add OFFSET clause
     *
     * @param int $offset
     * @return QueryBuilder
     */
    public static function offset($offset)
    {
        return (new QueryBuilder(static::class))->offset($offset);
    }

    /**
     * Select specific columns
     *
     * @param mixed $columns Column name(s) as string or array
     * @return QueryBuilder
     */
    public static function select($columns = ['*'])
    {
        $builder = new QueryBuilder(static::class);
        
        // Pass all arguments to handle multiple arguments
        if (func_num_args() > 0) {
            return call_user_func_array([$builder, 'select'], func_get_args());
        }
        
        return $builder->select($columns);
    }

    /**
     * Get first result matching conditions
     *
     * @return Model|null
     */
    public static function first()
    {
        return (new QueryBuilder(static::class))->first();
    }

    /**
     * Get count of records matching conditions
     *
     * @return int
     */
    public static function count()
    {
        return (new QueryBuilder(static::class))->count();
    }

    /**
     * Check if any records exist matching conditions
     *
     * @return bool
     */
    public static function exists()
    {
        return (new QueryBuilder(static::class))->exists();
    }

    /**
     * Execute query and get all results
     *
     * @return array
     */
    public static function get()
    {
        return (new QueryBuilder(static::class))->get();
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
