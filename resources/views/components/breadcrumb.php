<?php

declare(strict_types=1);

/** @var list<array{name: string, url: string}> $crumbs */
?>
<nav aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <?php foreach ($crumbs as $index => $crumb): ?>
            <li class="breadcrumb-item<?= $index === count($crumbs) - 1 ? ' active' : '' ?>"<?= $index === count($crumbs) - 1 ? ' aria-current="page"' : '' ?>>
                <?php if ($index === count($crumbs) - 1): ?>
                    <?= e($crumb['name']) ?>
                <?php else: ?>
                    <a href="<?= e($crumb['url']) ?>"><?= e($crumb['name']) ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
