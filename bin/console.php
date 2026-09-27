<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Migrator;
use App\Core\Seeder;

require dirname(__DIR__) . '/includes/bootstrap.php';

$command = $argv[1] ?? 'help';

try {
    match ($command) {
        'migrate' => migrate(),
        'seed' => seed(),
        'status' => status(),
        default => help(),
    };
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

function migrate(): void
{
    $applied = (new Migrator(Database::connection(), BASE_PATH . '/database/migrations'))->migrate();
    if ($applied === []) {
        fwrite(STDOUT, "No pending migrations.\n");
        return;
    }

    foreach ($applied as $migration) {
        fwrite(STDOUT, "Applied {$migration}\n");
    }
}

function seed(): void
{
    (new Seeder(Database::connection()))->run();
    fwrite(STDOUT, "Seeded states and categories.\n");
}

function status(): void
{
    $pdo = Database::connection();
    $migrations = (new Migrator($pdo, BASE_PATH . '/database/migrations'))->applied();
    $states = (int) $pdo->query('SELECT COUNT(*) FROM states')->fetchColumn();
    $categories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

    fwrite(STDOUT, 'Migrations: ' . ($migrations === [] ? 'none' : implode(', ', $migrations)) . PHP_EOL);
    fwrite(STDOUT, "States: {$states}\n");
    fwrite(STDOUT, "Categories: {$categories}\n");
}

function help(): void
{
    fwrite(STDOUT, "Usage: php bin/console.php [migrate|seed|status]\n");
}
