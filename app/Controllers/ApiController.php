<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\LocationRepository;

final class ApiController
{
    /**
     * @param array<string, string> $params
     */
    public function cities(Request $request, array $params = []): Response
    {
        unset($params);
        $stateId = $request->integer('state_id');
        if ($stateId === null || $stateId < 1) {
            return Response::json(['success' => false, 'data' => [], 'cities' => []], 422);
        }
        $cities = array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
        ], (new LocationRepository())->citiesByState($stateId, true));

        return Response::json(['success' => true, 'data' => $cities, 'cities' => $cities]);
    }

    /**
     * @param array<string, string> $params
     */
    public function localities(Request $request, array $params = []): Response
    {
        unset($params);
        $cityId = $request->integer('city_id');
        if ($cityId === null || $cityId < 1) {
            return Response::json(['success' => false, 'data' => [], 'locations' => []], 422);
        }
        $locations = array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
        ], (new LocationRepository())->localitiesByCity($cityId, true));

        return Response::json(['success' => true, 'data' => $locations, 'locations' => $locations]);
    }

    /**
     * @param array<string, string> $params
     */
    public function states(Request $request, array $params = []): Response
    {
        unset($request, $params);
        $states = array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
        ], array_values(array_filter(
            (new LocationRepository())->states(),
            static fn (array $row): bool => ($row['status'] ?? '') === 'active'
        )));

        return Response::json(['success' => true, 'data' => $states, 'states' => $states]);
    }

    /**
     * @param array<string, string> $params
     */
    public function categories(Request $request, array $params = []): Response
    {
        unset($request, $params);
        $categories = array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
        ], array_values(array_filter(
            (new LocationRepository())->categories(),
            static fn (array $row): bool => ($row['status'] ?? '') === 'active'
        )));

        return Response::json(['success' => true, 'data' => $categories, 'categories' => $categories]);
    }
}
