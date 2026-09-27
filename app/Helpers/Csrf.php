<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Session;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf', $token);
        }

        return $token;
    }

    public static function verify(?string $token): bool
    {
        $stored = Session::get('_csrf');
        if (!is_string($stored) || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }
}
