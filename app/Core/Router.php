<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class Router
{
    private array $routes = [];

    public function get(string $uri, array $controller): void
    {
        $this->addRoute('GET', $uri, $controller);
    }

    public function post(string $uri, array $controller): void
    {
        $this->addRoute('POST', $uri, $controller);
    }

    private function addRoute(string $method, string $uri, array $controller): void
    {
        // Convert {param} to regex group ([^/]+)
        // Escaping forward slashes for the regex delimiter
        $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $uri);
        $pattern = "#^" . $pattern . "$#";

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'controller' => $controller
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        // Remove query string if present (already handled by parse_url, but just in case of weird server configs)
        // and trimming trailing slash implies normalization
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {

                // Remove the full match from the array, leaving only captured groups
                array_shift($matches);

                // Handle extra args passed in route definition (e.g. ['Controller', 'method', 'arg1'])
                $extraArgs = array_slice($route['controller'], 2);
                $matches = array_merge($matches, $extraArgs);

                [$controllerClass, $action] = $route['controller'];

                if (!class_exists($controllerClass)) {
                    throw new RuntimeException("Controller class {$controllerClass} not found.");
                }

                $controllerInstance = new $controllerClass();

                if (!method_exists($controllerInstance, $action)) {
                    throw new RuntimeException("Method {$action} not found in controller {$controllerClass}.");
                }

                // Call the controller action with the matched parameters
                if ($method === 'POST') {
                    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
                    if (!\App\Core\Csrf::verify($token)) {
                        // Log CSRF failure
                        $securityLogger = new \App\Services\SecurityLogger();
                        $securityLogger->logCsrfFailure($uri);

                        http_response_code(403);
                        if (file_exists(__DIR__ . '/../../resources/views/errors/403.php')) {
                            require __DIR__ . '/../../resources/views/errors/403.php';
                        } else {
                            die('403 Forbidden - Invalid CSRF Token');
                        }
                        exit;
                    }
                }

                call_user_func_array([$controllerInstance, $action], $matches);
                return;
            }
        }

        // If no route matches
        http_response_code(404);
        if (file_exists(__DIR__ . '/../../resources/views/errors/404.php')) {
            require __DIR__ . '/../../resources/views/errors/404.php';
        } else {
            echo "404 Not Found";
        }
        exit;
    }
}
