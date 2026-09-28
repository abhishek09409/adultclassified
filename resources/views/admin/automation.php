<?php

/** @var array<string, mixed> $status */
/** @var App\Services\SettingsService $settings */
/** @var list<array<string, mixed>> $logs */
?>
<div class="stat-grid mb-4">
    <article class="stat-card"><span>Today</span><strong><?= e((string) $status['created_today']) ?> / <?= e((string) $status['cap']) ?></strong></article>
    <article class="stat-card"><span>Remaining</span><strong><?= e((string) $status['remaining']) ?></strong></article>
    <article class="stat-card"><span>Next configured run</span><strong><?= e((string) $status['next_run']) ?></strong><p class="meta">Requires the server cron. Last error: <?= e((string) ($status['last_error'] ?: 'none')) ?></p></article>
</div>
<form method="post" action="/admin/automation/run" class="mb-4">
    <?= csrf_field() ?>
    <button class="btn btn-brand" type="submit"<?= (int) $status['remaining'] < 1 ? ' disabled' : '' ?>>Run automation now</button>
</form>
<form method="post" action="/admin/automation" class="content-panel">
    <?= csrf_field() ?>
    <label><input type="checkbox" name="AUTOMATION_ENABLED" value="1"<?= $settings->bool('AUTOMATION_ENABLED') ? ' checked' : '' ?>> Automation enabled</label>
    <label class="d-block mt-2" for="daily">Daily listings (max 5)</label>
    <input class="form-control" id="daily" name="DAILY_AUTO_ADS" value="<?= e($settings->get('DAILY_AUTO_ADS', '5')) ?>">
    <label class="d-block mt-2" for="reuse">Content reuse days</label>
    <input class="form-control" id="reuse" name="CONTENT_REUSE_DAYS" value="<?= e($settings->get('CONTENT_REUSE_DAYS', '30')) ?>">
    <label class="d-block mt-2" for="attempts">Duplicate attempts</label>
    <input class="form-control" id="attempts" name="MAX_DUPLICATE_ATTEMPTS" value="<?= e($settings->get('MAX_DUPLICATE_ATTEMPTS', '10')) ?>">
    <label class="d-block mt-2"><input type="checkbox" name="LOCATION_ROTATION" value="1"<?= $settings->bool('LOCATION_ROTATION', true) ? ' checked' : '' ?>> Location rotation</label>
    <label class="d-block"><input type="checkbox" name="CATEGORY_ROTATION" value="1"<?= $settings->bool('CATEGORY_ROTATION', true) ? ' checked' : '' ?>> Category rotation</label>
    <label class="d-block"><input type="checkbox" name="IMAGE_ROTATION" value="1"<?= $settings->bool('IMAGE_ROTATION', true) ? ' checked' : '' ?>> Image rotation</label>
    <div class="row mt-2">
        <?php foreach (['CALL_GIRLS_WEIGHT' => 'Call girls', 'MASSAGE_WEIGHT' => 'Massage', 'MALE_ESCORTS_WEIGHT' => 'Male escorts', 'ESCORTS_WEIGHT' => 'Escorts'] as $key => $label): ?>
            <div class="col-md-3"><label for="<?= e($key) ?>"><?= e($label) ?></label><input class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($settings->get($key, '1')) ?>"></div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-brand mt-3" type="submit">Save settings</button>
</form>
<h2 class="h4 mt-4">Recent log</h2>
<ul><?php foreach ($logs as $log): ?><li><?= e((string) $log['created_at']) ?> · <?= e((string) $log['action']) ?> · <?= e((string) $log['message']) ?></li><?php endforeach; ?></ul>
