<div class="page-head">
    <div>
        <h1>Favorites</h1>
        <p class="muted">Things you want close.</p>
    </div>
</div>
<?php if (empty($items)): ?>
    <div class="empty card">
        <p>Nothing starred yet.</p>
    </div>
<?php else: ?>
    <ul class="item-list">
        <?php foreach ($items as $item): ?>
            <li>
                <a href="<?= e(url('/items/' . $item['id'])) ?>">
                    <span class="type-ico"><?= icon(item_type_icon($item['type'])) ?></span>
                    <span>
                        <strong><?= e($item['title']) ?></strong>
                        <span class="muted tiny"><?= e($item['workspace_name'] ?: '—') ?></span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
