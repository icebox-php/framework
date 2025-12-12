## About Icebox

This repository contains the core code of the Icebox framework.

## ActiveRecord Implementation

Icebox includes a modern PDO-based ActiveRecord implementation with full CRUD operations.

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

// Read
$user = User::find(1);
$users = User::all();
$activeUsers = User::all(['active' => 1]);

// Update
$user = User::find(1);
$user->name = 'Jane';
$user->save();

// Or update multiple attributes
$user->updateAttributes(['name' => 'Jane', 'email' => 'jane@example.com']);

// Delete
$user->delete();
```

### Advanced Queries

```php
// Find by conditions (using Connection directly)
$userData = \Icebox\ActiveRecord\Connection::selectOne('users', ['email' => 'john@example.com']);
if ($userData) {
    $user = new User($userData, true);
}

// Or find all matching conditions
$activeUsers = User::all(['active' => 1]);

// Get dirty attributes (changed but not saved)
if ($user->isDirty()) {
    $changes = $user->getDirty();
}

// Transactions
\Icebox\ActiveRecord\Connection::beginTransaction();
try {
    $user1->save();
    $user2->save();
    \Icebox\ActiveRecord\Connection::commit();
} catch (Exception $e) {
    \Icebox\ActiveRecord\Connection::rollback();
}
```

### Model Methods Available

- `find($id)` - Find by primary key
- `all($conditions = [])` - Find all matching conditions
- `save()` - Create or update record
- `updateAttributes($attributes)` - Update multiple attributes
- `delete()` - Delete record
- `toArray()` - Convert to array
- `isDirty()` - Check if model has changes
- `getDirty()` - Get changed attributes

### Connection Methods

- `Connection::select($table, $conditions, $options)`
- `Connection::selectOne($table, $conditions)`
- `Connection::insert($table, $data)`
- `Connection::update($table, $data, $conditions)`
- `Connection::delete($table, $conditions)`
- `Connection::query($sql, $params)` - Raw SQL queries

# How to run testsuite

$ vendor/bin/phpunit ./tests/

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
