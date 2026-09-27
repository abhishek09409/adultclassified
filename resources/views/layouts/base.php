<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/public/assets/css/foundation.css">
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header">
        <p class="brand"><?= e($title) ?></p>
        <p class="age-note">Adults 21+ only</p>
    </header>
    <main id="main">
        <?= $content ?>
    </main>
</body>
</html>
