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
    <link rel="stylesheet" href="/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/assets/css/directory.css">
</head>
<body class="simple-body">
    <main class="simple-main" id="main">
        <?= $content ?>
    </main>
</body>
</html>
