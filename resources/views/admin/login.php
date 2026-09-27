<section class="age-panel">
    <h1>Admin sign in</h1>
    <?php if (!empty($error)): ?><p class="alert alert-danger"><?= e((string) $error) ?></p><?php endif; ?>
    <form method="post" action="/admin/login">
        <?= csrf_field() ?>
        <label for="email">Email</label>
        <input class="form-control mb-3" id="email" name="email" type="email" required autocomplete="username">
        <label for="password">Password</label>
        <input class="form-control mb-3" id="password" name="password" type="password" required autocomplete="current-password">
        <button class="btn btn-brand" type="submit">Sign in</button>
    </form>
</section>
