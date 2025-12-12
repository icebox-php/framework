<?php

namespace Icebox\Tests\Exception;

use Icebox\Tests\TestCase;
use Icebox\Exception\ResourceNotFoundException;

/**
 * Test for ResourceNotFoundException
 */
class ResourceNotFoundExceptionTest extends TestCase
{
    /**
     * Test exception is instance of RuntimeException
     */
    public function testIsInstanceOfRuntimeException(): void
    {
        $exception = new ResourceNotFoundException();
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    /**
     * Test exception can be thrown and caught
     */
    public function testCanBeThrownAndCaught(): void
    {
        try {
            throw new ResourceNotFoundException('Resource not found');
            $this->fail('Exception should have been thrown');
        } catch (ResourceNotFoundException $e) {
            $this->assertEquals('Resource not found', $e->getMessage());
        }
    }

    /**
     * Test exception with default message
     */
    public function testDefaultMessage(): void
    {
        $exception = new ResourceNotFoundException();
        $this->assertEquals('', $exception->getMessage());
    }

    /**
     * Test exception with custom message and code
     */
    public function testWithMessageAndCode(): void
    {
        $exception = new ResourceNotFoundException('Custom message', 404);
        $this->assertEquals('Custom message', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    /**
     * Test exception with previous exception
     */
    public function testWithPreviousException(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new ResourceNotFoundException('Wrapper', 0, $previous);
        
        $this->assertEquals('Wrapper', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
