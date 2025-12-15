# Icebox ActiveRecord Documentation

## Overview

Icebox ActiveRecord is a modern PDO-based implementation with Rails-style query builder. It provides a clean, intuitive API for database operations.

## Table of Contents

1. [Installation & Configuration](#installation--configuration)
2. [Creating Models](#creating-models)
3. [Basic CRUD Operations](#basic-crud-operations)
4. [Query Builder](#query-builder)
5. [Advanced Queries](#advanced-queries)
6. [Transactions](#transactions)
7. [API Reference](#api-reference)

## Installation & Configuration

### Database Configuration

```php
use Icebox\ActiveRecord\Config;

Config::initialize(function() {
    return [
        'driver'    => 'mysql',     // mysql, pgsql, sqlite
        'host'      => 'localhost',
        'database'  => 'myapp',
        'username'  => 'root',
        'password'  => '',
        'charset'   => 'utf8mb4',
        'port'      => 3306,        // Optional
        'prefix'    => 'app_',      // Optional table prefix
    ];
});
```

### SQLite Configuration

```php
Config::initialize(function() {
    return [
        'driver'   => 'sqlite',
        'database' => '/path/to/database.sqlite',
    ];
});
```

## Creating Models

### Basic Model

```php
use Icebox\Model;

class User extends Model
{
    // Table name will be inferred as 'users'
    // Primary key will default to 'id'
}
```

### Custom Table Name

```php
class Post extends Model
{
    protected static $table = 'blog_posts';
    protected static $primaryKey = 'post_id';
}
```

### Model with Custom Attributes

```php
class Product extends Model
{
    protected static $table = 'products';
    
    // Custom attribute casting
    public function getPriceAttribute($value)
    {
        return number_format($value, 2);
    }
    
    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = (float) $value;
    }
}
```

## Basic CRUD Operations

### Create Records

```php
// Create new instance
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();

// Create with attributes array
$user = new User(['name' => 'John', 'email' => 'john@example.com']);
$user->save();

// Create and save in one step (if constructor accepts save parameter)
$user = new User(['name' => 'John'], false);
$user->save();
```

### Read Records

```php
// Find by primary key
$user = User::find(1);

// Find or fail (returns null if not found)
$user = User::find(1);
if (!$user) {
    throw new Exception('User not found');
}

// Get all records
$users = User::where()->get();

// Get first record
$user = User::where()->first();
```

### Update Records

```php
// Update single attribute
$user = User::find(1);
$user->name = 'Jane Doe';
$user->save();

// Update multiple attributes
$user->updateAttributes([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

// Mass update
User::where('active', 0)->update(['status' => 'inactive']);
```

### Delete Records

```php
// Delete single record
$user = User::find(1);
$user->delete();

// Delete by condition
User::where('active', 0)->delete();

// Soft delete pattern
$user = User::find(1);
$user->deleted_at = date('Y-m-d H:i:s');
$user->save();
```

## Query Builder

### Basic WHERE Clauses

```php
// Simple equality
User::where('name', 'John')->get();
User::where(['name' => 'John', 'active' => 1])->get();

// Comparison operators
User::where('age', '>', 18)->get();
User::where('age', '>=', 21)->get();
User::where('age', '<', 65)->get();
User::where('age', '<=', 30)->get();
User::where('age', '!=', 0)->get();

// LIKE operator
User::where('name', 'LIKE', 'John%')->get();
User::where('name', 'LIKE', '%Doe')->get();
User::where('name', 'LIKE', '%ohn%')->get();
```

### NULL Checks

```php
// Rails-style NULL checks
User::where('approved_at', null)->get();           // IS NULL
User::where('approved_at', '!=', null)->get();     // IS NOT NULL

// Helper methods
User::whereNull('deleted_at')->get();
User::whereNotNull('approved_at')->get();
```

### IN Clauses

```php
// Array syntax
User::where('id', [1, 2, 3])->get();               // IN (1,2,3)

// Explicit IN operator
User::where('id', 'IN', [1, 2, 3])->get();

// Helper methods
User::whereIn('category_id', [1, 2, 3])->get();
User::whereNotIn('status', ['banned', 'inactive'])->get();
```

### BETWEEN Clauses

```php
// BETWEEN operator
User::whereBetween('age', [18, 65])->get();
User::whereBetween('created_at', ['2023-01-01', '2023-12-31'])->get();

// NOT BETWEEN
User::where('age', 'NOT BETWEEN', [18, 65])->get();
```

### OR Conditions

```php
// Array syntax
User::where(['or' => [
    'status' => 'active',
    'status' => 'pending'
]])->get();

// Method chaining
User::where('status', 'active')
    ->orWhere('status', 'pending')
    ->get();

// Complex OR conditions
User::where('age', '>', 65)
    ->orWhere(function($query) {
        $query->where('active', 0)
              ->where('created_at', '<', '2023-01-01');
    })
    ->get();
```

### Raw SQL Expressions

```php
// Raw WHERE clause
User::where('price > cost * ?', [1.5])->get();
User::where('YEAR(created_at) = ?', [2023])->get();

// Raw SELECT
User::where()->selectRaw('COUNT(*) as count, status')->get();

// Raw ORDER BY
User::where()->orderByRaw('RAND()')->get();
```

## Advanced Queries

### Sorting and Limiting

```php
// ORDER BY
User::where('active', 1)->orderBy('name', 'ASC')->get();
User::where('active', 1)->orderBy('created_at', 'DESC')->get();

// Multiple ORDER BY
User::where('active', 1)
    ->orderBy('last_name', 'ASC')
    ->orderBy('first_name', 'ASC')
    ->get();

// LIMIT and OFFSET
User::where('active', 1)->limit(10)->get();
User::where('active', 1)->limit(10)->offset(20)->get();

// Pagination style
$page = 2;
$perPage = 15;
User::where('active', 1)
    ->limit($perPage)
    ->offset(($page - 1) * $perPage)
    ->get();
```

### Aggregates

```php
// Count
$count = User::where('active', 1)->count();
$count = User::where()->count();

// Check existence
$exists = User::where('email', 'john@example.com')->exists();

// Get first/last
$first = User::where('active', 1)->first();
$last = User::where('active', 1)->orderBy('id', 'DESC')->first();

// Min, Max, Sum, Avg (via raw queries)
$maxAge = User::where()->selectRaw('MAX(age) as max_age')->first()->max_age;
$avgAge = User::where()->selectRaw('AVG(age) as avg_age')->first()->avg_age;
```

### Joins

```php
// Basic join
$users = User::where()
    ->select('users.*', 'profiles.bio')
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->get();

// Left join
$users = User::where()
    ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
    ->get();

// Multiple joins
$orders = Order::where()
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->join('products', 'orders.product_id', '=', 'products.id')
    ->get();
```

### Scopes

```php
class User extends Model
{
    // Query scope
    public static function scopeActive($query)
    {
        return $query->where('active', 1);
    }
    
    public static function scopeOlderThan($query, $age)
    {
        return $query->where('age', '>', $age);
    }
}

// Using scopes
$activeUsers = User::active()->get();
$seniors = User::olderThan(65)->get();
$activeSeniors = User::active()->olderThan(65)->get();
```

### Eager Loading

```php
// Basic eager loading
$users = User::with('posts')->get();

// Multiple relations
$users = User::with(['posts', 'comments'])->get();

// Nested eager loading
$users = User::with('posts.comments')->get();

// Constrained eager loading
$users = User::with(['posts' => function($query) {
    $query->where('published', 1);
}])->get();
```

## Transactions

### Basic Transactions

```php
use Icebox\ActiveRecord\Connection;

Connection::beginTransaction();

try {
    $user1 = new User(['name' => 'John']);
    $user1->save();
    
    $user2 = new User(['name' => 'Jane']);
    $user2->save();
    
    Connection::commit();
    echo "Transaction successful!";
} catch (Exception $e) {
    Connection::rollback();
    echo "Transaction failed: " . $e->getMessage();
}
```

### Transaction Helpers

```php
// Automatic transaction
Connection::transaction(function() {
    $user1 = new User(['name' => 'John']);
    $user1->save();
    
    $user2 = new User(['name' => 'Jane']);
    $user2->save();
    
    return true;
});

// Nested transactions (savepoints)
Connection::beginTransaction();
try {
    // Outer transaction
    
    Connection::beginTransaction(); // Creates savepoint
    try {
        // Inner transaction
        Connection::commit(); // Releases savepoint
    } catch (Exception $e) {
        Connection::rollback(); // Rolls back to savepoint
        throw $e;
    }
    
    Connection::commit();
} catch (Exception $e) {
    Connection::rollback();
}
```

## API Reference

### Model Methods

| Method | Description |
|--------|-------------|
| `find($id)` | Find by primary key |
| `where($column, $operator, $value)` | Query builder entry point |
| `save()` | Create or update record |
| `updateAttributes($attributes)` | Update multiple attributes |
| `delete()` | Delete record |
| `toArray()` | Convert to array |
| `isDirty()` | Check if model has changes |
| `getDirty()` | Get changed attributes |
| `getTable()` | Get table name |
| `getPrimaryKey()` | Get primary key name |

### Query Builder Methods

| Method | Description |
|--------|-------------|
| `where($column, $operator, $value)` | Add WHERE condition |
| `orWhere($column, $operator, $value)` | Add OR WHERE condition |
| `whereNull($column)` | WHERE IS NULL |
| `whereNotNull($column)` | WHERE IS NOT NULL |
| `whereIn($column, $values)` | WHERE IN |
| `whereNotIn($column, $values)` | WHERE NOT IN |
| `whereBetween($column, $values)` | WHERE BETWEEN |
| `orderBy($column, $direction)` | ORDER BY |
| `limit($limit)` | LIMIT |
| `offset($offset)` | OFFSET |
| `get()` | Execute and get results |
| `first()` | Get first result |
| `count()` | Get count |
| `exists()` | Check if any records exist |
| `select($columns)` | SELECT specific columns |
| `join($table, $first, $operator, $second)` | JOIN clause |
| `leftJoin($table, $first, $operator, $second)` | LEFT JOIN clause |
| `toSql()` | Get the interpolated SQL string for debugging |

### Connection Methods

| Method | Description |
|--------|-------------|
| `select($table, $conditions, $options, $params)` | SELECT query |
| `selectOne($table, $conditions)` | SELECT single row |
| `insert($table, $data)` | INSERT query |
| `update($table, $data, $conditions)` | UPDATE query |
| `delete($table, $conditions)` | DELETE query |
| `query($sql, $params)` | Raw SQL query |
| `beginTransaction()` | Begin transaction |
| `commit()` | Commit transaction |
| `rollback()` | Rollback transaction |
| `transaction($callback)` | Automatic transaction |

## Best Practices

### 1. Use Query Scopes for Reusable Logic

```php
class User extends Model
{
    public static function scopeActive($query)
    {
        return $query->where('active', 1);
    }
    
    public static function scopeRecent($query)
    {
        return $query->where('created_at', '>', now()->subDays(30));
    }
}

// Clean and readable
$users = User::active()->recent()->get();
```

### 2. Always Use Transactions for Multiple Operations

```php
// Good
Connection::transaction(function() {
    $order = new Order($orderData);
    $order->save();
    
    foreach ($items as $item) {
        $orderItem = new OrderItem($item);
        $orderItem->order_id = $order->id;
        $orderItem->save();
    }
});

// Bad
$order = new Order($orderData);
$order->save(); // What if this fails after creating order?

foreach ($items as $item) {
    $orderItem = new OrderItem($item);
    $orderItem->order_id = $order->id;
    $orderItem->save();
}
```

### 3. Use Eager Loading to Avoid N+1 Problem

```php
// Good (1 query + 1 query)
$users = User::with('posts')->limit(10)->get();

// Bad (1 query + 10 queries)
$users = User::limit(10)->get();
foreach ($users as $user) {
    $posts = $user->posts; // Executes query for each user
}
```

### 4. Validate Data Before Saving

```php
class User extends Model
{
    public function save()
    {
        if (!$this->validate()) {
            throw new ValidationException($this->errors());
        }
        
        return parent::save();
    }
    
    private function validate()
    {
        // Add validation logic
        return !empty($this->name) && filter_var($this->email, FILTER_VALIDATE_EMAIL);
    }
}
```

## Troubleshooting

### Common Issues

1. **"Database connection not initialized"**
   - Make sure `Config::initialize()` is called before any database operations
   - Check database credentials

2. **"Table not found"**
   - Check table name inference or explicitly set `protected static $table`
   - Verify database migrations have run

3. **"PDOException: SQLSTATE[...]"**
   - Check SQL syntax in raw queries
   - Verify parameter binding in raw SQL

4. **"Method not found"**
   - Ensure you're calling methods on QueryBuilder instance, not Model class
   - Check method names (camelCase vs snake_case)

### Debugging Queries

```php
// Enable query logging
Config::set('log_queries', true);

// Manually log queries
$sql = "SELECT * FROM users WHERE active = ?";
$params = [1];
Connection::query($sql, $params);

// Check last query
$lastQuery = Connection::getLastQuery();
echo "SQL: " . $lastQuery['sql'] . "\n";
echo "Params: " . print_r($lastQuery['params'], true) . "\n";

// Use toSql() to get interpolated SQL string for debugging
$query = User::where('active', 1)->where('age', '>', 18);
echo $query->toSql(); // Output: SELECT * FROM users WHERE active = 1 AND age > 18

// Works with complex conditions
$query = User::whereIn('id', [1, 2, 3])->whereNull('deleted_at');
echo $query->toSql(); // Output: SELECT * FROM users WHERE id IN (1,2,3) AND deleted_at IS NULL
```

## Examples Repository

For more examples, check the `examples/` directory:

- `examples/basic_crud.php` - Basic CRUD operations
- `examples/query_builder.php` - Query builder examples
- `examples/transactions.php` - Transaction examples
- `examples/advanced_queries.php` - Advanced query patterns

---

*Last updated: December 2023*
*Icebox ActiveRecord v1.0*
