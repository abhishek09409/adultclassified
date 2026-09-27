<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Logger;
use Throwable;

final class Application
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function run(): void
    {
        $key = (string) Config::get('app.key', '');
        if ($key === '' || $key === 'generate-a-long-random-secret-key') {
            Logger::error('APP_KEY is missing or still set to the example placeholder.');
        }

        $request = Request::fromGlobals();
        $router = new Router();
        $register = require $this->basePath . '/routes/web.php';
        if (!is_callable($register)) {
            (new \App\Controllers\ErrorController())->serverError()->send();
            return;
        }

        $register($router);

        try {
            $router->dispatch($request)->send();
        } catch (Throwable $exception) {
            ErrorHandler::handleException($exception);
        }
    }
}
