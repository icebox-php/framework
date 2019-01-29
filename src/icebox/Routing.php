<?php

namespace Icebox;

use Icebox\Exception\ResourceNotFoundException;
use Icebox\Request;

class Routing
{
    private $routes = [];

    public function __construct() {}

    public function add(string $method, string $path, string $controller_action)
    {
        $this->routes[$path] = array('method' => $method, 'path' => ltrim($path, '/'), 'controller_action' => $controller_action);
    }

    public function get($path, $controller_action)
    {
        $this->add('get', $path, $controller_action);
    }

    public function post($path, $controller_action)
    {
        $this->add('post', $path, $controller_action);
    }

    public function url_matcher()
    {
        $uri = '';

        $request_uri = trim($_SERVER['REQUEST_URI'], '/'); // remove trailing and leading slash
        $project_directory = trim(App::project_directory(), '/'); // remove trailing and leading slash
        $index_file = '/' . App::index_file();

        // remove get parameters
        $parts = explode('?', $request_uri, 2);
        $request_uri = $parts[0];

        // remove project_directory if project is in subfolder
        // if $uri = '/ice-box/leap_year/2012', you need to get '/leap_year/2012'
        $find = strpos($request_uri, $project_directory);
        if ($find === 0) { $uri = substr($request_uri, strlen($project_directory)); }

        // remove /index.php if you request a page like this: http://localhost/ice-box/index.php/posts
        // if $uri = '/index.php/posts', you need to get '/posts'
        $find = strpos($uri, $index_file);
        if ($find === 0) { $uri = substr($uri, strlen($index_file)); }

        // remove leading slash
        // if $url = '/posts/5', I need 'posts/5'
        $uri = ltrim($uri, '/');


        //=== start url matching ===
        $uri_parts = explode('/', $uri);
        $uri_parts_count = count($uri_parts);

        // var_dump($uri_parts);
        // echo '<br><br>';

        // var_dump($uri_parts_count);
        // echo '<br><br>';

        // $controller_action = false;

        foreach ($this->routes as $route) {
            // var_dump($route['path']);
            // echo '<br><br>';

            // $controller_action = $route['controller_action'];

            if(Request::method() != $route['method']) {
              continue;
            }

            Request::clear_params();

            if($route['path'] == $uri) { // if directly match, return from here
              return $route['controller_action'];
            }

            $route_parts = explode('/', $route['path']);

            if ($uri_parts_count == count($route_parts)) {
                $match_count = 0;

                for ($i=0; $i<$uri_parts_count; $i++) {

                    // echo '<br>';
                    // echo 'route parts' . $uri_parts[$i] . '<br>';

                    // if starts with ':', consider it as params
                    if (substr($route_parts[$i], 0, 1) == ':') { // echo 'if<br>';
                      $match_count++;
                        Request::set_param(ltrim($route_parts[$i], ':'), urldecode($uri_parts[$i]));
                    } else { // echo 'else<br>'; // else, consider is as uri string
                        if ($route_parts[$i] == $uri_parts[$i]) {
                            $match_count++;
                        } else {
                            continue; // echo 'continue<br>';
                        }
                        // echo 'match<br>';
                    }
                }

                if ($match_count == $uri_parts_count) {
                    return $route['controller_action'];
                }
            }
        }

        return false;

        //=== end url matching ===
    }
}
