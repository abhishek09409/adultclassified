<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Migrator;
use App\Core\Seeder;
use App\Services\AuthService;
use App\Services\AutomationService;

require dirname(__DIR__) . '/includes/bootstrap.php';

$command = $argv[1] ?? 'help';

try {
    match ($command) {
        'migrate' => migrate(),
        'seed' => seed(),
        'status' => status(),
        'admin:create' => createAdmin($argv),
        'ads:demo' => demoAds($argv),
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

function createAdmin(array $argv): void
{
    $name = $argv[2] ?? '';
    $email = $argv[3] ?? '';
    $password = $argv[4] ?? '';
    $role = $argv[5] ?? 'super_admin';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || !in_array($role, ['super_admin', 'admin', 'moderator', 'editor'], true)) {
        fwrite(STDERR, "Usage: php bin/console.php admin:create \"Name\" email password role\n");
        exit(1);
    }
    AuthService::createAdmin(Database::connection(), $name, $email, $password, $role);
    fwrite(STDOUT, "Admin created.\n");
}

function demoAds(array $argv): void
{
    $count = isset($argv[2]) ? (int) $argv[2] : 36;
    $result = (new AutomationService())->publishDemo($count);
    fwrite($result['ok'] ? STDOUT : STDERR, $result['message'] . PHP_EOL);
    if (!$result['ok']) {
        exit(1);
    }
}

function help(): void
{
    fwrite(STDOUT, "Usage: php bin/console.php [migrate|seed|status|admin:create|ads:demo]\n");
}
