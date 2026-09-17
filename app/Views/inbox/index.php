<?php
/** @var array $items */
/** @var array $workspaces */
?>
<div class="page-head">
    <div>
        <h1>Inbox</h1>
        <p class="muted">Capture first. Place it when you’re ready.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/items/create?type=note')) ?>">+ Quick note</a>
</div>

<?php if (!$items): ?>
    <div class="empty card">
        <h2>Inbox zero.</h2>
        <p>Everything has a place.</p>
    </div>
<?php else: ?>
    <ul class="inbox-list">
        <?php foreach ($items as $item): ?>
            <li class="card inbox-item">
                <a class="inbox-main" href="<?= e(url('/items/' . $item['id'])) ?>">
                    <span class="type-ico"><?= icon(item_type_icon($item['type'])) ?></span>
                    <span>
                        <strong><?= e($item['title']) ?></strong>
                        <?php if (!empty($item['description'])): ?>
                            <span class="muted"><?= e(truncate($item['description'], 120)) ?></span>
                        <?php endif; ?>
                        <span class="muted tiny"><?= e(item_type_label($item['type'])) ?> · <?= e(relative_day($item['created_at'])) ?></span>
                    </span>
                </a>
                <form method="post" action="<?= e(url('/inbox/' . $item['id'] . '/classify')) ?>" class="classify">
                    <?= csrf_field() ?>
                    <select name="workspace_id">
                        <option value="">Workspace…</option>
                        <?php foreach ($workspaces as $ws): ?>
                            <option value="<?= (int) $ws['id'] ?>"><?= e($ws['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type">
                        <?php foreach (['task' => 'Task', 'note' => 'Note', 'document' => 'Document', 'idea' => 'Idea', 'link' => 'Link'] as $k => $label): ?>
                            <option value="<?= e($k) ?>" <?= $item['type'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-secondary" type="submit">Place</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
