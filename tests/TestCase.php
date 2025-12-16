<?php

namespace Icebox\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for all Icebox tests
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        TestHelper::resetHttpContext();
        
        // Start output buffering to capture any accidental output
        ob_start();
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        // Clean up output buffering
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        parent::tearDown();
    }

    /**
     * Assert that two arrays are equal ignoring order
     */
    public static function assertArraysEqual(array $expected, array $actual, string $message = ''): void
    {
        sort($expected);
        sort($actual);
        self::assertEquals($expected, $actual, $message);
    }

    /**
     * Assert that a string contains another string
     */
    public static function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        self::assertStringContainsString($needle, $haystack, $message);
    }

    /**
     * Assert that a string does not contain another string
     */
    public static function assertStringNotContains(string $needle, string $haystack, string $message = ''): void
    {
        self::assertStringNotContainsString($needle, $haystack, $message);
    }

    /**
     * Get protected/private method for testing
     */
    protected function getMethod(object $object, string $methodName): \ReflectionMethod
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }
        return $method;
    }

    /**
     * Get protected/private property for testing
     */
    protected function getProperty(object $object, string $propertyName): \ReflectionProperty
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        return $property;
    }

    /**
     * Set protected/private property for testing
     */
    protected function setProperty(object $object, string $propertyName, $value): void
    {
        $property = $this->getProperty($object, $propertyName);
        $property->setValue($object, $value);
    }
}
