<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $states */
/** @var list<array<string, mixed>> $categories */
/** @var list<array<string, mixed>> $latest */
/** @var list<array<string, mixed>> $recent */
/** @var list<array<string, mixed>> $featured */
/** @var list<array<string, mixed>> $popularCities */
/** @var list<array<string, mixed>> $categoryCounts */
/** @var string $intro */
?>
<section class="hero">
    <div class="container">
        <p class="pill">Adults 21+ only</p>
        <h1>A clearer adult classified directory.</h1>
        <p><?= e($intro) ?></p>
        <form class="hero-card" method="get" action="/search">
            <?php \App\Core\View::partial('components/location-selector', ['states' => $states, 'categories' => $categories, 'prefix' => 'hero']); ?>
            <button class="btn btn-brand mt-3" type="submit">Search</button>
        </form>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Latest listings</h2>
        <?php if ($latest === []): ?><p>No published listings yet.</p><?php endif; ?>
        <div class="listing-grid">
            <?php foreach ($latest as $listing): \App\Core\View::partial('components/listing-card', ['listing' => $listing]); endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Recently added</h2>
        <div class="listing-grid">
            <?php foreach ($recent as $listing): \App\Core\View::partial('components/listing-card', ['listing' => $listing]); endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Featured listings</h2>
        <?php if ($featured === []): ?><p>Featured badges appear only when an editor marks a listing.</p><?php endif; ?>
        <div class="listing-grid">
            <?php foreach ($featured as $listing): \App\Core\View::partial('components/listing-card', ['listing' => $listing]); endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Popular cities</h2>
        <div class="state-grid">
            <?php foreach ($popularCities as $city): ?>
                <a class="place-link" href="/<?= e((string) $city['state_slug']) ?>/<?= e((string) $city['slug']) ?>"><?= e((string) $city['name']) ?><br><span class="meta"><?= e((string) $city['state_name']) ?> · <?= e((string) $city['listing_count']) ?> listings</span></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Popular categories</h2>
        <div class="category-grid">
            <?php foreach ($categoryCounts as $category): ?>
                <a class="category-tile" href="/<?= e((string) $category['slug']) ?>"><strong><?= e((string) $category['name']) ?></strong><span class="meta d-block"><?= e((string) $category['listing_count']) ?> published</span></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Browse by state</h2>
        <div class="state-grid">
            <?php foreach ($states as $state): ?>
                <a class="place-link" href="/<?= e((string) $state['slug']) ?>"><?= e((string) $state['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="container content-panel">
        <h2>How the directory works</h2>
        <p><?= e($intro) ?></p>
        <p>Reports and removal requests are reviewed by a moderator. Automated listings cannot exceed five published records a day, and they are rejected if they fail the content checks.</p>
    </div>
</section>
