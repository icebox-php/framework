<?php

namespace Icebox;

class Utils
{
    # Helper function to get environment variable
    public static function env(string $key, $default = null) {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}
