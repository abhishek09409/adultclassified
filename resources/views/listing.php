<?php

declare(strict_types=1);

use App\Services\ListingPresenter;

/** @var array<string, mixed> $listing */
/** @var list<array<string, mixed>> $images */
/** @var list<array<string, mixed>> $related */
/** @var list<array{name: string, url: string}> $crumbs */
?>
<div class="container section">
    <?php \App\Core\View::partial('components/breadcrumb', ['crumbs' => $crumbs]); ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="gallery">
                <?php if ($images === []): ?>
                    <span class="media-fallback">21+</span>
                <?php endif; ?>
                <?php foreach ($images as $image): ?>
                    <img src="<?= e(ListingPresenter::uploadUrl((string) $image['path'])) ?>" alt="<?= e((string) $image['alt_text']) ?>" loading="lazy">
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <article class="profile-panel">
                <ul class="badge-row">
                    <?php foreach (ListingPresenter::badges($listing) as $badge): ?><li class="pill"><?= e($badge) ?></li><?php endforeach; ?>
                </ul>
                <h1 class="ad-title"><?= e((string) $listing['title']) ?></h1>
                <p class="ad-meta"><?= e(ListingPresenter::ageLabel($listing)) ?> Years | <?= e((string) $listing['category_name']) ?> | <?= e((string) $listing['city_name']) ?></p>
                <p class="meta"><?= e((string) $listing['locality_name']) ?>, <?= e((string) $listing['state_name']) ?></p>
                <p>Status: <?= e((string) $listing['status']) ?></p>
                <p>Posted <?= e(ListingPresenter::postedOn($listing)) ?></p>
                <?php if (!empty($listing['availability_note'])): ?>
                    <p>Availability: <?= e((string) $listing['availability_note']) ?></p>
                <?php else: ?>
                    <p>No availability hours are published for this listing.</p>
                <?php endif; ?>
                <p><a class="btn btn-outline-dark" href="/report?listing_id=<?= e((string) $listing['id']) ?>">Report listing</a></p>
            </article>
        </div>
    </div>
    <article class="content-panel mt-4">
        <?php foreach (preg_split("/\n{2,}/", trim((string) $listing['description'])) ?: [] as $paragraph): ?>
            <p><?= e(trim($paragraph)) ?></p>
        <?php endforeach; ?>
    </article>
    <section class="mt-4">
        <h2>Related listings</h2>
        <div class="listing-grid">
            <?php foreach ($related as $item): \App\Core\View::partial('components/listing-card', ['listing' => $item]); endforeach; ?>
        </div>
    </section>
</div>
