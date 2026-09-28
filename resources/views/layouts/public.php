<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $content */
/** @var array<string, mixed> $seo */
$seo = $seo ?? ['title' => $title, 'description' => '', 'canonical' => '', 'robots' => 'index,follow', 'og_title' => $title, 'og_description' => ''];
$nav = \App\Services\Navigation::data();
$schema = $schema ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e((string) $seo['title']) ?></title>
    <meta name="description" content="<?= e((string) $seo['description']) ?>">
    <link rel="canonical" href="<?= e((string) $seo['canonical']) ?>">
    <meta name="robots" content="<?= e((string) $seo['robots']) ?>">
    <meta property="og:title" content="<?= e((string) $seo['og_title']) ?>">
    <meta property="og:description" content="<?= e((string) $seo['og_description']) ?>">
    <meta property="og:url" content="<?= e((string) $seo['canonical']) ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= e((string) $seo['og_title']) ?>">
    <meta name="twitter:description" content="<?= e((string) $seo['og_description']) ?>">
    <?php if (is_array($schema)): ?>
        <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
    <link rel="stylesheet" href="/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/assets/css/directory.css">
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <?php \App\Core\View::partial('components/header', ['states' => $nav['states'], 'categories' => $nav['categories']]); ?>
    <main id="main">
        <?= $content ?>
    </main>
    <?php \App\Core\View::partial('components/footer'); ?>
    <script src="/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/public/assets/js/directory.js"></script>
</body>
</html>
