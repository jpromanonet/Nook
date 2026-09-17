<?php
/** @var array $workspace */
/** @var array $counts */
require __DIR__ . '/_nav.php';
$wid = (int) $workspace['id'];
?>

<section class="ws-quick">
    <a class="ws-quick-tile" href="<?= e(url('/items/create?type=task&workspace_id=' . $wid)) ?>">
        <?= icon('task', 18) ?>
        <span>Task</span>
    </a>
    <a class="ws-quick-tile" href="<?= e(url('/items/create?type=note&workspace_id=' . $wid)) ?>">
        <?= icon('note', 18) ?>
        <span>Note</span>
    </a>
    <a class="ws-quick-tile" href="<?= e(url('/items/create?type=document&workspace_id=' . $wid)) ?>">
        <?= icon('doc', 18) ?>
        <span>Document</span>
    </a>
    <a class="ws-quick-tile" href="<?= e(url('/items/create?type=link&workspace_id=' . $wid)) ?>">
        <?= icon('link', 18) ?>
        <span>Link</span>
    </a>
    <a class="ws-quick-tile" href="<?= e(url('/items/create?type=audio&workspace_id=' . $wid)) ?>">
        <?= icon('audio', 18) ?>
        <span>Audio</span>
    </a>
    <a class="ws-quick-tile" href="<?= e(url('/items/create?type=video&workspace_id=' . $wid)) ?>">
        <?= icon('video', 18) ?>
        <span>Video</span>
    </a>
</section>

<div class="split-2">
    <section class="panel overview-panel">
        <div class="section-head">
            <h2><?= icon('task', 16) ?> Next</h2>
            <a href="<?= e(url('/items/create?type=task&workspace_id=' . $wid)) ?>">+ Add</a>
        </div>
        <?php if (empty($next_tasks)): ?>
            <div class="empty-quiet">
                <p>No open tasks.</p>
                <p class="tiny">Enjoy the quiet, or start the next thing.</p>
            </div>
        <?php else: ?>
            <ul class="item-list">
                <?php foreach ($next_tasks as $item): ?>
                    <li>
                        <a href="<?= e(url('/items/' . $item['id'])) ?>">
                            <span class="type-ico"><?= icon('task', 14) ?></span>
                            <span>
                                <strong><?= e($item['title']) ?></strong>
                                <span class="muted tiny"><?= e($item['due_date'] ? relative_day($item['due_date']) : (task_statuses()[$item['status']] ?? '')) ?></span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="panel overview-panel">
        <div class="section-head">
            <h2><?= icon('calendar', 16) ?> Upcoming</h2>
            <a href="<?= e(url('/items/create?type=event&workspace_id=' . $wid)) ?>">+ Event</a>
        </div>
        <?php if (empty($upcoming)): ?>
            <div class="empty-quiet">
                <p>Nothing scheduled.</p>
                <p class="tiny">Deadlines and episodes will show up here.</p>
            </div>
        <?php else: ?>
            <ul class="item-list">
                <?php foreach ($upcoming as $item): ?>
                    <li>
                        <a href="<?= e(url('/items/' . $item['id'])) ?>">
                            <span class="type-ico"><?= icon(item_type_icon($item['type']), 14) ?></span>
                            <span>
                                <strong><?= e($item['title']) ?></strong>
                                <span class="muted tiny"><?= e(format_date($item['start_date'] ?: $item['due_date'])) ?></span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<section class="panel overview-panel">
    <div class="section-head">
        <h2><?= icon('pin', 16) ?> Pinned</h2>
    </div>
    <?php if (empty($pinned)): ?>
        <div class="empty-quiet">
            <p>Nothing pinned yet.</p>
            <p class="tiny">Keep a brand manual, structure note or current sprint close.</p>
        </div>
    <?php else: ?>
        <div class="chip-row">
            <?php foreach ($pinned as $item): ?>
                <a class="chip chip-rich" href="<?= e(url('/items/' . $item['id'])) ?>">
                    <?= icon(item_type_icon($item['type']), 14) ?>
                    <?= e($item['title']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="split-2">
    <section class="panel overview-panel">
        <div class="section-head">
            <h2><?= icon('doc', 16) ?> Recent</h2>
            <a href="<?= e(url('/workspaces/' . $wid . '/documents')) ?>">Browse</a>
        </div>
        <?php if (empty($recent)): ?>
            <div class="empty-quiet">
                <p>No recent items.</p>
                <p class="tiny">Documents, notes and media will gather here as you work.</p>
            </div>
        <?php else: ?>
            <ul class="item-list">
                <?php foreach ($recent as $item): ?>
                    <li>
                        <a href="<?= e(url('/items/' . $item['id'])) ?>">
                            <span class="type-ico"><?= icon(item_type_icon($item['type']), 14) ?></span>
                            <span>
                                <strong><?= e($item['title']) ?></strong>
                                <span class="muted tiny"><?= e(item_type_label($item['type'])) ?> · <?= e(relative_day($item['updated_at'])) ?></span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="panel overview-panel">
        <div class="section-head">
            <h2><?= icon('today', 16) ?> Activity</h2>
        </div>
        <?php if (empty($activity)): ?>
            <div class="empty-quiet">
                <p>The story starts here.</p>
                <p class="tiny">Creates, uploads and decisions will leave a trail.</p>
            </div>
        <?php else: ?>
            <ul class="activity">
                <?php foreach ($activity as $row): ?>
                    <li>
                        <span><?= e($row['summary']) ?></span>
                        <span class="muted tiny"><?= e(relative_day($row['created_at'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
