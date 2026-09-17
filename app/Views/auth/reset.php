<div class="auth-split">
    <section class="auth-brand">
        <div>
            <div class="brand-mark"><?= icon('nook', 28) ?></div>
            <h1>Nook</h1>
        </div>
    </section>
    <section class="auth-panel">
        <div class="card auth-card">
            <h2>New password</h2>
            <form method="post" action="<?= e(url('/reset-password')) ?>" class="stack">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
                <label class="field">
                    <span>New password</span>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password">
                </label>
                <label class="field">
                    <span>Confirm new password</span>
                    <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
                </label>
                <button type="submit" class="btn btn-primary btn-block">Save password</button>
            </form>
        </div>
    </section>
</div>
