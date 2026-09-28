<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Str;
use App\Repositories\LocationRepository;

final class CsvImporter
{
    /**
     * @return array{imported: int, skipped: int, duplicated: int, failed: int, errors: list<string>}
     */
    public function import(string $path): array
    {
        $summary = ['imported' => 0, 'skipped' => 0, 'duplicated' => 0, 'failed' => 0, 'errors' => []];
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $summary['failed']++;
            $summary['errors'][] = 'The file could not be read.';

            return $summary;
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            $summary['failed']++;
            $summary['errors'][] = 'The file has no header row.';

            return $summary;
        }
        $header = array_map(static fn (mixed $value): string => strtolower(trim((string) $value)), $header);
        if ($header !== ['state', 'city', 'locality']) {
            fclose($handle);
            $summary['failed']++;
            $summary['errors'][] = 'The header must be state,city,locality.';

            return $summary;
        }

        $pdo = Database::connection();
        $stateLookup = $pdo->prepare('SELECT id FROM states WHERE name = :name OR slug = :slug LIMIT 1');
        $cityLookup = $pdo->prepare('SELECT id FROM cities WHERE state_id = :state_id AND slug = :slug LIMIT 1');
        $localityLookup = $pdo->prepare('SELECT id FROM locations WHERE city_id = :city_id AND slug = :slug LIMIT 1');
        $insertCity = $pdo->prepare('INSERT INTO cities (state_id, name, slug, status) VALUES (:state_id, :name, :slug, :status)');
        $insertLocality = $pdo->prepare('INSERT INTO locations (city_id, name, slug, status) VALUES (:city_id, :name, :slug, :status)');
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if ($line > 5001) {
                $summary['failed']++;
                $summary['errors'][] = 'Import stopped after 5000 data rows.';
                break;
            }
            if ($row === [null] || $row === []) {
                $summary['skipped']++;
                continue;
            }
            $stateName = $this->normalize((string) ($row[0] ?? ''));
            $cityName = $this->normalize((string) ($row[1] ?? ''));
            $localityName = $this->normalize((string) ($row[2] ?? ''));
            if ($stateName === '' || $cityName === '' || $localityName === '') {
                $summary['failed']++;
                $summary['errors'][] = 'Row ' . $line . ' is missing a value.';
                continue;
            }

            $stateLookup->execute(['name' => $stateName, 'slug' => Str::slug($stateName)]);
            $stateId = $stateLookup->fetchColumn();
            if ($stateId === false) {
                $summary['failed']++;
                $summary['errors'][] = 'Row ' . $line . ' uses an unknown state.';
                continue;
            }

            $citySlug = Str::slug($cityName);
            $cityLookup->execute(['state_id' => (int) $stateId, 'slug' => $citySlug]);
            $cityId = $cityLookup->fetchColumn();
            $createdCity = false;
            if ($cityId === false) {
                $insertCity->execute(['state_id' => (int) $stateId, 'name' => $cityName, 'slug' => $citySlug, 'status' => 'active']);
                $cityId = (int) $pdo->lastInsertId();
                $createdCity = true;
            }

            $localitySlug = Str::slug($localityName);
            $localityLookup->execute(['city_id' => (int) $cityId, 'slug' => $localitySlug]);
            if ($localityLookup->fetchColumn() !== false) {
                $summary['duplicated']++;
                continue;
            }
            $insertLocality->execute(['city_id' => (int) $cityId, 'name' => $localityName, 'slug' => $localitySlug, 'status' => 'active']);
            if ($createdCity) {
                $summary['imported']++;
            } else {
                $summary['imported']++;
            }
        }
        fclose($handle);
        LocationRepository::forgetReferenceData();

        return $summary;
    }

    private function normalize(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

        return mb_substr($value, 0, 140);
    }
}
