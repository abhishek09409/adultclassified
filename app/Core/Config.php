<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    public static function load(string $configDirectory): void
    {
        self::$items = [];
        $files = glob($configDirectory . '/*.php');
        if ($files === false) {
            throw new RuntimeException('Configuration directory could not be read.');
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $loaded = require $file;
            if (!is_array($loaded)) {
                throw new RuntimeException('Configuration file must return an array.');
            }
            self::$items[$key] = $loaded;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
