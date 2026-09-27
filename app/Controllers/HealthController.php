<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Logger;
use Throwable;

final class HealthController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($request, $params);

        try {
            $pdo = Database::connection();
            $states = (int) $pdo->query('SELECT COUNT(*) FROM states')->fetchColumn();
            $categories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
            $cities = (int) $pdo->query('SELECT COUNT(*) FROM cities')->fetchColumn();
        } catch (Throwable $exception) {
            Logger::error('Health check failed', $exception);

            return Response::json([
                'status' => 'error',
                'database' => 'unavailable',
            ], 503);
        }

        return Response::json([
            'status' => 'ok',
            'database' => 'connected',
            'states' => $states,
            'categories' => $categories,
            'cities' => $cities,
        ]);
    }
}
