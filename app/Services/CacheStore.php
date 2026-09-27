<?php

declare(strict_types=1);

namespace App\Services;

final class CacheStore
{
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $path = self::path($key);
        if (is_file($path)) {
            $raw = file_get_contents($path);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded) && (int) ($decoded['expires'] ?? 0) >= time()) {
                return $decoded['value'];
            }
        }

        $value = $callback();
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents($path, json_encode(['expires' => time() + $ttl, 'value' => $value]), LOCK_EX);

        return $value;
    }

    public static function forget(string $key): void
    {
        $path = self::path($key);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private static function path(string $key): string
    {
        return BASE_PATH . '/storage/cache/' . hash('sha256', $key) . '.json';
    }
}
