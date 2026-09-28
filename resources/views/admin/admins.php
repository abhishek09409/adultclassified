<?php /** @var list<array<string, mixed>> $rows */ ?>
<form method="post" class="content-panel mb-4">
    <?= csrf_field() ?>
    <label for="name">Name</label><input class="form-control mb-2" id="name" name="name" required>
    <label for="email">Email</label><input class="form-control mb-2" id="email" name="email" type="email" required>
    <label for="password">Password</label><input class="form-control mb-2" id="password" name="password" type="password" minlength="12" required>
    <label for="role">Role</label>
    <select class="form-select mb-2" id="role" name="role">
        <?php foreach (['editor', 'moderator', 'admin', 'super_admin'] as $role): ?><option value="<?= e($role) ?>"><?= e($role) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-brand" type="submit">Create admin</button>
</form>
<table class="table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= e((string) $row['name']) ?></td><td><?= e((string) $row['email']) ?></td><td><?= e((string) $row['role']) ?></td><td><?= e((string) $row['status']) ?></td></tr><?php endforeach; ?>
</tbody></table>
