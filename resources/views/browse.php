<?php

declare(strict_types=1);

/** @var string $heading */
/** @var string $intro */
/** @var list<array<string, mixed>> $listings */
/** @var int $total */
/** @var int $page */
/** @var int $pages */
/** @var string $path */
/** @var list<array{name: string, url: string}> $crumbs */
/** @var string $sort */
?>
<div class="container section">
    <?php \App\Core\View::partial('components/breadcrumb', ['crumbs' => $crumbs]); ?>
    <div class="row g-4">
        <div class="col-lg-3">
            <aside class="content-panel" aria-label="Filters">
                <h2 class="h5">Filters</h2>
                <form method="get" action="<?= e($path) ?>">
                    <label for="sort">Sort</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="newest"<?= $sort === 'newest' ? ' selected' : '' ?>>Newest</option>
                        <option value="featured"<?= $sort === 'featured' ? ' selected' : '' ?>>Featured</option>
                    </select>
                    <button class="btn btn-brand mt-3" type="submit">Apply</button>
                </form>
            </aside>
        </div>
        <div class="col-lg-9">
            <h1><?= e($heading) ?></h1>
            <p><?= e($intro) ?></p>
            <p class="meta"><?= e((string) $total) ?> published</p>
            <div class="listing-grid">
                <?php foreach ($listings as $listing): \App\Core\View::partial('components/listing-card', ['listing' => $listing]); endforeach; ?>
            </div>
            <?php if ($listings === []): ?><p>No published listings match this page yet.</p><?php endif; ?>
            <?php \App\Core\View::partial('components/pagination', ['page' => $page, 'pages' => $pages, 'path' => $path, 'query' => ['sort' => $sort]]); ?>
        </div>
    </div>
</div>
