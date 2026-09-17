<?php /** @var array $account */ ?>
<div class="page-head">
    <div>
        <h1>Settings</h1>
        <p class="muted">Your corner of Nook.</p>
    </div>
</div>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="split-2">
    <section class="card">
        <h2>Profile photo</h2>
        <div class="avatar-row">
            <span class="avatar lg">
                <?php $src = avatar_url($account['avatar'] ?? null); ?>
                <?php if ($src): ?><img src="<?= e($src) ?>" alt=""><?php else: ?><?= e(user_initials($account['name'] ?? '')) ?><?php endif; ?>
            </span>
            <form method="post" action="<?= e(url('/settings/avatar')) ?>" enctype="multipart/form-data" class="stack">
                <?= csrf_field() ?>
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
                <button class="btn btn-secondary" type="submit">Upload</button>
            </form>
            <?php if (!empty($account['avatar'])): ?>
                <form method="post" action="<?= e(url('/settings/avatar/remove')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-ghost" type="submit">Remove</button>
                </form>
            <?php endif; ?>
        </div>
        <p class="muted tiny">JPG, PNG or WEBP. Max 2 MB.</p>
    </section>
    <section class="card">
        <h2>Name & email</h2>
        <form method="post" action="<?= e(url('/settings/profile')) ?>" class="stack">
            <?= csrf_field() ?>
            <label class="field">
                <span>Name</span>
                <input type="text" name="name" value="<?= e($account['name'] ?? '') ?>" required>
            </label>
            <label class="field">
                <span>New email</span>
                <input type="email" name="email" value="<?= e($account['email'] ?? '') ?>" required>
            </label>
            <label class="field">
                <span>Current password (only if changing email)</span>
                <input type="password" name="current_password" autocomplete="current-password">
            </label>
            <button class="btn btn-primary" type="submit">Save</button>
        </form>
    </section>
</div>
