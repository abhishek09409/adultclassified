<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\CacheStore;
use PDO;

final class ListingRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function search(array $filters, bool $publicOnly, int $page, int $perPage = 12): array
    {
        [$where, $params] = $this->filters($filters, $publicOnly);
        $order = $this->order($filters, $publicOnly);
        $page = max(1, $page);
        $perPage = max(1, min(24, $perPage));
        $offset = ($page - 1) * $perPage;

        $pdo = Database::connection();
        $count = $pdo->prepare('SELECT COUNT(*) ' . $this->from() . ' WHERE ' . implode(' AND ', $where));
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $sql = 'SELECT l.id, l.title, l.slug, l.description, l.age, l.age_display, l.availability_note, l.status,
                       l.moderation_status, l.is_featured, l.is_verified, l.is_indexable, l.is_automated,
                       l.published_at, l.created_at, l.category_id, l.state_id, l.city_id, l.location_id,
                       c.name AS category_name, c.slug AS category_slug,
                       s.name AS state_name, s.slug AS state_slug,
                       ci.name AS city_name, ci.slug AS city_slug,
                       loc.name AS locality_name, loc.slug AS locality_slug,
                       img.path AS image_path, img.alt_text AS image_alt
                ' . $this->from() . '
                LEFT JOIN listing_images img ON img.id = (
                    SELECT li.id FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.sort_order ASC, li.id ASC LIMIT 1
                )
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ' . $order . '
                LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return ['rows' => $statement->fetchAll(), 'total' => $total];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id, bool $publicOnly = false): ?array
    {
        [$where, $params] = $this->filters(['id' => $id], $publicOnly);
        $sql = 'SELECT l.*, c.name AS category_name, c.slug AS category_slug,
                       s.name AS state_name, s.slug AS state_slug,
                       ci.name AS city_name, ci.slug AS city_slug,
                       loc.name AS locality_name, loc.slug AS locality_slug
                ' . $this->from() . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function images(int $listingId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, filename, path, thumbnail_path, alt_text, sort_order
             FROM listing_images WHERE listing_id = :id ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute(['id' => $listingId]);

        return $statement->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function popularCities(int $limit = 8): array
    {
        $limit = max(1, min(24, $limit));
        $rows = CacheStore::remember('popular-cities-' . $limit, 300, static function () use ($limit): array {
            $sql = "SELECT ci.id, ci.name, ci.slug, s.slug AS state_slug, s.name AS state_name, COUNT(l.id) AS listing_count
                    FROM cities ci
                    INNER JOIN states s ON s.id = ci.state_id
                    LEFT JOIN listings l ON l.city_id = ci.id AND l.status = 'published' AND l.moderation_status = 'approved'
                    WHERE ci.status = 'active' AND s.status = 'active'
                    GROUP BY ci.id, ci.name, ci.slug, s.slug, s.name
                    ORDER BY listing_count DESC, ci.name ASC
                    LIMIT {$limit}";

            return Database::connection()->query($sql)->fetchAll();
        });

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function categoryCounts(): array
    {
        return Database::connection()->query(
            "SELECT c.id, c.name, c.slug, c.description, COUNT(l.id) AS listing_count
             FROM categories c
             LEFT JOIN listings l ON l.category_id = c.id AND l.status = 'published' AND l.moderation_status = 'approved'
             WHERE c.status = 'active'
             GROUP BY c.id, c.name, c.slug, c.description
             ORDER BY c.id ASC"
        )->fetchAll();
    }

    /**
     * @param array<string, mixed> $listing
     * @return list<array<string, mixed>>
     */
    public function related(array $listing, int $limit = 4): array
    {
        $result = $this->search([
            'category_id' => (int) $listing['category_id'],
            'city_id' => (int) $listing['city_id'],
            'exclude_id' => (int) $listing['id'],
        ], true, 1, $limit);

        return $result['rows'];
    }

    private function from(): string
    {
        return 'FROM listings l
            INNER JOIN categories c ON c.id = l.category_id
            INNER JOIN states s ON s.id = l.state_id
            INNER JOIN cities ci ON ci.id = l.city_id
            INNER JOIN locations loc ON loc.id = l.location_id';
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function filters(array $filters, bool $publicOnly): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($publicOnly) {
            $where[] = "l.status = 'published'";
            $where[] = "l.moderation_status = 'approved'";
            $where[] = 'l.age >= 21';
            $where[] = "c.status = 'active'";
            $where[] = "s.status = 'active'";
            $where[] = "ci.status = 'active'";
            $where[] = "loc.status = 'active'";
        } elseif (!empty($filters['status']) && is_string($filters['status'])) {
            $where[] = 'l.status = :status';
            $params['status'] = $filters['status'];
        } else {
            $where[] = "l.status <> 'deleted'";
        }

        foreach (['category_id', 'state_id', 'city_id', 'location_id', 'id', 'exclude_id'] as $field) {
            if (!isset($filters[$field])) {
                continue;
            }
            $value = (string) $filters[$field];
            if (!ctype_digit($value)) {
                continue;
            }
            $column = $field === 'exclude_id' ? 'l.id <>' : 'l.' . ($field === 'id' ? 'id' : $field);
            if ($field === 'location_id') {
                $column = 'l.location_id =';
            } elseif ($field === 'exclude_id') {
                $column = 'l.id <>';
            } elseif ($field === 'id') {
                $column = 'l.id =';
            } else {
                $column = 'l.' . $field . ' =';
            }
            $where[] = $column . ' :' . $field;
            $params[$field] = (int) $value;
        }

        $keyword = trim((string) ($filters['q'] ?? ''));
        $keyword = str_replace(['%', '_'], '', $keyword);
        if (mb_strlen($keyword) >= 2) {
            if (mb_strlen($keyword) >= 3) {
                $boolean = preg_replace('/[+\-<>()~*"@]+/', ' ', $keyword) ?? $keyword;
                $boolean = trim(preg_replace('/\s+/', ' ', $boolean) ?? $boolean);
                if ($boolean !== '') {
                    $where[] = 'MATCH(l.title, l.description) AGAINST (:q IN BOOLEAN MODE)';
                    $params['q'] = $boolean;
                }
            } else {
                $where[] = '(l.title LIKE :q OR l.description LIKE :q)';
                $params['q'] = '%' . $keyword . '%';
            }
        }

        if (!empty($filters['featured'])) {
            $where[] = 'l.is_featured = 1';
        }

        return [$where, $params];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function order(array $filters, bool $publicOnly): string
    {
        $sort = (string) ($filters['sort'] ?? 'newest');
        if ($sort === 'featured') {
            return 'l.is_featured DESC, l.published_at DESC, l.id DESC';
        }

        return $publicOnly ? 'l.published_at DESC, l.id DESC' : 'l.created_at DESC, l.id DESC';
    }
}
