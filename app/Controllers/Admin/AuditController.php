<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class AuditController extends AdminController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'audit')) {
            return $denied;
        }
        $rows = Database::connection()->query(
            'SELECT a.*, admins.email FROM audit_logs a LEFT JOIN admins ON admins.id = a.admin_id ORDER BY a.id DESC LIMIT 200'
        )->fetchAll();

        return $this->render('admin/audit', [
            'title' => 'Audit logs',
            'rows' => $rows,
        ]);
    }
}
