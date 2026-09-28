<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AuditService
{
    public function log(?int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $previous = null, ?string $newStatus = null, ?string $ip = null): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO audit_logs (admin_id, action, target_type, target_id, previous_status, new_status, ip_address)
             VALUES (:admin_id, :action, :target_type, :target_id, :previous_status, :new_status, :ip_address)'
        );
        $statement->execute([
            'admin_id' => $adminId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'previous_status' => $previous,
            'new_status' => $newStatus,
            'ip_address' => $ip,
        ]);
    }
}
