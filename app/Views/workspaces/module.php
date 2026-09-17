<?php
/** @var array $workspace */
/** @var string $module */
/** @var array $items */
/** @var array $empty */
/** @var array $counts */
require __DIR__ . '/_nav.php';
$createType = $empty['type'] ?? 'note';
$wid = (int) $workspace['id'];
?>
<div class="section-head">
    <h2><?= e(ucfirst($module)) ?></h2>
    <div class="row-actions">
        <?php if ($module === 'media'): ?>
            <a class="btn btn-ghost <?= ($layoutMode ?? '') === 'grid' ? 'is-on' : '' ?>" href="<?= e(url('/workspaces/' . $wid . '/media?layout=grid')) ?>">Grid</a>
            <a class="btn btn-ghost <?= ($layoutMode ?? '') === 'list' ? 'is-on' : '' ?>" href="<?= e(url('/workspaces/' . $wid . '/media?layout=list')) ?>">List</a>
            <a class="btn btn-secondary" href="<?= e(url('/items/create?type=image&workspace_id=' . $wid)) ?>">+ Image</a>
            <a class="btn btn-secondary" href="<?= e(url('/items/create?type=audio&workspace_id=' . $wid)) ?>">+ Audio</a>
            <a class="btn btn-primary" href="<?= e(url('/items/create?type=video&workspace_id=' . $wid)) ?>">+ Video</a>
        <?php elseif ($module !== 'archive'): ?>
            <a class="btn btn-primary" href="<?= e(url('/items/create?type=' . $createType . '&workspace_id=' . $wid)) ?>">+ <?= e(item_type_label($createType)) ?></a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$items): ?>
    <div class="empty-quiet card" style="padding:2rem 1.2rem">
        <h3 style="margin-bottom:0.35rem"><?= e($empty['title']) ?></h3>
        <p class="muted"><?= e($empty['body']) ?></p>
        <?php if ($module === 'media'): ?>
            <div class="row-actions" style="justify-content:center;margin-top:1rem">
                <a class="btn btn-secondary" href="<?= e(url('/items/create?type=image&workspace_id=' . $wid)) ?>">+ Image</a>
                <a class="btn btn-secondary" href="<?= e(url('/items/create?type=audio&workspace_id=' . $wid)) ?>">+ Audio</a>
                <a class="btn btn-primary" href="<?= e(url('/items/create?type=video&workspace_id=' . $wid)) ?>">+ Video</a>
            </div>
        <?php elseif ($module !== 'archive'): ?>
            <p style="margin-top:1rem"><a class="btn btn-primary" href="<?= e(url('/items/create?type=' . $createType . '&workspace_id=' . $wid)) ?>">+ Create</a></p>
        <?php endif; ?>
    </div>
<?php elseif ($module === 'media' && ($layoutMode ?? 'grid') === 'grid'): ?>
    <div class="media-grid">
        <?php foreach ($items as $item):
            $mime = (string) ($item['mime_type'] ?? '');
            $fileUrl = url('/items/' . $item['id'] . '/file');
            ?>
            <a class="media-tile" href="<?= e(url('/items/' . $item['id'])) ?>">
                <?php if (!empty($item['stored_filename']) && str_starts_with($mime, 'image/')): ?>
                    <img src="<?= e($fileUrl) ?>" alt="">
                <?php elseif (str_starts_with($mime, 'video/') || $item['type'] === 'video'): ?>
                    <div class="media-fallback media-video">
                        <?= icon('video', 28) ?>
                        <span class="muted tiny">Video</span>
                    </div>
                <?php elseif (str_starts_with($mime, 'audio/') || $item['type'] === 'audio'): ?>
                    <div class="media-fallback media-audio">
                        <?= icon('audio', 28) ?>
                        <span class="muted tiny">Audio</span>
                    </div>
                <?php else: ?>
                    <div class="media-fallback"><?= icon(item_type_icon($item['type']), 28) ?></div>
                <?php endif; ?>
                <span><?= e($item['title']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="panel">
        <ul class="item-list">
            <?php foreach ($items as $item): ?>
                <li>
                    <a href="<?= e(url('/items/' . $item['id'])) ?>">
                        <span class="type-ico"><?= icon(item_type_icon($item['type']), 14) ?></span>
                        <span>
                            <strong><?= e($item['title']) ?></strong>
                            <span class="muted tiny">
                                <?= e(item_type_label($item['type'])) ?>
                                <?php if ($item['type'] === 'task'): ?> · <?= e(task_statuses()[$item['status']] ?? $item['status']) ?><?php endif; ?>
                                <?php if (!empty($item['due_date'])): ?> · <?= e(format_date($item['due_date'])) ?><?php endif; ?>
                                <?php if (!empty($item['file_size'])): ?> · <?= e(UploadService::formatSize((int) $item['file_size'])) ?><?php endif; ?>
                            </span>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
