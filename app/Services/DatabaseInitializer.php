<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Migrator;
use App\Core\Seeder;
use App\Helpers\Logger;
use App\Repositories\LocationRepository;
use PDO;
use RuntimeException;
use Throwable;

final class DatabaseInitializer
{
    public const SEED_VERSION = '1';

    public const MIN_CITIES = 1000;

    /** @var list<string> */
    public const REQUIRED_TABLES = [
        'states',
        'cities',
        'locations',
        'categories',
        'listings',
        'listing_images',
        'listing_variations',
        'listing_variation_usage',
        'automation_runs',
        'automation_logs',
        'admins',
        'reports',
        'seo_metadata',
        'audit_logs',
        'settings',
    ];

    /** @var array<string, string> */
    private const CATEGORIES = [
        'call-girls' => 'Call Girls',
        'massage' => 'Massage',
        'male-escorts' => 'Male Escorts',
        'escorts' => 'Escorts',
    ];

    private static string $message = 'ok';

    public static function message(): string
    {
        return self::$message;
    }

    /**
     * Skip the city import when this database was already initialized.
     */
    public static function ensureIfNeeded(): void
    {
        self::$message = 'ok';
        try {
            $initializer = new self();
            $pdo = Database::connection();
            $initializer->migrate($pdo);
            if ($initializer->isComplete($pdo)) {
                return;
            }
            $initializer->run($pdo);
        } catch (Throwable $exception) {
            self::$message = $exception->getMessage();
            Logger::error('Database initialization failed', $exception);
        }
    }

    /**
     * @return array{ok: bool, message: string, counts: array<string, int|null>, missing: list<string>, orphans: int}
     */
    public function run(?PDO $pdo = null): array
    {
        $pdo ??= Database::connection();
        $this->migrate($pdo);
        $locked = (int) $pdo->query("SELECT GET_LOCK('directory_seed', 30)")->fetchColumn();
        if ($locked !== 1) {
            throw new RuntimeException('Another initialization is already running.');
        }

        try {
            if (!$this->isComplete($pdo)) {
                (new Seeder($pdo))->run();
                LocationRepository::forgetReferenceData();
            }
            $published = (int) $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'published'")->fetchColumn();
            if ($published === 0) {
                $automation = new AutomationService();
                $automation->run('manual');
                if ((int) $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn() === 0) {
                    $automation->publishDemo(12);
                }
                LocationRepository::forgetReferenceData();
            }
            $report = $this->report($pdo);
            $this->assertCategories($pdo);
            if ($report['orphans'] > 0 || $report['missing'] !== [] || ($report['counts']['states'] ?? 0) < 29 || ($report['counts']['categories'] ?? 0) < 4 || ($report['counts']['cities'] ?? 0) < self::MIN_CITIES) {
                throw new RuntimeException($this->failureText($report));
            }
            $this->markComplete($pdo);
            self::$message = 'ok';

            return $report;
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('directory_seed')");
        }
    }

    /**
     * @return array{ok: bool, message: string, counts: array<string, int|null>, missing: list<string>, orphans: int}
     */
    public function report(?PDO $pdo = null): array
    {
        $pdo ??= Database::connection();
        $present = [];
        foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $row) {
            $present[(string) $row[0]] = true;
        }
        $counts = [];
        $missing = [];
        foreach (self::REQUIRED_TABLES as $table) {
            if (!isset($present[$table])) {
                $missing[] = $table;
                $counts[$table] = null;
                continue;
            }
            $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        }
        $orphans = 0;
        if (isset($present['cities'], $present['states'])) {
            $orphans += (int) $pdo->query('SELECT COUNT(*) FROM cities c LEFT JOIN states s ON s.id = c.state_id WHERE s.id IS NULL')->fetchColumn();
        }
        if (isset($present['locations'], $present['cities'])) {
            $orphans += (int) $pdo->query('SELECT COUNT(*) FROM locations l LEFT JOIN cities c ON c.id = l.city_id WHERE c.id IS NULL')->fetchColumn();
        }
        $ok = $missing === []
            && $orphans === 0
            && ($counts['states'] ?? 0) >= 29
            && ($counts['categories'] ?? 0) >= 4
            && ($counts['cities'] ?? 0) >= self::MIN_CITIES;

        return [
            'ok' => $ok,
            'message' => $ok ? 'ok' : $this->failureText(['counts' => $counts, 'missing' => $missing, 'orphans' => $orphans, 'ok' => false, 'message' => '']),
            'counts' => $counts,
            'missing' => $missing,
            'orphans' => $orphans,
        ];
    }

    private function migrate(PDO $pdo): void
    {
        (new Migrator($pdo, BASE_PATH . '/database/migrations'))->migrate();
    }

    private function isComplete(PDO $pdo): bool
    {
        $version = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
        $version->execute(['key' => 'seed_version']);
        if ((string) $version->fetchColumn() !== self::SEED_VERSION) {
            return false;
        }
        $states = (int) $pdo->query('SELECT COUNT(*) FROM states')->fetchColumn();
        $categories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        $cities = (int) $pdo->query('SELECT COUNT(*) FROM cities')->fetchColumn();

        return $states >= 29 && $categories >= 4 && $cities >= self::MIN_CITIES;
    }

    private function assertCategories(PDO $pdo): void
    {
        $found = $pdo->query("SELECT slug, name FROM categories WHERE status = 'active'")->fetchAll();
        $bySlug = [];
        foreach ($found as $row) {
            $bySlug[(string) $row['slug']] = (string) $row['name'];
        }
        foreach (self::CATEGORIES as $slug => $name) {
            if (($bySlug[$slug] ?? '') !== $name) {
                throw new RuntimeException('Category ' . $name . ' is missing.');
            }
        }
    }

    private function markComplete(PDO $pdo): void
    {
        $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        )->execute(['key' => 'seed_version', 'value' => self::SEED_VERSION]);
    }

    /**
     * @param array{ok: bool, message: string, counts: array<string, int|null>, missing: list<string>, orphans: int} $report
     */
    private function failureText(array $report): string
    {
        $parts = [];
        if ($report['missing'] !== []) {
            $parts[] = 'Missing tables: ' . implode(', ', $report['missing']);
        }
        if ($report['orphans'] > 0) {
            $parts[] = 'Orphan place rows: ' . $report['orphans'];
        }
        $states = $report['counts']['states'] ?? 0;
        $categories = $report['counts']['categories'] ?? 0;
        $cities = $report['counts']['cities'] ?? 0;
        if ($states < 29 || $categories < 4 || $cities < self::MIN_CITIES) {
            $parts[] = 'Counts are states ' . (string) $states . ', categories ' . (string) $categories . ', cities ' . (string) $cities;
        }

        return $parts === [] ? 'Database initialization failed.' : implode('. ', $parts);
    }
}
