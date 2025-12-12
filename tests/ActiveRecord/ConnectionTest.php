<?php

namespace Icebox\Tests\ActiveRecord;

use Icebox\Tests\TestCase;
use Icebox\Tests\TestHelper;
use Icebox\ActiveRecord\Connection;
use Icebox\ActiveRecord\Config;

/**
 * Test for Connection class
 */
class ConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize test database
        TestHelper::initializeTestDatabase();
        
        // Create test table
        $pdo = Config::getConnection();
        $pdo->exec("
            CREATE TABLE test_connection (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255),
                value INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    protected function tearDown(): void
    {
        // Drop test table
        $pdo = Config::getConnection();
        $pdo->exec("DROP TABLE IF EXISTS test_connection");
        
        TestHelper::cleanupTestDatabase();
        parent::tearDown();
    }

    /**
     * Test select method
     */
    public function testSelect(): void
    {
        // Insert test data
        $pdo = Config::getConnection();
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('Test1', 100)");
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('Test2', 200)");
        
        $results = Connection::select('test_connection');
        $this->assertCount(2, $results);
        
        $results = Connection::select('test_connection', ['name' => 'Test1']);
        $this->assertCount(1, $results);
        $this->assertEquals('Test1', $results[0]['name']);
        $this->assertEquals(100, $results[0]['value']);
    }

    /**
     * Test select with options
     */
    public function testSelectWithOptions(): void
    {
        // Insert test data
        $pdo = Config::getConnection();
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('A', 300)");
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('B', 100)");
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('C', 200)");
        
        $results = Connection::select('test_connection', '', ['order' => 'value DESC', 'limit' => 2]);
        $this->assertCount(2, $results);
        $this->assertEquals('A', $results[0]['name']); // Value 300
        $this->assertEquals('C', $results[1]['name']); // Value 200
    }

    /**
     * Test selectOne method
     */
    public function testSelectOne(): void
    {
        $pdo = Config::getConnection();
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('Single', 999)");
        
        $result = Connection::selectOne('test_connection', ['name' => 'Single']);
        $this->assertIsArray($result);
        $this->assertEquals('Single', $result['name']);
        $this->assertEquals(999, $result['value']);
        
        $result = Connection::selectOne('test_connection', ['name' => 'NonExistent']);
        $this->assertNull($result);
    }

    /**
     * Test insert method
     */
    public function testInsert(): void
    {
        $id = Connection::insert('test_connection', [
            'name' => 'Insert Test',
            'value' => 123
        ]);
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        
        // Verify insertion
        $pdo = Config::getConnection();
        $stmt = $pdo->query("SELECT * FROM test_connection WHERE id = $id");
        $result = $stmt->fetch();
        
        $this->assertEquals('Insert Test', $result['name']);
        $this->assertEquals(123, $result['value']);
    }

    /**
     * Test insert with empty data returns false
     */
    public function testInsertEmptyData(): void
    {
        $result = Connection::insert('test_connection', []);
        $this->assertFalse($result);
    }

    /**
     * Test update method
     */
    public function testUpdate(): void
    {
        // First insert a record
        $pdo = Config::getConnection();
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('ToUpdate', 1)");
        $id = $pdo->lastInsertId();
        
        // Update the record
        $affected = Connection::update('test_connection', 
            ['name' => 'Updated', 'value' => 2],
            ['id' => $id]
        );
        
        $this->assertEquals(1, $affected);
        
        // Verify update
        $stmt = $pdo->query("SELECT * FROM test_connection WHERE id = $id");
        $result = $stmt->fetch();
        
        $this->assertEquals('Updated', $result['name']);
        $this->assertEquals(2, $result['value']);
    }

    /**
     * Test update with empty data returns false
     */
    public function testUpdateEmptyData(): void
    {
        $result = Connection::update('test_connection', [], ['id' => 1]);
        $this->assertFalse($result);
    }

    /**
     * Test update without conditions updates all records
     */
    public function testUpdateWithoutConditions(): void
    {
        // Insert multiple records
        $pdo = Config::getConnection();
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('A', 1)");
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('B', 1)");
        
        $affected = Connection::update('test_connection', ['value' => 999]);
        $this->assertEquals(2, $affected);
        
        // Verify all records updated
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM test_connection WHERE value = 999");
        $result = $stmt->fetch();
        $this->assertEquals(2, $result['count']);
    }

    /**
     * Test delete method
     */
    public function testDelete(): void
    {
        // Insert test data
        $pdo = Config::getConnection();
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('ToDelete', 1)");
        $id = $pdo->lastInsertId();
        
        $affected = Connection::delete('test_connection', ['id' => $id]);
        $this->assertEquals(1, $affected);
        
        // Verify deletion
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM test_connection WHERE id = $id");
        $result = $stmt->fetch();
        $this->assertEquals(0, $result['count']);
    }

    /**
     * Test delete without conditions deletes all records
     */
    public function testDeleteWithoutConditions(): void
    {
        // Insert multiple records
        $pdo = Config::getConnection();
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('A', 1)");
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('B', 2)");
        
        $affected = Connection::delete('test_connection');
        $this->assertEquals(2, $affected);
        
        // Verify all records deleted
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM test_connection");
        $result = $stmt->fetch();
        $this->assertEquals(0, $result['count']);
    }

    /**
     * Test query method with raw SQL
     */
    public function testQuery(): void
    {
        $pdo = Config::getConnection();
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('QueryTest', 555)");
        
        $stmt = Connection::query("SELECT * FROM test_connection WHERE name = ?", ['QueryTest']);
        $result = $stmt->fetch();
        
        $this->assertNotFalse($result);
        $this->assertEquals('QueryTest', $result['name']);
        $this->assertEquals(555, $result['value']);
    }

    /**
     * Test transaction methods
     */
    public function testTransactions(): void
    {
        $beginResult = Connection::beginTransaction();
        $this->assertTrue($beginResult);
        
        // Insert within transaction
        Connection::insert('test_connection', ['name' => 'InTransaction', 'value' => 777]);
        
        // Should be visible within transaction
        $countBefore = Connection::select('test_connection', ['name' => 'InTransaction']);
        $this->assertCount(1, $countBefore);
        
        // Rollback
        Connection::rollback();
        
        // Should not be visible after rollback
        $countAfter = Connection::select('test_connection', ['name' => 'InTransaction']);
        $this->assertCount(0, $countAfter);
        
        // Test commit
        Connection::beginTransaction();
        Connection::insert('test_connection', ['name' => 'ToCommit', 'value' => 888]);
        Connection::commit();
        
        $countCommitted = Connection::select('test_connection', ['name' => 'ToCommit']);
        $this->assertCount(1, $countCommitted);
    }

    /**
     * Test select with string WHERE clause
     */
    public function testSelectWithStringWhere(): void
    {
        $pdo = Config::getConnection();
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('StringWhere', 111)");
        $pdo->exec("INSERT INTO test_connection (name, value) VALUES ('StringWhere', 222)");
        
        $results = Connection::select('test_connection', 'name = "StringWhere" AND value = 111');
        $this->assertCount(1, $results);
        $this->assertEquals(111, $results[0]['value']);
    }
}
