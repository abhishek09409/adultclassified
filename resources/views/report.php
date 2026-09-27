<?php

declare(strict_types=1);

/** @var int|null $listingId */
/** @var array<string, string> $reasons */
/** @var string|null $notice */
/** @var string $kind */
?>
<div class="container section">
    <div class="content-panel">
        <h1>Report a listing</h1>
        <?php if ($notice): ?><p class="alert alert-info"><?= e($notice) ?></p><?php endif; ?>
        <form method="post" action="/report">
            <?= csrf_field() ?>
            <input type="hidden" name="kind" value="<?= e($kind) ?>">
            <label for="listing_id">Listing ID</label>
            <input class="form-control mb-3" id="listing_id" name="listing_id" inputmode="numeric" required value="<?= e($listingId ? (string) $listingId : '') ?>">
            <label for="reason">Reason</label>
            <select class="form-select mb-3" id="reason" name="reason" required>
                <?php foreach ($reasons as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="email">Email</label>
            <input class="form-control mb-3" id="email" name="email" type="email" required autocomplete="email">
            <label for="message">Message</label>
            <textarea class="form-control mb-3" id="message" name="message" rows="5" required></textarea>
            <button class="btn btn-brand" type="submit">Submit report</button>
        </form>
    </div>
</div>
