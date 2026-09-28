<?php

declare(strict_types=1);

namespace App\Services;

final class ListingPresenter
{
    /**
     * @param array<string, mixed> $listing
     */
    public static function url(array $listing): string
    {
        return '/' . $listing['state_slug'] . '/' . $listing['city_slug'] . '/' . $listing['category_slug'] . '/' . $listing['slug'];
    }

    /**
     * @param array<string, mixed> $listing
     */
    public static function excerpt(array $listing, int $length = 140): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $listing['description'])) ?? '');
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length - 1)) . '…';
    }

    /**
     * @param array<string, mixed> $listing
     */
    public static function ageLabel(array $listing): string
    {
        $age = (int) $listing['age'];
        if (($listing['age_display'] ?? 'exact') === 'minimum') {
            return $age . '+';
        }

        return (string) $age;
    }

    /**
     * @param array<string, mixed> $listing
     * @return list<string>
     */
    public static function badges(array $listing): array
    {
        $badges = [];
        $published = strtotime((string) ($listing['published_at'] ?? '')) ?: 0;
        if ($published > 0 && $published >= time() - 2 * 86400) {
            $badges[] = 'Recently Added';
        } elseif ($published > 0 && $published >= time() - 7 * 86400) {
            $badges[] = 'New';
        }
        if ((int) ($listing['is_featured'] ?? 0) === 1) {
            $badges[] = 'Featured';
        }
        if ((int) ($listing['is_verified'] ?? 0) === 1) {
            $badges[] = 'Verified';
        }

        return $badges;
    }

    /**
     * @param array<string, mixed> $listing
     */
    public static function postedOn(array $listing): string
    {
        $value = (string) ($listing['published_at'] ?: $listing['created_at']);
        $time = strtotime($value);

        return $time === false ? '' : date('j M Y', $time);
    }

    public static function uploadUrl(?string $path): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        return '/public/uploads/' . ltrim($path, '/');
    }
}
