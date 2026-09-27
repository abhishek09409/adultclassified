<?php /** @var array<string, mixed>|null $summary */ ?>
<form method="post" enctype="multipart/form-data" class="content-panel">
    <?= csrf_field() ?>
    <label for="csv">locations.csv</label>
    <input class="form-control mb-3" id="csv" name="csv" type="file" accept=".csv,text/csv" required>
    <p class="meta">Header: state,city,locality. Unknown states are rejected. Cities and localities are created when missing.</p>
    <button class="btn btn-brand" type="submit">Import</button>
</form>
<?php if (is_array($summary)): ?>
    <ul class="mt-3">
        <li>Imported <?= e((string) $summary['imported']) ?></li>
        <li>Skipped <?= e((string) $summary['skipped']) ?></li>
        <li>Duplicated <?= e((string) $summary['duplicated']) ?></li>
        <li>Failed <?= e((string) $summary['failed']) ?></li>
    </ul>
    <?php foreach ($summary['errors'] as $error): ?><p><?= e((string) $error) ?></p><?php endforeach; ?>
<?php endif; ?>
