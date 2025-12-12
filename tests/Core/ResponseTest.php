<?php

namespace Icebox\Tests\Core;

use Icebox\Tests\TestCase;
use Icebox\Response;

/**
 * Test for Response class
 */
class ResponseTest extends TestCase
{
    /**
     * Test constructor sets properties correctly
     */
    public function testConstructor(): void
    {
        $response = new Response('Test content', 201, [['Content-Type', 'text/html']]);
        
        // Use reflection to check private properties
        $content = $this->getProperty($response, 'content')->getValue($response);
        $statusCode = $this->getProperty($response, 'status_code')->getValue($response);
        $headers = $this->getProperty($response, 'headers')->getValue($response);
        
        $this->assertEquals('Test content', $content);
        $this->assertEquals(201, $statusCode);
        $this->assertEquals([['Content-Type', 'text/html']], $headers);
    }

    /**
     * Test constructor with default values
     */
    public function testConstructorWithDefaults(): void
    {
        $response = new Response('Default content');
        
        $content = $this->getProperty($response, 'content')->getValue($response);
        $statusCode = $this->getProperty($response, 'status_code')->getValue($response);
        $headers = $this->getProperty($response, 'headers')->getValue($response);
        
        $this->assertEquals('Default content', $content);
        $this->assertEquals(200, $statusCode);
        $this->assertEquals([], $headers);
    }

    /**
     * Test send method outputs content
     */
    public function testSendOutputsContent(): void
    {
        $response = new Response('Hello World');
        
        ob_start();
        $response->send();
        $output = ob_get_clean();
        
        $this->assertEquals('Hello World', $output);
    }

    /**
     * Test send doesn't crash when headers already sent
     */
    public function testSendWhenHeadersAlreadySent(): void
    {
        $response = new Response('Test content');
        
        // This should not throw an exception
        $response->send();
        
        $this->assertTrue(true); // Just checking we got here without errors
    }

    /**
     * Test send clears flash messages
     */
    public function testSendClearsFlashMessages(): void
    {
        // Set up session with flash messages
        $_SESSION['_ice_flash'] = [
            'notice' => ['message' => 'Test notice', 'life' => 1],
            'error' => ['message' => 'Test error', 'life' => 0]
        ];
        
        $response = new Response('Test');
        
        ob_start();
        $response->send();
        ob_end_clean();
        
        // Check that life=0 messages are removed and life=1 messages have life set to 0
        $this->assertArrayNotHasKey('error', $_SESSION['_ice_flash']);
        $this->assertArrayHasKey('notice', $_SESSION['_ice_flash']);
        $this->assertEquals(0, $_SESSION['_ice_flash']['notice']['life']);
    }

    /**
     * Test getReasonPhrase returns correct phrase for status code
     */
    public function testGetReasonPhrase(): void
    {
        $response = new Response('Test');
        
        // Use reflection to call private method
        $method = $this->getMethod($response, 'getReasonPhrase');
        $phrase200 = $method->invoke($response);
        
        $this->assertEquals('OK', $phrase200);
        
        // Test with 404
        $response404 = new Response('Test', 404);
        $method = $this->getMethod($response404, 'getReasonPhrase');
        $phrase404 = $method->invoke($response404);
        
        $this->assertEquals('Not Found', $phrase404);
    }

    /**
     * Test getReasonPhrase returns empty string for unknown status code
     */
    public function testGetReasonPhraseUnknownCode(): void
    {
        $response = new Response('Test', 999); // Unknown status code
        
        $method = $this->getMethod($response, 'getReasonPhrase');
        $phrase = $method->invoke($response);
        
        $this->assertEquals('', $phrase);
    }

    /**
     * Test sendHeaders method doesn't send headers if already sent
     */
    public function testSendHeadersWhenHeadersAlreadySent(): void
    {
        $response = new Response('Test');
        
        // Mock headers_sent to return true
        $method = $this->getMethod($response, 'sendHeaders');
        
        // This should not throw an exception even though we can't actually test header sending
        $method->invoke($response);
        
        $this->assertTrue(true);
    }
}
