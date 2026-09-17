<?php /** @var array $account */ ?>
<div class="page-head"><div><h1>Settings</h1></div></div>
<?php require __DIR__ . '/_nav.php'; ?>
<section class="card">
    <h2>Account</h2>
    <dl class="meta-dl">
        <div><dt>Account ID</dt><dd><?= (int) $account['id'] ?></dd></div>
        <div><dt>Account created</dt><dd><?= e(format_dt($account['created_at'] ?? null)) ?></dd></div>
        <div><dt>Last login</dt><dd><?= e(format_dt($account['last_login_at'] ?? null)) ?></dd></div>
    </dl>
</section>
<section class="card danger-zone">
    <h2>Delete account</h2>
    <p class="muted">This removes your workspaces and items. There is no undo.</p>
    <form method="post" action="<?= e(url('/settings/account/delete')) ?>" class="stack" onsubmit="return confirm('Delete this account permanently?');">
        <?= csrf_field() ?>
        <label class="field">
            <span>Current password</span>
            <input type="password" name="current_password" required>
        </label>
        <button class="btn btn-danger" type="submit">Delete account</button>
    </form>
</section>
