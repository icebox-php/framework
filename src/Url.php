<?php

namespace Icebox;

class Url
{
    /**
     * Generate URL from pattern with parameters
     *
     * @param string $pattern URL pattern (e.g., 'posts/:id' or 'users/:userId/posts/:postId')
     * @param array $params Parameters to replace (e.g., [':id' => 123])
     * @param bool $absolute Whether to generate absolute URL
     * @return string
     */
    public static function to(string $pattern, array $params = [], bool $absolute = false): string
    {
        $url = $pattern;

        // Replace parameters in pattern
        foreach ($params as $key => $value) {
            // Support both :id and {id} syntax
            $placeholder = str_starts_with($key, ':') ? $key : ":{$key}";
            $url = str_replace($placeholder, (string)$value, $url);
            
            // Also support {id} syntax
            $placeholder = '{' . ltrim($key, ':') . '}';
            $url = str_replace($placeholder, (string)$value, $url);
        }

        // Add query string if there are unused params
        $queryParams = array_filter($params, function($key) use ($pattern) {
            $placeholder = str_starts_with($key, ':') ? $key : ":{$key}";
            return !str_contains($pattern, $placeholder) && 
                   !str_contains($pattern, '{' . ltrim($key, ':') . '}');
        }, ARRAY_FILTER_USE_KEY);

        if (!empty($queryParams)) {
            // Remove : prefix from query params
            $cleanParams = [];
            foreach ($queryParams as $key => $value) {
                $cleanKey = ltrim($key, ':');
                $cleanParams[$cleanKey] = $value;
            }
            $url .= '?' . http_build_query($cleanParams);
        }

        return $absolute ? self::absolute($url) : self::relative($url);
    }

    /**
     * Generate URL from named route
     *
     * @param string $name Route name
     * @param array $params Route parameters
     * @param bool $absolute Whether to generate absolute URL
     * @return string
     */
    public static function route(string $name, array $params = [], bool $absolute = false): string
    {
        // This would integrate with your Router class
        // For now, simple implementation
        $routes = Config::get('routes', []);
        
        if (!isset($routes[$name])) {
            throw new \InvalidArgumentException("Route '{$name}' not found");
        }

        return self::to($routes[$name], $params, $absolute);
    }

    /**
     * Generate absolute URL
     *
     * @param string $path Relative path
     * @return string
     */
    public static function absolute(string $path): string
    {
        $path = self::relative($path);
        $scheme = self::isSecure() ? 'https' : 'http';
        $host = Config::get('app_host'); //$_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return "{$scheme}://{$host}{$path}";
    }

    /**
     * Generate relative URL (with base path)
     *
     * @param string $path Path
     * @return string
     */
    public static function relative(string $path): string
    {
        return '';  

        $basePath = Config::get('app.base_path', '');
        $path = '/' . ltrim($path, '/');
        
        if ($basePath) {
            $basePath = '/' . trim($basePath, '/');
            return $basePath . $path;
        }
        
        return $path;
    }

    /**
     * Generate asset URL
     *
     * @param string $path Asset path (e.g., 'css/style.css')
     * @param bool $absolute Whether to generate absolute URL
     * @return string
     */
    public static function asset(string $path): string
    {
        $asset_host = Config::get('asset_host');
        if($asset_host == null) {
          return $path;
        }
        // $fullPath = $assetsPath . '/' . ltrim($path, '/');
        // return $absolute ? self::absolute($fullPath) : self::relative($fullPath);
        return $asset_host .  '/' . ltrim($path, '/');
    }

    /**
     * Get current URL
     *
     * @param bool $withQuery Include query string
     * @return string
     */
    public static function current(bool $withQuery = true): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        if (!$withQuery && str_contains($uri, '?')) {
            $uri = explode('?', $uri)[0];
        }
        
        return self::absolute($uri);
    }

    /**
     * Get previous URL from referrer
     *
     * @param string|null $default Default URL if no referrer
     * @return string
     */
    public static function previous(?string $default = null): string
    {
        return $_SERVER['HTTP_REFERER'] ?? $default ?? '/';
    }

    /**
     * Check if request is secure (HTTPS)
     *
     * @return bool
     */
    public static function isSecure(): bool
    {
        if(Config::get('force_ssl') == true) {
          return true;
        }

        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 80) == 443
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Build query string from array
     *
     * @param array $params Query parameters
     * @return string
     */
    public static function query(array $params): string
    {
        return http_build_query($params);
    }
}
