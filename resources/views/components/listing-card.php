<?php

declare(strict_types=1);

use App\Services\ListingPresenter;

/** @var array<string, mixed> $listing */
$url = ListingPresenter::url($listing);
$image = ListingPresenter::uploadUrl(isset($listing['image_path']) ? (string) $listing['image_path'] : null);
$featured = (int) ($listing['is_featured'] ?? 0) === 1;
$badges = ListingPresenter::badges($listing);
$showNew = !$featured && in_array('New', $badges, true);
$showRecent = !$featured && in_array('Recently Added', $badges, true);
?>
<article class="ad-card">
    <div class="ad-photo">
        <?php if ($featured): ?>
            <span class="premium-badge"><span aria-hidden="true">♛</span> TOP PREMIUM</span>
        <?php elseif ($showRecent): ?>
            <span class="premium-badge">RECENT</span>
        <?php elseif ($showNew): ?>
            <span class="premium-badge">NEW</span>
        <?php endif; ?>
        <button class="heart-btn" type="button" aria-pressed="false" aria-label="Save listing">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9z"/></svg>
        </button>
        <a href="<?= e($url) ?>">
            <?php if ($image !== ''): ?>
                <img src="<?= e($image) ?>" alt="<?= e((string) ($listing['image_alt'] ?: $listing['title'])) ?>" loading="lazy" width="420" height="460">
            <?php else: ?>
                <span class="media-fallback" role="img" aria-label="No photo for this listing">21+</span>
            <?php endif; ?>
        </a>
    </div>
    <div class="ad-copy">
        <?php if ((int) ($listing['is_verified'] ?? 0) === 1): ?>
            <p class="verified-line">Verified</p>
        <?php endif; ?>
        <h2 class="ad-title"><a href="<?= e($url) ?>"><?= e((string) $listing['title']) ?></a></h2>
        <p class="ad-excerpt"><?= e(ListingPresenter::excerpt($listing, 180)) ?></p>
        <p class="ad-meta"><?= e(ListingPresenter::ageLabel($listing)) ?> Years | <?= e((string) $listing['category_name']) ?> | <?= e((string) $listing['city_name']) ?></p>
        <p class="ad-submeta"><?= e((string) $listing['locality_name']) ?>, <?= e((string) $listing['state_name']) ?> · <?= e(ListingPresenter::postedOn($listing)) ?></p>
    </div>
    <div class="ad-actions">
        <a class="btn-wa" href="<?= e($url) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 0 0-7.7 13.5L3 21l4.7-1.2A9 9 0 1 0 12 3zm0 2a7 7 0 0 1 0 14c-1.1 0-2.2-.3-3.1-.8l-.4-.2-2 .5.5-1.9-.2-.4A7 7 0 0 1 12 5zm-3 3.2c.1 0 .3 0 .4.3.2.3.7 1.2.7 1.3.1.1 0 .3 0 .4-.1.2-.2.3-.3.5l-.2.2c-.1.1-.2.2-.1.4.2.3.7 1.1 1.6 1.5.8.4 1 .3 1.2.2.2-.1.4-.4.6-.6.1-.2.2-.2.4-.1.2.1 1.3.6 1.5.7.2.1.3.1.4.2.1.2 0 .8-.2 1.2-.3.4-.9.7-1.5.6-.4 0-.9 0-1.6-.3-1.5-.6-2.5-2-2.6-2.1-.4-.5-.9-1.3-.9-2.1 0-.8.4-1.3.6-1.5.2-.2.3-.3.5-.3z"/></svg>
            WhatsApp
        </a>
        <a class="btn-call" href="<?= e($url) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h3l1 4-2 1a12 12 0 0 0 6 6l1-2 4 1v3c0 1-1 2-2 2C10 18 6 14 4 7c0-1 1-2 2-2z"/></svg>
            Call Now
        </a>
    </div>
</article>
