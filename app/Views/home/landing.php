<div class="auth-split">
    <section class="auth-brand">
        <div>
            <div class="brand-mark"><?= icon('nook', 28) ?></div>
            <h1>Nook</h1>
            <p class="tagline">Everything has a place.</p>
            <p>Centralize the full context of each company, product, book or personal project — not just the tasks.</p>
        </div>
    </section>
    <section class="auth-panel landing-panel">
        <div class="card auth-card">
            <h2>Sign in</h2>
            <form method="post" action="<?= e(url('/login')) ?>" class="stack">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" required autocomplete="username" autofocus>
                </label>
                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" required autocomplete="current-password">
                </label>
                <label class="check">
                    <input type="checkbox" name="remember" value="1"> Remember me
                </label>
                <button type="submit" class="btn btn-primary btn-block">Enter Nook</button>
            </form>
            <p class="auth-links">
                <a href="<?= e(url('/forgot-password')) ?>">Forgot password?</a>
            </p>
        </div>

        <div class="card auth-card auth-card-secondary">
            <h2>A cozy digital workroom</h2>
            <p class="muted">Documents, notes, files, links and days — together.</p>
            <div class="auth-cta">
                <a class="btn btn-secondary" href="<?= e(url('/register')) ?>">Create account</a>
            </div>
        </div>
    </section>
</div>
