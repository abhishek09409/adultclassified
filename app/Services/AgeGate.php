<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Request;

final class AgeGate
{
    public static function allows(Request $request): bool
    {
        if (self::exempt($request->path())) {
            return true;
        }

        $cookie = $_COOKIE['directory_age'] ?? '';
        if (!is_string($cookie) || $cookie === '') {
            return false;
        }

        return hash_equals(self::token(), $cookie);
    }

    public static function token(): string
    {
        $key = (string) Config::get('app.key', '');

        return hash_hmac('sha256', 'age-21', $key);
    }

    public static function exempt(string $path): bool
    {
        $allowed = [
            '/age',
            '/terms',
            '/privacy',
            '/content-policy',
            '/health',
            '/robots.txt',
        ];
        if (in_array($path, $allowed, true) || str_starts_with($path, '/admin') || str_starts_with($path, '/sitemap') || str_starts_with($path, '/api')) {
            return true;
        }

        return false;
    }
}
