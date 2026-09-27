<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class RateLimiter
{
    public function tooMany(string $action, string $ip, int $limit, int $seconds): bool
    {
        $seconds = max(1, $seconds);
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM rate_limits
             WHERE action_key = :action AND ip_address = :ip AND created_at >= (NOW() - INTERVAL ' . $seconds . ' SECOND)'
        );
        $statement->execute(['action' => $action, 'ip' => $ip]);

        return (int) $statement->fetchColumn() >= $limit;
    }

    public function hit(string $action, string $ip): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO rate_limits (action_key, ip_address) VALUES (:action, :ip)'
        );
        $statement->execute(['action' => $action, 'ip' => $ip]);
    }
}
