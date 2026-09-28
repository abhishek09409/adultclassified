<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Helpers\Str;
use PDO;
use PDOStatement;

final class LocationSeeder
{
    /**
     * Real neighborhoods for the original city set. Other cities get two area names.
     *
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

    /**
     * @return array<string, list<string>>
     */
    public static function citiesByState(): array
    {
        $path = dirname(__DIR__, 2) . '/resources/data/india-cities.json';
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return [];
        }
        $cities = [];
        foreach ($decoded as $state => $names) {
            if (!is_string($state) || !is_array($names)) {
                continue;
            }
            $cities[$state] = array_values(array_filter($names, 'is_string'));
        }

        return $cities;
    }

    public function run(PDO $pdo): void
    {
        $state = $pdo->prepare('SELECT id FROM states WHERE slug = :slug LIMIT 1');
        $cityLookup = $pdo->prepare('SELECT id FROM cities WHERE state_id = :state_id AND slug = :slug LIMIT 1');
        $insertCity = $pdo->prepare('INSERT INTO cities (state_id, name, slug, status) VALUES (:state_id, :name, :slug, :status)');
        $localityLookup = $pdo->prepare('SELECT id FROM locations WHERE city_id = :city_id AND slug = :slug LIMIT 1');
        $insertLocality = $pdo->prepare('INSERT INTO locations (city_id, name, slug, status) VALUES (:city_id, :name, :slug, :status)');

        foreach (self::citiesByState() as $stateName => $cities) {
            $state->execute(['slug' => Str::slug($stateName)]);
            $stateId = $state->fetchColumn();
            if ($stateId === false) {
                continue;
            }
            foreach ($cities as $cityName) {
                $cityId = $this->cityId($pdo, $cityLookup, $insertCity, (int) $stateId, $cityName);
                $this->ensureLocalities($localityLookup, $insertLocality, $cityId, self::defaultLocalities($cityName));
            }
        }

        foreach (self::definitions() as $row) {
            $state->execute(['slug' => Str::slug($row['state'])]);
            $stateId = $state->fetchColumn();
            if ($stateId === false) {
                continue;
            }
            $cityId = $this->cityId($pdo, $cityLookup, $insertCity, (int) $stateId, $row['city']);
            $this->ensureLocalities($localityLookup, $insertLocality, $cityId, $row['localities']);
        }
    }

    /**
     * @return list<string>
     */
    public static function defaultLocalities(string $city): array
    {
        $pool = ['Civil Lines', 'Station Road', 'Gandhi Nagar', 'Sadar Bazaar', 'Model Town', 'Shastri Nagar', 'Rajendra Nagar', 'Cantonment', 'Nehru Nagar', 'Subhash Nagar'];
        $index = abs(crc32(mb_strtolower($city)));
        $first = $pool[$index % count($pool)];
        $second = $pool[($index + 4) % count($pool)];

        return [$city . ' Central', $first === $second ? 'Market Area' : $second];
    }

    /**
     * @param list<string> $localities
     */
    private function ensureLocalities(PDOStatement $lookup, PDOStatement $insert, int $cityId, array $localities): void
    {
        foreach ($localities as $locality) {
            $slug = Str::slug($locality);
            if ($slug === '') {
                continue;
            }
            $lookup->execute(['city_id' => $cityId, 'slug' => $slug]);
            if ($lookup->fetchColumn() === false) {
                $insert->execute(['city_id' => $cityId, 'name' => $locality, 'slug' => $slug, 'status' => 'active']);
            }
        }
    }

    private function cityId(PDO $pdo, PDOStatement $lookup, PDOStatement $insert, int $stateId, string $name): int
    {
        $slug = Str::slug($name);
        $lookup->execute(['state_id' => $stateId, 'slug' => $slug]);
        $cityId = $lookup->fetchColumn();
        if ($cityId !== false) {
            return (int) $cityId;
        }
        $insert->execute(['state_id' => $stateId, 'name' => $name, 'slug' => $slug, 'status' => 'active']);

        return (int) $pdo->lastInsertId();
    }
}
