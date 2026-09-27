<?php

declare(strict_types=1);

/** @var string|null $detail */
?>
<section class="panel">
    <h1>Something went wrong</h1>
    <p>The application could not complete this request.</p>
    <?php if (is_string($detail) && $detail !== ''): ?>
        <p><?= e($detail) ?></p>
    <?php endif; ?>
</section>
