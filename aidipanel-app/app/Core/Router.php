<?php

declare(strict_types=1);

namespace Core;

use Middleware\AuthMiddleware;
use Middleware\CsrfMiddleware;

class Router
{
    private array $routes = [];
    private Request $request;

    // Routes that do NOT require authentication
    private array $publicRoutes = ['/login'];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get(string $path, string $handler): void
    {
        $this->routes[] = ['GET', $path, $handler];
    }

    public function post(string $path, string $handler): void
    {
        $this->routes[] = ['POST', $path, $handler];
    }

    public function dispatch(): void
    {
        $method = $this->request->method();
        $uri    = $this->request->uri();

        foreach ($this->routes as [$routeMethod, $routePath, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }

            $params = $this->match($routePath, $uri);
            if ($params === null) {
                continue;
            }

            // Auth check
            if (!in_array($uri, $this->publicRoutes, true)) {
                AuthMiddleware::handle();
            }

            // CSRF check for POST requests
            if ($method === 'POST') {
                CsrfMiddleware::handle($this->request);
            }

            $this->call($handler, $params);
            return;
        }

        abort(404, 'Page not found.');
    }

    /**
     * Match a route pattern against the URI, returning params or null
     * Supports {param} placeholders
     */
    private function match(string $pattern, string $uri): ?array
    {
        // Convert {param} to named capture groups
        $regex = preg_replace('/\{([a-z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        // Return only named params
        return array_filter($matches, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);
    }

    private function call(string $handler, array $params): void
    {
        [$controllerName, $method] = explode('@', $handler);
        $class = "Controllers\\{$controllerName}";

        if (!class_exists($class)) {
            abort(500, "Controller not found: {$class}");
        }

        $controller = new $class($this->request);

        if (!method_exists($controller, $method)) {
            abort(500, "Method not found: {$class}::{$method}");
        }

        $controller->{$method}($params);
    }
}
