<?php

declare(strict_types=1);

namespace App\Services;

final class Gate
{
    /** @var array<string, list<string>> */
    private const ROLES = [
        'super_admin' => ['*'],
        'admin' => ['dashboard', 'listings', 'listings.delete', 'listings.verify', 'listings.publish', 'locations', 'categories', 'imports', 'reports', 'automation', 'seo', 'settings', 'audit', 'compliance'],
        'moderator' => ['dashboard', 'listings', 'listings.verify', 'listings.publish', 'reports', 'compliance', 'audit'],
        'editor' => ['dashboard', 'listings', 'locations', 'categories'],
    ];

    public static function allows(?array $admin, string $ability): bool
    {
        if ($admin === null) {
            return false;
        }

        $role = (string) ($admin['role'] ?? '');
        $granted = self::ROLES[$role] ?? [];

        return in_array('*', $granted, true) || in_array($ability, $granted, true);
    }
}
