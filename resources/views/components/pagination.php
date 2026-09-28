<?php

declare(strict_types=1);

/** @var int $page */
/** @var int $pages */
/** @var string $path */
/** @var array<string, scalar|null> $query */
if ($pages < 2) {
    return;
}
$build = static function (int $target) use ($path, $query): string {
    $query['page'] = $target;
    return $path . '?' . http_build_query($query);
};
?>
<nav aria-label="Pagination">
    <ul class="pagination">
        <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
            <?php if ($page > 1): ?><a class="page-link" href="<?= e($build($page - 1)) ?>">Previous</a><?php else: ?><span class="page-link">Previous</span><?php endif; ?>
        </li>
        <li class="page-item active"><span class="page-link">Page <?= e($page) ?> of <?= e($pages) ?></span></li>
        <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>">
            <?php if ($page < $pages): ?><a class="page-link" href="<?= e($build($page + 1)) ?>">Next</a><?php else: ?><span class="page-link">Next</span><?php endif; ?>
        </li>
    </ul>
</nav>
