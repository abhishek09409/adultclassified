<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
    $extension = strtolower(pathinfo($requestPath, PATHINFO_EXTENSION));
    $dangerous = in_array($extension, ['php', 'phtml', 'php3', 'phar', 'cgi', 'pl'], true);
    $staticFile = __DIR__ . $requestPath;
    if (
        str_starts_with($requestPath, '/public/')
        && !$dangerous
        && !str_contains($requestPath, '..')
        && is_file($staticFile)
    ) {
        return false;
    }
}

require __DIR__ . '/includes/bootstrap.php';

(new App\Core\Application(BASE_PATH))->run();
