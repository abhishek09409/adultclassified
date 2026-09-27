<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $states */
/** @var list<array<string, mixed>> $categories */
?>
<header class="site-header">
    <nav class="navbar navbar-expand-lg" aria-label="Primary">
        <div class="container">
            <a class="navbar-brand" href="/">Directory<span>21</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Open menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoryMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Categories</a>
                        <ul class="dropdown-menu" aria-labelledby="categoryMenu">
                            <?php foreach ($categories as $category): ?>
                                <li><a class="dropdown-item" href="/<?= e((string) $category['slug']) ?>"><?= e((string) $category['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="locationMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Locations</a>
                        <ul class="dropdown-menu location-menu" aria-labelledby="locationMenu">
                            <?php foreach ($states as $state): ?>
                                <li><a class="dropdown-item" href="/<?= e((string) $state['slug']) ?>"><?= e((string) $state['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
                <form class="header-search d-flex" method="get" action="/search" role="search">
                    <label class="visually-hidden" for="header-q">Search listings</label>
                    <input class="form-control" id="header-q" type="search" name="q" placeholder="Search listings">
                    <button class="btn btn-brand" type="submit">Search</button>
                </form>
                <a class="admin-link" href="/admin/login">Admin</a>
            </div>
        </div>
    </nav>
</header>
