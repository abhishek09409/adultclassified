<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\DatabaseInitializer;
use Throwable;

final class SetupController extends AdminController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($params);
        $denied = $this->guard($request, 'settings');
        if ($denied !== null) {
            return $denied;
        }

        $initializer = new DatabaseInitializer();
        if ($request->method() === 'POST') {
            try {
                $initializer->run();
                Session::flash('success', 'Database initialization completed.');
            } catch (Throwable $exception) {
                Session::flash('error', $exception->getMessage());
            }

            return Response::redirect('/admin/system/setup');
        }

        try {
            $report = $initializer->report();
        } catch (Throwable $exception) {
            $report = [
                'ok' => false,
                'message' => $exception->getMessage(),
                'counts' => [],
                'missing' => DatabaseInitializer::REQUIRED_TABLES,
                'orphans' => 0,
            ];
        }

        return $this->render('admin/setup', [
            'title' => 'Database setup',
            'report' => $report,
        ]);
    }
}
