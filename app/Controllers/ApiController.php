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
            return Response::json(['cities' => []], 422);
        }
        $cities = array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
        ], (new LocationRepository())->citiesByState($stateId, true));

        return Response::json(['cities' => $cities]);
    }

    /**
     * @param array<string, string> $params
     */
    public function localities(Request $request, array $params = []): Response
    {
        unset($params);
        $cityId = $request->integer('city_id');
        if ($cityId === null || $cityId < 1) {
            return Response::json(['locations' => []], 422);
        }
        $locations = array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
        ], (new LocationRepository())->localitiesByCity($cityId, true));

        return Response::json(['locations' => $locations]);
    }
}
