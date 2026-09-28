<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\CacheStore;

final class LocationRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function states(): array
    {
        $rows = CacheStore::remember('states', 300, static function (): array {
            return Database::connection()->query(
                "SELECT id, name, slug, status FROM states ORDER BY name ASC"
            )->fetchAll();
        });

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function categories(): array
    {
        $rows = CacheStore::remember('categories', 300, static function (): array {
            return Database::connection()->query(
                'SELECT id, name, slug, description, status FROM categories ORDER BY id ASC'
            )->fetchAll();
        });

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function citiesByState(int $stateId, bool $activeOnly = true): array
    {
        $sql = 'SELECT id, state_id, name, slug, status FROM cities WHERE state_id = :state_id';
        if ($activeOnly) {
            $sql .= " AND status = 'active'";
        }
        $sql .= ' ORDER BY name ASC';
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['state_id' => $stateId]);

        return $statement->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function localitiesByCity(int $cityId, bool $activeOnly = true): array
    {
        $sql = 'SELECT id, city_id, name, slug, status FROM locations WHERE city_id = :city_id';
        if ($activeOnly) {
            $sql .= " AND status = 'active'";
        }
        $sql .= ' ORDER BY name ASC';
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['city_id' => $cityId]);

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function stateBySlug(string $slug): ?array
    {
        return $this->one('SELECT * FROM states WHERE slug = :slug AND status = :status LIMIT 1', ['slug' => $slug, 'status' => 'active']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function categoryBySlug(string $slug): ?array
    {
        return $this->one('SELECT * FROM categories WHERE slug = :slug AND status = :status LIMIT 1', ['slug' => $slug, 'status' => 'active']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function cityBySlug(int $stateId, string $slug): ?array
    {
        return $this->one(
            'SELECT * FROM cities WHERE state_id = :state_id AND slug = :slug AND status = :status LIMIT 1',
            ['state_id' => $stateId, 'slug' => $slug, 'status' => 'active']
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function localityBySlug(int $cityId, string $slug): ?array
    {
        return $this->one(
            'SELECT * FROM locations WHERE city_id = :city_id AND slug = :slug AND status = :status LIMIT 1',
            ['city_id' => $cityId, 'slug' => $slug, 'status' => 'active']
        );
    }

    public static function forgetReferenceData(): void
    {
        CacheStore::forget('states');
        CacheStore::forget('categories');
        CacheStore::forget('popular-cities');
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    private function one(string $sql, array $params): ?array
    {
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }
}
