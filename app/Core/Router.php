<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\ErrorController;
use Closure;

final class Router
{
    /** @var list<array{method: string, pattern: string, handler: mixed}> */
    private array $routes = [];

    private mixed $fallback = null;

    public function get(string $pattern, mixed $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, mixed $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function fallback(mixed $handler): void
    {
        $this->fallback = $handler;
    }

    public function add(string $method, string $pattern, mixed $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern === '/' ? '/' : rtrim($pattern, '/'),
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            $params = $this->match($route['pattern'], $request->path());
            if ($params === null) {
                continue;
            }

            return $this->invoke($route['handler'], $request, $params);
        }

        if ($this->fallback !== null && $request->method() === 'GET') {
            return $this->invoke($this->fallback, $request, []);
        }

        return (new ErrorController())->notFound();
    }

    /**
     * @return array<string, string>|null
     */
    public function match(string $pattern, string $path): ?array
    {
        $pattern = $pattern === '/' ? '/' : rtrim($pattern, '/');
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        if (!is_string($regex)) {
            return null;
        }

        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * @param array<string, string> $params
     */
    private function invoke(mixed $handler, Request $request, array $params): Response
    {
        if ($handler instanceof Closure) {
            $response = $handler($request, $params);
        } elseif (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (!is_string($class) || !is_string($method) || !class_exists($class)) {
                return (new ErrorController())->serverError();
            }
            $controller = new $class();
            if (!method_exists($controller, $method)) {
                return (new ErrorController())->serverError();
            }
            $response = $controller->{$method}($request, $params);
        } else {
            return (new ErrorController())->serverError();
        }

        return $response instanceof Response ? $response : (new ErrorController())->serverError();
    }
}
