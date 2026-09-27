<?php

declare(strict_types=1);

namespace App\Helpers;

final class Str
{
    public static function slug(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($transliterated) && $transliterated !== '') {
            $value = $transliterated;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value;
    }

    public static function limit(string $value, int $length): string
    {
        if ($length < 1) {
            return '';
        }

        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $length));
    }
}
