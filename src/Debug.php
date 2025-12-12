<?php

namespace Icebox;

class Debug {
  public static function details($e) {

    $html = "<br><br>\n";

    // var_dump($e->getTrace());
    foreach ($e->getTrace() as $value) {

      if(array_key_exists('file', $value)) {
        $html .= "\n<hr>\n";
        $html .= 'File: ' . $value['file'];
        if(array_key_exists('line', $value)) {
          $html .= "<br>\n";
          $html .= 'Line: ' . $value['line'];
          $html .= self::read_file($value['file'], $value['line']);
        }

        //$html .= "<br><br><br>\n";
      } else {

        $html .= print_r($value, true);

      }

      $html .= "<br>\n";

      // print_r($value);
      // echo $html;
      // echo '<br><br><br><br><br><br><br>';
      // $html = $html . $value . '<br>' . "\n";
    }
    return $html;
  }

  private static function read_file($file, $line) {

    $html = "\n <br>--- start ---<br>\n";
    $lines_array = file($file); //file in to an array

    //$html .= '<br>============================================<br>';
    //$html .= print_r($lines_array, true);
    //$html .= '<br>============================================<br>';

    $start_line = max(0, $line-8);
    $end_line = min(count($lines_array)-1, $line + 8);

    //$html = $html . $start_line . '--' . $end_line . '<br>';

    // read file
    for($index = $start_line; $index <= $end_line; $index++ ) {
      $html .= ($index + 1) . '&nbsp;&nbsp;&nbsp;';
      $html .= htmlspecialchars($lines_array[$index]) . '<br>';
    }

    $html .= "\n--- end ---\n";

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
