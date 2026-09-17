<?php
/** @var array $workspace */
/** @var string $module */
/** @var array $items */
/** @var array $empty */
/** @var array $counts */
/** @var bool $folderable */
/** @var int|null $folderId */
/** @var array|null $currentFolder */
/** @var array $breadcrumbs */
/** @var string|null $folderScope */
/** @var array $folders */
/** @var array $fileItems */
require __DIR__ . '/_nav.php';
$createType = $empty['type'] ?? 'note';
$wid = (int) $workspace['id'];
$folderable = !empty($folderable);
$folderId = $folderId ?? null;
$folders = $folders ?? [];
$fileItems = $fileItems ?? $items;
$breadcrumbs = $breadcrumbs ?? [];
$layoutMode = $layoutMode ?? ($module === 'media' ? 'grid' : 'list');
$baseModuleUrl = '/workspaces/' . $wid . '/' . $module;
$returnQuery = [];
if ($folderId) {
    $returnQuery['folder'] = $folderId;
}
if ($folderable) {
    $returnQuery['layout'] = $layoutMode;
}
$returnPath = $baseModuleUrl . ($returnQuery ? '?' . http_build_query($returnQuery) : '');
$layoutLink = static function (string $mode) use ($baseModuleUrl, $folderId): string {
    $q = ['layout' => $mode];
    if ($folderId) {
        $q['folder'] = $folderId;
    }
    return url($baseModuleUrl . '?' . http_build_query($q));
};
$createUrl = static function (string $type) use ($wid, $folderId, $folderScope, $returnPath): string {
    $q = [
        'type' => $type,
        'workspace_id' => $wid,
        'return_to' => $returnPath,
    ];
    if ($folderId) {
        $q['parent_id'] = $folderId;
    }
    if ($type === 'folder') {
        $q['scope'] = $folderScope ?? 'documents';
    }
    return url('/items/create?' . http_build_query($q));
};
?>
<div class="section-head">
    <div>
        <h2><?= e(ucfirst($module)) ?></h2>
        <?php if ($folderable && ($breadcrumbs || $currentFolder)): ?>
            <nav class="folder-crumbs" aria-label="Breadcrumb">
                <a href="<?= e(url($baseModuleUrl . ($folderable ? '?' . http_build_query(['layout' => $layoutMode]) : ''))) ?>">Root</a>
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <span class="crumb-sep">/</span>
                    <a href="<?= e(url($baseModuleUrl . '?' . http_build_query([
                        'folder' => (int) $crumb['id'],
                        'layout' => $layoutMode,
                    ]))) ?>"><?= e($crumb['title']) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
    <div class="row-actions">
        <?php if ($folderable): ?>
            <a class="btn btn-ghost" href="<?= e($createUrl('folder')) ?>">+ Folder</a>
            <a class="btn btn-ghost <?= $layoutMode === 'grid' ? 'is-on' : '' ?>" href="<?= e($layoutLink('grid')) ?>">Grid</a>
            <a class="btn btn-ghost <?= $layoutMode === 'list' ? 'is-on' : '' ?>" href="<?= e($layoutLink('list')) ?>">List</a>
        <?php endif; ?>
        <?php if ($module === 'media'): ?>
            <a class="btn btn-secondary" href="<?= e($createUrl('image')) ?>">+ Image</a>
            <a class="btn btn-secondary" href="<?= e($createUrl('audio')) ?>">+ Audio</a>
            <a class="btn btn-primary" href="<?= e($createUrl('video')) ?>">+ Video</a>
        <?php elseif ($module !== 'archive'): ?>
            <a class="btn btn-primary" href="<?= e($createUrl($createType)) ?>">+ <?= e(item_type_label($createType)) ?></a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$items): ?>
    <div class="empty-quiet card" style="padding:2rem 1.2rem">
        <h3 style="margin-bottom:0.35rem"><?= e($empty['title']) ?></h3>
        <p class="muted"><?= e($empty['body']) ?></p>
        <?php if ($module === 'media'): ?>
            <div class="row-actions" style="justify-content:center;margin-top:1rem">
                <a class="btn btn-secondary" href="<?= e($createUrl('folder')) ?>">+ Folder</a>
                <a class="btn btn-secondary" href="<?= e($createUrl('image')) ?>">+ Image</a>
                <a class="btn btn-secondary" href="<?= e($createUrl('audio')) ?>">+ Audio</a>
                <a class="btn btn-primary" href="<?= e($createUrl('video')) ?>">+ Video</a>
            </div>
        <?php elseif ($folderable): ?>
            <div class="row-actions" style="justify-content:center;margin-top:1rem">
                <a class="btn btn-secondary" href="<?= e($createUrl('folder')) ?>">+ Folder</a>
                <a class="btn btn-primary" href="<?= e($createUrl($createType)) ?>">+ Create</a>
            </div>
        <?php elseif ($module !== 'archive'): ?>
            <p style="margin-top:1rem"><a class="btn btn-primary" href="<?= e($createUrl($createType)) ?>">+ Create</a></p>
        <?php endif; ?>
    </div>
<?php elseif ($folderable && $layoutMode === 'grid'): ?>
    <div
        class="media-grid sortable-board"
        data-sortable
        data-reorder-url="<?= e(url('/items/reorder')) ?>"
        data-move-url="<?= e(url('/items/move')) ?>"
        data-csrf="<?= e(csrf_token()) ?>"
        data-parent-id="<?= e($folderId !== null ? (string) $folderId : '') ?>"
    >
        <?php foreach ($folders as $item): ?>
            <div
                class="media-tile is-folder"
                draggable="true"
                data-id="<?= (int) $item['id'] ?>"
                data-type="folder"
                data-drop-folder="<?= (int) $item['id'] ?>"
            >
                <button type="button" class="drag-handle" aria-label="Drag to reorder" title="Drag">⋮⋮</button>
                <a class="media-tile-body" href="<?= e(url($baseModuleUrl . '?' . http_build_query([
                    'folder' => (int) $item['id'],
                    'layout' => $layoutMode,
                ]))) ?>">
                    <div class="media-fallback media-folder"><?= icon('folder', 28) ?><span class="muted tiny">Folder</span></div>
                    <span><?= e($item['title']) ?></span>
                </a>
            </div>
        <?php endforeach; ?>
        <?php foreach ($fileItems as $item):
            $mime = (string) ($item['mime_type'] ?? '');
            $fileUrl = url('/items/' . $item['id'] . '/file');
            ?>
            <div
                class="media-tile"
                draggable="true"
                data-id="<?= (int) $item['id'] ?>"
                data-type="<?= e($item['type']) ?>"
            >
                <button type="button" class="drag-handle" aria-label="Drag to reorder" title="Drag">⋮⋮</button>
                <a class="media-tile-body" href="<?= e(url('/items/' . $item['id'])) ?>">
                    <?php if (!empty($item['stored_filename']) && str_starts_with($mime, 'image/')): ?>
                        <img src="<?= e($fileUrl) ?>" alt="">
                    <?php elseif (str_starts_with($mime, 'video/') || $item['type'] === 'video'): ?>
                        <div class="media-fallback media-video"><?= icon('video', 28) ?><span class="muted tiny">Video</span></div>
                    <?php elseif (str_starts_with($mime, 'audio/') || $item['type'] === 'audio'): ?>
                        <div class="media-fallback media-audio"><?= icon('audio', 28) ?><span class="muted tiny">Audio</span></div>
                    <?php elseif ($item['type'] === 'document'): ?>
                        <div class="media-fallback"><?= icon(item_type_icon('document'), 28) ?><span class="muted tiny">Document</span></div>
                    <?php else: ?>
                        <div class="media-fallback"><?= icon(item_type_icon($item['type']), 28) ?><span class="muted tiny"><?= e(item_type_label($item['type'])) ?></span></div>
                    <?php endif; ?>
                    <span><?= e($item['title']) ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="panel">
        <ul
            class="item-list <?= $folderable ? 'sortable-board' : '' ?>"
            <?php if ($folderable): ?>
                data-sortable
                data-reorder-url="<?= e(url('/items/reorder')) ?>"
                data-move-url="<?= e(url('/items/move')) ?>"
                data-csrf="<?= e(csrf_token()) ?>"
                data-parent-id="<?= e($folderId !== null ? (string) $folderId : '') ?>"
            <?php endif; ?>
        >
            <?php if ($folderable): ?>
                <?php foreach ($folders as $item): ?>
                    <li
                        draggable="true"
                        data-id="<?= (int) $item['id'] ?>"
                        data-type="folder"
                        data-drop-folder="<?= (int) $item['id'] ?>"
                    >
                        <button type="button" class="drag-handle" aria-label="Drag to reorder" title="Drag">⋮⋮</button>
                <a href="<?= e(url($baseModuleUrl . '?' . http_build_query(array_filter([
                    'folder' => (int) $item['id'],
                    'layout' => $layoutMode,
                ])))) ?>">
                            <span class="type-ico"><?= icon('folder', 14) ?></span>
                            <span>
                                <strong><?= e($item['title']) ?></strong>
                                <span class="muted tiny">Folder</span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php foreach ($folderable ? $fileItems : $items as $item): ?>
                <li
                    <?php if ($folderable): ?>
                        draggable="true"
                        data-id="<?= (int) $item['id'] ?>"
                        data-type="<?= e($item['type']) ?>"
                    <?php endif; ?>
                >
                    <?php if ($folderable): ?>
                        <button type="button" class="drag-handle" aria-label="Drag to reorder" title="Drag">⋮⋮</button>
                    <?php endif; ?>
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
