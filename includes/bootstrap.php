<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Config;
use App\Core\ErrorHandler;
use App\Helpers\Env;
use App\Helpers\Html;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Autoloader.php';
Autoloader::register(BASE_PATH);

Env::load(BASE_PATH . '/.env');

$config = require BASE_PATH . '/config/app.php';
date_default_timezone_set((string) $config['timezone']);

Config::load(BASE_PATH . '/config');

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('expose_php', '0');

if (PHP_SAPI !== 'cli') {
    header_remove('X-Powered-By');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'self'");
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

if (!function_exists('e')) {
    function e(string|int|float|null $value): string
    {
        return Html::escape($value);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(\App\Helpers\Csrf::token()) . '">';
    }
}

ErrorHandler::register();
