<?php /** @var list<array<string, mixed>> $rows */ ?>
<?php foreach ($rows as $row): ?>
    <article class="content-panel mb-3">
        <h2 class="h5"><?= e((string) $row['title']) ?> · <?= e((string) $row['reason']) ?></h2>
        <p><?= e((string) $row['message']) ?></p>
        <p class="meta"><?= e((string) $row['email']) ?> · <?= e((string) $row['status']) ?> · <?= e((string) $row['kind']) ?></p>
        <form method="post" action="/admin/reports/<?= e((string) $row['id']) ?>">
            <?= csrf_field() ?>
            <label for="notes-<?= e((string) $row['id']) ?>">Internal notes</label>
            <textarea class="form-control mb-2" id="notes-<?= e((string) $row['id']) ?>" name="admin_notes"><?= e((string) ($row['admin_notes'] ?? '')) ?></textarea>
            <select class="form-select mb-2" name="status">
                <?php foreach (['open', 'reviewing', 'resolved', 'dismissed'] as $status): ?>
                    <option value="<?= e($status) ?>"<?= $row['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select mb-2" name="listing_action">
                <option value="">No listing change</option>
                <option value="suspend">Suspend listing</option>
                <option value="delete">Delete listing</option>
                <option value="restore">Restore listing</option>
            </select>
            <button class="btn btn-brand btn-sm" type="submit">Save report</button>
        </form>
    </article>
<?php endforeach; ?>
