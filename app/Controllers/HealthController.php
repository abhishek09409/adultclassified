<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\Logger;
use App\Services\DatabaseInitializer;
use Throwable;

final class HealthController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($request, $params);
        DatabaseInitializer::ensureIfNeeded();

        try {
            $report = (new DatabaseInitializer())->report();
        } catch (Throwable $exception) {
            Logger::error('Health check failed', $exception);

            return Response::json([
                'status' => 'error',
                'database' => 'unavailable',
                'bootstrap' => $exception->getMessage(),
            ], 503);
        }

        return Response::json([
            'status' => $report['ok'] ? 'ok' : 'incomplete',
            'database' => 'connected',
            'tables' => [
                'states' => $report['counts']['states'] ?? 0,
                'categories' => $report['counts']['categories'] ?? 0,
                'cities' => $report['counts']['cities'] ?? 0,
                'locations' => $report['counts']['locations'] ?? 0,
                'listings' => $report['counts']['listings'] ?? 0,
            ],
            'bootstrap' => DatabaseInitializer::message(),
            'orphans' => $report['orphans'],
        ]);
    }
}
