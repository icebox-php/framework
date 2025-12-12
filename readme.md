## About Icebox

This repository contains the core code of the Icebox framework.

## ActiveRecord Implementation

Icebox includes a modern PDO-based ActiveRecord implementation with Rails-style query builder.

### Database Configuration

```php
use Icebox\ActiveRecord\Config;

Config::initialize(function() {
    return [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'myapp',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ];
});
```

### Creating Models

```php
use Icebox\Model;

class User extends Model
{
    // Optional: specify custom table name
    protected static $table = 'users';
    
    // Optional: specify custom primary key
    protected static $primaryKey = 'id';
}

class Post extends Model
{
    // Table name will be inferred as 'posts'
    // Primary key will default to 'id'
}
```

### Basic CRUD Operations

```php
// Create
$user = new User(['name' => 'John', 'email' => 'john@example.com']);
$user->save();

// Read by primary key
$user = User::find(1);

// Update
$user = User::find(1);
$user->name = 'Jane';
$user->save();

// Or update multiple attributes
$user->updateAttributes(['name' => 'Jane', 'email' => 'jane@example.com']);

// Delete
$user->delete();
```

### Rails-style Query Builder

Icebox uses a Rails-inspired query builder with a single `where()` method for all queries.

#### Basic Queries

```php
// Equality (multiple syntaxes)
User::where('name', 'John')->get();
User::where(['name' => 'John', 'active' => 1])->get();

// Comparison operators
User::where('age', '>', 18)->get();
User::where('age', '>=', 21)->get();
User::where('name', 'LIKE', 'John%')->get();

// NULL checks (Rails-style)
User::where('approved_at', null)->get();           // IS NULL
User::where('approved_at', '!=', null)->get();     // IS NOT NULL
User::whereNull('deleted_at')->get();
User::whereNotNull('approved_at')->get();

// IN clauses
User::where('id', [1, 2, 3])->get();               // IN (1,2,3)
User::whereIn('category_id', [1, 2, 3])->get();
User::whereNotIn('status', ['banned', 'inactive'])->get();

// BETWEEN
User::whereBetween('age', [18, 65])->get();

// OR conditions
User::where(['or' => ['status' => 'active', 'status' => 'pending']])->get();
User::where('status', 'active')->orWhere('status', 'pending')->get();

// Raw SQL
User::where('price > cost * ?', [1.5])->get();

// Get all records
User::where()->get();
User::where([])->get();
```

#### Method Chaining

```php
User::where('active', 1)
    ->where('age', '>', 18)
    ->orderBy('name', 'DESC')
    ->limit(10)
    ->offset(5)
    ->get();
```

#### Aggregates and Checks

```php
// Count records
$count = User::where('active', 1)->count();

// Check if exists
$exists = User::where('email', 'john@example.com')->exists();

// Get first result
$user = User::where('active', 1)->first();
```

### Model Methods Available

- `find($id)` - Find by primary key
- `where($column, $operator = null, $value = null)` - Query builder entry point
- `save()` - Create or update record
- `updateAttributes($attributes)` - Update multiple attributes
- `delete()` - Delete record
- `toArray()` - Convert to array
- `isDirty()` - Check if model has changes
- `getDirty()` - Get changed attributes

### Query Builder Methods

- `where($column, $operator = null, $value = null)` - Add WHERE condition
- `orWhere($column, $operator = null, $value = null)` - Add OR WHERE condition
- `whereNull($column)` - WHERE IS NULL
- `whereNotNull($column)` - WHERE IS NOT NULL
- `whereIn($column, array $values)` - WHERE IN
- `whereNotIn($column, array $values)` - WHERE NOT IN
- `whereBetween($column, array $values)` - WHERE BETWEEN
- `orderBy($column, $direction = 'ASC')` - ORDER BY
- `limit($limit)` - LIMIT
- `offset($offset)` - OFFSET
- `get()` - Execute and get results
- `first()` - Get first result
- `count()` - Get count of records
- `exists()` - Check if any records exist

### Transactions

```php
\Icebox\ActiveRecord\Connection::beginTransaction();
try {
    $user1->save();
    $user2->save();
    \Icebox\ActiveRecord\Connection::commit();
} catch (Exception $e) {
    \Icebox\ActiveRecord\Connection::rollback();
}
```

# How to run testsuite

You can run tests using the Icebox CLI:

```bash
php icebox test
```

This will run all tests in the `tests` folder.

To run a specific test file or folder:

```bash
php icebox test tests/create_user_test.php
php icebox test tests/Feature/
```

Any additional arguments are passed directly to PHPUnit, so you can use PHPUnit options:

```bash
php icebox test --filter testCreateUser
php icebox test tests/UserTest.php --filter testCreateUser # more specific
php icebox test --filter testCreateUser
php icebox test --coverage-html coverage
```

For more options, see `php icebox test --help`.

# Crud generator

php icebox generate crud box

## Test all column type

`php icebox g crud post title:string picture:string content:text published:boolean publish_date:date create_time:datetime decimal_col:decimal float_col:float int_col:integer time_col:time`

```
-- db schema
CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci,
  `published` tinyint(1) DEFAULT NULL,
  `publish_date` date DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `decimal_col` decimal(10,0) DEFAULT NULL,
  `float_col` decimal(10,0) DEFAULT NULL,
  `int_col` int(11) DEFAULT NULL,
  `time_col` time DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

## Supported Column Types
```
'boolean' => array( 'html_tag' => 'checkbox', 'type' => ''),
'date' => array( 'html_tag' => 'input', 'type' => 'date'),
'datetime' => array( 'html_tag' => 'input', 'type' => 'datetime-local'),
'decimal' => array( 'html_tag' => 'input', 'type' => 'number'),
<br>
'float' => array( 'html_tag' => 'input', 'type' => 'number'),
'integer' => array( 'html_tag' => 'input', 'type' => 'number'),
'string' => array( 'html_tag' => 'input', 'type' => 'text'),
'text' => array( 'html_tag' => 'textarea', 'type' => ''),
<br>
'time' => array( 'html_tag' => 'input', 'type' => 'time'),
```
