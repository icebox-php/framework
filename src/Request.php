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

  /**
   * @var string unique Request id in a specific time
    */
  private static $requestId = null;

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

  public static function set_param($key, $value) {
    self::$params[$key] = $value;
  }

  public static function clear_params() {
    self::$params = [];
  }

  public static function method() {

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
