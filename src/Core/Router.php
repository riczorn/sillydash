<?php

namespace App\Core;

class Router
{
    protected $routes = [];

    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    protected function addRoute($method, $path, $handler)
    {
        // Convert route path to regex
        // e.g., /users/edit/(:num) -> #^/users/edit/(\d+)$#
        $pattern = preg_replace('/\(:\w+\)/', '(\w+)', $path);

        // Ensure pattern matches start and end
        // Also handle the case where path is just '/'
        if ($path === '/') {
            $pattern = '#^/$#';
        } else {
            $pattern = "#^{$pattern}$#";
        }

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function dispatch($uri, $method)
    {
        $uri = $this->parseUri($uri);

        // Debugging
        // echo "Parsed URI: " . $uri . "<br>";

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches); // Remove full match

                list($controllerName, $methodName) = explode('::', $route['handler']);
                $controllerClass = "App\\Controllers\\{$controllerName}";

                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    if (method_exists($controller, $methodName)) {
                        return call_user_func_array([$controller, $methodName], $matches);
                    }
                }
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo "404 Not Found (Router)";
        // Optional: include a 404 view
    }

    protected function parseUri($uri)
    {
        // Get the script path (e.g. /utils/index.php)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);

        // If the script dir is not root, remove it from URI
        // Ensure scriptDir has a leading slash
        $scriptDir = '/' . trim($scriptDir, '/');

        if ($scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }

        // Also remove /index.php if present
        if (strpos($uri, '/index.php') === 0) {
            $uri = substr($uri, strlen('/index.php'));
        }

        $uri = trim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        } else {
            $uri = '/' . $uri;
        }

        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        return $uri;
    }
}
