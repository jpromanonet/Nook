<?php
/** @var array $workspaces */
/** @var array $recent */
/** @var array $today */
$name = first_name(Auth::user()['name'] ?? '');
$dueCount = count($today['due'] ?? []);
$overdue = count($today['overdue'] ?? []);
$events = count($today['events'] ?? []);
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><?= e(greeting_for_hour()) ?><?= $name !== '' ? ', ' . e($name) : '' ?></p>
        <h1>Home</h1>
        <p class="muted"><?= e(format_day_long()) ?></p>
    </div>
    <div class="row-actions">
        <a class="btn btn-secondary" href="<?= e(url('/today')) ?>"><?= icon('today', 14) ?> Today</a>
        <a class="btn btn-primary" href="<?= e(url('/items/create?type=note')) ?>"><?= icon('plus', 14) ?> Capture</a>
    </div>
</div>

<div class="stat-row">
    <a class="card stat stat-today" href="<?= e(url('/today')) ?>">
        <span class="stat-label"><?= icon('today', 14) ?> Today</span>
        <strong><?= (int) $dueCount ?> <?= $dueCount === 1 ? 'task due' : 'tasks due' ?></strong>
    </a>
    <a class="card stat stat-upcoming" href="<?= e(url('/calendar')) ?>">
        <span class="stat-label"><?= icon('calendar', 14) ?> Upcoming</span>
        <strong><?= (int) $events ?> <?= $events === 1 ? 'event' : 'events' ?></strong>
    </a>
    <a class="card stat stat-attention<?= $overdue ? ' is-urgent' : '' ?>" href="<?= e(url('/today')) ?>">
        <span class="stat-label"><?= icon('check', 14) ?> Attention</span>
        <strong><?= $overdue ? $overdue . ' overdue' : 'Nothing urgent here.' ?></strong>
    </a>
</div>

<div class="split-2">
    <section class="panel">
        <div class="section-head">
            <h2>Workspaces</h2>
            <a href="<?= e(url('/workspaces/create')) ?>">+ New</a>
        </div>
        <?php if (!$workspaces): ?>
            <div class="empty-quiet">
                <p>No workspaces yet.</p>
                <p class="tiny">A workspace is a place for a company, product, book or project.</p>
                <p style="margin-top:0.85rem"><a class="btn btn-primary" href="<?= e(url('/workspaces/create')) ?>">Create one</a></p>
            </div>
        <?php else: ?>
            <div class="ws-grid">
                <?php foreach ($workspaces as $ws): ?>
                    <a class="card ws-card" href="<?= e(url('/workspaces/' . $ws['id'])) ?>" style="--ws-color: <?= e($ws['color']) ?>">
                        <span class="dot"></span>
                        <div>
                            <strong><?= e($ws['name']) ?></strong>
                            <p class="muted tiny"><?= e(workspace_types()[$ws['type']] ?? $ws['type']) ?> · <?= e(workspace_statuses()[$ws['status']] ?? $ws['status']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <section class="panel">
        <div class="section-head">
            <h2>Recent</h2>
            <a href="<?= e(url('/search')) ?>">Search</a>
        </div>
        <?php if (!$recent): ?>
            <div class="empty-quiet">
                <p>Nothing recent.</p>
                <p class="tiny">That’s alright — capture something when you’re ready.</p>
            </div>
        <?php else: ?>
            <ul class="item-list">
                <?php foreach ($recent as $item): ?>
                    <li>
                        <a href="<?= e(url('/items/' . $item['id'])) ?>">
                            <span class="type-ico"><?= icon(item_type_icon($item['type']), 14) ?></span>
                            <span>
                                <strong><?= e($item['title']) ?></strong>
                                <span class="muted tiny"><?= e($item['workspace_name'] ?: 'Inbox') ?> · <?= e(relative_day($item['updated_at'])) ?></span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
