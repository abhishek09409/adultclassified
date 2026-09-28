<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $content */
/** @var array<string, mixed>|null $admin */
/** @var string|null $success */
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> · Admin</title>
    <link rel="stylesheet" href="/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/assets/css/directory.css">
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-side">
            <p class="footer-brand">Admin</p>
            <a href="/admin">Dashboard</a>
            <a href="/admin/listings">Listings</a>
            <a href="/admin/categories">Categories</a>
            <a href="/admin/states">States</a>
            <a href="/admin/cities">Cities</a>
            <a href="/admin/locations">Locations</a>
            <a href="/admin/import">CSV import</a>
            <a href="/admin/reports">Reports</a>
            <a href="/admin/automation">Automation</a>
            <a href="/admin/seo">SEO</a>
            <a href="/admin/settings">Settings</a>
            <a href="/admin/system/setup">Database setup</a>
            <a href="/admin/admins">Admins</a>
            <a href="/admin/audit">Audit logs</a>
            <a href="/admin/compliance">Compliance</a>
            <form method="post" action="/admin/logout"><?= csrf_field() ?><button class="btn btn-sm btn-outline-light mt-3" type="submit">Log out</button></form>
        </aside>
        <div class="admin-main">
            <h1><?= e($title) ?></h1>
            <?php if (!empty($success)): ?><p class="alert alert-success"><?= e($success) ?></p><?php endif; ?>
            <?php if (!empty($error)): ?><p class="alert alert-danger"><?= e($error) ?></p><?php endif; ?>
            <?= $content ?>
        </div>
    </div>
    <script src="/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/public/assets/js/directory.js"></script>
</body>
</html>
