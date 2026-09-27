<?php

declare(strict_types=1);

namespace Database\Seeders;

use PDO;

final class StateSeeder
{
    /**
     * @return list<array{name: string, slug: string}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'Andhra Pradesh', 'slug' => 'andhra-pradesh'],
            ['name' => 'Arunachal Pradesh', 'slug' => 'arunachal-pradesh'],
            ['name' => 'Assam', 'slug' => 'assam'],
            ['name' => 'Bihar', 'slug' => 'bihar'],
            ['name' => 'Chhattisgarh', 'slug' => 'chhattisgarh'],
            ['name' => 'Goa', 'slug' => 'goa'],
            ['name' => 'Gujarat', 'slug' => 'gujarat'],
            ['name' => 'Haryana', 'slug' => 'haryana'],
            ['name' => 'Himachal Pradesh', 'slug' => 'himachal-pradesh'],
            ['name' => 'Jharkhand', 'slug' => 'jharkhand'],
            ['name' => 'Karnataka', 'slug' => 'karnataka'],
            ['name' => 'Kerala', 'slug' => 'kerala'],
            ['name' => 'Madhya Pradesh', 'slug' => 'madhya-pradesh'],
            ['name' => 'Maharashtra', 'slug' => 'maharashtra'],
            ['name' => 'Manipur', 'slug' => 'manipur'],
            ['name' => 'Meghalaya', 'slug' => 'meghalaya'],
            ['name' => 'Mizoram', 'slug' => 'mizoram'],
            ['name' => 'Nagaland', 'slug' => 'nagaland'],
            ['name' => 'Odisha', 'slug' => 'odisha'],
            ['name' => 'Punjab', 'slug' => 'punjab'],
            ['name' => 'Rajasthan', 'slug' => 'rajasthan'],
            ['name' => 'Sikkim', 'slug' => 'sikkim'],
            ['name' => 'Tamil Nadu', 'slug' => 'tamil-nadu'],
            ['name' => 'Telangana', 'slug' => 'telangana'],
            ['name' => 'Tripura', 'slug' => 'tripura'],
            ['name' => 'Uttar Pradesh', 'slug' => 'uttar-pradesh'],
            ['name' => 'Uttarakhand', 'slug' => 'uttarakhand'],
            ['name' => 'West Bengal', 'slug' => 'west-bengal'],
            ['name' => 'Delhi', 'slug' => 'delhi'],
        ];
    }

    public function run(PDO $pdo): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO states (name, slug, status)
             VALUES (:name, :slug, :status)
             ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status)'
        );

        foreach (self::definitions() as $state) {
            $statement->execute([
                'name' => $state['name'],
                'slug' => $state['slug'],
                'status' => 'active',
            ]);
        }
    }
}
