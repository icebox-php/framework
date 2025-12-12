<?php

namespace Icebox\Tests\ActiveRecord;

use Icebox\Tests\TestCase;
use Icebox\Tests\TestHelper;
use Icebox\ActiveRecord\Config;

/**
 * Test model for User
 */
class TestUser extends \Icebox\ActiveRecord\Model
{
    protected static $table = 'users';
    protected static $primaryKey = 'id';
}

/**
 * Test for Model class
 */
class ModelTest extends TestCase
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
     * Test getTable method
     */
    public function testGetTable(): void
    {
        $table = TestUser::getTable();
        $this->assertEquals('users', $table);
        
        // Test default table name generation
        $anonymousModel = new class extends \Icebox\ActiveRecord\Model {
            // No table defined
        };
        
        $table = $anonymousModel::getTable();
        $this->assertStringContains('s', $table); // Should be plural
    }

    /**
     * Test getPrimaryKey method
     */
    public function testGetPrimaryKey(): void
    {
        $primaryKey = TestUser::getPrimaryKey();
        $this->assertEquals('id', $primaryKey);
    }

    /**
     * Test find method
     */
    public function testFind(): void
    {
        // Find existing record
        $user = TestUser::find(1);
        $this->assertInstanceOf(TestUser::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        
        // Find non-existent record
        $user = TestUser::find(999);
        $this->assertNull($user);
    }

    /**
     * Test attribute access via magic methods
     */
    public function testAttributeAccess(): void
    {
        $user = TestUser::find(1);
        
        // Test __get
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals(30, $user->age);
        
        // Test __isset
        $this->assertTrue(isset($user->name));
        $this->assertFalse(isset($user->non_existent));
        
        // Test __set
        $user->name = 'John Updated';
        $this->assertEquals('John Updated', $user->name);
    }

    /**
     * Test save method for new record
     */
    public function testSaveNewRecord(): void
    {
        $user = new TestUser([
            'name' => 'New User',
            'email' => 'new@example.com',
            'age' => 25
        ]);
        
        $result = $user->save();
        $this->assertTrue($result);
        
        // Should have an ID after save
        $this->assertNotNull($user->id);
        $this->assertGreaterThan(0, $user->id);
        
        // Verify record was saved
        $savedUser = TestUser::find($user->id);
        $this->assertEquals('New User', $savedUser->name);
    }

    /**
     * Test save method for existing record
     */
    public function testSaveExistingRecord(): void
    {
        $user = TestUser::find(1);
        $originalName = $user->name;
        
        $user->name = 'Updated Name';
        $result = $user->save();
        
        $this->assertNotFalse($result); // Could be 0 or 1
        
        // Verify update
        $updatedUser = TestUser::find(1);
        $this->assertEquals('Updated Name', $updatedUser->name);
    }

    /**
     * Test delete method
     */
    public function testDelete(): void
    {
        $user = TestUser::find(1);
        $result = $user->delete();
        
        $this->assertNotFalse($result); // Could be 0 or 1
        
        // Verify deletion
        $deletedUser = TestUser::find(1);
        $this->assertNull($deletedUser);
    }

    /**
     * Test delete on non-existent record
     */
    public function testDeleteNonExistent(): void
    {
        $user = new TestUser(['name' => 'Temp']);
        $result = $user->delete();
        
        $this->assertFalse($result);
    }

    /**
     * Test updateAttributes method
     */
    public function testUpdateAttributes(): void
    {
        $user = TestUser::find(1);
        $result = $user->updateAttributes([
            'name' => 'Updated Name',
            'age' => 35
        ]);
        
        $this->assertNotFalse($result); // Could be 0 or 1
        
        // Verify updates
        $updatedUser = TestUser::find(1);
        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertEquals(35, $updatedUser->age);
    }

    /**
     * Test toArray method
     */
    public function testToArray(): void
    {
        $user = TestUser::find(1);
        $array = $user->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertEquals('John Doe', $array['name']);
    }

    /**
     * Test getDirty and isDirty methods
     */
    public function testDirtyAttributes(): void
    {
        $user = TestUser::find(1);
        
        // Initially should not be dirty
        $this->assertFalse($user->isDirty());
        $this->assertEquals([], $user->getDirty());
        
        // Make a change
        $user->name = 'Changed Name';
        $this->assertTrue($user->isDirty());
        
        $dirty = $user->getDirty();
        $this->assertArrayHasKey('name', $dirty);
        $this->assertEquals('Changed Name', $dirty['name']);
        
        // Save should clear dirty state
        $user->save();
        $this->assertFalse($user->isDirty());
    }

    /**
     * Test where method returns QueryBuilder
     */
    public function testWhereReturnsQueryBuilder(): void
    {
        $builder = TestUser::where('active', 1);
        $this->assertInstanceOf(\Icebox\ActiveRecord\QueryBuilder::class, $builder);
    }

    /**
     * Test constructor with exists parameter
     */
    public function testConstructorWithExists(): void
    {
        $user = new TestUser(['name' => 'Test'], true);
        $exists = $this->getProperty($user, 'exists')->getValue($user);
        $this->assertTrue($exists);
        
        $user2 = new TestUser(['name' => 'Test'], false);
        $exists2 = $this->getProperty($user2, 'exists')->getValue($user2);
        $this->assertFalse($exists2);
    }
}
