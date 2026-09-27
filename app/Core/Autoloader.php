<?php

declare(strict_types=1);

namespace App\Core;

final class Autoloader
{
    public static function register(string $basePath): void
    {
        spl_autoload_register(static function (string $class) use ($basePath): void {
            $prefixes = [
                'App\\' => $basePath . '/app/',
                'Database\\Seeders\\' => $basePath . '/database/seeders/',
            ];

            foreach ($prefixes as $prefix => $directory) {
                if (!str_starts_with($class, $prefix)) {
                    continue;
                }

                $relative = substr($class, strlen($prefix));
                $file = $directory . str_replace('\\', '/', $relative) . '.php';
                if (is_file($file)) {
                    require $file;
                }

                return;
            }
        });
    }
}
