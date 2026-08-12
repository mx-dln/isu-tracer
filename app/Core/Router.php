<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use App\Middleware\MiddlewareInterface;
use App\Middleware\RouteMiddleware;

/**
 * Lightweight route dispatcher.
 */
final class Router
{
    /** @var array<int, Route> */
    private array $routes = [];

    public function get(string $uri, string|array|Closure $action): Route
    {
        return $this->add('GET', $uri, $action);
    }

    public function post(string $uri, string|array|Closure $action): Route
    {
        return $this->add('POST', $uri, $action);
    }

    public function put(string $uri, string|array|Closure $action): Route
    {
        return $this->add('PUT', $uri, $action);
    }

    public function delete(string $uri, string|array|Closure $action): Route
    {
        return $this->add('DELETE', $uri, $action);
    }

    public function any(string $uri, string|array|Closure $action): Route
    {
        return $this->add('ANY', $uri, $action);
    }

    private function add(string $method, string $uri, string|array|Closure $action): Route
    {
        $uri = '/' . trim($uri, '/');
        $route = new Route($method, $uri, $action);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * Dispatch the request through matching routes and middleware.
     */
    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            $params = $route->matches($method, $path);
            if ($params === null) {
                continue;
            }

            // Execute middleware chain.
            $next = function () use ($route, $request, $params): void {
                $this->executeAction($route, $request, $params);
            };

            foreach (array_reverse($route->middleware) as $middleware) {
                $next = $this->wrapMiddleware($middleware, $next);
            }
            $next();
            return;
        }

        abort(404, 'The page you are looking for was not found.');
    }

    private function wrapMiddleware($middleware, Closure $next): Closure
    {
        return function () use ($middleware, $next): void {
            if ($middleware instanceof Closure) {
                $middleware($next);
                return;
            }

            if (is_string($middleware)) {
                $instance = $this->resolveMiddleware($middleware);
                $instance->handle($next);
                return;
            }

            $next();
        };
    }

    private function resolveMiddleware(string $class): MiddlewareInterface
    {
        $class = str_starts_with($class, 'App\\Middleware\\') ? $class : 'App\\Middleware\\' . $class;
        $instance = new $class();
        if (!$instance instanceof MiddlewareInterface && !method_exists($instance, 'handle')) {
            throw new \RuntimeException("Middleware [{$class}] must implement MiddlewareInterface.");
        }
        return $instance;
    }

    private function executeAction(Route $route, Request $request, array $params): void
    {
        $action = $route->action;

        if ($action instanceof Closure) {
            $action($request, $params);
            return;
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$controller, $method] = explode('@', $action, 2);
        } elseif (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;
        } else {
            throw new \RuntimeException('Invalid route action for ' . $route->uri);
        }

        $controller = str_starts_with($controller, 'App\\Controllers\\')
            ? $controller
            : 'App\\Controllers\\' . $controller;

        $instance = new $controller();
        $instance->{$method}($request, $params);
    }
}
