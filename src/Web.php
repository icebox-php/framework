<?php

namespace Icebox;

use Icebox\Exception\ResourceNotFoundException;

/**
 * Web request handler
 *
 * Encapsulates request logging, route matching, controller dispatch,
 * and response sending. Intended to be used from the application's public/index.php.
 */
class Web
{
    // private $app;
    // private $routes;

    /**
     * @param App $app
     * @param Routing $routes
     */
    // public function __construct(App $app, Routing $routes)
    // {
    //     $this->app = $app;
    //     $this->routes = $routes;
    // }

    /**
     * Run the web request lifecycle.
     *
     * This method:
     * 1. Matches the current request against the route collection.
     * 2. Logs the start of the request (Rails‑style).
     * 3. Determines the controller/action for logging.
     * 4. Delegates to dispatch() to obtain a Response.
     * 5. Logs the completion of the request.
     * 6. Sends the response to the client.
     *
     * @return void
     */
    public static function run(): void
    {
        try {
            $startTime = microtime(true);
            
            $routes = include App::basePath('/config/routes.php');
            $matcher = $routes->url_matcher();

            // Log request start
            Log::requestStart();

            // Determine controller/action for logging
            $controllerAction = 'Unknown';
            if ($matcher !== false) {
                $parts = explode('::', $matcher);
                if (count($parts) === 2) {
                    $controllerAction = $parts[0] . 'Controller#' . $parts[1];
                }
            }

            Log::info(sprintf(
                'Processing by %s as HTML',
                $controllerAction
            ));

            // Dispatch request
            $response = self::dispatch($matcher);

            // Log completion
            $durationMs = round((microtime(true) - $startTime) * 1000, 1);
            $status = $response->getStatusCode();
            $reason = $status >= 200 && $status < 300 ? 'OK' : 'Error';

            Log::info(sprintf(
                'Completed %d %s in %dms',
                $status,
                $reason,
                $durationMs
            ));

            // Send response
            $response->send();
        } catch (\Throwable $e) {
            $response = Error::exceptionResponse($e);
            $response->send();
        }
    }

    /**
     * Dispatch the request to the appropriate controller action.
     *
     * @param mixed $matcher The route matcher result
     * @return Response
     * @throws ResourceNotFoundException If route not found
     * @throws \Exception If controller/action issues
     */
    private static function dispatch($matcher): Response
    {
        if ($matcher === false) {
            throw new ResourceNotFoundException();
        }

        $matcher_parts = self::clip_action($matcher);
        [$controllerInstance, $action] = $matcher_parts;

        // Check if method exists and is public
        if (!method_exists($controllerInstance, $action)) {
            throw new \Exception(
                sprintf(
                    'Method %s::%s does not exist',
                    get_class($controllerInstance),
                    $action
                )
            );
        }

        $reflection = new \ReflectionMethod($controllerInstance, $action);
        if (!$reflection->isPublic()) {
            throw new \Exception(
                sprintf(
                    'Method %s::%s must be public',
                    get_class($controllerInstance),
                    $action
                )
            );
        }

        // TODO: Call before action
        // if returned value from any before_action is a "Response Object" and has response code 301 or 302
        // return this response object

        $response = $controllerInstance->$action();
        if ($response === null) {
            throw new \Exception('Controller action must return a Response object');
        }

        // TODO: Call after action

        return $response;
    }

    private static function clip_action($matcher) {
        $parts = explode('::', $matcher);

        if (str_ends_with($parts[0], 'Controller')) {
            $controller = $parts[0];
        } else {
            $controller = "\App\Controller\\{$parts[0]}Controller";
        }

        $action = $parts[1];

        App::$controller = $controller;
        App::$action = $action;

        return array(new $controller, $action);
    }
}
