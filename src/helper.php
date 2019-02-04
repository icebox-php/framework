<?php

if (! function_exists('h')) {
  function h($text) {
    return htmlspecialchars($text);
  }
}
