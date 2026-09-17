<div class="auth-split">
    <section class="auth-brand">
        <div>
            <div class="brand-mark"><?= icon('nook', 28) ?></div>
            <h1>Nook</h1>
            <p class="tagline">Your work, in one quiet place.</p>
        </div>
    </section>
    <section class="auth-panel">
        <div class="card auth-card">
            <h2>Create account</h2>
            <form method="post" action="<?= e(url('/register')) ?>" class="stack">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Name</span>
                    <input type="text" name="name" required autocomplete="name">
                </label>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" required autocomplete="email">
                </label>
                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password">
                </label>
                <label class="field">
                    <span>Confirm password</span>
                    <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
                </label>
                <button type="submit" class="btn btn-primary btn-block">Create account</button>
            </form>
            <p class="auth-links">
                <a href="<?= e(url('/login')) ?>">Already have an account?</a>
            </p>
        </div>
    </section>
</div>
