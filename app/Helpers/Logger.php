<?php

declare(strict_types=1);

namespace App\Helpers;

use Throwable;

final class Logger
{
    public static function error(string $message, ?Throwable $exception = null): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if ($exception !== null) {
            $line .= ' ' . $exception::class . ': ' . $exception->getMessage();
            $line .= ' in ' . $exception->getFile() . ':' . $exception->getLine();
        }

        $line .= PHP_EOL;
        $directory = BASE_PATH . '/storage/logs';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = $directory . '/app-' . date('Y-m-d') . '.log';
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
