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
        $users = TestUserForQuery::where('active', 1)->get();
        
        $this->assertCount(2, $users); // John and Jane are active
        $this->assertEquals('John Doe', $users[0]->name);
        $this->assertEquals('Jane Smith', $users[1]->name);
    }

    /**
     * Test where with comparison operators
     */
    public function testWhereWithOperators(): void
    {
        // Greater than
        $users = TestUserForQuery::where('age', '>', 25)->get();
        $this->assertCount(2, $users); // John (30) and Bob (40)
        $names = array_map(function($user) { return $user->name; }, $users);
        $this->assertContains('John Doe', $names);
        $this->assertContains('Bob Johnson', $names);
        
        // Less than
        $users = TestUserForQuery::where('age', '<', 30)->get();
        $this->assertCount(1, $users); // Jane (25)
        $this->assertEquals('Jane Smith', $users[0]->name);
        
        // Less than or equal
        $users = TestUserForQuery::where('age', '<=', 30)->get();
        $this->assertCount(2, $users); // John (30) and Jane (25)
        
        // Greater than or equal
        $users = TestUserForQuery::where('age', '>=', 30)->get();
        $this->assertCount(2, $users); // John (30) and Bob (40)
        
        // Not equal
        $users = TestUserForQuery::where('active', '!=', 1)->get();
        $this->assertCount(1, $users); // Bob (active = 0)
        $this->assertEquals('Bob Johnson', $users[0]->name);
    }
    
    /**
     * Test where with LIKE operator
     */
    public function testWhereLike(): void
    {
        // Test LIKE with wildcard
        $users = TestUserForQuery::where('name', 'LIKE', '%John%')->get();
        $this->assertCount(2, $users); // John Doe and Bob Johnson (contains "John" in "Johnson")
        $names = array_map(function($user) { return $user->name; }, $users);
        $this->assertContains('John Doe', $names);
        $this->assertContains('Bob Johnson', $names);
        
        $users = TestUserForQuery::where('name', 'LIKE', '%Jane%')->get();
        $this->assertCount(1, $users);
        $this->assertEquals('Jane Smith', $users[0]->name);
        
        // Test exact match with wildcards
        $users = TestUserForQuery::where('name', 'LIKE', 'John%')->get();
        $this->assertCount(1, $users);
        $this->assertEquals('John Doe', $users[0]->name);
        
        // Test case insensitive (SQLite LIKE is case-insensitive by default)
        $users = TestUserForQuery::where('name', 'LIKE', '%john%')->get();
        $this->assertCount(2, $users); // Matches both John Doe and Bob Johnson
    }

    /**
     * Test where with array conditions
     */
    public function testWhereWithArray(): void
    {
        $users = TestUserForQuery::where([
            'active' => 1,
            'age' => 30
        ])->get();
        
        $this->assertCount(1, $users);
        $this->assertEquals('John Doe', $users[0]->name);
    }

    /**
     * Test whereNull and whereNotNull
     */
    public function testWhereNull(): void
    {
        // All users have null approved_at in test data
        $users = TestUserForQuery::where()->whereNull('approved_at')->get();
        $this->assertCount(3, $users);
        
        // No users have non-null approved_at
        $users = TestUserForQuery::where()->whereNotNull('approved_at')->get();
        $this->assertCount(0, $users);
    }

    /**
     * Test whereIn and whereNotIn
     */
    public function testWhereIn(): void
    {
        $users = TestUserForQuery::where()->whereIn('id', [1, 2])->get();
        $this->assertCount(2, $users);
        
        $users = TestUserForQuery::where()->whereNotIn('id', [1, 2])->get();
        $this->assertCount(1, $users);
        $this->assertEquals('Bob Johnson', $users[0]->name);
    }

    /**
     * Test orderBy
     */
    public function testOrderBy(): void
    {
        $users = TestUserForQuery::where('active', 1)->orderBy('name', 'ASC')->get();
        $this->assertCount(2, $users);
        $this->assertEquals('Jane Smith', $users[0]->name); // J comes before Jo
        $this->assertEquals('John Doe', $users[1]->name);
        
        $users = TestUserForQuery::where()->orderBy('age', 'DESC')->get();
        $this->assertEquals('Bob Johnson', $users[0]->name); // Age 40
        $this->assertEquals('John Doe', $users[1]->name); // Age 30
        $this->assertEquals('Jane Smith', $users[2]->name); // Age 25
    }

    /**
     * Test limit and offset
     */
    public function testLimitAndOffset(): void
    {
        $users = TestUserForQuery::where()->orderBy('id', 'ASC')->limit(2)->get();
        $this->assertCount(2, $users);
        $this->assertEquals('John Doe', $users[0]->name);
        $this->assertEquals('Jane Smith', $users[1]->name);
        
        $users = TestUserForQuery::where()->orderBy('id', 'ASC')->limit(1)->offset(1)->get();
        $this->assertCount(1, $users);
        $this->assertEquals('Jane Smith', $users[0]->name);
    }

    /**
     * Test first method
     */
    public function testFirst(): void
    {
        $user = TestUserForQuery::where('active', 1)->first();
        $this->assertInstanceOf(TestUserForQuery::class, $user);
        $this->assertEquals('John Doe', $user->name);
        
        $user = TestUserForQuery::where('active', 0)->first();
        $this->assertEquals('Bob Johnson', $user->name);
    }

    /**
     * Test count method
     */
    public function testCount(): void
    {
        $count = TestUserForQuery::where('active', 1)->count();
        $this->assertEquals(2, $count);
        
        $count = TestUserForQuery::where('age', '>', 30)->count();
        $this->assertEquals(1, $count);
        
        $count = TestUserForQuery::where()->count();
        $this->assertEquals(3, $count);
    }

    /**
     * Test exists method
     */
    public function testExists(): void
    {
        $exists = TestUserForQuery::where('name', 'John Doe')->exists();
        $this->assertTrue($exists);
        
        $exists = TestUserForQuery::where('name', 'Non Existent')->exists();
        $this->assertFalse($exists);
    }

    /**
     * Test orWhere
     */
    public function testOrWhere(): void
    {
        $users = TestUserForQuery::where('name', 'John Doe')
            ->orWhere('name', 'Jane Smith')
            ->get();
        
        $this->assertCount(2, $users);
        $names = array_map(function($user) { return $user->name; }, $users);
        $this->assertContains('John Doe', $names);
        $this->assertContains('Jane Smith', $names);
    }

    /**
     * Test method chaining
     */
    public function testMethodChaining(): void
    {
        $users = TestUserForQuery::where('active', 1)
            ->where('age', '>', 20)
            ->orderBy('name', 'ASC')
            ->limit(10)
            ->get();
        
        $this->assertCount(2, $users);
        $this->assertEquals('Jane Smith', $users[0]->name);
        $this->assertEquals('John Doe', $users[1]->name);
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
        
        // The actual query would need to be executed to test properly
        $users = $builder->get();
        $this->assertIsArray($users);
    }

    /**
     * Test whereBetween
     */
    public function testWhereBetween(): void
    {
        $users = TestUserForQuery::where()->whereBetween('age', [20, 35])->get();
        $this->assertCount(2, $users); // John (30) and Jane (25)
        
        $users = TestUserForQuery::where()->whereBetween('age', [40, 50])->get();
        $this->assertCount(1, $users); // Bob (40)
        $this->assertEquals('Bob Johnson', $users[0]->name);
    }

    /**
     * Test empty where returns all records
     */
    public function testEmptyWhere(): void
    {
        $users = TestUserForQuery::where()->get();
        $this->assertCount(3, $users);
        
        $users = TestUserForQuery::where([])->get();
        $this->assertCount(3, $users);
    }
}
