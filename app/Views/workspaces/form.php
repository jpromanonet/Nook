<?php
/** @var array|null $workspace */
$w = $workspace ?? [];
?>
<div class="page-head">
    <div>
        <h1><?= $workspace ? 'Edit workspace' : 'New workspace' ?></h1>
    </div>
</div>
<form method="post" action="<?= e($workspace ? url('/workspaces/' . $workspace['id']) : url('/workspaces')) ?>" class="card stack form-wide">
    <?= csrf_field() ?>
    <label class="field"><span>Name</span><input type="text" name="name" value="<?= e($w['name'] ?? '') ?>" required></label>
    <label class="field"><span>Description</span><textarea name="description" rows="3"><?= e($w['description'] ?? '') ?></textarea></label>
    <div class="form-grid">
        <label class="field">
            <span>Type</span>
            <select name="type">
                <?php foreach (workspace_types() as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= ($w['type'] ?? 'project') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Status</span>
            <select name="status">
                <?php foreach (workspace_statuses() as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= ($w['status'] ?? 'active') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Color</span>
            <select name="color">
                <?php foreach (workspace_colors() as $hex => $label): ?>
                    <option value="<?= e($hex) ?>" <?= ($w['color'] ?? '#71806A') === $hex ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Start date</span><input type="date" name="start_date" value="<?= e($w['start_date'] ?? '') ?>"></label>
        <label class="field"><span>End date</span><input type="date" name="end_date" value="<?= e($w['end_date'] ?? '') ?>"></label>
        <label class="field"><span>Website</span><input type="url" name="website" value="<?= e($w['website'] ?? '') ?>"></label>
        <label class="field"><span>Repository</span><input type="url" name="repository" value="<?= e($w['repository'] ?? '') ?>"></label>
    </div>
    <label class="field"><span>Notes</span><textarea name="notes" rows="4"><?= e($w['notes'] ?? '') ?></textarea></label>
    <div class="row-actions">
        <button class="btn btn-primary" type="submit">Save</button>
        <?php if ($workspace): ?>
            <button
                class="btn btn-secondary"
                type="submit"
                form="ws-archive-form"
                onclick="return confirm('Archive this workspace?');"
            >Archive</button>
        <?php endif; ?>
    </div>
</form>

<?php if ($workspace): ?>
    <form id="ws-archive-form" method="post" action="<?= e(url('/workspaces/' . $workspace['id'] . '/archive')) ?>" hidden>
        <?= csrf_field() ?>
    </form>
    <section class="card danger-zone" style="margin-top:1.25rem">
        <h2>Delete workspace</h2>
        <p class="muted">This permanently removes the workspace and its items. There is no undo.</p>
        <form
            method="post"
            action="<?= e(url('/workspaces/' . $workspace['id'] . '/delete')) ?>"
            onsubmit="return confirm('Delete this workspace and all of its content permanently?');"
        >
            <?= csrf_field() ?>
            <button class="btn btn-danger" type="submit">Delete workspace</button>
        </form>
    </section>
<?php endif; ?>
