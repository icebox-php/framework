## About Icebox

This repository contains the core code of the Icebox framework.

## ActiveRecord Implementation

Icebox includes a modern PDO-based ActiveRecord implementation with Rails-style query builder.

### Database Configuration

```php
use Icebox\ActiveRecord\Config as ArConfig;

$database_url = (string) env('DATABASE_URL');

if($database_url == null) {
    throw new \LengthException('DATABASE_URL must have a value. example: mysql://username:password@host:port/database_name');
}

// parse the URL
$parsed = ArConfig::parseDatabaseUrl($database_url);

// Extract components
$dsn = $parsed['dsn'];
$username = $parsed['username'];
$password = $parsed['password'];
$options = $parsed['options'];

// User can customize options if needed
$options[PDO::ATTR_PERSISTENT] = true;

// Create PDO connection
$connection = new PDO($dsn, $username, $password, $options);

// Initialize ActiveRecord
ArConfig::initialize($connection);
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

# Interactive Console

Icebox includes an interactive PHP console (REPL) powered by PsySH, similar to Rails console:

```bash
php icebox console
php icebox c  # Short alias
```

This starts an interactive PHP shell. When used within an Icebox application, the framework and application environment will be automatically loaded.

## Features

- **Tab completion** for class names, methods, and variables
- **Syntax highlighting** for better readability
- **Documentation lookup** with `doc function_name`
- **Source code viewing** with `show function_name`
- **Command history** with up/down arrows
- **Error handling** with pretty output

## Example Usage

```bash
$ php icebox console
Loading development environment (PHP 8.5.0)
Type 'exit' to quit, 'help' for help.

Psy Shell v0.12.17 (PHP 8.5.0 — cli) by Justin Hileman
>>> echo App::root_url();
http://localhost/myapp
>>> $user = User::find(1);
>>> echo $user->name;
John Doe
>>> exit
```

## Available Commands in PsySH

- `exit` or `quit` - Exit the console
- `help` - Show help
- `doc function_name` - Show documentation for a function
- `show function_name` - Show source code for a function
- `ls` - List variables in current scope
- `clear` - Clear the screen

## Note for Application Usage

When using the console within an Icebox application, the application bootstrap will handle loading the framework and environment. The console command simply starts PsySH with the development environment.

# Logging

Icebox includes a simple, Rails-like logger built on Monolog with PSR-3 compatibility. The logger supports multiple simultaneous handlers and works for both terminal and web applications.

## Basic Usage

```php
use Icebox\Log;

// Add handlers (no default handlers - you must add at least one)
Log::addFileHandler('storage/logs/app.log');
Log::addStdoutHandler(); // For CLI output

// Log messages
Log::info('User logged in');
Log::error('Database connection failed', ['db' => 'primary']);
Log::debug('Processing request', ['method' => 'GET']);

// All PSR-3 levels are supported:
// emergency, alert, critical, error, warning, notice, info, debug
```

## Handler Types

### 1. File Handler (with rotation)
```php
Log::addFileHandler(
    'storage/logs/app.log',  // path
    'debug',                 // level (optional, default: debug)
    7                        // max files to keep (optional, default: 7)
);
```

### 2. Syslog Handler
```php
Log::addSyslogHandler(
    'myapp',      // ident (optional, default: icebox)
    LOG_USER,     // facility (optional, default: LOG_USER)
    'warning'     // level (optional, default: debug)
);
```

### 3. Stdout Handler
```php
Log::addStdoutHandler(
    'info'  // level (optional, default: debug)
);
// Colorful output in CLI, plain output in web
```

### 4. Closure/ Broadcast Handler
```php
Log::addClosureHandler(function($log) {
    // $log object contains:
    // - level: 'debug', 'info', 'warning', 'error', etc.
    // - message: The log message
    // - context: Additional context data
    // - channel: Logger channel name
    // - datetime: When the log occurred
    
    // Send to monitoring services (Sentry, Airbrake, etc.)
    if ($log->level === 'error') {
        \Sentry\captureMessage($log->message, $log->context);
    }
    
    // Or send to Slack, email, etc.
}, 'error');  // level (optional, default: debug)
```

## Multiple Handlers Simultaneously

```php
// Development setup
Log::addFileHandler('storage/logs/development.log');
Log::addStdoutHandler();

// Production setup
Log::addFileHandler('storage/logs/production.log', 'info');
Log::addSyslogHandler('myapp-prod', LOG_USER, 'error');
Log::addClosureHandler(function($log) {
    // Send critical errors to monitoring
    if (in_array($log->level, ['error', 'critical', 'alert', 'emergency'])) {
        sendToMonitoringService($log);
    }
}, 'error');
```

## Log Levels

Each handler can have its own log level:
- `debug`: Detailed debug information
- `info`: Interesting events
- `notice`: Normal but significant events  
- `warning`: Exceptional occurrences that are not errors
- `error`: Runtime errors
- `critical`: Critical conditions
- `alert`: Action must be taken immediately
- `emergency`: System is unusable

## Testing

```php
use Icebox\Log;

// Clear handlers for testing
Log::clearHandlers();

// Add test handlers
Log::addClosureHandler(function($log) {
    echo "Test log: {$log->level} - {$log->message}\n";
});

// Check handler count
echo Log::handlerCount(); // 1
```

## Notes

- **No default handlers**: Logs are silently ignored until handlers are added
- **Multiple handlers**: All added handlers receive log messages simultaneously
- **Web/CLI compatibility**: Works with Apache, Nginx, PHP built-in server, and CLI
- **PSR-3 compliant**: Interoperable with other PHP logging libraries
- **Monolog under the hood**: Uses the industry-standard Monolog library

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
