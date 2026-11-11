<?php
declare(strict_types=1);

namespace App;

/**
 * Maps "METHOD /path" to the function that handles it.
 * A path part in braces is a variable: '/api/products/{slug}' matches
 * '/api/products/elden-ring-pc-steam-key-global' and passes ['slug' => '...'].
 */
final class Router
{
    /** @var array<int, array{string, string, callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes[] = ['GET', $path, $handler];
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes[] = ['POST', $path, $handler];
    }

    /** Finds the matching route, runs it and sends what it returns as JSON. */
    public function dispatch(string $method, string $path): never
    {
        $path       = rtrim($path, '/') ?: '/';
        $pathExists = false;

        foreach ($this->routes as [$routeMethod, $routePath, $handler]) {
            $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?<$1>[^/]+)', $routePath) . '$#';
            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }
            $pathExists = true;
            if ($routeMethod !== $method) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            Http::send($handler(array_map('rawurldecode', $params)));
        }

        throw $pathExists
            ? new ApiError(405, "Method $method is not allowed for this address.")
            : new ApiError(404, 'Not found.');
    }
}
