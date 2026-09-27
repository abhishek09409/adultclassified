<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     * @param array<string, mixed> $files
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $server,
        private readonly array $post = [],
        private readonly array $files = []
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $server
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     */
    public static function capture(
        string $method,
        string $uri,
        array $query = [],
        array $server = [],
        array $post = [],
        array $files = []
    ): self {
        return new self(strtoupper($method), self::normalizePath($uri), $query, $server, $post, $files);
    }

    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

        return self::capture($method, $uri, $_GET, $_SERVER, $_POST, $_FILES);
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
        return $this->scalar($this->query[$key] ?? null, $default);
    }

    public function input(string $key, ?string $default = null): ?string
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? null;

        return $this->scalar($value, $default);
    }

    public function integer(string $key): ?int
    {
        $value = $this->input($key);
        if ($value === null || !preg_match('/^\d+$/', $value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    public function ip(): string
    {
        $ip = $this->server['REMOTE_ADDR'] ?? '';

        return is_string($ip) ? $ip : '';
    }

    public function isSecure(): bool
    {
        $https = $this->server['HTTPS'] ?? '';

        return $https === 'on' || $https === '1';
    }

    private function scalar(mixed $value, ?string $default): ?string
    {
        if ($value === null) {
            return $default;
        }

        return is_scalar($value) ? trim((string) $value) : $default;
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
