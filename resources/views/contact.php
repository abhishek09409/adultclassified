<?php

declare(strict_types=1);

/** @var string|null $notice */
?>
<div class="container section">
    <div class="content-panel">
        <h1>Contact</h1>
        <p>Send a directory question. Do not include passwords or copies of identity documents.</p>
        <?php if ($notice): ?><p class="alert alert-info"><?= e($notice) ?></p><?php endif; ?>
        <form method="post" action="/contact">
            <?= csrf_field() ?>
            <label for="name">Name</label>
            <input class="form-control mb-3" id="name" name="name" autocomplete="name">
            <label for="email">Email</label>
            <input class="form-control mb-3" id="email" name="email" type="email" required autocomplete="email">
            <label for="message">Message</label>
            <textarea class="form-control mb-3" id="message" name="message" rows="5" required></textarea>
            <button class="btn btn-brand" type="submit">Send</button>
        </form>
    </div>
</div>
