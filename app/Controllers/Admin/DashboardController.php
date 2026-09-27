<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AutomationService;

final class DashboardController extends AdminController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'dashboard')) {
            return $denied;
        }
        $pdo = Database::connection();
        $counts = $pdo->query(
            "SELECT
                SUM(status <> 'deleted') AS total_listings,
                SUM(status <> 'deleted' AND DATE(created_at) = CURDATE()) AS todays_listings,
                SUM(status = 'published') AS published,
                SUM(status = 'pending') AS pending,
                SUM(status = 'suspended') AS suspended,
                SUM(is_featured = 1 AND status <> 'deleted') AS featured
             FROM listings"
        )->fetch();
        $places = [
            'states' => (int) $pdo->query('SELECT COUNT(*) FROM states')->fetchColumn(),
            'cities' => (int) $pdo->query('SELECT COUNT(*) FROM cities')->fetchColumn(),
            'locations' => (int) $pdo->query('SELECT COUNT(*) FROM locations')->fetchColumn(),
            'reports' => (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'open'")->fetchColumn(),
        ];

        return $this->render('admin/dashboard', [
            'title' => 'Dashboard',
            'counts' => is_array($counts) ? $counts : [],
            'places' => $places,
            'automation' => (new AutomationService())->status(),
        ]);
    }
}
