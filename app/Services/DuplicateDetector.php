<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class DuplicateDetector
{
    public function __construct(private readonly SettingsService $settings = new SettingsService())
    {
    }

    public function isDuplicateContent(string $title, string $description, int $locationId, int $categoryId): bool
    {
        $pdo = Database::connection();
        $normalizedTitle = $this->normalize($title);
        $normalizedDescription = $this->normalize($description);
        $titleHash = hash('sha256', $normalizedTitle);
        $descriptionHash = hash('sha256', $normalizedDescription);
        $contentHash = hash('sha256', $normalizedTitle . "\n" . $normalizedDescription);

        $exact = $pdo->prepare(
            'SELECT id FROM listings
             WHERE status <> :deleted AND (title = :title OR description = :description OR title_fingerprint = :title_hash
                OR description_fingerprint = :description_hash OR content_fingerprint = :content_hash
                OR (title_fingerprint = :title_hash_location AND location_id = :location_id))
             LIMIT 1'
        );
        $exact->execute([
            'deleted' => 'deleted',
            'title' => $title,
            'description' => $description,
            'title_hash' => $titleHash,
            'description_hash' => $descriptionHash,
            'content_hash' => $contentHash,
            'title_hash_location' => $titleHash,
            'location_id' => $locationId,
        ]);
        if ($exact->fetch() !== false) {
            return true;
        }

        $threshold = (float) $this->settings->get('DUPLICATE_SIMILARITY', '0.82');
        $nearby = $pdo->prepare(
            'SELECT title, description FROM listings
             WHERE status <> :deleted AND (location_id = :location_id OR category_id = :category_id)
             ORDER BY id DESC LIMIT 40'
        );
        $nearby->execute([
            'deleted' => 'deleted',
            'location_id' => $locationId,
            'category_id' => $categoryId,
        ]);
        $candidateTokens = $this->tokens($normalizedTitle . ' ' . $normalizedDescription);
        foreach ($nearby->fetchAll() as $row) {
            $other = $this->tokens($this->normalize((string) $row['title'] . ' ' . (string) $row['description']));
            if ($this->jaccard($candidateTokens, $other) >= $threshold) {
                return true;
            }
        }

        return false;
    }

    public function combinationUsedRecently(string $fingerprint): bool
    {
        $days = max(1, (new SettingsService())->int('CONTENT_REUSE_DAYS', 30));
        $statement = Database::connection()->prepare(
            'SELECT id FROM listing_variation_sets
             WHERE fingerprint = :fingerprint AND created_at >= (NOW() - INTERVAL ' . $days . ' DAY)
             LIMIT 1'
        );
        $statement->execute(['fingerprint' => $fingerprint]);

        return $statement->fetch() !== false;
    }

    public function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    public function fingerprint(string $title, string $description): string
    {
        return hash('sha256', $this->normalize($title) . "\n" . $this->normalize($description));
    }

    /**
     * @return array<string, true>
     */
    private function tokens(string $value): array
    {
        $tokens = [];
        $stop = array_flip(['the', 'and', 'for', 'this', 'that', 'with', 'from', 'directory', 'listing', 'listings', 'adult', 'adults', 'record', 'page', 'category', 'published', 'locality', 'over', 'aged', 'only', 'not', 'does', 'are', 'was', 'has', 'have', 'its', 'into', 'than', 'then', 'when', 'your', 'other', 'public']);
        foreach (preg_split('/\s+/', $value) ?: [] as $token) {
            if (strlen($token) > 2 && !isset($stop[$token])) {
                $tokens[$token] = true;
            }
        }

        return $tokens;
    }

    /**
     * @param array<string, true> $left
     * @param array<string, true> $right
     */
    private function jaccard(array $left, array $right): float
    {
        if ($left === [] || $right === []) {
            return 0.0;
        }
        $intersection = count(array_intersect_key($left, $right));
        $union = count($left + $right);

        return $union === 0 ? 0.0 : $intersection / $union;
    }
}
