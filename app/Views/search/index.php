<?php
/** @var string $q */
/** @var array $items */
/** @var array $workspaceHits */
/** @var array $workspaces */
?>
<div class="page-head">
    <div>
        <h1>Search</h1>
        <p class="muted">Find anything you put in Nook.</p>
    </div>
</div>
<form class="card search-form" method="get" action="<?= e(url('/search')) ?>">
    <label class="field grow">
        <span>Query</span>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="premisely google auth">
    </label>
    <label class="field">
        <span>Type</span>
        <select name="type">
            <option value="">Any</option>
            <?php foreach (item_types() as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= ($type ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">
        <span>Workspace</span>
        <select name="workspace_id">
            <option value="">Any</option>
            <?php foreach ($workspaces as $ws): ?>
                <option value="<?= (int) $ws['id'] ?>" <?= (string) ($workspaceId ?? '') === (string) $ws['id'] ? 'selected' : '' ?>><?= e($ws['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">
        <span>Tag</span>
        <input type="text" name="tag" value="<?= e($tag ?? '') ?>" placeholder="research">
    </label>
    <button class="btn btn-primary" type="submit">Search</button>
</form>

<?php if ($q === '' && empty($type) && empty($workspaceId) && empty($tag)): ?>
    <p class="muted">Type something, or press Ctrl + K from anywhere.</p>
<?php elseif (!$items && !$workspaceHits): ?>
    <div class="empty card"><p>Nothing matched. That can be a good thing.</p></div>
<?php else: ?>
    <?php if ($workspaceHits): ?>
        <h2>Workspaces</h2>
        <div class="ws-grid">
            <?php foreach ($workspaceHits as $ws): ?>
                <a class="card ws-card" href="<?= e(url('/workspaces/' . $ws['id'])) ?>">
                    <span class="dot" style="background:<?= e($ws['color']) ?>"></span>
                    <strong><?= e($ws['name']) ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <ul class="item-list">
        <?php foreach ($items as $item): ?>
            <li>
                <a href="<?= e(url('/items/' . $item['id'])) ?>">
                    <span class="type-ico"><?= icon(item_type_icon($item['type'])) ?></span>
                    <span>
                        <strong><?= e($item['title']) ?></strong>
                        <span class="muted tiny"><?= e(item_type_label($item['type'])) ?><?= $item['workspace_name'] ? ' · ' . e($item['workspace_name']) : '' ?></span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
