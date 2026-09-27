<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/base'): string
    {
        $content = self::capture($template, $data);
        if ($layout === null) {
            return $content;
        }

        return self::capture($layout, $data + ['content' => $content]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function capture(string $template, array $data): string
    {
        $path = BASE_PATH . '/resources/views/' . $template . '.php';
        if (!is_file($path)) {
            throw new RuntimeException('View template is missing.');
        }

        $resolved = realpath($path);
        $root = realpath(BASE_PATH . '/resources/views');
        if ($resolved === false || $root === false || !str_starts_with($resolved, $root . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('View template path is not allowed.');
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $resolved;
        $output = ob_get_clean();

        return $output === false ? '' : $output;
    }
}
