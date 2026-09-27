<?php

declare(strict_types=1);

use App\Services\ListingPresenter;

/** @var array<string, mixed> $listing */
$url = ListingPresenter::url($listing);
$image = ListingPresenter::uploadUrl(isset($listing['image_path']) ? (string) $listing['image_path'] : null);
?>
<article class="listing-card">
    <a class="card-media" href="<?= e($url) ?>">
        <?php if ($image !== ''): ?>
            <img src="<?= e($image) ?>" alt="<?= e((string) ($listing['image_alt'] ?: $listing['title'])) ?>" loading="lazy" width="640" height="420">
        <?php else: ?>
            <span class="media-fallback" role="img" aria-label="No photo for this listing">21+</span>
        <?php endif; ?>
    </a>
    <div class="card-body">
        <ul class="badge-row">
            <?php foreach (ListingPresenter::badges($listing) as $badge): ?>
                <li class="pill"><?= e($badge) ?></li>
            <?php endforeach; ?>
        </ul>
        <h2><a href="<?= e($url) ?>"><?= e((string) $listing['title']) ?></a></h2>
        <p class="meta"><?= e((string) $listing['category_name']) ?> · <?= e((string) $listing['state_name']) ?> · <?= e((string) $listing['city_name']) ?> · <?= e((string) $listing['locality_name']) ?></p>
        <p><?= e(ListingPresenter::excerpt($listing)) ?></p>
        <p class="meta">Age <?= e(ListingPresenter::ageLabel($listing)) ?> · <?= e((string) ($listing['status'] === 'published' ? 'Published' : (string) $listing['status'])) ?> · <?= e(ListingPresenter::postedOn($listing)) ?></p>
        <a class="btn btn-brand" href="<?= e($url) ?>">View Profile</a>
    </div>
</article>
