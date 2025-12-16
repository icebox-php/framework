<?php

namespace Icebox\Tests\Generator;

use Icebox\Tests\TestCase;
use Icebox\Generator\MigrationGenerator;

/**
 * Test migration generator functionality
 */
class MigrationGeneratorTest extends TestCase
{
    private $testRootDir;
    private $migrationsDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test migrations directory
        $this->testRootDir = __DIR__ . '/../../tests';
        $this->migrationsDir = $this->testRootDir . '/db/migrations';
        if (!is_dir($this->migrationsDir)) {
            mkdir($this->migrationsDir, 0755, true);
        }
        
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
     * Test SQL generation for create table migrations
     */
    public function testCreateTableSqlGeneration(): void
    {
        $migrationName = 'create_posts_table';
        $columns = ['title:string', 'content:text', 'user_id:integer'];
        
        list($upSql, $downSql) = MigrationGenerator::generateSql($migrationName, $columns);
        
        // Check up SQL
        $this->assertStringContainsString('CREATE TABLE posts', $upSql);
        $this->assertStringContainsString('id INT AUTO_INCREMENT PRIMARY KEY', $upSql);
        $this->assertStringContainsString('title VARCHAR(255)', $upSql);
        $this->assertStringContainsString('content TEXT', $upSql);
        $this->assertStringContainsString('user_id INT', $upSql);
        
        // Check down SQL
        $this->assertEquals('DROP TABLE posts', $downSql);
    }

    /**
     * Test SQL generation for add column migrations
     */
    public function testAddColumnSqlGeneration(): void
    {
        $migrationName = 'add_user_id_to_posts';
        $columns = ['user_id:integer'];
        
        list($upSql, $downSql) = MigrationGenerator::generateSql($migrationName, $columns);
        
        $this->assertStringContainsString('ALTER TABLE posts ADD COLUMN user_id INT', $upSql);
        $this->assertStringContainsString('ALTER TABLE posts DROP COLUMN user_id', $downSql);
    }

    /**
     * Test SQL generation for remove column migrations
     */
    public function testRemoveColumnSqlGeneration(): void
    {
        $migrationName = 'remove_title_from_posts';
        $columns = ['title:string'];
        
        list($upSql, $downSql) = MigrationGenerator::generateSql($migrationName, $columns);
        
        $this->assertStringContainsString('ALTER TABLE posts DROP COLUMN title', $upSql);
        $this->assertStringContainsString('ALTER TABLE posts ADD COLUMN title VARCHAR(255)', $downSql);
    }

    /**
     * Test migration file creation
     */
    public function testMigrationFileCreation(): void
    {
        $migrationName = 'test_create_table';
        $upSql = 'CREATE TABLE test (id INT PRIMARY KEY)';
        $downSql = 'DROP TABLE test';
        
        // Create migration file
        $filePath = MigrationGenerator::createMigrationFile($migrationName, $upSql, $downSql, $this->migrationsDir);
        
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

    /**
     * Test complete migration generation flow
     */
    public function testCompleteMigrationGeneration(): void
    {
        $migrationName = 'create_products_table';
        $columns = ['name:string', 'price:decimal', 'in_stock:boolean'];
        
        // Generate SQL
        list($upSql, $downSql) = MigrationGenerator::generateSql($migrationName, $columns);
        
        // Create migration file
        $filePath = MigrationGenerator::createMigrationFile($migrationName, $upSql, $downSql, $this->migrationsDir);
        
        // Check that file was created
        $this->assertFileExists($filePath, 'Migration file should be created');
        
        $content = file_get_contents($filePath);
        
        // Verify SQL content
        $this->assertStringContainsString('CREATE TABLE products', $content);
        $this->assertStringContainsString('name VARCHAR(255)', $content);
        $this->assertStringContainsString('price DECIMAL(10,2)', $content);
        $this->assertStringContainsString('in_stock BOOLEAN', $content);
        $this->assertStringContainsString('DROP TABLE products', $content);
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
     * Clean up migration files in test directory
     */
    private function cleanupMigrationFiles(): void
    {
        if (!is_dir($this->migrationsDir)) {
            return;
        }
        
        $files = glob($this->migrationsDir . '/*.php');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
