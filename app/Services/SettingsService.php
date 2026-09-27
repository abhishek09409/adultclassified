<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Env;
use PDO;

final class SettingsService
{
    public function get(string $key, ?string $default = null): string
    {
        $statement = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
        $statement->execute(['key' => $key]);
        $value = $statement->fetchColumn();
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return Env::get($key, $default) ?? (string) $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = strtolower($this->get($key, $default ? 'true' : 'false'));

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public function int(string $key, int $default): int
    {
        $value = $this->get($key, (string) $default);

        return preg_match('/^-?\d+$/', $value) ? (int) $value : $default;
    }

    public function put(string $key, string $value): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $statement->execute(['key' => $key, 'value' => $value]);
    }

    /**
     * @param array<string, string> $values
     */
    public function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value);
        }
    }
}
