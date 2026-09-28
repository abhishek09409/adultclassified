<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AgeController;
use App\Core\Session;
use App\Helpers\Logger;
use App\Services\AgeGate;
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

        Session::start();
        $request = Request::fromGlobals();
        if (preg_match('#(?:^|/)\.#', $request->path()) === 1 || preg_match('#^/(?:app|config|database|storage|includes|cron|routes|resources|bin|tests)(?:/|$)#', $request->path()) === 1) {
            (new \App\Controllers\ErrorController())->forbidden()->send();
            return;
        }
        if (!AgeGate::allows($request)) {
            (new AgeController())->prompt($request)->send();
            return;
        }
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
