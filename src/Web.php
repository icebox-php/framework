<?php

namespace Icebox;

use Icebox\Exception\ResourceNotFoundException;

/**
 * Web request handler
 *
 * Encapsulates Rails‑style request logging, route matching, controller dispatch,
 * and response sending. Intended to be used from the application's public/index.php.
 */
class Web
{
    private $app;
    private $routes;

    /**
     * @param App $app
     * @param Routing $routes
     */
    public function __construct(App $app, Routing $routes)
    {
        $this->app = $app;
        $this->routes = $routes;
    }

    /**
     * Run the web request lifecycle.
     *
     * This method:
     * 1. Matches the current request against the route collection.
     * 2. Logs the start of the request (Rails‑style).
     * 3. Determines the controller/action for logging.
     * 4. Delegates to App::handle() to obtain a Response.
     * 5. Logs the completion of the request.
     * 6. Sends the response to the client.
     *
     * @return void
     */
    public function run(): void
    {
        $matcher = $this->routes->url_matcher();

        $requestId = $this->generateRequestId();
        $startTime = microtime(true);

        // Log request start
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $timestamp = gmdate('Y-m-d H:i:s') . ' +0000';

        Log::info(sprintf(
            '%s Started %s "%s" for %s at %s',
            $requestId,
            strtoupper($method),
            $path,
            $clientIp,
            $timestamp
        ));

        // Determine controller/action for logging
        $controllerAction = 'Unknown';
        if ($matcher !== false) {
            $parts = explode('::', $matcher);
            if (count($parts) === 2) {
                $controllerAction = $parts[0] . 'Controller#' . $parts[1];
            }
        }

        Log::info(sprintf(
            '%s Processing by %s as HTML',
            $requestId,
            $controllerAction
        ));

        // Handle request
        $response = $this->app->handle($matcher);

        // Log completion
        $durationMs = round((microtime(true) - $startTime) * 1000, 1);
        $status = $response->getStatusCode();
        $reason = $status >= 200 && $status < 300 ? 'OK' : 'Error';

        Log::info(sprintf(
            '%s Completed %d %s in %dms',
            $requestId,
            $status,
            $reason,
            $durationMs
        ));

        // Send response
        $response->send();
    }

    /**
     * Generate a short unique request ID.
     *
     * @param int $length Number of hex characters (default 6)
     * @return string
     */
    private function generateRequestId(int $length = 6): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}