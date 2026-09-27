<?php

declare(strict_types=1);

/** @var array<string, mixed> $filters */
/** @var list<array<string, mixed>> $listings */
/** @var list<array<string, mixed>> $states */
/** @var list<array<string, mixed>> $categories */
/** @var int $total */
/** @var int $page */
/** @var int $pages */
?>
<div class="container section">
    <h1>Search</h1>
    <form class="content-panel mb-4" method="get" action="/search">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="q">Keyword</label>
                <input class="form-control" id="q" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label for="sort">Sort</label>
                <select class="form-select" id="sort" name="sort">
                    <option value="newest">Newest</option>
                    <option value="featured"<?= ($filters['sort'] ?? '') === 'featured' ? ' selected' : '' ?>>Featured</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <label class="me-2"><input type="checkbox" name="featured" value="1"<?= !empty($filters['featured']) ? ' checked' : '' ?>> Featured only</label>
            </div>
        </div>
        <?php \App\Core\View::partial('components/location-selector', ['states' => $states, 'categories' => $categories, 'prefix' => 'search']); ?>
        <button class="btn btn-brand mt-3" type="submit">Search</button>
    </form>
    <p class="meta"><?= e((string) $total) ?> results</p>
    <div class="listing-grid">
        <?php foreach ($listings as $listing): \App\Core\View::partial('components/listing-card', ['listing' => $listing]); endforeach; ?>
    </div>
    <?php \App\Core\View::partial('components/pagination', ['page' => $page, 'pages' => $pages, 'path' => '/search', 'query' => array_filter([
        'q' => $filters['q'] ?? null,
        'sort' => $filters['sort'] ?? null,
        'category_id' => $filters['category_id'] ?? null,
        'state_id' => $filters['state_id'] ?? null,
        'city_id' => $filters['city_id'] ?? null,
        'location_id' => $filters['location_id'] ?? null,
        'featured' => !empty($filters['featured']) ? '1' : null,
    ])]); ?>
</div>
