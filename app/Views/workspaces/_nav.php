<?php
/** @var array $workspace */
/** @var string $module */
/** @var array $counts */
$wid = (int) $workspace['id'];
$counts = $counts ?? [];
$tabs = [
    'overview' => ['Overview', '/workspaces/' . $wid, 'workspace'],
    'documents' => ['Documents', '/workspaces/' . $wid . '/documents', 'doc'],
    'tasks' => ['Tasks', '/workspaces/' . $wid . '/tasks', 'task'],
    'notes' => ['Notes', '/workspaces/' . $wid . '/notes', 'note'],
    'files' => ['Files', '/workspaces/' . $wid . '/files', 'file'],
    'media' => ['Media', '/workspaces/' . $wid . '/media', 'image'],
    'links' => ['Links', '/workspaces/' . $wid . '/links', 'link'],
    'accounts' => ['Accounts', '/workspaces/' . $wid . '/accounts', 'account'],
    'archive' => ['Archive', '/workspaces/' . $wid . '/archive', 'archive'],
];
$statusClass = match ($workspace['status'] ?? '') {
    'active' => 'badge-moss',
    'paused' => 'badge-mustard',
    'completed' => 'badge-blue',
    'archived' => 'badge-ink',
    default => 'badge-ink',
};
?>
<header class="ws-hero card" style="--ws-color: <?= e($workspace['color']) ?>">
    <div class="ws-hero-main">
        <div class="ws-hero-top">
            <span class="ws-type"><?= e(workspace_types()[$workspace['type']] ?? $workspace['type']) ?></span>
            <span class="badge <?= e($statusClass) ?>"><?= e(workspace_statuses()[$workspace['status']] ?? $workspace['status']) ?></span>
        </div>
        <h1 class="ws-title"><?= e($workspace['name']) ?></h1>
        <?php if (!empty($workspace['description'])): ?>
            <p class="ws-desc"><?= e($workspace['description']) ?></p>
        <?php endif; ?>
        <div class="ws-meta">
            <span><?= icon('task', 14) ?> <?= (int) ($counts['task'] ?? 0) ?> tasks</span>
            <span><?= icon('doc', 14) ?> <?= (int) ($counts['document'] ?? 0) ?> docs</span>
            <span><?= icon('note', 14) ?> <?= (int) (($counts['note'] ?? 0) + ($counts['idea'] ?? 0)) ?> notes</span>
            <span><?= icon('image', 14) ?> <?= (int) (($counts['image'] ?? 0) + ($counts['audio'] ?? 0) + ($counts['video'] ?? 0)) ?> media</span>
        </div>
    </div>
    <div class="ws-hero-actions">
        <a class="btn btn-secondary" href="<?= e(url('/workspaces/' . $wid . '/edit')) ?>">Edit</a>
        <a class="btn btn-primary" href="<?= e(url('/items/create?type=task&workspace_id=' . $wid)) ?>"><?= icon('plus', 14) ?> Task</a>
    </div>
</header>

<nav class="tabs ws-tabs" aria-label="Workspace sections">
    <?php foreach ($tabs as $key => [$label, $path, $iconName]): ?>
        <a class="<?= ($module ?? '') === $key ? 'is-active' : '' ?>" href="<?= e(url($path)) ?>">
            <?= icon($iconName, 14) ?>
            <span><?= e($label) ?></span>
            <?php
            $countKey = match ($key) {
                'documents' => (int) ($counts['document'] ?? 0),
                'tasks' => (int) ($counts['task'] ?? 0),
                'notes' => (int) (($counts['note'] ?? 0) + ($counts['idea'] ?? 0)),
                'files' => (int) ($counts['file'] ?? 0),
                'media' => (int) (($counts['image'] ?? 0) + ($counts['audio'] ?? 0) + ($counts['video'] ?? 0)),
                'links' => (int) ($counts['link'] ?? 0),
                'accounts' => (int) ($counts['account'] ?? 0),
                default => null,
            };
            if ($countKey !== null && $countKey > 0):
            ?>
                <em class="tab-count"><?= $countKey ?></em>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
