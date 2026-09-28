<?php

declare(strict_types=1);

/** @var array{ok: bool, message: string, counts: array<string, int|null>, missing: list<string>, orphans: int} $report */
$counts = $report['counts'];
?>
<p>This writes states, categories, cities, and localities into MySQL. It does not drop tables or existing listings.</p>
<?php if ($report['missing'] !== []): ?>
    <p class="alert alert-danger">Missing tables: <?= e(implode(', ', $report['missing'])) ?></p>
<?php endif; ?>
<?php if (($report['orphans'] ?? 0) > 0): ?>
    <p class="alert alert-danger">Orphan place rows: <?= e((string) $report['orphans']) ?></p>
<?php endif; ?>
<ul>
    <li>States: <?= e((string) ($counts['states'] ?? 0)) ?></li>
    <li>Categories: <?= e((string) ($counts['categories'] ?? 0)) ?></li>
    <li>Cities: <?= e((string) ($counts['cities'] ?? 0)) ?></li>
    <li>Locations: <?= e((string) ($counts['locations'] ?? 0)) ?></li>
    <li>Listings: <?= e((string) ($counts['listings'] ?? 0)) ?></li>
</ul>
<p><?= $report['ok'] ? 'Reference data is present.' : e($report['message']) ?></p>
<form method="post" action="/admin/system/setup">
    <?= csrf_field() ?>
    <button class="btn btn-brand" type="submit">Run initialization</button>
</form>
