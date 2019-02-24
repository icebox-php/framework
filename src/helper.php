<?php

if (! function_exists('h')) {
  function h($text) {
    return htmlspecialchars($text);
  }
}

function php_start_tag() {
  echo '<?php';
}

function php_end_tag() {
  echo '?>';
}
