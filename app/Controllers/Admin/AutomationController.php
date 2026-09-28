<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\AutomationService;
use App\Services\SettingsService;

final class AutomationController extends AdminController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'automation')) {
            return $denied;
        }
        $logs = Database::connection()->query(
            'SELECT * FROM automation_logs ORDER BY id DESC LIMIT 50'
        )->fetchAll();

        return $this->render('admin/automation', [
            'title' => 'Automation',
            'status' => (new AutomationService())->status(),
            'settings' => new SettingsService(),
            'logs' => $logs,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function save(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'automation')) {
            return $denied;
        }
        $daily = max(0, min(AutomationService::ABSOLUTE_CAP, (int) $request->input('DAILY_AUTO_ADS', '5')));
        $settings = new SettingsService();
        $settings->putMany([
            'AUTOMATION_ENABLED' => $request->input('AUTOMATION_ENABLED') === '1' ? 'true' : 'false',
            'DAILY_AUTO_ADS' => (string) $daily,
            'CONTENT_REUSE_DAYS' => (string) max(1, (int) $request->input('CONTENT_REUSE_DAYS', '30')),
            'MAX_DUPLICATE_ATTEMPTS' => (string) max(1, min(10, (int) $request->input('MAX_DUPLICATE_ATTEMPTS', '10'))),
            'LOCATION_ROTATION' => $request->input('LOCATION_ROTATION') === '1' ? 'true' : 'false',
            'CATEGORY_ROTATION' => $request->input('CATEGORY_ROTATION') === '1' ? 'true' : 'false',
            'IMAGE_ROTATION' => $request->input('IMAGE_ROTATION') === '1' ? 'true' : 'false',
            'CALL_GIRLS_WEIGHT' => (string) max(1, (int) $request->input('CALL_GIRLS_WEIGHT', '30')),
            'MASSAGE_WEIGHT' => (string) max(1, (int) $request->input('MASSAGE_WEIGHT', '25')),
            'MALE_ESCORTS_WEIGHT' => (string) max(1, (int) $request->input('MALE_ESCORTS_WEIGHT', '20')),
            'ESCORTS_WEIGHT' => (string) max(1, (int) $request->input('ESCORTS_WEIGHT', '25')),
            'DUPLICATE_SIMILARITY' => (string) min(0.99, max(0.5, (float) $request->input('DUPLICATE_SIMILARITY', '0.82'))),
        ]);
        $admin = (new AuthService())->user();
        (new AuditService())->log($admin ? (int) $admin['id'] : null, 'Settings Changed', 'settings', null, null, 'automation', $request->ip());
        Session::flash('success', 'Automation settings saved. The daily cap cannot exceed 5.');

        return Response::redirect('/admin/automation');
    }

    /**
     * @param array<string, string> $params
     */
    public function run(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'automation')) {
            return $denied;
        }
        $status = (new AutomationService())->status();
        if ((int) $status['remaining'] < 1) {
            Session::flash('error', 'The daily limit has already been reached.');

            return Response::redirect('/admin/automation');
        }
        $admin = (new AuthService())->user();
        $result = (new AutomationService())->run('manual', $admin ? (int) $admin['id'] : null, $request->ip());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);

        return Response::redirect('/admin/automation');
    }
}
