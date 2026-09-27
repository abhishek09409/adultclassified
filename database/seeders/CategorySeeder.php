<?php

declare(strict_types=1);

namespace Database\Seeders;

use PDO;

final class CategorySeeder
{
    /**
     * @return list<array{name: string, slug: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            [
                'name' => 'Call Girls',
                'slug' => 'call-girls',
                'description' => 'Classified listings for adult companions, organized by state, city, and locality. Profiles are limited to adults aged 21 or older and use non-explicit directory descriptions.',
            ],
            [
                'name' => 'Massage',
                'slug' => 'massage',
                'description' => 'Classified listings for adult massage services, organized by state, city, and locality. Descriptions stay general and do not claim licenses, medical outcomes, or verification.',
            ],
            [
                'name' => 'Male Escorts',
                'slug' => 'male-escorts',
                'description' => 'Classified listings for adult male companions, organized by state, city, and locality. Profiles are limited to adults aged 21 or older.',
            ],
            [
                'name' => 'Escorts',
                'slug' => 'escorts',
                'description' => 'Classified listings for adult escort services, organized by state, city, and locality. Copy stays non-explicit and does not invent credentials or reviews.',
            ],
        ];
    }

    public function run(PDO $pdo): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO categories (name, slug, description, status)
             VALUES (:name, :slug, :description, :status) AS new_row
             ON DUPLICATE KEY UPDATE name = new_row.name, description = new_row.description, status = new_row.status'
        );

        foreach (self::definitions() as $category) {
            $statement->execute([
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => $category['description'],
                'status' => 'active',
            ]);
        }
    }
}
