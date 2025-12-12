<?php

namespace Icebox\Tests\ActiveRecord;

use Icebox\Tests\TestCase;
use Icebox\ActiveRecord\Config;
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
        Config::initialize(function() {
            return [
                'driver' => 'sqlite',
                'host' => ':memory:',
                'database' => ':memory:',
                'username' => '',
                'password' => '',
                'charset' => 'utf8'
            ];
        });
        
        $this->assertTrue(Config::isInitialized());
        
        $connection = Config::getConnection();
        $this->assertInstanceOf(\PDO::class, $connection);
        
        // Clean up
        Config::closeConnection();
    }

    /**
     * Test initialize with invalid configuration throws exception
     */
    public function testInitializeWithInvalidConfigThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Config::initialize(function() {
            return []; // Missing required keys
        });
    }

    /**
     * Test initialize with non-array return throws exception
     */
    public function testInitializeWithNonArrayThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Config::initialize(function() {
            return 'not an array';
        });
    }

    /**
     * Test getConnection throws exception when not initialized
     */
    public function testGetConnectionThrowsExceptionWhenNotInitialized(): void
    {
        $this->expectException(\RuntimeException::class);
        
        // Ensure connection is closed
        if (Config::isInitialized()) {
            Config::closeConnection();
        }
        
        Config::getConnection();
    }

    /**
     * Test get and set configuration values
     */
    public function testGetAndSetConfig(): void
    {
        Config::set('test_key', 'test_value');
        
        $value = Config::get('test_key');
        $this->assertEquals('test_value', $value);
        
        // Test default value
        $default = Config::get('non_existent', 'default');
        $this->assertEquals('default', $default);
    }

    /**
     * Test isInitialized returns correct state
     */
    public function testIsInitialized(): void
    {
        // Start with no connection
        if (Config::isInitialized()) {
            Config::closeConnection();
        }
        
        $this->assertFalse(Config::isInitialized());
        
        // Initialize
        Config::initialize(function() {
            return [
                'driver' => 'sqlite',
                'host' => ':memory:',
                'database' => ':memory:',
                'username' => '',
                'password' => '',
                'charset' => 'utf8'
            ];
        });
        
        $this->assertTrue(Config::isInitialized());
        
        // Close connection
        Config::closeConnection();
        $this->assertFalse(Config::isInitialized());
    }

    /**
     * Test closeConnection
     */
    public function testCloseConnection(): void
    {
        Config::initialize(function() {
            return [
                'driver' => 'sqlite',
                'host' => ':memory:',
                'database' => ':memory:',
                'username' => '',
                'password' => '',
                'charset' => 'utf8'
            ];
        });
        
        $this->assertTrue(Config::isInitialized());
        
        Config::closeConnection();
        
        $this->assertFalse(Config::isInitialized());
    }

    protected function tearDown(): void
    {
        // Ensure connection is closed after each test
        if (Config::isInitialized()) {
            Config::closeConnection();
        }
        
        parent::tearDown();
    }
}
