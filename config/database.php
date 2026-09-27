<?php

declare(strict_types=1);

use App\Helpers\Env;

return [
    'host' => Env::get('DB_HOST', '127.0.0.1') ?? '127.0.0.1',
    'port' => Env::int('DB_PORT', 3306),
    'name' => Env::get('DB_NAME', 'directory') ?? 'directory',
    'user' => Env::get('DB_USER', 'root') ?? 'root',
    'pass' => Env::get('DB_PASS', '') ?? '',
    'charset' => 'utf8mb4',
];
