<?php /** @var list<array<string, mixed>> $rows */ ?>
<form method="post" class="content-panel mb-4">
    <?= csrf_field() ?>
    <label for="name">Name</label>
    <input class="form-control mb-2" id="name" name="name" required>
    <label for="description">Description</label>
    <textarea class="form-control mb-2" id="description" name="description" required></textarea>
    <button class="btn btn-brand" type="submit">Add category</button>
</form>
<table class="table"><thead><tr><th>Name</th><th>Slug</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= e((string) $row['name']) ?></td><td><?= e((string) $row['slug']) ?></td><td><?= e((string) $row['status']) ?></td></tr><?php endforeach; ?>
</tbody></table>
