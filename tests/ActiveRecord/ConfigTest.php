<?php

namespace Icebox\Tests\ActiveRecord;

use Icebox\Tests\TestCase;
use Icebox\Tests\TestHelper;
use Icebox\ActiveRecord\Config as ArConfig;
use PDOException;

/**
 * Test for Config class
 */
class ConfigTest extends TestCase
{
    /**
     * Test initialize with valid configuration
     */
    public function testInitializeWithValidConfig(): void
    {
        TestHelper::connectToDatabase();
        
        $this->assertTrue(ArConfig::isInitialized());
        
        $connection = ArConfig::getConnection();
        $this->assertInstanceOf(\PDO::class, $connection);
        
        // Clean up
        ArConfig::closeConnection();
    }

    /**
     * Test initialize with invalid configuration throws exception
     */
    // public function testInitializeWithInvalidConfigThrowsException(): void
    // {
    //     $this->expectException(\InvalidArgumentException::class);
        
    //     ArConfig::initialize(function() {
    //         return []; // Missing required keys
    //     });
    // }

    /**
     * Test initialize with non-array return throws exception
     */
    // public function testInitializeWithNonArrayThrowsException(): void
    // {
    //     $this->expectException(\InvalidArgumentException::class);
        
    //     ArConfig::initialize(function() {
    //         return 'not an array';
    //     });
    // }

    /**
     * Test getConnection throws exception when not initialized
     */
    public function testGetConnectionThrowsExceptionWhenNotInitialized(): void
    {
        $this->expectException(\RuntimeException::class);
        
        // Ensure connection is closed
        if (ArConfig::isInitialized()) {
            ArConfig::closeConnection();
        }
        
        ArConfig::getConnection();
    }

    /**
     * Test get and set configuration values
     */
    public function testGetAndSetConfig(): void
    {
        ArConfig::set('test_key', 'test_value');
        
        $value = ArConfig::get('test_key');
        $this->assertEquals('test_value', $value);
        
        // Test default value
        $default = ArConfig::get('non_existent', 'default');
        $this->assertEquals('default', $default);
    }

    /**
     * Test isInitialized returns correct state
     */
    public function testIsInitialized(): void
    {
        // Start with no connection
        if (ArConfig::isInitialized()) {
            ArConfig::closeConnection();
        }
        
        $this->assertFalse(ArConfig::isInitialized());
        
        // Initialize
        TestHelper::connectToDatabase();
        
        $this->assertTrue(ArConfig::isInitialized());
        
        // Close connection
        ArConfig::closeConnection();
        $this->assertFalse(ArConfig::isInitialized());
    }

    /**
     * Test closeConnection
     */
    public function testCloseConnection(): void
    {
        TestHelper::connectToDatabase();
        
        $this->assertTrue(ArConfig::isInitialized());
        
        ArConfig::closeConnection();
        
        $this->assertFalse(ArConfig::isInitialized());
    }

    protected function tearDown(): void
    {
        // Ensure connection is closed after each test
        if (ArConfig::isInitialized()) {
            ArConfig::closeConnection();
        }
        
        parent::tearDown();
    }
}
