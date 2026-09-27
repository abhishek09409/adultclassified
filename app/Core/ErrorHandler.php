<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Logger;
use ErrorException;
use Throwable;

final class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        if (in_array($severity, [E_WARNING, E_USER_WARNING, E_RECOVERABLE_ERROR], true)) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }

        Logger::error('PHP notice: ' . $message . ' in ' . $file . ':' . $line);

        return true;
    }

    public static function handleException(Throwable $exception): void
    {
        Logger::error('Unhandled exception', $exception);

        if (PHP_SAPI === 'cli') {
            $debug = (bool) Config::get('app.debug', false);
            fwrite(STDERR, $debug ? $exception->getMessage() : 'Application error.' . PHP_EOL);
            exit(1);
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo self::publicBody($exception);
    }

    public static function publicBody(Throwable $exception): string
    {
        $debug = (bool) Config::get('app.debug', false);
        $detail = $debug ? $exception->getMessage() : null;

        try {
            return View::render('errors/500', [
                'title' => 'Something went wrong',
                'detail' => $detail,
            ]);
        } catch (Throwable) {
            if ($debug && $detail !== null) {
                return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Error</title></head><body><h1>Something went wrong</h1><p>'
                    . htmlspecialchars($detail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</p></body></html>';
            }

            return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Error</title></head><body><h1>Something went wrong</h1><p>Please try again later.</p></body></html>';
        }
    }
}
