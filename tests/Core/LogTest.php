<?php

namespace Icebox\Tests\Core;

use Icebox\Log;
use Icebox\Tests\TestCase;

/**
 * Test for Log class
 */
class LogTest extends TestCase
{
    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        Log::clearHandlers();
    }

    /**
     * Test initial state has no handlers
     */
    public function testInitialStateHasNoHandlers(): void
    {
        $this->assertEquals(0, Log::handlerCount());
    }

    /**
     * Test adding file handler increases handler count
     */
    public function testAddFileHandlerIncreasesHandlerCount(): void
    {
        $initialCount = Log::handlerCount();
        Log::addFileHandler('log/test.log');
        $this->assertEquals($initialCount + 1, Log::handlerCount());
    }

    /**
     * Test adding stdout handler increases handler count
     */
    public function testAddStdoutHandlerIncreasesHandlerCount(): void
    {
        $initialCount = Log::handlerCount();
        Log::addStdoutHandler();
        $this->assertEquals($initialCount + 1, Log::handlerCount());
    }

    /**
     * Test adding syslog handler increases handler count
     */
    public function testAddSyslogHandlerIncreasesHandlerCount(): void
    {
        $initialCount = Log::handlerCount();
        Log::addSyslogHandler('test-app');
        $this->assertEquals($initialCount + 1, Log::handlerCount());
    }

    /**
     * Test adding closure handler increases handler count
     */
    public function testAddClosureHandlerIncreasesHandlerCount(): void
    {
        $initialCount = Log::handlerCount();
        Log::addClosureHandler(function($log) {});
        $this->assertEquals($initialCount + 1, Log::handlerCount());
    }

    /**
     * Test clear handlers resets handler count
     */
    public function testClearHandlersResetsHandlerCount(): void
    {
        Log::addFileHandler('log/test.log');
        Log::addStdoutHandler();
        $this->assertGreaterThan(0, Log::handlerCount());
        
        Log::clearHandlers();
        $this->assertEquals(0, Log::handlerCount());
    }

    /**
     * Test logging without handlers does nothing (no errors)
     */
    public function testLoggingWithoutHandlersDoesNothing(): void
    {
        // This should not throw any exceptions
        Log::info('Test message');
        Log::error('Error message', ['context' => 'data']);
        $this->assertTrue(true); // Just verify we reached this point
    }

    /**
     * Test closure handler captures log data
     */
    public function testClosureHandlerCapturesLogData(): void
    {
        /** @var object|null $capturedLog */
        $capturedLog = null;
        
        Log::addClosureHandler(function($log) use (&$capturedLog) {
            $capturedLog = $log;
        }, 'info');
        
        Log::info('Test message', ['user_id' => 123]);
        
        $this->assertNotNull($capturedLog);
        $this->assertIsObject($capturedLog);
        /** @var object $capturedLog */
        $this->assertEquals('info', $capturedLog->level);
        $this->assertEquals('Test message', $capturedLog->message);
        $this->assertEquals(['user_id' => 123], $capturedLog->context);
        $this->assertEquals('icebox', $capturedLog->channel);
        $this->assertInstanceOf(\DateTimeInterface::class, $capturedLog->datetime);
    }

    /**
     * Test all log levels work with closure handler
     */
    public function testAllLogLevelsWorkWithClosureHandler(): void
    {
        $capturedLevels = [];
        
        Log::addClosureHandler(function($log) use (&$capturedLevels) {
            $capturedLevels[] = $log->level;
        }, 'debug');
        
        // Test all PSR-3 levels
        Log::emergency('Emergency message');
        Log::alert('Alert message');
        Log::critical('Critical message');
        Log::error('Error message');
        Log::warning('Warning message');
        Log::notice('Notice message');
        Log::info('Info message');
        Log::debug('Debug message');
        
        $expectedLevels = [
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            'debug'
        ];
        
        $this->assertEquals($expectedLevels, $capturedLevels);
    }

    /**
     * Test context data is passed to handler
     */
    public function testContextDataIsPassedToHandler(): void
    {
        $capturedContext = null;
        
        Log::addClosureHandler(function($log) use (&$capturedContext) {
            $capturedContext = $log->context;
        }, 'info');
        
        $testContext = [
            'user_id' => 123,
            'action' => 'login',
            'ip' => '192.168.1.1'
        ];
        
        Log::info('User action', $testContext);
        
        $this->assertEquals($testContext, $capturedContext);
    }

    /**
     * Test generic log method works
     */
    public function testGenericLogMethodWorks(): void
    {
        /** @var object|null $capturedLog */
        $capturedLog = null;
        
        Log::addClosureHandler(function($log) use (&$capturedLog) {
            $capturedLog = $log;
        }, 'warning');
        
        Log::log('warning', 'Generic log message', ['data' => 'test']);
        
        $this->assertNotNull($capturedLog);
        $this->assertIsObject($capturedLog);
        /** @var object $capturedLog */
        $this->assertEquals('warning', $capturedLog->level);
        $this->assertEquals('Generic log message', $capturedLog->message);
        $this->assertEquals(['data' => 'test'], $capturedLog->context);
    }

    /**
     * Test normalizeLevel converts string to Monolog constant
     */
    public function testNormalizeLevelConvertsStringToMonologConstant(): void
    {
        // Use reflection to test private static method
        $reflection = new \ReflectionClass(Log::class);
        $method = $reflection->getMethod('normalizeLevel');
        if (PHP_VERSION_ID < 80100) {
          $method->setAccessible(true);
        }
        
        // Test all valid levels
        $this->assertEquals(\Monolog\Level::Debug->value, $method->invoke(null, 'debug'));
        $this->assertEquals(\Monolog\Level::Info->value, $method->invoke(null, 'info'));
        $this->assertEquals(\Monolog\Level::Notice->value, $method->invoke(null, 'notice'));
        $this->assertEquals(\Monolog\Level::Warning->value, $method->invoke(null, 'warning'));
        $this->assertEquals(\Monolog\Level::Error->value, $method->invoke(null, 'error'));
        $this->assertEquals(\Monolog\Level::Critical->value, $method->invoke(null, 'critical'));
        $this->assertEquals(\Monolog\Level::Alert->value, $method->invoke(null, 'alert'));
        $this->assertEquals(\Monolog\Level::Emergency->value, $method->invoke(null, 'emergency'));
        
        // Test case insensitivity
        $this->assertEquals(\Monolog\Level::Debug->value, $method->invoke(null, 'DEBUG'));
        $this->assertEquals(\Monolog\Level::Info->value, $method->invoke(null, 'INFO'));
        
        // Test default fallback for invalid level
        $this->assertEquals(\Monolog\Level::Debug->value, $method->invoke(null, 'invalid'));
    }

    /**
     * Test closure handler respects log level
     */
    public function testClosureHandlerRespectsLogLevel(): void
    {
        $capturedLogs = [];
        
        // Add handler that only captures error level and above
        Log::addClosureHandler(function($log) use (&$capturedLogs) {
            $capturedLogs[] = $log->level;
        }, 'error');
        
        // These should be captured
        Log::error('Error message');
        Log::critical('Critical message');
        Log::alert('Alert message');
        Log::emergency('Emergency message');
        
        // These should NOT be captured
        Log::warning('Warning message');
        Log::notice('Notice message');
        Log::info('Info message');
        Log::debug('Debug message');
        
        $expectedLevels = ['error', 'critical', 'alert', 'emergency'];
        $this->assertEquals($expectedLevels, $capturedLogs);
    }

    /**
     * Test multiple handlers work together
     */
    public function testMultipleHandlersWorkTogether(): void
    {
        $capture1 = [];
        $capture2 = [];
        
        Log::addClosureHandler(function($log) use (&$capture1) {
            $capture1[] = $log->level;
        }, 'info');
        
        Log::addClosureHandler(function($log) use (&$capture2) {
            $capture2[] = $log->level;
        }, 'error');
        
        Log::info('Info message');
        Log::error('Error message');
        Log::warning('Warning message');
        
        $this->assertEquals(['info', 'error', 'warning'], $capture1);
        $this->assertEquals(['error'], $capture2);
    }

    /**
     * Test closure handler exceptions don't break other handlers
     */
    public function testClosureHandlerExceptionsDontBreakOtherHandlers(): void
    {
        $capturedLogs = [];
        
        // First handler throws exception
        Log::addClosureHandler(function($log) {
            throw new \Exception('Handler error');
        }, 'info');
        
        // Second handler should still work
        Log::addClosureHandler(function($log) use (&$capturedLogs) {
            $capturedLogs[] = $log->level;
        }, 'info');
        
        // This should not throw an exception
        Log::info('Test message');
        
        $this->assertEquals(['info'], $capturedLogs);
    }

    /**
     * Test rotating file handler creates dated file
     */
    public function testRotatingFileHandlerCreatesDatedFile(): void
    {
        $testFile = 'log/test-rotating.log';
        
        // Clean up any existing test files
        foreach (glob('log/test-rotating*.log') as $file) {
            unlink($file);
        }
        
        Log::addFileHandler($testFile, 'info');
        Log::info('Test message for rotating file handler');
        
        // The exact file name should NOT exist
        $this->assertFileDoesNotExist($testFile);
        
        // But a dated version should exist
        $files = glob('log/test-rotating*.log');
        $this->assertGreaterThan(0, count($files), 'Should have at least one dated log file');
        
        // File should have date suffix pattern YYYY-MM-DD
        $datedFile = $files[0];
        $this->assertMatchesRegularExpression('/test-rotating-\d{4}-\d{2}-\d{2}\.log$/', $datedFile);
        
        // Verify the log message is written to the file
        $this->assertFileExists($datedFile);
        $fileContent = file_get_contents($datedFile);
        $this->assertStringContainsString('Test message for rotating file handler', $fileContent);
        
        // Clean up
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
