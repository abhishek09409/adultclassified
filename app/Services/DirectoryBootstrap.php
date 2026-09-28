<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Migrator;
use App\Core\Seeder;
use App\Helpers\Logger;
use App\Repositories\LocationRepository;
use Throwable;

final class DirectoryBootstrap
{
    private static string $message = 'ok';

    public static function message(): string
    {
        return self::$message;
    }

    public static function ensure(): void
    {
        self::$message = 'ok';
        try {
            $pdo = Database::connection();
        } catch (Throwable $exception) {
            self::fail($exception);

            return;
        }

        try {
            (new Migrator($pdo, BASE_PATH . '/database/migrations'))->migrate();
        } catch (Throwable $exception) {
            self::fail($exception);
        }

        try {
            self::seedPlaces($pdo);
            self::seedListings($pdo);
        } catch (Throwable $exception) {
            self::fail($exception);
        }
    }

    private static function fail(Throwable $exception): void
    {
        self::$message = $exception->getMessage();
        Logger::error('Directory bootstrap failed', $exception);
    }

    private static function seedPlaces(\PDO $pdo): void
    {
        $states = (int) $pdo->query('SELECT COUNT(*) FROM states')->fetchColumn();
        $categories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        $cities = (int) $pdo->query('SELECT COUNT(*) FROM cities')->fetchColumn();
        if ($states >= 29 && $categories >= 4 && $cities >= 100) {
            return;
        }

        $locked = (int) $pdo->query("SELECT GET_LOCK('directory_bootstrap', 20)")->fetchColumn();
        if ($locked !== 1) {
            return;
        }
        try {
            (new Seeder($pdo))->run();
            LocationRepository::forgetReferenceData();
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('directory_bootstrap')");
        }
    }

    private static function seedListings(\PDO $pdo): void
    {
        $published = (int) $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'published'")->fetchColumn();
        if ($published > 0) {
            return;
        }
        $cities = (int) $pdo->query('SELECT COUNT(*) FROM cities')->fetchColumn();
        if ($cities < 1) {
            return;
        }

        $locked = (int) $pdo->query("SELECT GET_LOCK('directory_bootstrap_ads', 30)")->fetchColumn();
        if ($locked !== 1) {
            return;
        }
        try {
            $automation = new AutomationService();
            $automation->run('manual');
            $automation->publishDemo(18);
            LocationRepository::forgetReferenceData();
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('directory_bootstrap_ads')");
        }
    }
}
