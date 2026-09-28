<?php

/** @var array<string, mixed> $counts */
/** @var array<string, int> $places */
/** @var array<string, mixed> $automation */
?>
<div class="stat-grid">
    <?php foreach ([
        'Total listings' => (int) ($counts['total_listings'] ?? 0),
        "Today's listings" => (int) ($counts['todays_listings'] ?? 0),
        'Published' => (int) ($counts['published'] ?? 0),
        'Pending' => (int) ($counts['pending'] ?? 0),
        'Suspended' => (int) ($counts['suspended'] ?? 0),
        'Featured' => (int) ($counts['featured'] ?? 0),
        'States' => $places['states'],
        'Cities' => $places['cities'],
        'Locations' => $places['locations'],
        'Open reports' => $places['reports'],
    ] as $label => $value): ?>
        <article class="stat-card"><span><?= e($label) ?></span><strong><?= e((string) $value) ?></strong></article>
    <?php endforeach; ?>
    <article class="stat-card">
        <span>Automation</span>
        <strong><?= e((string) $automation['created_today']) ?> / <?= e((string) $automation['cap']) ?></strong>
        <p class="meta">Remaining <?= e((string) $automation['remaining']) ?>. Next configured run <?= e((string) $automation['next_run']) ?> if cron is installed.</p>
    </article>
</div>
