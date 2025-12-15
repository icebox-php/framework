<?php

namespace Icebox\Tests\ActiveRecord;

use Icebox\Tests\TestCase;
use Icebox\Tests\TestHelper;
use Icebox\ActiveRecord\QueryBuilder;

/**
 * Test model for QueryBuilder tests
 */
class TestUserForQuery extends \Icebox\ActiveRecord\Model
{
    protected static $table = 'users';
    protected static $primaryKey = 'id';
}

/**
 * Test for QueryBuilder class
 */
class QueryBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize test database
        TestHelper::initializeTestDatabase();
        TestHelper::createTestData();
    }

    protected function tearDown(): void
    {
        TestHelper::cleanupTestDatabase();
        parent::tearDown();
    }

    /**
     * Test basic where clause
     */
    public function testBasicWhere(): void
    {
        $query = TestUserForQuery::where('active', 1);
        $users = $query->get();
        
        $this->assertCount(2, $users); // John and Jane are active
        $this->assertEquals('John Doe', $users[0]->name);
        $this->assertEquals('Jane Smith', $users[1]->name);
        
        // Test toSql()
        $this->assertEquals("SELECT * FROM users WHERE active = 1", $query->toSql());
    }

    /**
     * Test where with comparison operators
     */
    public function testWhereWithOperators(): void
    {
        // Greater than
        $query = TestUserForQuery::where('age', '>', 25);
        $users = $query->get();
        $this->assertCount(2, $users); // John (30) and Bob (40)
        $names = array_map(function($user) { return $user->name; }, $users);
        $this->assertContains('John Doe', $names);
        $this->assertContains('Bob Johnson', $names);
        $this->assertEquals("SELECT * FROM users WHERE age > 25", $query->toSql());
        
        // Less than
        $query = TestUserForQuery::where('age', '<', 30);
        $users = $query->get();
        $this->assertCount(1, $users); // Jane (25)
        $this->assertEquals('Jane Smith', $users[0]->name);
        $this->assertEquals("SELECT * FROM users WHERE age < 30", $query->toSql());
        
        // Less than or equal
        $query = TestUserForQuery::where('age', '<=', 30);
        $users = $query->get();
        $this->assertCount(2, $users); // John (30) and Jane (25)
        $this->assertEquals("SELECT * FROM users WHERE age <= 30", $query->toSql());
        
        // Greater than or equal
        $query = TestUserForQuery::where('age', '>=', 30);
        $users = $query->get();
        $this->assertCount(2, $users); // John (30) and Bob (40)
        $this->assertEquals("SELECT * FROM users WHERE age >= 30", $query->toSql());
        
        // Not equal
        $query = TestUserForQuery::where('active', '!=', 1);
        $users = $query->get();
        $this->assertCount(1, $users); // Bob (active = 0)
        $this->assertEquals('Bob Johnson', $users[0]->name);
        $this->assertEquals("SELECT * FROM users WHERE active != 1", $query->toSql());
    }
    
    /**
     * Test where with LIKE operator
     */
    public function testWhereLike(): void
    {
        // Test LIKE with wildcard
        $query = TestUserForQuery::where('name', 'LIKE', '%John%');
        $users = $query->get();
        $this->assertCount(2, $users); // John Doe and Bob Johnson (contains "John" in "Johnson")
        $names = array_map(function($user) { return $user->name; }, $users);
        $this->assertContains('John Doe', $names);
        $this->assertContains('Bob Johnson', $names);
        $this->assertEquals("SELECT * FROM users WHERE name LIKE '%John%'", $query->toSql());
        
        $query = TestUserForQuery::where('name', 'LIKE', '%Jane%');
        $users = $query->get();
        $this->assertCount(1, $users);
        $this->assertEquals('Jane Smith', $users[0]->name);
        $this->assertEquals("SELECT * FROM users WHERE name LIKE '%Jane%'", $query->toSql());
        
        // Test exact match with wildcards
        $query = TestUserForQuery::where('name', 'LIKE', 'John%');
        $users = $query->get();
        $this->assertCount(1, $users);
        $this->assertEquals('John Doe', $users[0]->name);
        $this->assertEquals("SELECT * FROM users WHERE name LIKE 'John%'", $query->toSql());
        
        // Test case insensitive (SQLite LIKE is case-insensitive by default)
        $query = TestUserForQuery::where('name', 'LIKE', '%john%');
        $users = $query->get();
        $this->assertCount(2, $users); // Matches both John Doe and Bob Johnson
        $this->assertEquals("SELECT * FROM users WHERE name LIKE '%john%'", $query->toSql());
    }

    /**
     * Test where with array conditions
     */
    public function testWhereWithArray(): void
    {
        $query = TestUserForQuery::where([
            'active' => 1,
            'age' => 30
        ]);
        $users = $query->get();
        
        $this->assertCount(1, $users);
        $this->assertEquals('John Doe', $users[0]->name);
        $this->assertEquals("SELECT * FROM users WHERE active = 1 AND age = 30", $query->toSql());
    }

    /**
     * Test whereNull and whereNotNull
     */
    public function testWhereNull(): void
    {
        // All users have null approved_at in test data
        $query = TestUserForQuery::whereNull('approved_at');
        $users = $query->get();
        $this->assertCount(3, $users);
        $this->assertEquals("SELECT * FROM users WHERE approved_at IS NULL", $query->toSql());
        
        // No users have non-null approved_at
        $query = TestUserForQuery::whereNotNull('approved_at');
        $users = $query->get();
        $this->assertCount(0, $users);
        $this->assertEquals("SELECT * FROM users WHERE approved_at IS NOT NULL", $query->toSql());
    }

    /**
     * Test whereIn and whereNotIn
     */
    public function testWhereIn(): void
    {
        $query = TestUserForQuery::whereIn('id', [1, 2]);
        $users = $query->get();
        $this->assertCount(2, $users);
        $this->assertEquals("SELECT * FROM users WHERE id IN (1, 2)", $query->toSql());
        
        $query = TestUserForQuery::whereNotIn('id', [1, 2]);
        $users = $query->get();
        $this->assertCount(1, $users);
        $this->assertEquals('Bob Johnson', $users[0]->name);
        $this->assertEquals("SELECT * FROM users WHERE id NOT IN (1, 2)", $query->toSql());
    }

    /**
     * Test orderBy
     */
    public function testOrderBy(): void
    {
        $query = TestUserForQuery::where('active', 1)->orderBy('name', 'ASC');
        $users = $query->get();
        $this->assertCount(2, $users);
        $this->assertEquals('Jane Smith', $users[0]->name); // J comes before Jo
        $this->assertEquals('John Doe', $users[1]->name);
        $this->assertEquals("SELECT * FROM users WHERE active = 1 ORDER BY name ASC", $query->toSql());
        
        $query = TestUserForQuery::orderBy('age', 'DESC');
        $users = $query->get();
        $this->assertEquals('Bob Johnson', $users[0]->name); // Age 40
        $this->assertEquals('John Doe', $users[1]->name); // Age 30
        $this->assertEquals('Jane Smith', $users[2]->name); // Age 25
        $this->assertEquals("SELECT * FROM users ORDER BY age DESC", $query->toSql());
    }

    /**
     * Test limit and offset
     */
    public function testLimitAndOffset(): void
    {
        $query = TestUserForQuery::orderBy('id', 'ASC')->limit(2);
        $users = $query->get();
        $this->assertCount(2, $users);
        $this->assertEquals('John Doe', $users[0]->name);
        $this->assertEquals('Jane Smith', $users[1]->name);
        $this->assertEquals("SELECT * FROM users ORDER BY id ASC LIMIT 2", $query->toSql());

        $query = TestUserForQuery::orderBy('id', 'ASC')->limit(1)->offset(1);
        $users = $query->get();
        $this->assertCount(1, $users);
        $this->assertEquals('Jane Smith', $users[0]->name);
        $this->assertEquals("SELECT * FROM users ORDER BY id ASC LIMIT 1 OFFSET 1", $query->toSql());
    }

    /**
     * Test first method
     */
    public function testFirst(): void
    {
        $query = TestUserForQuery::where('active', 1);
        $user = $query->first();
        $this->assertInstanceOf(TestUserForQuery::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals("SELECT * FROM users WHERE active = 1 LIMIT 1", $query->toSql());
        
        $query = TestUserForQuery::where('active', 0);
        $user = $query->first();
        $this->assertEquals('Bob Johnson', $user->name);
        $this->assertEquals("SELECT * FROM users WHERE active = 0 LIMIT 1", $query->toSql());
    }

    /**
     * Test count method
     */
    public function testCount(): void
    {
        $query = TestUserForQuery::where('active', 1);
        $count = $query->count();
        $this->assertEquals(2, $count);
        $this->assertEquals("SELECT * FROM users WHERE active = 1", $query->toSql());
        
        $query = TestUserForQuery::where('age', '>', 30);
        $count = $query->count();
        $this->assertEquals(1, $count);
        $this->assertEquals("SELECT * FROM users WHERE age > 30", $query->toSql());
        
        $query = TestUserForQuery::where([]);
        $count = $query->count();
        $this->assertEquals(3, $count);
        $this->assertEquals("SELECT * FROM users", $query->toSql());
    }

    /**
     * Test exists method
     */
    public function testExists(): void
    {
        $query = TestUserForQuery::where('name', 'John Doe');
        $exists = $query->exists();
        $this->assertTrue($exists);
        $this->assertEquals("SELECT * FROM users WHERE name = 'John Doe'", $query->toSql());
        
        $query = TestUserForQuery::where('name', 'Non Existent');
        $exists = $query->exists();
        $this->assertFalse($exists);
        $this->assertEquals("SELECT * FROM users WHERE name = 'Non Existent'", $query->toSql());
    }

    /**
     * Test orWhere
     */
    public function testOrWhere(): void
    {
        $query = TestUserForQuery::where('name', 'John Doe')
            ->orWhere('name', 'Jane Smith');
        $users = $query->get();
        
        $this->assertCount(2, $users);
        $names = array_map(function($user) { return $user->name; }, $users);
        $this->assertContains('John Doe', $names);
        $this->assertContains('Jane Smith', $names);
        $this->assertEquals("SELECT * FROM users WHERE name = 'John Doe' OR name = 'Jane Smith'", $query->toSql());
    }

    /**
     * Test method chaining
     */
    public function testMethodChaining(): void
    {
        $query = TestUserForQuery::where('active', 1)
            ->where('age', '>', 20)
            ->orderBy('name', 'ASC')
            ->limit(10);
        $users = $query->get();
        
        $this->assertCount(2, $users);
        $this->assertEquals('Jane Smith', $users[0]->name);
        $this->assertEquals('John Doe', $users[1]->name);
        $this->assertEquals("SELECT * FROM users WHERE active = 1 AND age > 20 ORDER BY name ASC LIMIT 10", $query->toSql());
    }

    /**
     * Test where with raw SQL
     */
    public function testWhereRaw(): void
    {
        // Note: Raw SQL testing is limited without actual query execution
        // We'll just test that the method doesn't throw errors
        $builder = TestUserForQuery::where('age > ?', [25]);
        $this->assertInstanceOf(QueryBuilder::class, $builder);
        // Raw SQL parameters are now interpolated in toSql()
        $this->assertEquals("SELECT * FROM users WHERE age > 25", $builder->toSql());
        
        // The actual query would need to be executed to test properly
        $users = $builder->get();
        $this->assertIsArray($users);
    }

    /**
     * Test select with string columns
     */
    public function testSelectWithString(): void
    {
        $query = TestUserForQuery::select('id, name');
        $this->assertEquals("SELECT id, name FROM users", $query->toSql());
        
        $query = TestUserForQuery::select('id');
        $this->assertEquals("SELECT id FROM users", $query->toSql());
    }

    /**
     * Test select with array of columns
     */
    public function testSelectWithArray(): void
    {
        $query = TestUserForQuery::select(['id', 'name']);
        $this->assertEquals("SELECT id, name FROM users", $query->toSql());
        
        $query = TestUserForQuery::select(['id']);
        $this->assertEquals("SELECT id FROM users", $query->toSql());
    }

    /**
     * Test select with where clause
     */
    public function testSelectWithWhere(): void
    {
        $query = TestUserForQuery::select('id')->where('active', 1);
        $this->assertEquals("SELECT id FROM users WHERE active = 1", $query->toSql());
        
        $query = TestUserForQuery::where('active', 1)->select('id', 'name');
        $this->assertEquals("SELECT id, name FROM users WHERE active = 1", $query->toSql());
    }

    /**
     * Test select with order by, limit, offset
     */
    public function testSelectWithOrderLimitOffset(): void
    {
        $query = TestUserForQuery::select('id', 'name')
            ->where('active', 1)
            ->orderBy('name', 'ASC')
            ->limit(10)
            ->offset(5);
        $this->assertEquals("SELECT id, name FROM users WHERE active = 1 ORDER BY name ASC LIMIT 10 OFFSET 5", $query->toSql());
    }

    /**
     * Test select with get() returns only selected columns
     */
    public function testSelectWithGet(): void
    {
        $query = TestUserForQuery::select('id', 'name', 'email')
            ->orderBy('id', 'ASC');
        $users = $query->get();
        
        $this->assertNotEmpty($users);
        $firstUser = $users[0];
        $secondUser = $users[1];
        
        // Should have selected columns
        $this->assertArrayHasKey('id', $firstUser->toArray());
        $this->assertArrayHasKey('name', $firstUser->toArray());
        $this->assertArrayHasKey('email', $firstUser->toArray());
        
        // Verify specific values with ORDER BY id for consistency
        $this->assertEquals('John Doe', $firstUser->name);
        $this->assertEquals('john@example.com', $firstUser->email);
        $this->assertEquals('Jane Smith', $secondUser->name);
        $this->assertEquals('jane@example.com', $secondUser->email);
        
        // Check SQL
        $this->assertEquals("SELECT id, name, email FROM users ORDER BY id ASC", $query->toSql());
    }

    /**
     * Test select chaining
     */
    public function testSelectChaining(): void
    {
        $query = TestUserForQuery::select('id')->select('name');
        // Last select should override previous
        $this->assertEquals("SELECT name FROM users", $query->toSql());
    }

    /**
     * Test select with empty array defaults to *
     */
    public function testSelectEmptyArray(): void
    {
        $query = TestUserForQuery::select([]);
        $this->assertEquals("SELECT * FROM users", $query->toSql());
    }

    /**
     * Test select with empty string defaults to *
     */
    public function testSelectEmptyString(): void
    {
        $query = TestUserForQuery::select('');
        $this->assertEquals("SELECT * FROM users", $query->toSql());
    }

    /**
     * Test whereBetween
     */
    public function testWhereBetween(): void
    {
        $query = TestUserForQuery::whereBetween('age', [20, 35]);
        $users = $query->get();
        $this->assertCount(2, $users); // John (30) and Jane (25)
        $this->assertEquals("SELECT * FROM users WHERE age BETWEEN 20 AND 35", $query->toSql());
        
        $query = TestUserForQuery::whereBetween('age', [40, 50]);
        $users = $query->get();
        $this->assertCount(1, $users); // Bob (40)
        $this->assertEquals('Bob Johnson', $users[0]->name);
        $this->assertEquals("SELECT * FROM users WHERE age BETWEEN 40 AND 50", $query->toSql());
    }

    /**
     * Test empty where returns all records
     */
    public function testEmptyWhere(): void
    {
        $query = TestUserForQuery::where([]);
        $users = $query->get();
        $this->assertCount(3, $users);
        $this->assertEquals("SELECT * FROM users", $query->toSql());
        
        // Test get() without any conditions
        $users = TestUserForQuery::get();
        $this->assertCount(3, $users);
    }

    /**
     * Test static methods return QueryBuilder instances
     */
    public function testStaticMethodsReturnQueryBuilder(): void
    {
        // Test that each static method returns a QueryBuilder instance
        $query = TestUserForQuery::where('active', 1);
        $this->assertInstanceOf(QueryBuilder::class, $query);
        $this->assertEquals("SELECT * FROM users WHERE active = 1", $query->toSql());
        
        $this->assertInstanceOf(QueryBuilder::class, TestUserForQuery::orWhere('active', 1));
        $this->assertInstanceOf(QueryBuilder::class, TestUserForQuery::whereNull('approved_at'));
        $this->assertInstanceOf(QueryBuilder::class, TestUserForQuery::whereNotNull('approved_at'));
        $this->assertInstanceOf(QueryBuilder::class, TestUserForQuery::whereIn('id', [1, 2]));
        $this->assertInstanceOf(QueryBuilder::class, TestUserForQuery::whereNotIn('id', [1, 2]));
        $this->assertInstanceOf(QueryBuilder::class, TestUserForQuery::whereBetween('age', [20, 30]));
        $this->assertInstanceOf(QueryBuilder::class, TestUserForQuery::orderBy('name', 'ASC'));
        $this->assertInstanceOf(QueryBuilder::class, TestUserForQuery::limit(10));
        $this->assertInstanceOf(QueryBuilder::class, TestUserForQuery::offset(0));
    }

    /**
     * Test method chaining from static calls
     */
    public function testMethodChainingFromStaticCalls(): void
    {
        // Chain multiple static method calls
        $query = TestUserForQuery::whereIn('id', [1, 2])
            ->whereNull('approved_at')
            ->orderBy('name', 'ASC')
            ->limit(10);
        $users = $query->get();
        
        $this->assertCount(2, $users);
        $this->assertEquals('Jane Smith', $users[0]->name);
        $this->assertEquals('John Doe', $users[1]->name);
        $this->assertEquals("SELECT * FROM users WHERE id IN (1, 2) AND approved_at IS NULL ORDER BY name ASC LIMIT 10", $query->toSql());
        
        // Test combination of where and whereIn
        $query = TestUserForQuery::where('active', 1)
            ->whereIn('id', [1, 2]);
        $users = $query->get();
        $this->assertCount(2, $users);
        $this->assertEquals("SELECT * FROM users WHERE active = 1 AND id IN (1, 2)", $query->toSql());
        
        // Test whereBetween with orderBy
        $query = TestUserForQuery::whereBetween('age', [20, 35])
            ->orderBy('age', 'DESC');
        $users = $query->get();
        $this->assertCount(2, $users);
        $this->assertEquals('John Doe', $users[0]->name); // Age 30
        $this->assertEquals('Jane Smith', $users[1]->name); // Age 25
        $this->assertEquals("SELECT * FROM users WHERE age BETWEEN 20 AND 35 ORDER BY age DESC", $query->toSql());
    }

    /**
     * Test error handling in static methods
     */
    public function testErrorHandling(): void
    {
        // First test that toSql() works for a valid whereBetween
        $query = TestUserForQuery::whereBetween('age', [20, 30]);
        $this->assertEquals("SELECT * FROM users WHERE age BETWEEN 20 AND 30", $query->toSql());
        
        // Test whereBetween with wrong number of values
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('whereBetween requires exactly 2 values');
        TestUserForQuery::whereBetween('age', [20]); // Only 1 value, should throw
    }

    /**
     * Test backward compatibility with old syntax
     */
    public function testBackwardCompatibility(): void
    {
        // Old syntax: where()->method() should still work
        $query1 = TestUserForQuery::where()->whereIn('id', [1, 2]);
        $users1 = $query1->get();
        $query2 = TestUserForQuery::whereIn('id', [1, 2]);
        $users2 = $query2->get();
        $this->assertEquals(count($users1), count($users2));
        $this->assertEquals($query1->toSql(), $query2->toSql());
        $this->assertEquals("SELECT * FROM users WHERE id IN (1, 2)", $query1->toSql());
        
        // Old syntax with conditions
        $query1 = TestUserForQuery::where('active', 1)->whereIn('id', [1, 2]);
        $users1 = $query1->get();
        $query2 = TestUserForQuery::where('active', 1)->whereIn('id', [1, 2]);
        $users2 = $query2->get();
        $this->assertEquals(count($users1), count($users2));
        $this->assertEquals($query1->toSql(), $query2->toSql());
        $this->assertEquals("SELECT * FROM users WHERE active = 1 AND id IN (1, 2)", $query1->toSql());
        
        // Mixed: where()->method() vs direct method()
        $builder1 = TestUserForQuery::where()->orderBy('name', 'ASC');
        $builder2 = TestUserForQuery::orderBy('name', 'ASC');
        $this->assertInstanceOf(QueryBuilder::class, $builder1);
        $this->assertInstanceOf(QueryBuilder::class, $builder2);
        $this->assertEquals("SELECT * FROM users ORDER BY name ASC", $builder1->toSql());
        $this->assertEquals("SELECT * FROM users ORDER BY name ASC", $builder2->toSql());
    }

    /**
     * Test static get method with conditions
     */
    public function testStaticGetWithConditions(): void
    {
        // get() without conditions returns all
        $query = TestUserForQuery::where([]);
        $allUsers = $query->get();
        $this->assertCount(3, $allUsers);
        $this->assertEquals("SELECT * FROM users", $query->toSql());
        
        // get() after where conditions
        $query = TestUserForQuery::where('active', 1);
        $activeUsers = $query->get();
        $this->assertCount(2, $activeUsers);
        $this->assertEquals("SELECT * FROM users WHERE active = 1", $query->toSql());
        
        // Verify the actual data
        $names = array_map(function($user) { return $user->name; }, $activeUsers);
        $this->assertContains('John Doe', $names);
        $this->assertContains('Jane Smith', $names);
    }

    /**
     * Test whereIn with empty array
     */
    public function testWhereInWithEmptyArray(): void
    {
        // whereIn with empty array should return no results
        $query = TestUserForQuery::whereIn('id', []);
        $users = $query->get();
        $this->assertCount(0, $users);
        // Note: SQL for empty IN clause might be "id IN ()" which is invalid
        // but the test database might handle it or the QueryBuilder might handle it differently
        // We'll still test toSql() returns something
        $this->assertIsString($query->toSql());
        
        // whereNotIn with empty array should return all results
        $query = TestUserForQuery::whereNotIn('id', []);
        $users = $query->get();
        $this->assertCount(3, $users);
        $this->assertIsString($query->toSql());
    }
}
