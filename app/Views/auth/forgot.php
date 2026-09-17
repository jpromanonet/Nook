<div class="auth-split">
    <section class="auth-brand">
        <div>
            <div class="brand-mark"><?= icon('nook', 28) ?></div>
            <h1>Nook</h1>
            <p class="tagline">We’ll send you a quiet way back in.</p>
        </div>
    </section>
    <section class="auth-panel">
        <div class="card auth-card">
            <h2>Forgot password</h2>
            <form method="post" action="<?= e(url('/forgot-password')) ?>" class="stack">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" required>
                </label>
                <button type="submit" class="btn btn-primary btn-block">Send recovery link</button>
            </form>
            <p class="auth-links"><a href="<?= e(url('/login')) ?>">Back to sign in</a></p>
        </div>
    </section>
</div>
