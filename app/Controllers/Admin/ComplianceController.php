<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class ComplianceController extends AdminController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'compliance')) {
            return $denied;
        }
        $pdo = Database::connection();
        $reason = (string) $request->query('reason', '');
        $status = (string) $request->query('status', '');
        $sql = 'SELECT r.*, l.title, c.name AS category_name, s.name AS state_name FROM reports r
                INNER JOIN listings l ON l.id = r.listing_id
                INNER JOIN categories c ON c.id = l.category_id
                INNER JOIN states s ON s.id = l.state_id WHERE 1 = 1';
        $bind = [];
        if ($reason !== '' && preg_match('/^[a-z_]+$/', $reason) === 1) {
            $sql .= ' AND r.reason = :reason';
            $bind['reason'] = $reason;
        }
        if (in_array($status, ['open', 'reviewing', 'resolved', 'dismissed'], true)) {
            $sql .= ' AND r.status = :status';
            $bind['status'] = $status;
        }
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) === 1) {
            $sql .= ' AND r.created_at >= :from_date';
            $bind['from_date'] = $from . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) === 1) {
            $sql .= ' AND r.created_at <= :to_date';
            $bind['to_date'] = $to . ' 23:59:59';
        }
        $sql .= ' ORDER BY r.created_at DESC LIMIT 100';
        $reports = $pdo->prepare($sql);
        $reports->execute($bind);

        return $this->render('admin/compliance', [
            'title' => 'Compliance',
            'reports' => $reports->fetchAll(),
            'pending' => $pdo->query("SELECT id, title, status FROM listings WHERE status = 'pending' ORDER BY id DESC LIMIT 20")->fetchAll(),
            'suspended' => $pdo->query("SELECT id, title FROM listings WHERE status = 'suspended' ORDER BY id DESC LIMIT 20")->fetchAll(),
            'takedowns' => $pdo->query("SELECT r.id, r.reason, r.status, l.title FROM reports r INNER JOIN listings l ON l.id = r.listing_id WHERE r.kind = 'takedown' ORDER BY r.id DESC LIMIT 20")->fetchAll(),
            'failures' => $pdo->query("SELECT id, action, message, created_at FROM automation_logs WHERE action IN ('compliance_failed', 'duplicate_failed', 'listing_failed') ORDER BY id DESC LIMIT 20")->fetchAll(),
            'audit' => $pdo->query('SELECT id, action, target_type, target_id, created_at FROM audit_logs ORDER BY id DESC LIMIT 15')->fetchAll(),
            'messages' => $pdo->query('SELECT id, email, created_at FROM contact_messages ORDER BY id DESC LIMIT 10')->fetchAll(),
            'filters' => ['reason' => $reason, 'status' => $status, 'from' => $from, 'to' => $to],
        ]);
    }
}
