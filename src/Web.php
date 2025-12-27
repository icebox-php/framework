<?php

namespace Icebox;

use Icebox\Exception\ResourceNotFoundException;
use ErrorException;
use Exception;
use Error;

/**
 * Web request handler
 *
 * Encapsulates request logging, route matching, controller dispatch,
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
     * 4. Delegates to App::handle() to obtain a Response.
     * 5. Logs the completion of the request.
     * 6. Sends the response to the client.
     *
     * @return void
     */
    public static function run(): void
    {
        $routes = include App::basePath('/config/routes.php');
        $matcher = $routes->url_matcher();

        $requestId = Log::getRequestId();
        $startTime = microtime(true);

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
            '%s Processing by %s as HTML',
            $requestId,
            $controllerAction
        ));

        // Handle request
        $response = self::handle($matcher);

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

    public static function handle($matcher) {

        try {

            if($matcher === false) {
                throw new ResourceNotFoundException();
            }

            //==============================================

            $matcher_parts = self::clip_action($matcher);

            if(method_exists($matcher_parts[0], $matcher_parts[1]) && is_callable($matcher_parts)) {

                // TODO: Call before action
                // if returned value from any before_action is a "Response Object" and has response code 301 or 302
                // return this response object

                $response = call_user_func($matcher_parts);
                if($response === null) { throw new \Exception('"May be, you forgot to \'return $this->render()\' from controller::action"'); }

                // TODO: Call after action

                return $response;
            } else {
                throw new \Exception(
                  sprintf(
                    "Can not call %sController::%s. Please check if this function exists, or the function is public",
                    App::$controller, App::$action
                  )
                );
            }

        } catch (ResourceNotFoundException $e) {

            return new Response('Not Found', 404);

        } catch(ErrorException $e) {
            $msg = '';

            if(Utils::env('DEBUG') == true) {
              $msg .= "ErrorException: ".$e->getMessage();
              $msg .= "\n<br>\n";
              $msg .= Debug::details($e);
            } else {
              $msg = 'An error occurred';
            }

            // Debug::details($e);

            return new Response($msg, 500);
        } catch (Exception $e) {

          $msg = '';

          if(Utils::env('DEBUG') == true) {
            $msg .= "Exception: ".$e->getMessage();
            $msg .= "\n<br>\n";
            $msg .= Debug::details($e);
          } else {
            $msg = 'An error occurred';
          }

          return new Response($msg, 500);

        } catch(Error $e) {
          $msg = '';

          if(Utils::env('DEBUG') == true) {
            $msg .= "Error: ".$e->getMessage();
            $msg .= "\n<br>\n";
            $msg .= Debug::details($e);
          } else {
            $msg = 'An error occurred';
          }
          return new Response($msg, 500);
        } finally {
            restore_error_handler();
        }

    }

    private static function clip_action($matcher) {
        $parts = explode('::', $matcher);

        App::$controller = $parts[0];
        App::$action = $parts[1];

        $controller = App::$controller_namespace . $parts[0] . 'Controller';
        $action = $parts[1];

        return array(new $controller, $action);
    }
}
