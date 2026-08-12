<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

/**
 * A single registered route.
 */
final class Route
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly string|array|Closure $action,
    ) {
    }

    /** @var array<int, class-string|Closure> */
    public array $middleware = [];

    public string $name = '';

    /**
     * Attach middleware (class names or closures) to the route.
     */
    public function middleware(...$middleware): self
    {
        foreach ($middleware as $m) {
            $this->middleware[] = $m;
        }
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Determine if the route matches the given method and path.
     * Returns matched route parameters or null when there is no match.
     */
    public function matches(string $method, string $path): ?array
    {
        if ($this->method !== 'ANY' && $this->method !== $method) {
            return null;
        }

        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) {
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $this->uri);

        $regex = '#^' . $pattern . '$#';
        if (preg_match($regex, $path, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = urldecode($value);
                }
            }
            return $params;
        }
        return null;
    }
}
