<div class="page-head"><div><h1>Settings</h1></div></div>
<?php require __DIR__ . '/_nav.php'; ?>
<section class="card">
    <h2>Theme</h2>
    <form method="post" action="<?= e(url('/settings/appearance')) ?>" class="stack">
        <?= csrf_field() ?>
        <?php $theme = Auth::theme(); ?>
        <label class="choice"><input type="radio" name="theme" value="light" <?= $theme === 'light' ? 'checked' : '' ?>> Light</label>
        <label class="choice"><input type="radio" name="theme" value="dark" <?= $theme === 'dark' ? 'checked' : '' ?>> Dark</label>
        <label class="choice"><input type="radio" name="theme" value="system" <?= $theme === 'system' ? 'checked' : '' ?>> System</label>
        <button class="btn btn-primary" type="submit">Save</button>
    </form>
</section>
