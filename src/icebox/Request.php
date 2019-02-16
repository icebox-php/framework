<?php

namespace Icebox;

class Request {

  private static $params = [];
  private static $request_method;

  public static function params($key = null) {
    if($key === null) { // return all params
      return self::$params;
    } else {
      if(isset(self::$params[$key])) {
        return self::$params[$key];
      } else {
        return null;
      }
    }
  }

  public static function set_param($key, $value) {
    self::$params[$key] = $value;
  }

  public static function clear_params() {
    self::$params = [];
  }

  public function method() {

    if(isset(self::$request_method)) {
      return self::$request_method;
    }

    #============== start default value ==========
    // default value
    $request_method = 'get';

    // update default value, if REQUEST_METHOD exists
    if( isset($_SERVER['REQUEST_METHOD']) ) {
      $request_method = strtolower($_SERVER['REQUEST_METHOD']);
    }
    #============== end default value ============

    if($request_method != 'get' && $request_method != 'post') {
      return $request_method;
    }

    if($request_method == 'post' && isset($_POST['_method'])) {
      return strtolower($_POST['_method']);
    }

    return $request_method;

  }
}
