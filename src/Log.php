<?php

namespace Icebox;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Simple yet powerful, full-featured logger for the Icebox Framework
 * 
 * Usage:
 *   // Add handlers (no default handlers)
 *   Icebox\Log::addFileHandler('log/development.log'); // Creates dated files: app-2025-12-21.log
 *   Icebox\Log::addStdoutHandler();
 *   Icebox\Log::addSyslogHandler('myapp');
 *   Icebox\Log::addClosureHandler(function($log) {
 *       // Custom handling (e.g., Sentry, Airbrake)
 *   });
 * 
 *   // Log messages
 *   Icebox\Log::info('User logged in');
 *   Icebox\Log::error('Database connection failed', ['db' => 'primary']);
 * 
 * All PSR-3 levels are supported:
 *   emergency, alert, critical, error, warning, notice, info, debug
 */
class Log
{
    /**
     * Level map for converting string levels to Monolog integer values
     */
    private const LEVEL_MAP = [
        'debug' => Level::Debug->value,
        'info' => Level::Info->value,
        'notice' => Level::Notice->value,
        'warning' => Level::Warning->value,
        'error' => Level::Error->value,
        'critical' => Level::Critical->value,
        'alert' => Level::Alert->value,
        'emergency' => Level::Emergency->value,
    ];

    /**
     * @var LoggerInterface|null Singleton logger instance
     */
    private static $logger = null;

    /**
     * @var array Registered handlers
     */
    private static $handlers = [];

    /**
     * @var string unique Request id in a specific time
     */
    private static $requestId = null;

    /**
     * @var string Default log line format
     */
    private const DEFAULT_LINE_FORMAT = '%datetime% %channel% - %level_name% - %message%';

    /**
     * @var string Default log datetime format
     */
    private const DEFAULT_DATETIME_FORMAT = 'Y-m-d\TH:i:s.uP';

    /**
     * @var string Default log channel name
     */
    private const DEFAULT_CHANNEL = 'icebox';

    /**
     * Get the logger instance, creating it if necessary
     * 
     * @return LoggerInterface
     */
    private static function getLogger(): LoggerInterface
    {
        if (self::$logger === null) {
            self::$logger = self::createLogger();
        }

        return self::$logger;
    }

    /**
     * Create and configure the logger instance
     * 
     * @return LoggerInterface
     */
    private static function createLogger(): LoggerInterface
    {
        $logger = new Logger(self::DEFAULT_CHANNEL);
        
        foreach (self::$handlers as $handler) {
            $logger->pushHandler($handler);
        }
        
        return $logger;
    }

    public static function getRequestId(): string
    {
        return self::$requestId ?? self::generateRequestId();
    }

    /**
     * Generate a short unique (in a specific time) request ID.
     *
     * @param int $length Number of hex characters (default 6)
     * @return string
     */
    private static function generateRequestId(int $length = 6): string
    {
        self::$requestId = bin2hex(random_bytes($length / 2));
        return self::$requestId;
    }

    /**
     * Add a file handler with rotation (creates dated files like app-2025-12-21.log)
     * 
     * @param string $path Log file path (e.g., 'log/development.log')
     * @param string $level Log level (debug, info, warning, error, etc.)
     * @param int $maxFiles Maximum number of files to keep (default: 7)
     * @return void
     */
    public static function addFileHandler(string $path, string $level = 'debug', int $maxFiles = 7): void
    {
        // Ensure directory exists
        $logDir = dirname($path);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $handler = new RotatingFileHandler($path, $maxFiles, self::normalizeLevel($level));
        $handler->setFormatter(self::createLineFormatter());
        
        self::$handlers[] = $handler;
        self::resetLogger();
    }

    /**
     * Add a syslog handler
     * 
     * @param string $ident Ident string
     * @param string $level Log level (debug, info, warning, error, etc.) 
     * @param int $facility Syslog facility (default: LOG_USER)
     * @return void
     */
    public static function addSyslogHandler(string $ident = 'icebox', string $level = 'debug', int $facility = LOG_USER): void
    {
        $handler = new SyslogHandler($ident, $facility, self::normalizeLevel($level));
        self::$handlers[] = $handler;
        self::resetLogger();
    }

    /**
     * Add a stdout handler
     * 
     * @param string $level Log level (debug, info, warning, error, etc.)
     * @return void
     */
    public static function addStdoutHandler(string $level = 'debug'): void
    {
        $handler = new StreamHandler('php://stdout', self::normalizeLevel($level));
        
        // Colorful output for CLI
        if (php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg') {
            $formatter = new LineFormatter(
                self::DEFAULT_LINE_FORMAT . "\n",
                self::DEFAULT_DATETIME_FORMAT,
                true,
                true
            );
            $handler->setFormatter($formatter);
        } else {
            $handler->setFormatter(self::createLineFormatter());
        }
        
        self::$handlers[] = $handler;
        self::resetLogger();
    }

    /**
     * Add a closure handler for custom logging (e.g., Sentry, Airbrake)
     * 
     * @param \Closure $closure Function that receives log data
     * @param string $level Log level (debug, info, warning, error, etc.)
     * @return void
     */
    public static function addClosureHandler(\Closure $closure, string $level = 'error'): void
    {
        $handler = new class($closure, self::normalizeLevel($level)) extends \Monolog\Handler\AbstractHandler {
            private $closure;
            
            public function __construct(\Closure $closure, $level)
            {
                parent::__construct($level, true);
                $this->closure = $closure;
            }
            
            public function handle(\Monolog\LogRecord $record): bool
            {
                if (!$this->isHandling($record)) {
                    return false;
                }
                
                try {
                    // Convert Monolog record to simple object
                    $log = new \stdClass();
                    $log->level = strtolower($record->level->getName());
                    $log->message = $record->message;
                    $log->context = $record->context;
                    $log->channel = $record->channel;
                    $log->datetime = $record->datetime;
                    
                    ($this->closure)($log);
                } catch (\Exception $e) {
                    // Silently ignore closure errors to not break other handlers
                }
                
                return false; // Don't bubble to other handlers
            }
        };
        
        self::$handlers[] = $handler;
        self::resetLogger();
    }

    /**
     * Clear all handlers (useful for testing)
     * 
     * @return void
     */
    public static function clearHandlers(): void
    {
        self::$handlers = [];
        self::$logger = null;
    }

    /**
     * Get the number of registered handlers
     * 
     * @return int
     */
    public static function handlerCount(): int
    {
        return count(self::$handlers);
    }

    /**
     * Create a standard line formatter
     * 
     * @return LineFormatter
     */
    private static function createLineFormatter(): LineFormatter
    {
        return new LineFormatter(
            self::DEFAULT_LINE_FORMAT . "\n",
            self::DEFAULT_DATETIME_FORMAT
        );
    }

    /**
     * Normalize log level string to Monolog constant
     * 
     * @param string $level
     * @return int
     */
    private static function normalizeLevel(string $level): int
    {
        $level = strtolower($level);
        return self::LEVEL_MAP[$level] ?? self::LEVEL_MAP['debug'];
    }

    /**
     * Reset logger instance (when handlers change)
     * 
     * @return void
     */
    private static function resetLogger(): void
    {
        self::$logger = null;
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function emergency($message, array $context = []): void
    {
        if (empty(self::$handlers)) {
            return; // Silently ignore if no handlers
        }
        self::getLogger()->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function alert($message, array $context = []): void
    {
        if (empty(self::$handlers)) {
            return; // Silently ignore if no handlers
        }
        self::getLogger()->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function critical($message, array $context = []): void
    {
        if (empty(self::$handlers)) {
            return; // Silently ignore if no handlers
        }
        self::getLogger()->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error($message, array $context = []): void
    {
        if (empty(self::$handlers)) {
            return; // Silently ignore if no handlers
        }
        self::getLogger()->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function warning($message, array $context = []): void
    {
        if (empty(self::$handlers)) {
            return; // Silently ignore if no handlers
        }
        self::getLogger()->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function notice($message, array $context = []): void
    {
        if (empty(self::$handlers)) {
            return; // Silently ignore if no handlers
        }
        self::getLogger()->notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info($message, array $context = []): void
    {
        if (empty(self::$handlers)) {
            return; // Silently ignore if no handlers
        }
        self::getLogger()->info($message, $context);
    }

    private static function forceInfo($message, array $context = []): void
    {
        if (empty(self::$handlers)) {
            return;
        }
        
        $logger = self::getLogger();
        $originalLevels = [];
        
        try {
            foreach (self::$handlers as $i => $handler) {
                $originalLevels[$i] = $handler->getLevel();
                $handler->setLevel(Level::Debug);
            }
            
            $logger->info($message, $context);
        } finally {
            // Always restore, even if exception thrown
            foreach (self::$handlers as $i => $handler) {
                if (isset($originalLevels[$i])) {
                    $handler->setLevel($originalLevels[$i]);
                }
            }
        }
    }

    public static function requestStart() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $timestamp = gmdate('Y-m-d H:i:s') . ' +0000';

        Log::forceInfo(sprintf(
            '%s Started %s "%s" for %s at %s',
            self::getRequestId(),
            strtoupper($method),
            $path,
            $clientIp,
            $timestamp
        ));
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug($message, array $context = []): void
    {
        if (empty(self::$handlers)) {
            return; // Silently ignore if no handlers
        }
        self::getLogger()->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function log($level, $message, array $context = []): void
    {
        if (empty(self::$handlers)) {
            return; // Silently ignore if no handlers
        }
        self::getLogger()->log($level, $message, $context);
    }
}
