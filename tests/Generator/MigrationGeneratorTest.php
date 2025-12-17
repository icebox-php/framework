<?php

namespace Icebox\Tests\Generator;

use Icebox\Tests\TestCase;
use Icebox\Tests\TestHelper;
use Icebox\Generator\MigrationGenerator;
use Icebox\ActiveRecord\MigrationRunner;

/**
 * Test migration generator functionality
 */
class MigrationGeneratorTest extends TestCase
{
    private static $migrationsDir;
    private static $oldMigrationsDir;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::prepareTestDbDir();
    }

    private static function delete_test_migration_files($migrations_dir) {
        $files = glob($migrations_dir . '/*.php');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private static function prepareTestDbDir()
    {
        $testRootDir = dirname(__DIR__); // __DIR__ . '/../../tests';
        self::$migrationsDir = $testRootDir . '/db/migrations';
        self::$oldMigrationsDir = $testRootDir . '/db/old-migrations';
        
        // Ensure /tests/db/migrations directory exists
        if (!is_dir(self::$migrationsDir)) {
            mkdir(self::$migrationsDir, 0755, true);
        }
        // delete test migration files
        self::delete_test_migration_files(self::$migrationsDir);

        // Ensure /tests/db/old-migrations directory exists
        if (!is_dir(self::$oldMigrationsDir)) {
            mkdir(self::$oldMigrationsDir, 0755, true);
        }
        // delete test migration files
        self::delete_test_migration_files(self::$oldMigrationsDir);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize test database
        TestHelper::initializeTestDatabase();

        // Note: prepareTestDbDir() moved to setUpBeforeClass()

        // Clear any existing test migration files
        $this->cleanupMigrationFiles();
    }

    protected function tearDown(): void
    {
        // Clean up test migration files
        $this->cleanupMigrationFiles();
        
        parent::tearDown();
    }

    /**
     * Test table name extraction from migration names
     */
    public function testTableNameExtraction(): void
    {
        // Test cases: migration name => expected table name
        $testCases = [
            'create_posts_table' => 'posts',
            'create_users_table' => 'users',
            'add_user_id_to_posts' => 'posts',
            'remove_title_from_posts' => 'posts',
            'create_join_table_for_posts_and_tags' => 'posts_tags',
            'create_categories' => 'categories',
            'add_status_to_users' => 'users',
        ];

        foreach ($testCases as $migrationName => $expectedTableName) {
            $actual = MigrationGenerator::extractTableName($migrationName);
            $this->assertEquals($expectedTableName, $actual, 
                "Failed for migration name: $migrationName");
        }
    }

    /**
     * Test column attribute parsing
     */
    public function testColumnAttributeParsing(): void
    {
        $testCases = [
            [
                'input' => ['title:string', 'content:text'],
                'expected' => ['title' => 'string', 'content' => 'text']
            ],
            [
                'input' => ['user_id:integer', 'active:boolean'],
                'expected' => ['user_id' => 'integer', 'active' => 'boolean']
            ],
            [
                'input' => ['amount:decimal', 'created_at:datetime'],
                'expected' => ['amount' => 'decimal', 'created_at' => 'datetime']
            ],
            [
                'input' => ['name:string'],
                'expected' => ['name' => 'string']
            ],
            [
                'input' => [],
                'expected' => []
            ],
        ];

        foreach ($testCases as $testCase) {
            $actual = MigrationGenerator::parseColumnAttributes($testCase['input']);
            $this->assertEquals($testCase['expected'], $actual);
        }
    }

    /**
     * Test column type to SQL mapping
     */
    public function testColumnTypeToSqlMapping(): void
    {
        $testCases = [
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
            'unknown' => 'VARCHAR(255)', // Default fallback
        ];

        foreach ($testCases as $phpType => $expectedSqlType) {
            $actual = MigrationGenerator::mapColumnTypeToSql($phpType);
            $this->assertEquals($expectedSqlType, $actual, 
                "Failed for PHP type: $phpType");
        }
    }




    /**
     * Test migration file creation (SQL-based)
     */
    public function testSqlMigrationFileCreation(): void
    {
        $migrationName = 'test_create_table';
        $upSql = 'CREATE TABLE test (id INT PRIMARY KEY)';
        $downSql = 'DROP TABLE test';

        // Create migration file
        $filePath = MigrationGenerator::createMigrationFile($migrationName, $upSql, $downSql, self::$migrationsDir);

        // Check that file was created
        $this->assertFileExists($filePath, 'Migration file should be created');

        // Check file content
        $content = file_get_contents($filePath);
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('use Icebox\ActiveRecord\Connection;', $content);
        $this->assertStringContainsString($upSql, $content);
        $this->assertStringContainsString($downSql, $content);

        // Check class name format
        $this->assertStringContainsString('class Migration', $content);

        // Check that class can be loaded and instantiated
        require_once $filePath;

        // Extract class name from file
        preg_match('/class\s+(\w+)/', $content, $matches);
        $className = $matches[1];

        $this->assertTrue(class_exists($className), "Class $className should exist");

        $migration = new $className();
        $this->assertTrue(method_exists($migration, 'up'), 'Migration should have up() method');
        $this->assertTrue(method_exists($migration, 'down'), 'Migration should have down() method');
    }

    // /**
    //  * Test generateColsForDSL method
    //  */
    // public function testGenerateColsForDSL(): void
    // {
    //     $migrationName = 'create_products_table';
    //     $columns = ['name:string', 'price:decimal', 'active:boolean'];

    //     $result = MigrationGenerator::generateColsForDSL($migrationName, $columns);

    //     // Should return the same columns (normalized/validated)
    //     $this->assertEquals($columns, $result);
    // }

    // /**
    //  * Test generateColsForDSL with invalid column type
    //  */
    // public function testGenerateColsForDSLInvalidType(): void
    // {
    //     $migrationName = 'create_products_table';
    //     $columns = ['name:invalid_type'];

    //     $this->expectException(\InvalidArgumentException::class);
    //     $this->expectExceptionMessage("Invalid column type 'invalid_type'");

    //     MigrationGenerator::generateColsForDSL($migrationName, $columns);
    // }

    /**
     * Test DSL migration file creation (using smart parameter detection)
     */
    public function testDslMigrationFileCreation(): void
    {
        $migrationName = 'create_items_table';
        $columns = ['name:string', 'price:decimal'];

        // Create DSL migration file using unified API (smart detection)
        $filePath = MigrationGenerator::createMigrationFile($migrationName, $columns, self::$migrationsDir);

        // Check that file was created
        $this->assertFileExists($filePath, 'DSL Migration file should be created');

        // Check file content
        $content = file_get_contents($filePath);
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('use Icebox\ActiveRecord\Migration\BaseMigration;', $content);
        $this->assertStringContainsString('extends BaseMigration', $content);
        $this->assertStringContainsString('$this->createTable(\'items\', function($t) {', $content);
        $this->assertStringContainsString('$t->string(\'name\');', $content);
        $this->assertStringContainsString('$t->decimal(\'price\');', $content);
        $this->assertStringContainsString('$this->dropTable(\'items\');', $content);

        // Check class name format
        $this->assertStringContainsString('class Migration', $content);

        // Check that class can be loaded and instantiated
        require_once $filePath;

        // Extract class name from file
        preg_match('/class\s+(\w+)/', $content, $matches);
        $className = $matches[1];

        $this->assertTrue(class_exists($className), "Class $className should exist");

        $migration = new $className();
        $this->assertTrue(method_exists($migration, 'up'), 'Migration should have up() method');
        $this->assertTrue(method_exists($migration, 'down'), 'Migration should have down() method');
        $this->assertInstanceOf('Icebox\ActiveRecord\Migration\BaseMigration', $migration, 'Migration should extend base Migration class');
    }


    /**
     * Test HTML input type mapping
     */
    public function testHtmlInputTypeMapping(): void
    {
        $testCases = [
            'string' => ['html_tag' => 'input', 'type' => 'text'],
            'text' => ['html_tag' => 'textarea', 'type' => ''],
            'integer' => ['html_tag' => 'input', 'type' => 'number'],
            'boolean' => ['html_tag' => 'checkbox', 'type' => ''],
            'date' => ['html_tag' => 'input', 'type' => 'date'],
            'datetime' => ['html_tag' => 'input', 'type' => 'datetime-local'],
            'decimal' => ['html_tag' => 'input', 'type' => 'number'],
            'float' => ['html_tag' => 'input', 'type' => 'number'],
            'time' => ['html_tag' => 'input', 'type' => 'time'],
            'select' => ['html_tag' => 'select', 'type' => ''],
            'unknown' => ['html_tag' => 'input', 'type' => 'text'], // Default fallback
        ];

        foreach ($testCases as $columnType => $expected) {
            $actual = MigrationGenerator::getHtmlInputType($columnType);
            $this->assertEquals($expected, $actual, "Failed for column type: $columnType");
        }
    }

    /**
     * Test migration class name generation
     */
    public function testMigrationClassNameGeneration(): void
    {
        $timestamp = '20251216000000';
        $migrationName = 'create_posts_table';
        
        $className = MigrationGenerator::generateClassName($migrationName, $timestamp);
        
        $this->assertStringStartsWith('Migration20251216000000_', $className);
        $this->assertStringContainsString('Create_Posts_Table', $className);
    }

    /**
     * Test migration file content generation
     */
    public function testMigrationFileContentGeneration(): void
    {
        $className = 'Migration20251216000000_Create_Posts_Table';
        $upSql = 'CREATE TABLE posts (id INT PRIMARY KEY)';
        $downSql = 'DROP TABLE posts';
        
        $content = MigrationGenerator::generateFileContent($className, $upSql, $downSql);
        
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('use Icebox\ActiveRecord\Connection;', $content);
        $this->assertStringContainsString("class $className", $content);
        $this->assertStringContainsString($upSql, $content);
        $this->assertStringContainsString($downSql, $content);
        $this->assertStringContainsString('public function up()', $content);
        $this->assertStringContainsString('public function down()', $content);
    }

    /**
     * Test migration runner - create schema_migrations table
     */
    public function testMigrationRunnerSchemaTable(): void
    {
        $runner = new MigrationRunner(self::$migrationsDir);
        $result = $runner->createSchemaMigrationsTable();
        
        $this->assertTrue($result, 'Should create schema_migrations table');
        
        // Verify table exists by checking status
        $status = $runner->status();
        $this->assertIsArray($status);
        $this->assertArrayHasKey('executed', $status);
        $this->assertArrayHasKey('pending', $status);
    }

    /**
     * Test migration runner - full lifecycle
     */
    public function testMigrationRunnerLifecycle(): void
    {
        // Create a migration file
        $migrationName = 'test_create_users_table';
        $upSql = 'CREATE TABLE test_users (id INTEGER PRIMARY KEY, name VARCHAR(255))';
        $downSql = 'DROP TABLE test_users';
        
        $filePath = MigrationGenerator::createMigrationFile($migrationName, $upSql, $downSql, self::$migrationsDir);
        $this->assertFileExists($filePath);

        // Initialize migration runner
        $runner = new MigrationRunner(self::$migrationsDir);
        
        // Check status before migration
        $statusBefore = $runner->status();
        $this->assertEquals(1, $statusBefore['total_pending'], 'Should have 1 pending migration');
        
        // Run migration
        $migrated = $runner->migrate();
        $this->assertCount(1, $migrated, 'Should migrate 1 file');
        
        // Check status after migration
        $statusAfter = $runner->status();
        $this->assertEquals(1, $statusAfter['total_executed'], 'Should have 1 executed migration');
        $this->assertEquals(0, $statusAfter['total_pending'], 'Should have 0 pending migrations');
        
        // Rollback migration
        $rolledBack = $runner->rollback();
        $this->assertCount(1, $rolledBack, 'Should rollback 1 migration');
        
        // Check status after rollback
        $statusFinal = $runner->status();
        $this->assertEquals(0, $statusFinal['total_executed'], 'Should have 0 executed migrations after rollback');
        $this->assertEquals(1, $statusFinal['total_pending'], 'Should have 1 pending migration after rollback');
    }

    /**
     * Test migration runner - rollback with steps
     */
    public function testMigrationRunnerRollbackWithSteps(): void
    {
        // Create multiple migration files
        $migrations = [
            ['name' => 'test_create_table1', 'up' => 'CREATE TABLE table1 (id INTEGER PRIMARY KEY)', 'down' => 'DROP TABLE table1'],
            ['name' => 'test_create_table2', 'up' => 'CREATE TABLE table2 (id INTEGER PRIMARY KEY)', 'down' => 'DROP TABLE table2'],
            ['name' => 'test_create_table3', 'up' => 'CREATE TABLE table3 (id INTEGER PRIMARY KEY)', 'down' => 'DROP TABLE table3'],
        ];
        
        foreach ($migrations as $migration) {
            MigrationGenerator::createMigrationFile($migration['name'], $migration['up'], $migration['down'], self::$migrationsDir);
        }

        $runner = new MigrationRunner(self::$migrationsDir);
        
        // Run all migrations
        $migrated = $runner->migrate();
        $this->assertCount(3, $migrated, 'Should migrate 3 files');
        
        // Rollback 2 migrations
        $rolledBack = $runner->rollback(2);
        $this->assertCount(2, $rolledBack, 'Should rollback 2 migrations');
        
        // Check status
        $status = $runner->status();
        $this->assertEquals(1, $status['total_executed'], 'Should have 1 executed migration after rolling back 2');
        $this->assertEquals(2, $status['total_pending'], 'Should have 2 pending migrations after rolling back 2');
    }

    /**
     * Test migration runner - reset all migrations
     */
    public function testMigrationRunnerReset(): void
    {
        // Create multiple migration files
        $migrations = [
            ['name' => 'test_reset_table1', 'up' => 'CREATE TABLE reset1 (id INTEGER PRIMARY KEY)', 'down' => 'DROP TABLE reset1'],
            ['name' => 'test_reset_table2', 'up' => 'CREATE TABLE reset2 (id INTEGER PRIMARY KEY)', 'down' => 'DROP TABLE reset2'],
        ];
        
        foreach ($migrations as $migration) {
            MigrationGenerator::createMigrationFile($migration['name'], $migration['up'], $migration['down'], self::$migrationsDir);
        }

        $runner = new MigrationRunner(self::$migrationsDir);
        
        // Run all migrations
        $migrated = $runner->migrate();
        $this->assertCount(2, $migrated);
        
        // Reset all migrations
        $reset = $runner->reset();
        $this->assertCount(2, $reset, 'Should reset 2 migrations');
        
        // Check status
        $status = $runner->status();
        $this->assertEquals(0, $status['total_executed'], 'Should have 0 executed migrations after reset');
        $this->assertEquals(2, $status['total_pending'], 'Should have 2 pending migrations after reset');
    }

    /**
     * Test migration runner - validation of migration methods
     */
    public function testMigrationRunnerValidation(): void
    {
        // Create a migration file without up/down methods
        $migrationName = 'test_invalid_migration';
        // Class name must match what MigrationRunner expects: Migration + timestamp + migration name
        $content = '<?php class Migration20251216000000_Test_Invalid_Migration { }';
        $filePath = self::$migrationsDir . '/20251216000000_' . $migrationName . '.php';
        file_put_contents($filePath, $content);

        $runner = new MigrationRunner(self::$migrationsDir);
        
        // Try to migrate - should fail with validation error
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must have both up() and down() methods');
        
        $runner->migrate();
    }

    /**
     * Clean up migration files in test directory
     */
    private function cleanupMigrationFiles(): void
    {
        if (!is_dir(self::$migrationsDir)) {
            return;
        }
        
        $files = glob(self::$migrationsDir . '/*.php');

        // move migration files for manual inspection
        foreach ($files as $file) {
            if (is_file($file)) {
                $newFileName = self::$oldMigrationsDir . '/' . basename($file);
                rename($file, $newFileName);
            }
        }
    }
}
