<?php

declare(strict_types=1);

/** @var int $minimumAge */
?>
<section class="panel">
    <h1>Directory foundation</h1>
    <p>This install is running the application foundation. Public directory pages are added in a later phase.</p>
    <p>The directory is limited to adults aged <?= e($minimumAge) ?> and older. Automated and public copy must stay non-explicit.</p>
    <p><a href="/health">Check application health</a></p>
</section>
