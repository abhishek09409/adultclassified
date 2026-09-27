<?php

declare(strict_types=1);

use App\Helpers\Env;

return [
    'name' => Env::get('APP_NAME', 'Adult Directory') ?? 'Adult Directory',
    'env' => Env::get('APP_ENV', 'production') ?? 'production',
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => rtrim(Env::get('APP_URL', 'http://localhost') ?? 'http://localhost', '/'),
    'key' => Env::get('APP_KEY', '') ?? '',
    'timezone' => 'Asia/Kolkata',
    'minimum_age' => 21,
];
