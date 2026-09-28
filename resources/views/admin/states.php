<?php /** @var list<array<string, mixed>> $rows */ ?>
<table class="table"><thead><tr><th>Name</th><th>Slug</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $row): ?>
<tr>
    <td><?= e((string) $row['name']) ?></td>
    <td><?= e((string) $row['slug']) ?></td>
    <td><?= e((string) $row['status']) ?></td>
    <td>
        <form method="post"><?= csrf_field() ?>
            <input type="hidden" name="toggle_id" value="<?= e((string) $row['id']) ?>">
            <input type="hidden" name="status" value="<?= $row['status'] === 'active' ? 'inactive' : 'active' ?>">
            <button class="btn btn-sm btn-outline-dark" type="submit"><?= $row['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table>
