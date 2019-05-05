<?php

namespace Icebox;

class Debug {
  public static function details($e) {

    $html = "<br><br>\n";

    // var_dump($e->getTrace());
    foreach ($e->getTrace() as $value) {

      if(array_key_exists('file', $value)) {
        $html .= 'File: ' . $value['file'];
        if(array_key_exists('line', $value)) {
          $html .= "<br>\n";
          $html .= 'Line: ' . $value['line'];
        }

        //$html .= "<br><br><br>\n";
      } else {

        $html .= print_r($value, true);

      }

      $html .= "<br><br><br>\n";

      // print_r($value);
      // echo $html;
      // echo '<br><br><br><br><br><br><br>';
      // $html = $html . $value . '<br>' . "\n";
    }
    return $html;
  }

  /*
  public static function details($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting, so let it fall
        // through to the standard PHP error handler
        return false;
    }

    $html = '';

    switch ($errno) {
      case E_USER_ERROR:
          $html .= "<b>My ERROR</b> [$errno] $errstr<br />\n";
          $html .= "  Fatal error on line $errline in file $errfile";
          $html .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
          $html .= "Aborting...<br />\n";
          break;

      case E_USER_WARNING:
          $html .= "<b>My WARNING</b> [$errno] $errstr<br />\n";
          break;

      case E_USER_NOTICE:
          $html .= "<b>My NOTICE</b> [$errno] $errstr<br />\n";
          break;

      default:
          $html .= "Unknown error type: [$errno] $errstr<br />\n";
          break;
      }

      // Don't execute PHP internal error handler
      return $html;
    }
    */
}
