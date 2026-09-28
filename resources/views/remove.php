<?php

declare(strict_types=1);

/** @var int|null $listingId */
/** @var array<string, string> $reasons */
/** @var string|null $notice */
?>
<div class="container section">
    <div class="content-panel">
        <h1>Request removal</h1>
        <p>FLAG FOR LEGAL REVIEW. This form records a takedown request for a moderator. It does not by itself decide a legal claim.</p>
        <?php if ($notice): ?><p class="alert alert-info"><?= e($notice) ?></p><?php endif; ?>
        <form method="post" action="/remove-listing">
            <?= csrf_field() ?>
            <input type="hidden" name="kind" value="takedown">
            <label for="listing_id">Listing ID</label>
            <input class="form-control mb-3" id="listing_id" name="listing_id" required value="<?= e($listingId ? (string) $listingId : '') ?>">
            <label for="reason">Reason</label>
            <select class="form-select mb-3" id="reason" name="reason" required>
                <?php foreach ($reasons as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="email">Email</label>
            <input class="form-control mb-3" id="email" name="email" type="email" required>
            <label for="message">Message</label>
            <textarea class="form-control mb-3" id="message" name="message" rows="5" required></textarea>
            <button class="btn btn-brand" type="submit">Submit request</button>
        </form>
    </div>
</div>
