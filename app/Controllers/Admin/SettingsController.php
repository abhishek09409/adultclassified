<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\Gate;

final class SettingsController extends AdminController
{
    /**
     * @param array<string, string> $params
     */
    public function settings(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'settings')) {
            return $denied;
        }

        return $this->render('admin/settings', [
            'title' => 'Settings',
            'rows' => Database::connection()->query('SELECT setting_key, setting_value, updated_at FROM settings ORDER BY setting_key ASC')->fetchAll(),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function seo(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'seo')) {
            return $denied;
        }

        return $this->render('admin/seo', [
            'title' => 'SEO',
            'rows' => Database::connection()->query('SELECT path, title, robots, updated_at FROM seo_metadata ORDER BY updated_at DESC LIMIT 100')->fetchAll(),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function admins(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'dashboard')) {
            return $denied;
        }
        $admin = (new AuthService())->user();
        if (!Gate::allows($admin, '*') && ($admin['role'] ?? '') !== 'super_admin') {
            return (new \App\Controllers\ErrorController())->forbidden();
        }
        if ($request->method() === 'POST') {
            $this->saveAdmin($request);
        }

        return $this->render('admin/admins', [
            'title' => 'Admins',
            'rows' => Database::connection()->query('SELECT id, name, email, role, status, last_login_at FROM admins ORDER BY id ASC')->fetchAll(),
        ]);
    }

    private function saveAdmin(Request $request): void
    {
        $name = mb_substr((string) $request->input('name', ''), 0, 120);
        $email = strtolower((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $role = (string) $request->input('role', 'editor');
        if (!in_array($role, ['super_admin', 'admin', 'moderator', 'editor'], true) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || $name === '') {
            Session::flash('error', 'Use a name, email, role, and a password of at least 12 characters.');

            return;
        }
        AuthService::createAdmin(Database::connection(), $name, $email, $password, $role);
        $actor = (new AuthService())->user();
        (new AuditService())->log($actor ? (int) $actor['id'] : null, 'Settings Changed', 'admin', null, null, $role, $request->ip());
        Session::flash('success', 'Admin account created.');
    }
}
