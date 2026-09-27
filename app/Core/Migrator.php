<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Migrator
{
    public function __construct(private readonly PDO $pdo, private readonly string $directory)
    {
    }

    /**
     * @return list<string>
     */
    public function migrate(): array
    {
        $this->ensureMigrationsTable();
        $applied = [];

        foreach ($this->files() as $file) {
            $name = basename($file);
            if ($this->isApplied($name)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new RuntimeException('Migration could not be read: ' . $name);
            }

            foreach ($this->statements($sql) as $statement) {
                $this->pdo->exec($statement);
            }

            $statement = $this->pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
            $statement->execute(['migration' => $name]);
            $applied[] = $name;
        }

        return $applied;
    }

    /**
     * @return list<string>
     */
    public function applied(): array
    {
        $this->ensureMigrationsTable();
        $rows = $this->pdo->query('SELECT migration FROM schema_migrations ORDER BY id ASC');
        if ($rows === false) {
            return [];
        }

        return array_map(static fn (array $row): string => (string) $row['migration'], $rows->fetchAll());
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                migration VARCHAR(191) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_schema_migrations_name (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @return list<string>
     */
    private function files(): array
    {
        $files = glob($this->directory . '/*.sql');
        if ($files === false) {
            throw new RuntimeException('Migration directory could not be read.');
        }

        sort($files, SORT_STRING);

        return $files;
    }

    private function isApplied(string $name): bool
    {
        $statement = $this->pdo->prepare('SELECT id FROM schema_migrations WHERE migration = :migration LIMIT 1');
        $statement->execute(['migration' => $name]);

        return $statement->fetch() !== false;
    }

    /**
     * @return list<string>
     */
    private function statements(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $parts = preg_split('/;\s*(?:\R|$)/', $sql);
        if ($parts === false) {
            return [];
        }

        $statements = [];
        foreach ($parts as $part) {
            $statement = trim($part);
            if ($statement !== '') {
                $statements[] = $statement;
            }
        }

        return $statements;
    }
}
