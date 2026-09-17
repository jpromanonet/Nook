<?php
/** @var array $rows */
/** @var array $columns */
/** @var array $grid */
/** @var array $columnLabels */
?>
<div class="page-head board-head">
    <div>
        <h1>Board</h1>
        <p class="muted">Tasks by workspace — drag across columns to update status.</p>
    </div>
    <div class="row-actions">
        <a class="btn btn-primary" href="<?= e(url('/items/create?type=task')) ?>"><?= icon('plus', 14) ?> Task</a>
    </div>
</div>

<?php if (!$rows): ?>
    <div class="empty-quiet card" style="padding:1.5rem">
        <h3 style="margin-bottom:0.3rem">No workspaces yet</h3>
        <p class="muted">Create a workspace to start placing tasks on the board.</p>
        <p style="margin-top:1rem"><a class="btn btn-primary" href="<?= e(url('/workspaces/create')) ?>">+ Workspace</a></p>
    </div>
<?php else:
    $colCounts = [];
    foreach ($columns as $col) {
        $colCounts[$col] = 0;
        foreach ($grid as $wsGrid) {
            $colCounts[$col] += count($wsGrid[$col] ?? []);
        }
    }
?>
<div
    class="task-board"
    data-board
    data-move-url="<?= e(url('/board/move')) ?>"
    data-csrf="<?= e(csrf_token()) ?>"
>
    <div class="task-board-header">
        <div class="task-board-corner">
            <span class="colhead-label">Workspace</span>
        </div>
        <?php foreach ($columns as $col): ?>
            <div class="task-board-colhead status-<?= e($col) ?>">
                <span class="colhead-dot" aria-hidden="true"></span>
                <span class="colhead-label"><?= e($columnLabels[$col] ?? $col) ?></span>
                <span class="colhead-count"><?= (int) $colCounts[$col] ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <?php foreach ($rows as $row):
        $rowKey = (string) $row['id'];
        ?>
        <div class="task-board-row" data-workspace="<?= e($rowKey) ?>">
            <div class="task-board-ws">
                <span class="dot" style="background:<?= e($row['color']) ?>"></span>
                <span class="ws-label"><?= e($row['name']) ?></span>
            </div>
            <?php foreach ($columns as $col): ?>
                <div
                    class="task-board-cell"
                    data-status="<?= e($col) ?>"
                    data-workspace="<?= e($rowKey) ?>"
                >
                    <?php foreach ($grid[$rowKey][$col] ?? [] as $task): ?>
                        <article
                            class="board-card"
                            draggable="true"
                            data-id="<?= (int) $task['id'] ?>"
                        >
                            <a href="<?= e(url('/items/' . $task['id'])) ?>" class="board-card-link">
                                <strong><?= e($task['title']) ?></strong>
                                <?php if (!empty($task['due_date'])): ?>
                                    <span class="muted tiny"><?= e(relative_day($task['due_date']) ?: format_date($task['due_date'])) ?></span>
                                <?php endif; ?>
                            </a>
                            <?php if (($task['priority'] ?? '') === 'high' || ($task['priority'] ?? '') === 'urgent'): ?>
                                <em class="board-prio"><?= e(priorities()[$task['priority']] ?? '') ?></em>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
