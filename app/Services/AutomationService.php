<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Str;
use PDO;
use Throwable;

final class AutomationService
{
    public const ABSOLUTE_CAP = 5;

    public function __construct(
        private readonly SettingsService $settings = new SettingsService(),
        private readonly ComplianceValidator $compliance = new ComplianceValidator(),
        private readonly DuplicateDetector $duplicates = new DuplicateDetector(),
        private readonly ImageService $images = new ImageService(),
        private readonly SeoService $seo = new SeoService(),
        private readonly AuditService $audit = new AuditService()
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $cap = $this->cap();
        $created = $this->publishedToday();
        $last = Database::connection()->query(
            'SELECT run_date, started_at, completed_at, status, error_message, created_count FROM automation_runs ORDER BY run_date DESC LIMIT 1'
        )->fetch();
        $hour = max(0, min(23, $this->settings->int('CRON_HOUR', 9)));
        $next = new \DateTimeImmutable('today ' . str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00:00');
        if ($next <= new \DateTimeImmutable('now')) {
            $next = $next->modify('+1 day');
        }

        return [
            'enabled' => $this->settings->bool('AUTOMATION_ENABLED', true),
            'cap' => $cap,
            'created_today' => $created,
            'remaining' => max(0, $cap - $created),
            'last_run' => is_array($last) ? $last : null,
            'last_error' => is_array($last) ? (string) ($last['error_message'] ?? '') : '',
            'next_run' => $next->format('Y-m-d H:i'),
        ];
    }

    /**
     * @return array{ok: bool, message: string, created: int}
     */
    public function run(string $trigger, ?int $adminId = null, ?string $ip = null): array
    {
        if (!$this->settings->bool('AUTOMATION_ENABLED', true)) {
            return ['ok' => false, 'message' => 'Automation is disabled.', 'created' => 0];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $runId = $this->acquire($pdo, $trigger);
            if ($runId === null) {
                $pdo->commit();

                return ['ok' => false, 'message' => 'Automation is already running.', 'created' => 0];
            }

            $remaining = max(0, $this->cap() - $this->publishedToday());
            if ($remaining === 0) {
                $this->finish($pdo, $runId, 0, 0, null);
                $pdo->commit();

                return ['ok' => true, 'message' => 'The daily limit has already been reached.', 'created' => 0];
            }

            $created = 0;
            $failed = 0;
            $usedCityIds = [];
            $usedAssetIds = [];
            $lastCategoryId = $this->lastCategoryId();
            for ($slot = 0; $slot < $remaining; $slot++) {
                $published = $this->publishSlot($pdo, $runId, $usedCityIds, $usedAssetIds, $lastCategoryId);
                if ($published === null) {
                    $failed++;
                    continue;
                }
                $created++;
                $usedCityIds[] = $published['city_id'];
                $usedAssetIds[] = $published['asset_id'];
                $lastCategoryId = $published['category_id'];
            }

            $this->finish($pdo, $runId, $created, $failed, $failed > 0 && $created === 0 ? 'No listing passed validation.' : null);
            $this->audit->log($adminId, 'Automation Run', 'automation_run', $runId, null, 'completed', $ip);
            $pdo->commit();

            return ['ok' => true, 'message' => 'Published ' . $created . ' listing' . ($created === 1 ? '' : 's') . '.', 'created' => $created];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function cap(): int
    {
        return min(self::ABSOLUTE_CAP, max(0, $this->settings->int('DAILY_AUTO_ADS', self::ABSOLUTE_CAP)));
    }

    private function publishedToday(): int
    {
        return (int) Database::connection()->query(
            "SELECT COUNT(*) FROM listings WHERE is_automated = 1 AND status = 'published' AND published_at >= CURDATE() AND published_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
        )->fetchColumn();
    }

    private function acquire(PDO $pdo, string $trigger): ?int
    {
        $today = date('Y-m-d');
        $statement = $pdo->prepare('SELECT id, status, started_at FROM automation_runs WHERE run_date = :run_date FOR UPDATE');
        $statement->execute(['run_date' => $today]);
        $row = $statement->fetch();
        $token = bin2hex(random_bytes(16));
        if (!is_array($row)) {
            $insert = $pdo->prepare(
                'INSERT INTO automation_runs (run_date, started_at, requested_count, status, lock_token, trigger_source)
                 VALUES (:run_date, NOW(), :requested_count, :status, :lock_token, :trigger_source)'
            );
            $insert->execute([
                'run_date' => $today,
                'requested_count' => $this->cap(),
                'status' => 'running',
                'lock_token' => $token,
                'trigger_source' => $trigger === 'manual' ? 'manual' : 'cron',
            ]);

            return (int) $pdo->lastInsertId();
        }

        $started = strtotime((string) $row['started_at']) ?: 0;
        if ($row['status'] === 'running' && $started > time() - 900) {
            return null;
        }

        $pdo->prepare(
            'UPDATE automation_runs SET started_at = NOW(), status = :status, lock_token = :lock_token, trigger_source = :trigger_source, error_message = NULL WHERE id = :id'
        )->execute([
            'status' => 'running',
            'lock_token' => $token,
            'trigger_source' => $trigger === 'manual' ? 'manual' : 'cron',
            'id' => (int) $row['id'],
        ]);

        return (int) $row['id'];
    }

    private function finish(PDO $pdo, int $runId, int $created, int $failed, ?string $error): void
    {
        $pdo->prepare(
            'UPDATE automation_runs
             SET completed_at = NOW(), created_count = created_count + :created, failed_count = failed_count + :failed,
                 status = :status, lock_token = NULL, error_message = :error_message
             WHERE id = :id'
        )->execute([
            'created' => $created,
            'failed' => $failed,
            'status' => 'completed',
            'error_message' => $error,
            'id' => $runId,
        ]);
    }

    /**
     * @param list<int> $usedCityIds
     * @param list<int> $usedAssetIds
     * @return array{city_id: int, category_id: int, asset_id: int}|null
     */
    private function publishSlot(PDO $pdo, int $runId, array $usedCityIds, array $usedAssetIds, ?int $lastCategoryId): ?array
    {
        $location = $this->selectLocation($usedCityIds);
        if ($location === null) {
            $this->log($runId, 'listing_failed', 'No active locality is available.', null, null, null);

            return null;
        }
        $category = $this->selectCategory($lastCategoryId);
        if ($category === null) {
            $this->log($runId, 'listing_failed', 'No active category is available.', null, (int) $location['id'], null);

            return null;
        }

        $maxAttempts = min(10, max(1, $this->settings->int('MAX_DUPLICATE_ATTEMPTS', 10)));
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $pieces = $this->selectPieces((int) $category['id'], $attempt);
            if ($pieces === null) {
                $this->log($runId, 'listing_failed', 'Variation pools are empty.', null, (int) $location['id'], (int) $category['id']);

                return null;
            }
            $combination = hash('sha256', implode(':', array_column($pieces, 'id')) . '|' . $location['id'] . '|' . $category['id']);
            if ($this->duplicates->combinationUsedRecently($combination)) {
                $this->log($runId, 'duplicate_failed', 'variation_combination', null, (int) $location['id'], (int) $category['id']);
                continue;
            }

            $replace = [
                '{locality}' => (string) $location['name'],
                '{city}' => (string) $location['city_name'],
                '{state}' => (string) $location['state_name'],
                '{label}' => (string) $category['label'],
                '{category}' => (string) $category['name'],
            ];
            $filled = [];
            foreach ($pieces as $piece) {
                $filled[$piece['type']] = strtr((string) $piece['content'], $replace);
            }
            $title = mb_substr($filled['title'], 0, 180);
            $description = trim($filled['introduction'] . "\n\n" . $filled['location_phrase'] . "\n\n" . $filled['category_info'] . "\n\n" . $filled['description_block'] . "\n\n" . $filled['availability'] . "\n\n" . $filled['closing']);
            $this->log($runId, 'candidate_generated', 'attempt ' . ($attempt + 1), null, (int) $location['id'], (int) $category['id']);

            $reason = $this->compliance->failureReason($title . "\n" . $description, 21);
            if ($reason !== null) {
                $this->log($runId, 'compliance_failed', $reason, null, (int) $location['id'], (int) $category['id']);
                continue;
            }
            if ($this->duplicates->isDuplicateContent($title, $description, (int) $location['id'], (int) $category['id'])) {
                $this->log($runId, 'duplicate_failed', 'content_similarity', null, (int) $location['id'], (int) $category['id']);
                continue;
            }
            if (mb_strlen($title) < 15 || mb_strlen($description) < 80 || substr_count(mb_strtolower($description), mb_strtolower((string) $location['city_name'])) > 8) {
                $this->log($runId, 'listing_failed', 'seo_validation', null, (int) $location['id'], (int) $category['id']);
                continue;
            }

            $asset = $this->settings->bool('IMAGE_ROTATION', true) ? $this->images->nextAsset($usedAssetIds) : $this->images->nextAsset([]);
            if ($asset === null) {
                $this->log($runId, 'listing_failed', 'image_missing', null, (int) $location['id'], (int) $category['id']);
                continue;
            }

            $listingId = $this->insertListing($pdo, $location, $category, $title, $description, $runId);
            $this->images->attachAsset($listingId, $asset);
            $this->log($runId, 'image_selected', 'asset ' . $asset['id'], $listingId, (int) $location['id'], (int) $category['id']);
            $this->recordVariations($pdo, $listingId, $pieces, $combination);
            $saved = $pdo->prepare(
                'SELECT l.*, c.name AS category_name, c.slug AS category_slug, s.name AS state_name, s.slug AS state_slug,
                        ci.name AS city_name, ci.slug AS city_slug, loc.name AS locality_name
                 FROM listings l
                 INNER JOIN categories c ON c.id = l.category_id
                 INNER JOIN states s ON s.id = l.state_id
                 INNER JOIN cities ci ON ci.id = l.city_id
                 INNER JOIN locations loc ON loc.id = l.location_id
                 WHERE l.id = :id'
            );
            $saved->execute(['id' => $listingId]);
            $row = $saved->fetch();
            if (is_array($row)) {
                $this->seo->storeListing($row);
            }
            $this->log($runId, 'listing_published', 'published', $listingId, (int) $location['id'], (int) $category['id']);

            return [
                'city_id' => (int) $location['city_id'],
                'category_id' => (int) $category['id'],
                'asset_id' => (int) $asset['id'],
            ];
        }

        $this->log($runId, 'listing_failed', 'attempts_exhausted', null, (int) $location['id'], (int) $category['id']);

        return null;
    }

    /**
     * @param array<string, mixed> $location
     * @param array<string, mixed> $category
     */
    private function insertListing(PDO $pdo, array $location, array $category, string $title, string $description, int $runId): int
    {
        $detector = $this->duplicates;
        $statement = $pdo->prepare(
            'INSERT INTO listings (
                category_id, state_id, city_id, location_id, title, slug, description, age, age_display, status,
                moderation_status, is_featured, is_verified, is_indexable, is_automated, automation_run_id,
                content_fingerprint, title_fingerprint, description_fingerprint, published_at
             ) VALUES (
                :category_id, :state_id, :city_id, :location_id, :title, :slug, :description, 21, :age_display, :status,
                :moderation_status, 0, 0, 1, 1, :automation_run_id,
                :content_fingerprint, :title_fingerprint, :description_fingerprint, NOW()
             )'
        );
        $statement->execute([
            'category_id' => (int) $category['id'],
            'state_id' => (int) $location['state_id'],
            'city_id' => (int) $location['city_id'],
            'location_id' => (int) $location['id'],
            'title' => $title,
            'slug' => 'pending-' . bin2hex(random_bytes(4)),
            'description' => $description,
            'age_display' => 'minimum',
            'status' => 'published',
            'moderation_status' => 'approved',
            'automation_run_id' => $runId,
            'content_fingerprint' => $detector->fingerprint($title, $description),
            'title_fingerprint' => hash('sha256', $detector->normalize($title)),
            'description_fingerprint' => hash('sha256', $detector->normalize($description)),
        ]);
        $id = (int) $pdo->lastInsertId();
        $slug = mb_substr(Str::slug($title), 0, 160) . '-' . $id;
        $pdo->prepare('UPDATE listings SET slug = :slug WHERE id = :id')->execute(['slug' => $slug, 'id' => $id]);

        return $id;
    }

    /**
     * @param list<array{id: int, type: string, content: string}> $pieces
     */
    private function recordVariations(PDO $pdo, int $listingId, array $pieces, string $combination): void
    {
        $usage = $pdo->prepare(
            'INSERT INTO listing_variation_usage (variation_type, variation_id, listing_id, usage_count, last_used_at)
             VALUES (:variation_type, :variation_id, :listing_id, 1, NOW())'
        );
        $touch = $pdo->prepare('UPDATE listing_variations SET usage_count = usage_count + 1, last_used_at = NOW() WHERE id = :id');
        foreach ($pieces as $piece) {
            $usage->execute([
                'variation_type' => $piece['type'],
                'variation_id' => $piece['id'],
                'listing_id' => $listingId,
            ]);
            $touch->execute(['id' => $piece['id']]);
        }
        $pdo->prepare('INSERT INTO listing_variation_sets (listing_id, fingerprint) VALUES (:listing_id, :fingerprint)')
            ->execute(['listing_id' => $listingId, 'fingerprint' => $combination]);
    }

    /**
     * @param list<int> $usedCityIds
     * @return array<string, mixed>|null
     */
    private function selectLocation(array $usedCityIds): ?array
    {
        $rotate = $this->settings->bool('LOCATION_ROTATION', true);
        $sql = 'SELECT loc.id, loc.name, loc.city_id, ci.name AS city_name, ci.state_id, s.name AS state_name
                FROM locations loc
                INNER JOIN cities ci ON ci.id = loc.city_id AND ci.status = \'active\'
                INNER JOIN states s ON s.id = ci.state_id AND s.status = \'active\'
                LEFT JOIN (
                    SELECT location_id, MAX(published_at) AS last_pub FROM listings WHERE is_automated = 1 GROUP BY location_id
                ) used ON used.location_id = loc.id
                WHERE loc.status = \'active\'
                ORDER BY ' . ($rotate ? 'used.last_pub IS NULL DESC, used.last_pub ASC, loc.id ASC' : 'loc.id ASC');
        $rows = Database::connection()->query($sql)->fetchAll();
        if ($rows === []) {
            return null;
        }
        if ($usedCityIds !== []) {
            $fresh = array_values(array_filter($rows, static fn (array $row): bool => !in_array((int) $row['city_id'], $usedCityIds, true)));
            if ($fresh !== []) {
                $rows = $fresh;
            }
        }

        return $rows[0];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function selectCategory(?int $avoidId): ?array
    {
        $rows = Database::connection()->query("SELECT id, name, slug FROM categories WHERE status = 'active' ORDER BY id ASC")->fetchAll();
        if ($rows === []) {
            return null;
        }
        $labels = ContentLibrary::categories();
        $weights = [
            'call-girls' => max(1, $this->settings->int('CALL_GIRLS_WEIGHT', 30)),
            'massage' => max(1, $this->settings->int('MASSAGE_WEIGHT', 25)),
            'male-escorts' => max(1, $this->settings->int('MALE_ESCORTS_WEIGHT', 20)),
            'escorts' => max(1, $this->settings->int('ESCORTS_WEIGHT', 25)),
        ];
        $bag = [];
        foreach ($rows as $row) {
            $weight = $weights[$row['slug']] ?? 1;
            for ($i = 0; $i < $weight; $i++) {
                $bag[] = $row;
            }
        }
        $rotate = $this->settings->bool('CATEGORY_ROTATION', true);
        for ($try = 0; $try < 8; $try++) {
            $picked = $bag[random_int(0, count($bag) - 1)];
            if (!$rotate || $avoidId === null || (int) $picked['id'] !== $avoidId || count($rows) === 1) {
                $picked['label'] = $labels[$picked['slug']] ?? $picked['name'];

                return $picked;
            }
        }
        $picked = $rows[0];
        $picked['label'] = $labels[$picked['slug']] ?? $picked['name'];

        return $picked;
    }

    /**
     * @return list<array{id: int, type: string, content: string}>|null
     */
    private function selectPieces(int $categoryId, int $attempt): ?array
    {
        $types = ['title', 'introduction', 'location_phrase', 'category_info', 'description_block', 'availability', 'closing'];
        $pieces = [];
        foreach ($types as $type) {
            $shared = in_array($type, ['location_phrase', 'availability'], true);
            $sql = 'SELECT id, content FROM listing_variations WHERE variation_type = :type AND status = \'active\' AND '
                . ($shared ? 'category_id IS NULL' : 'category_id = :category_id')
                . ' ORDER BY last_used_at IS NULL DESC, last_used_at ASC, usage_count ASC, id ASC LIMIT 12';
            $statement = Database::connection()->prepare($sql);
            $params = ['type' => $type];
            if (!$shared) {
                $params['category_id'] = $categoryId;
            }
            $statement->execute($params);
            $pool = $statement->fetchAll();
            if ($pool === []) {
                return null;
            }
            $row = $pool[$attempt % count($pool)];
            $pieces[] = ['id' => (int) $row['id'], 'type' => $type, 'content' => (string) $row['content']];
        }

        return $pieces;
    }

    private function lastCategoryId(): ?int
    {
        $value = Database::connection()->query(
            "SELECT category_id FROM listings WHERE is_automated = 1 ORDER BY id DESC LIMIT 1"
        )->fetchColumn();

        return $value === false ? null : (int) $value;
    }

    private function log(int $runId, string $action, string $message, ?int $listingId, ?int $locationId, ?int $categoryId): void
    {
        Database::connection()->prepare(
            'INSERT INTO automation_logs (run_id, listing_id, location_id, category_id, action, message)
             VALUES (:run_id, :listing_id, :location_id, :category_id, :action, :message)'
        )->execute([
            'run_id' => $runId,
            'listing_id' => $listingId,
            'location_id' => $locationId,
            'category_id' => $categoryId,
            'action' => $action,
            'message' => mb_substr($message, 0, 1000),
        ]);
    }
}
