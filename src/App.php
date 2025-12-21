<?php

namespace Icebox;

use Icebox\Exception\ResourceNotFoundException;
use Icebox\Debug;

use ErrorException;
use Exception;
use Error;

if (!defined('WARNING')) { define('WARNING', false); }
if (!defined('DEBUG')) { define('DEBUG', false); }

class App {
    public static $controller_namespace  = 'App\Controller\\';

    public static $controller;
    public static $action;

    private static $index_file;
    private static $project_directory;

    // it is you application root directory;
    private static $file;

    // root url without index.php # http://localhost/ice-box
    private static $root_url;

    // root url with index.php # http://localhost/ice-box/index.php
    // really depends on your configuatuin, depends on $index_page variable in constructor
    private static $url;

    private static $url_prefix; // http or, https

    /*
    If your project is in subfolder set it here. It will be used in url
    $project_directory = '/ice-box'; # root_url() will be like: http://localhost/ice-box
    if you project root is not in subfolder, you do not need to send this parameter

    when you use .htaccess in apache, or nginx configuration to remove index.php from url,
    you do not need to send $index_page parameter
    $index_page parameter has effect on App::url() function
    */
    public function __construct($file, $project_directory = '')
    {
      self::catch_warning_error();

      $dir = dirname($file);
      $index_file = basename($file);

      self::set_index_file($index_file);
      self::set_file($dir);
      self::set_project_directory($project_directory);
      self::set_url_prefix();
      $root_url = self::set_root_url($project_directory);
      self::set_url($root_url, $index_file);
      self::init_active_record();
    }

    public static function catch_warning_error() {
      if(defined('WARNING') && WARNING == true) {
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
          if (!(error_reporting() & $errno)) {
            return;
          }
          throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
      }
    }

    public static function file($path) {
      $file_path = self::$file;
      $path_array = func_get_args();
      foreach($path_array as $value) {
          $file_path = $file_path . DIRECTORY_SEPARATOR . $value;
      }
      return $file_path;
    }

    public static function index_file() {
        return self::$index_file;
    }

    public static function project_directory() {
        return self::$project_directory;
    }

    public static function root_url($path = '', Array $params = [], $url_prefix = null) {
      // set params in path
      $prepared_path = self::append_params_to_path($path, $params);

      // set url_prefix
      if($url_prefix == null) { $url_prefix = self::$url_prefix; }

      return $url_prefix . '://' . self::$root_url . $prepared_path;
    }

    public static function url($path = '', Array $params = [], $url_prefix = null) {
      // set params in path
      $prepared_path = self::append_params_to_path($path, $params);

      // set url_prefix
      if($url_prefix == null) { $url_prefix = self::$url_prefix; }

      return $url_prefix . '://' . self::$url . $prepared_path;
    }

    public function handle($matcher) {

        try {

            if($matcher === false) {
                throw new ResourceNotFoundException();
            }

            //==============================================

            $matcher_parts = $this->clip_action($matcher);

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

            if(defined('DEBUG') && DEBUG == true) {
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

          if(defined('DEBUG') && DEBUG == true) {
            $msg .= "Exception: ".$e->getMessage();
            $msg .= "\n<br>\n";
            $msg .= Debug::details($e);
          } else {
            $msg = 'An error occurred';
          }

          return new Response($msg, 500);

        } catch(Error $e) {
          $msg = '';

          if(defined('DEBUG') && DEBUG == true) {
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

    public static function view_root() {
       return dirname(self::$file) . '/app/View';
    }

    public static function is_https_connection() {
      return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
    }

    private static function set_index_file($index_file) {
        self::$index_file = $index_file;
    }

    private static function set_file($dir) {
        self::$file = $dir;
    }

    private static function set_project_directory($project_directory) {
        self::$project_directory = $project_directory;
    }

    private static function set_url_prefix() {
      self::$url_prefix = 'http' . ( self::is_https_connection() ? 's' : '' );
    }

    private static function set_root_url($project_directory) {
      $url = $_SERVER['HTTP_HOST'];
      // if( $_SERVER['SERVER_PORT'] != 80) { $url .= ':' . $_SERVER['SERVER_PORT']; }

      // $app_directory = str_replace($_SERVER['DOCUMENT_ROOT'], '', $dir);
      // self::$root_url = $url . $app_directory;

      self::$root_url = $url . $project_directory;

      return self::$root_url;
    }

    private static function set_url($root_url, $index_page) {
      self::$url = $root_url . '/' . $index_page;
    }

    private static function init_active_record() {
      // $capsule = new \Illuminate\Database\Capsule\Manager;
      // $config = include(self::file('Config', 'database.php'));
      // $capsule->addConnection($config);
      // $capsule->bootEloquent();
    }

    private function clip_action($matcher) {
        $parts = explode('::', $matcher);

        App::$controller = $parts[0];
        App::$action = $parts[1];

        $controller = App::$controller_namespace . $parts[0] . 'Controller';
        $action = $parts[1];

        return array(new $controller, $action);
    }

    private static function append_params_to_path($path = '', Array $params = []) {
        if(strpos($path, ':') == false) { return $path; }

        $path = trim($path, '/'); // remove trailing and leading slash
        $route_parts = explode('/', $path);

        $uri = '';
        for($i=0; $i < count($route_parts); $i++) {
            $uri_part = $route_parts[$i];
            if(substr($uri_part, 0, 1) == ':' && isset($params[$uri_part])) {
                // if(!isset($params[$uri_part])) {
                  // throw new \Exception("Parameter is missing in url");
                // }
                $uri .= urlencode($params[$uri_part]);
            } else {
                $uri .= $uri_part;
            }
            $uri .= '/';
        }

        return '/' . trim($uri, '/');
    }
}
