<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Helpers\Str;
use PDO;

final class LocationSeeder
{
    /**
     * @return list<array{state: string, city: string, localities: list<string>}>
     */
    public static function definitions(): array
    {
        return [
            ['state' => 'Andhra Pradesh', 'city' => 'Visakhapatnam', 'localities' => ['MVP Colony', 'Dwaraka Nagar']],
            ['state' => 'Arunachal Pradesh', 'city' => 'Itanagar', 'localities' => ['Bank Tinali', 'Ganga']],
            ['state' => 'Assam', 'city' => 'Guwahati', 'localities' => ['Paltan Bazaar', 'Dispur']],
            ['state' => 'Bihar', 'city' => 'Patna', 'localities' => ['Boring Road', 'Kankarbagh']],
            ['state' => 'Chhattisgarh', 'city' => 'Raipur', 'localities' => ['Shankar Nagar', 'Pandri']],
            ['state' => 'Goa', 'city' => 'Panaji', 'localities' => ['Dona Paula', 'Fontainhas']],
            ['state' => 'Gujarat', 'city' => 'Ahmedabad', 'localities' => ['Navrangpura', 'Satellite']],
            ['state' => 'Haryana', 'city' => 'Gurugram', 'localities' => ['DLF Phase 1', 'Sector 29']],
            ['state' => 'Himachal Pradesh', 'city' => 'Shimla', 'localities' => ['Mall Road', 'Sanjauli']],
            ['state' => 'Jharkhand', 'city' => 'Ranchi', 'localities' => ['Lalpur', 'Harmu']],
            ['state' => 'Karnataka', 'city' => 'Bengaluru', 'localities' => ['Indiranagar', 'Koramangala']],
            ['state' => 'Kerala', 'city' => 'Kochi', 'localities' => ['Ernakulam', 'Fort Kochi']],
            ['state' => 'Madhya Pradesh', 'city' => 'Bhopal', 'localities' => ['MP Nagar', 'Arera Colony']],
            ['state' => 'Maharashtra', 'city' => 'Mumbai', 'localities' => ['Andheri', 'Bandra']],
            ['state' => 'Manipur', 'city' => 'Imphal', 'localities' => ['Thangal Bazar', 'Lamphel']],
            ['state' => 'Meghalaya', 'city' => 'Shillong', 'localities' => ['Police Bazar', 'Laitumkhrah']],
            ['state' => 'Mizoram', 'city' => 'Aizawl', 'localities' => ['Zarkawt', 'Bara Bazar']],
            ['state' => 'Nagaland', 'city' => 'Kohima', 'localities' => ['BOC', 'PR Hill']],
            ['state' => 'Odisha', 'city' => 'Bhubaneswar', 'localities' => ['Saheed Nagar', 'Jayadev Vihar']],
            ['state' => 'Punjab', 'city' => 'Amritsar', 'localities' => ['Lawrence Road', 'Ranjit Avenue']],
            ['state' => 'Rajasthan', 'city' => 'Jaipur', 'localities' => ['C Scheme', 'Malviya Nagar']],
            ['state' => 'Sikkim', 'city' => 'Gangtok', 'localities' => ['MG Marg', 'Deorali']],
            ['state' => 'Tamil Nadu', 'city' => 'Chennai', 'localities' => ['T Nagar', 'Adyar']],
            ['state' => 'Telangana', 'city' => 'Hyderabad', 'localities' => ['Banjara Hills', 'Jubilee Hills']],
            ['state' => 'Tripura', 'city' => 'Agartala', 'localities' => ['Agartala Bazaar', 'Dhaleswar']],
            ['state' => 'Uttar Pradesh', 'city' => 'Lucknow', 'localities' => ['Hazratganj', 'Gomti Nagar']],
            ['state' => 'Uttarakhand', 'city' => 'Dehradun', 'localities' => ['Rajpur Road', 'Clement Town']],
            ['state' => 'West Bengal', 'city' => 'Kolkata', 'localities' => ['Park Street', 'Salt Lake']],
            ['state' => 'Delhi', 'city' => 'New Delhi', 'localities' => ['Connaught Place', 'Saket']],
        ];
    }

    public function run(PDO $pdo): void
    {
        $state = $pdo->prepare('SELECT id FROM states WHERE slug = :slug LIMIT 1');
        $cityLookup = $pdo->prepare('SELECT id FROM cities WHERE state_id = :state_id AND slug = :slug LIMIT 1');
        $insertCity = $pdo->prepare('INSERT INTO cities (state_id, name, slug, status) VALUES (:state_id, :name, :slug, :status)');
        $localityLookup = $pdo->prepare('SELECT id FROM locations WHERE city_id = :city_id AND slug = :slug LIMIT 1');
        $insertLocality = $pdo->prepare('INSERT INTO locations (city_id, name, slug, status) VALUES (:city_id, :name, :slug, :status)');

        foreach (self::definitions() as $row) {
            $state->execute(['slug' => Str::slug($row['state'])]);
            $stateId = $state->fetchColumn();
            if ($stateId === false) {
                continue;
            }
            $citySlug = Str::slug($row['city']);
            $cityLookup->execute(['state_id' => (int) $stateId, 'slug' => $citySlug]);
            $cityId = $cityLookup->fetchColumn();
            if ($cityId === false) {
                $insertCity->execute(['state_id' => (int) $stateId, 'name' => $row['city'], 'slug' => $citySlug, 'status' => 'active']);
                $cityId = (int) $pdo->lastInsertId();
            }
            foreach ($row['localities'] as $locality) {
                $slug = Str::slug($locality);
                $localityLookup->execute(['city_id' => (int) $cityId, 'slug' => $slug]);
                if ($localityLookup->fetchColumn() === false) {
                    $insertLocality->execute(['city_id' => (int) $cityId, 'name' => $locality, 'slug' => $slug, 'status' => 'active']);
                }
            }
        }
    }
}
