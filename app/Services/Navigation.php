<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LocationRepository;
use Throwable;

final class Navigation
{
    /**
     * @return array{states: list<array<string, mixed>>, categories: list<array<string, mixed>>}
     */
    public static function data(): array
    {
        try {
            $locations = new LocationRepository();
            $states = array_values(array_filter($locations->states(), static fn (array $row): bool => ($row['status'] ?? '') === 'active'));
            $categories = array_values(array_filter($locations->categories(), static fn (array $row): bool => ($row['status'] ?? '') === 'active'));

            return ['states' => $states, 'categories' => $categories];
        } catch (Throwable) {
            return ['states' => [], 'categories' => []];
        }
    }
}
