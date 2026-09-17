<?php /** @var array $account */ /** @var array $sessions */ /** @var string $currentToken */ ?>
<div class="page-head"><div><h1>Settings</h1></div></div>
<?php require __DIR__ . '/_nav.php'; ?>
<section class="card">
    <h2>Change password</h2>
    <form method="post" action="<?= e(url('/settings/password')) ?>" class="stack">
        <?= csrf_field() ?>
        <label class="field"><span>Current password</span><input type="password" name="current_password" required></label>
        <label class="field"><span>New password</span><input type="password" name="new_password" minlength="8" required></label>
        <label class="field"><span>Confirm new password</span><input type="password" name="new_password_confirm" minlength="8" required></label>
        <button class="btn btn-primary" type="submit">Update password</button>
    </form>
</section>
<section class="card">
    <h2>Active sessions</h2>
    <p class="muted tiny">Last login: <?= e(format_dt($account['last_login_at'] ?? null)) ?></p>
    <ul class="session-list">
        <?php foreach ($sessions as $s): ?>
            <li>
                <div>
                    <strong><?= e(truncate($s['user_agent'] ?? 'Unknown device', 70)) ?></strong>
                    <p class="muted tiny"><?= e($s['ip_address'] ?? '') ?> · <?= e(format_dt($s['last_seen_at'])) ?>
                        <?= hash_equals($currentToken, (string) $s['session_token']) ? ' · this device' : '' ?>
                    </p>
                </div>
                <?php if (!hash_equals($currentToken, (string) $s['session_token'])): ?>
                    <form method="post" action="<?= e(url('/settings/sessions/' . $s['id'] . '/revoke')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost" type="submit">Revoke</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <?php if (!$sessions): ?>
            <li class="muted">No extra sessions recorded.</li>
        <?php endif; ?>
    </ul>
</section>
