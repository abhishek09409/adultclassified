<?php

declare(strict_types=1);

/** @var string $next */
?>
<section class="age-panel">
    <p class="pill">Adults only</p>
    <h1>This directory is for adults aged 21 and over.</h1>
    <p>It contains classified listings for adults. It is not for minors. No account or date of birth is collected to pass this notice.</p>
    <form method="post" action="/age">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <button class="btn btn-brand" type="submit" name="choice" value="enter">I am 21 or older</button>
        <button class="btn btn-outline-dark" type="submit" name="choice" value="leave">I am under 21</button>
    </form>
    <p class="mt-3"><a href="/terms">Terms</a> · <a href="/privacy">Privacy</a> · <a href="/content-policy">Content policy</a></p>
</section>
