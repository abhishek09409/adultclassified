<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\ComplianceValidator;
use App\Services\ContentLibrary;
use App\Services\ImageService;
use PDO;

final class CatalogSeeder
{
    public function run(PDO $pdo): void
    {
        $this->settings($pdo);
        $this->variations($pdo);
        (new ImageService())->ensureLibrary();
    }

    private function settings(PDO $pdo): void
    {
        $defaults = [
            'DAILY_AUTO_ADS' => '5',
            'AUTOMATION_ENABLED' => 'true',
            'CONTENT_REUSE_DAYS' => '30',
            'MAX_DUPLICATE_ATTEMPTS' => '10',
            'LOCATION_ROTATION' => 'true',
            'CATEGORY_ROTATION' => 'true',
            'IMAGE_ROTATION' => 'true',
            'CALL_GIRLS_WEIGHT' => '30',
            'MASSAGE_WEIGHT' => '25',
            'MALE_ESCORTS_WEIGHT' => '20',
            'ESCORTS_WEIGHT' => '25',
            'DUPLICATE_SIMILARITY' => '0.82',
            'CRON_HOUR' => '9',
        ];
        $statement = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_key = setting_key'
        );
        foreach ($defaults as $key => $value) {
            $statement->execute(['setting_key' => $key, 'setting_value' => $value]);
        }
    }

    private function variations(PDO $pdo): void
    {
        $validator = new ComplianceValidator();
        $categories = [];
        foreach ($pdo->query('SELECT id, slug FROM categories')->fetchAll() as $row) {
            $categories[(string) $row['slug']] = (int) $row['id'];
        }
        $pdo->exec("UPDATE listing_variations SET status = 'inactive'");
        $insert = $pdo->prepare(
            'INSERT INTO listing_variations (variation_type, category_id, content, fingerprint, status)
             VALUES (:variation_type, :category_id, :content, :fingerprint, :status) AS new_row
             ON DUPLICATE KEY UPDATE content = new_row.content, status = new_row.status'
        );
        foreach (ContentLibrary::variations() as $item) {
            $reason = $validator->failureReason((string) $item['content'], 21);
            if ($reason !== null) {
                throw new \RuntimeException('Variation failed compliance check: ' . $reason);
            }
            $categoryId = $item['category'] === null ? null : ($categories[$item['category']] ?? null);
            $insert->bindValue('variation_type', $item['type']);
            if ($categoryId === null) {
                $insert->bindValue('category_id', null, PDO::PARAM_NULL);
            } else {
                $insert->bindValue('category_id', $categoryId, PDO::PARAM_INT);
            }
            $insert->bindValue('content', $item['content']);
            $insert->bindValue('fingerprint', hash('sha256', $item['type'] . '|' . (string) $item['category'] . '|' . $item['content']));
            $insert->bindValue('status', 'active');
            $insert->execute();
        }
    }
}
