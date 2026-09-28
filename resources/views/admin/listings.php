<?php /** @var list<array<string, mixed>> $listings */ /** @var string $status */ ?>
<p><a class="btn btn-brand" href="/admin/listings/create">New listing</a></p>
<form method="get" class="mb-3">
    <label for="status">Status</label>
    <select id="status" name="status" class="form-select" style="max-width:16rem">
        <option value="">All except deleted</option>
        <?php foreach (['draft', 'pending', 'published', 'suspended', 'deleted'] as $item): ?>
            <option value="<?= e($item) ?>"<?= $status === $item ? ' selected' : '' ?>><?= e($item) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-dark mt-2" type="submit">Filter</button>
</form>
<table class="table">
    <thead><tr><th>Title</th><th>Place</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($listings as $listing): ?>
        <tr>
            <td><?= e((string) $listing['title']) ?></td>
            <td><?= e((string) $listing['city_name']) ?></td>
            <td><?= e((string) $listing['status']) ?></td>
            <td><a href="/admin/listings/<?= e((string) $listing['id']) ?>/edit">Edit</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
