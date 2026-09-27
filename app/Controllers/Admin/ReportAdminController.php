<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\AuthService;

final class ReportAdminController extends AdminController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'reports')) {
            return $denied;
        }
        $status = (string) $request->query('status', '');
        $sql = 'SELECT r.*, l.title FROM reports r INNER JOIN listings l ON l.id = r.listing_id';
        $bind = [];
        if (in_array($status, ['open', 'reviewing', 'resolved', 'dismissed'], true)) {
            $sql .= ' WHERE r.status = :status';
            $bind['status'] = $status;
        }
        $sql .= ' ORDER BY r.created_at DESC LIMIT 100';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($bind);

        return $this->render('admin/reports', [
            'title' => 'Reports',
            'rows' => $statement->fetchAll(),
            'status' => $status,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): Response
    {
        if ($denied = $this->guard($request, 'reports')) {
            return $denied;
        }
        $id = (int) ($params['id'] ?? 0);
        $status = (string) $request->input('status', 'reviewing');
        if (!in_array($status, ['open', 'reviewing', 'resolved', 'dismissed'], true)) {
            $status = 'reviewing';
        }
        $notes = mb_substr((string) $request->input('admin_notes', ''), 0, 5000);
        $listingAction = (string) $request->input('listing_action', '');
        $report = Database::connection()->prepare('SELECT * FROM reports WHERE id = :id');
        $report->execute(['id' => $id]);
        $row = $report->fetch();
        if (!is_array($row)) {
            return (new \App\Controllers\ErrorController())->notFound();
        }
        Database::connection()->prepare(
            'UPDATE reports SET status = :status, admin_notes = :admin_notes, resolved_at = CASE WHEN :status_check IN (\'resolved\', \'dismissed\') THEN NOW() ELSE resolved_at END WHERE id = :id'
        )->execute([
            'status' => $status,
            'admin_notes' => $notes,
            'status_check' => $status,
            'id' => $id,
        ]);
        if (in_array($listingAction, ['suspend', 'delete', 'restore'], true)) {
            $newStatus = match ($listingAction) {
                'suspend' => 'suspended',
                'delete' => 'deleted',
                default => 'draft',
            };
            $moderation = $listingAction === 'suspend' ? 'suspended' : ($listingAction === 'delete' ? 'rejected' : 'pending');
            Database::connection()->prepare(
                'UPDATE listings SET status = :status, moderation_status = :moderation, is_indexable = 0 WHERE id = :id'
            )->execute(['status' => $newStatus, 'moderation' => $moderation, 'id' => (int) $row['listing_id']]);
        }
        $admin = (new AuthService())->user();
        (new AuditService())->log($admin ? (int) $admin['id'] : null, 'Report Resolved', 'report', $id, (string) $row['status'], $status, $request->ip());
        Session::flash('success', 'Report updated.');

        return Response::redirect('/admin/reports');
    }
}
