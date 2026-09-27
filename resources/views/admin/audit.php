<?php /** @var list<array<string, mixed>> $rows */ ?>
<table class="table"><thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Target</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?>
<tr>
    <td><?= e((string) $row['created_at']) ?></td>
    <td><?= e((string) ($row['email'] ?? '')) ?></td>
    <td><?= e((string) $row['action']) ?></td>
    <td><?= e((string) ($row['target_type'] ?? '')) ?> <?= e((string) ($row['target_id'] ?? '')) ?></td>
    <td><?= e((string) ($row['previous_status'] ?? '')) ?> → <?= e((string) ($row['new_status'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
