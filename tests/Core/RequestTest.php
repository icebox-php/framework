<?php

namespace Icebox\Tests\Core;

use Icebox\Tests\TestCase;
use Icebox\Request;

/**
 * Test for Request class
 */
class RequestTest extends TestCase
{
    /**
     * Test params method with no key returns all params
     */
    public function testParamsReturnsAllParams(): void
    {
        // Set up some params
        Request::set_param('name', 'John');
        Request::set_param('age', 30);
        
        $params = Request::params();
        
        $this->assertEquals(['name' => 'John', 'age' => 30], $params);
    }

    /**
     * Test params method with specific key
     */
    public function testParamsWithKeyReturnsValue(): void
    {
        Request::set_param('name', 'John');
        
        $value = Request::params('name');
        
        $this->assertEquals('John', $value);
    }

    /**
     * Test params method with non-existent key returns null
     */
    public function testParamsWithNonExistentKeyReturnsNull(): void
    {
        $value = Request::params('non_existent');
        
        $this->assertNull($value);
    }

    /**
     * Test set_param method
     */
    public function testSetParam(): void
    {
        Request::set_param('test', 'value');
        
        $this->assertEquals('value', Request::params('test'));
    }

    /**
     * Test clear_params method
     */
    public function testClearParams(): void
    {
        Request::set_param('test1', 'value1');
        Request::set_param('test2', 'value2');
        
        Request::clear_params();
        
        $this->assertEquals([], Request::params());
    }

    /**
     * Test method returns GET by default
     */
    public function testMethodReturnsGetByDefault(): void
    {
        // Reset server variables
        $_SERVER = [];
        
        $method = Request::method();
        
        $this->assertEquals('get', $method);
    }

    /**
     * Test method returns POST when REQUEST_METHOD is POST
     */
    public function testMethodReturnsPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        $method = Request::method();
        
        $this->assertEquals('post', $method);
    }

    /**
     * Test method returns lowercase method
     */
    public function testMethodReturnsLowercase(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        
        $method = Request::method();
        
        $this->assertEquals('delete', $method);
    }

    /**
     * Test method with _method parameter for method override
     */
    public function testMethodWithMethodOverride(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_method'] = 'PUT';
        
        $method = Request::method();
        
        $this->assertEquals('put', $method);
    }

    /**
     * Test method override only works with POST
     */
    public function testMethodOverrideOnlyWithPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST['_method'] = 'PUT';
        
        $method = Request::method();
        
        $this->assertEquals('get', $method);
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        Request::clear_params();
        parent::tearDown();
    }
}
