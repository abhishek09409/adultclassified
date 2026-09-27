<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $server
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $server
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $server
     */
    public static function capture(string $method, string $uri, array $query = [], array $server = []): self
    {
        return new self(strtoupper($method), self::normalizePath($uri), $query, $server);
    }

    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

        return self::capture($method, $uri, $_GET, $_SERVER);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, ?string $default = null): ?string
    {
        $value = $this->query[$key] ?? $default;
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        $path = rawurldecode($path);
        if (str_contains($path, "\0") || str_contains($path, '..')) {
            return '/';
        }

        $path = preg_replace('#/+#', '/', $path) ?? '/';
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        if ($path === '' || !str_starts_with($path, '/')) {
            return '/';
        }

        return $path;
    }
}
