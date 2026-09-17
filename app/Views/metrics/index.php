<?php
/** @var array $metrics */
$tasks = $metrics['tasks'];
$library = $metrics['library'];
$statusOrder = ['todo', 'doing', 'blocked', 'done', 'cancelled'];
$statusColors = [
    'todo' => '#788FA0',
    'doing' => '#71806A',
    'blocked' => '#C67D67',
    'done' => '#5B6A55',
    'cancelled' => '#9A958C',
];
$statusLabels = task_statuses();
$typeLabels = [];
foreach ($library['by_type'] as $type => $count) {
    $typeLabels[$type] = item_type_label($type);
}
$chartPayload = [
    'tasks' => [
        'status' => [
            'labels' => [],
            'values' => [],
            'colors' => [],
        ],
        'workspaces' => [
            'labels' => array_column($tasks['by_workspace'], 'label'),
            'values' => array_column($tasks['by_workspace'], 'count'),
            'colors' => array_column($tasks['by_workspace'], 'color'),
        ],
        'priority' => [
            'labels' => [],
            'values' => [],
        ],
        'trend' => [
            'labels' => array_column($tasks['completion_trend'], 'label'),
            'values' => array_column($tasks['completion_trend'], 'count'),
        ],
    ],
    'library' => [
        'types' => [
            'labels' => [],
            'values' => [],
        ],
        'workspaces' => [
            'labels' => array_column($library['by_workspace'], 'label'),
            'values' => array_column($library['by_workspace'], 'count'),
            'colors' => array_column($library['by_workspace'], 'color'),
        ],
        'activity' => [
            'labels' => array_column($library['activity_trend'], 'label'),
            'values' => array_column($library['activity_trend'], 'count'),
        ],
    ],
];
foreach ($statusOrder as $s) {
    if (!isset($tasks['by_status'][$s])) {
        continue;
    }
    $chartPayload['tasks']['status']['labels'][] = $statusLabels[$s] ?? $s;
    $chartPayload['tasks']['status']['values'][] = (int) $tasks['by_status'][$s];
    $chartPayload['tasks']['status']['colors'][] = $statusColors[$s] ?? '#71806A';
}
foreach (['urgent', 'high', 'normal', 'low'] as $p) {
    if (!isset($tasks['by_priority'][$p])) {
        continue;
    }
    $chartPayload['tasks']['priority']['labels'][] = priorities()[$p] ?? $p;
    $chartPayload['tasks']['priority']['values'][] = (int) $tasks['by_priority'][$p];
}
foreach ($library['by_type'] as $type => $count) {
    $chartPayload['library']['types']['labels'][] = $typeLabels[$type] ?? $type;
    $chartPayload['library']['types']['values'][] = (int) $count;
}
$completionRate = $tasks['total'] > 0
    ? (int) round(($tasks['done'] / max(1, $tasks['done'] + $tasks['open'])) * 100)
    : 0;
?>
<div class="page-head metrics-head">
    <div>
        <h1>Metrics</h1>
        <p class="muted">A wide look at how work is moving — tasks first, then the rest of your library.</p>
    </div>
    <div class="row-actions">
        <a class="btn btn-secondary" href="<?= e(url('/board')) ?>"><?= icon('board', 14) ?> Board</a>
        <a class="btn btn-primary" href="<?= e(url('/items/create?type=task')) ?>"><?= icon('plus', 14) ?> Task</a>
    </div>
</div>

<section class="metrics-hero">
    <article class="metric-stat">
        <span class="metric-label">Open tasks</span>
        <strong class="metric-value"><?= (int) $tasks['open'] ?></strong>
        <span class="muted tiny">To do, doing & blocked</span>
    </article>
    <article class="metric-stat is-warn">
        <span class="metric-label">Overdue</span>
        <strong class="metric-value"><?= (int) $tasks['overdue'] ?></strong>
        <span class="muted tiny">Past due date</span>
    </article>
    <article class="metric-stat is-moss">
        <span class="metric-label">Done this week</span>
        <strong class="metric-value"><?= (int) $tasks['done_this_week'] ?></strong>
        <span class="muted tiny">Updated as done</span>
    </article>
    <article class="metric-stat">
        <span class="metric-label">Due in 7 days</span>
        <strong class="metric-value"><?= (int) $tasks['due_this_week'] ?></strong>
        <span class="muted tiny">Upcoming deadlines</span>
    </article>
    <article class="metric-stat is-moss">
        <span class="metric-label">Completion</span>
        <strong class="metric-value"><?= $completionRate ?>%</strong>
        <span class="muted tiny">Done vs open</span>
    </article>
</section>

<section class="metrics-section">
    <header class="metrics-section-head">
        <h2><?= icon('task', 18) ?> Tasks</h2>
        <p class="muted">Status, focus and recent completion.</p>
    </header>
    <div class="metrics-grid">
        <article class="metrics-card metrics-card-lg">
            <h3>By status</h3>
            <div class="chart-wrap chart-donut">
                <canvas id="chart-task-status" aria-label="Tasks by status"></canvas>
            </div>
        </article>
        <article class="metrics-card metrics-card-lg">
            <h3>Open by workspace</h3>
            <div class="chart-wrap">
                <canvas id="chart-task-workspace" aria-label="Open tasks by workspace"></canvas>
            </div>
        </article>
        <article class="metrics-card metrics-card-wide">
            <h3>Completed · last 14 days</h3>
            <div class="chart-wrap chart-line">
                <canvas id="chart-task-trend" aria-label="Task completion trend"></canvas>
            </div>
        </article>
        <article class="metrics-card metrics-snapshot">
            <h3>Health</h3>
            <?php
            $snapTotal = max(1, (int) $tasks['total']);
            $snapOpen = (int) $tasks['open'];
            $snapDone = (int) $tasks['done'];
            $snapOverdue = (int) $tasks['overdue'];
            $pctDone = (int) round(($snapDone / $snapTotal) * 100);
            $pctOpen = (int) round(($snapOpen / $snapTotal) * 100);
            $pctOver = (int) round(($snapOverdue / $snapTotal) * 100);
            $ring = 2 * M_PI * 42;
            $ringDone = $ring * ($pctDone / 100);
            ?>
            <div class="snap-layout">
                <div class="snap-ring" aria-hidden="true">
                    <svg viewBox="0 0 100 100" width="132" height="132">
                        <circle class="snap-ring-track" cx="50" cy="50" r="42" />
                        <circle
                            class="snap-ring-progress"
                            cx="50" cy="50" r="42"
                            stroke-dasharray="<?= e(number_format($ringDone, 2, '.', '')) ?> <?= e(number_format($ring, 2, '.', '')) ?>"
                        />
                    </svg>
                    <div class="snap-ring-label">
                        <strong><?= $pctDone ?>%</strong>
                        <span>done</span>
                    </div>
                </div>
                <div class="snap-bars">
                    <div class="snap-row">
                        <div class="snap-row-head">
                            <span>Open</span>
                            <strong><?= $snapOpen ?></strong>
                        </div>
                        <div class="snap-bar"><i style="width:<?= min(100, $pctOpen) ?>%;background:var(--dusty-blue)"></i></div>
                    </div>
                    <div class="snap-row">
                        <div class="snap-row-head">
                            <span>Done</span>
                            <strong><?= $snapDone ?></strong>
                        </div>
                        <div class="snap-bar"><i style="width:<?= min(100, $pctDone) ?>%;background:var(--moss)"></i></div>
                    </div>
                    <div class="snap-row">
                        <div class="snap-row-head">
                            <span>Overdue</span>
                            <strong class="<?= $snapOverdue > 0 ? 'is-warn' : '' ?>"><?= $snapOverdue ?></strong>
                        </div>
                        <div class="snap-bar"><i style="width:<?= min(100, $pctOver) ?>%;background:var(--terracotta)"></i></div>
                    </div>
                    <p class="muted tiny snap-foot"><?= (int) $tasks['total'] ?> tasks in total</p>
                </div>
            </div>
        </article>
        <article class="metrics-card metrics-card-wide">
            <h3>Priority (open)</h3>
            <div class="chart-wrap">
                <canvas id="chart-task-priority" aria-label="Open tasks by priority"></canvas>
            </div>
        </article>
        <article class="metrics-card metrics-focus">
            <h3>Focus</h3>
            <div class="focus-stack">
                <div class="focus-pill">
                    <span class="metric-label">Due in 7 days</span>
                    <strong><?= (int) $tasks['due_this_week'] ?></strong>
                </div>
                <div class="focus-pill <?= ((int) $tasks['overdue']) > 0 ? 'is-warn' : '' ?>">
                    <span class="metric-label">Overdue now</span>
                    <strong><?= (int) $tasks['overdue'] ?></strong>
                </div>
                <div class="focus-pill is-moss">
                    <span class="metric-label">Done this week</span>
                    <strong><?= (int) $tasks['done_this_week'] ?></strong>
                </div>
            </div>
        </article>
    </div>
</section>

<section class="metrics-section">
    <header class="metrics-section-head">
        <h2><?= icon('workspace', 18) ?> Everything else</h2>
        <p class="muted">Your broader library across types and workspaces.</p>
    </header>
    <div class="metrics-grid">
        <article class="metrics-card metrics-card-lg">
            <h3>By type</h3>
            <div class="chart-wrap">
                <canvas id="chart-type" aria-label="Items by type"></canvas>
            </div>
        </article>
        <article class="metrics-card metrics-card-lg">
            <h3>By workspace</h3>
            <div class="chart-wrap">
                <canvas id="chart-all-workspace" aria-label="Items by workspace"></canvas>
            </div>
        </article>
        <article class="metrics-card metrics-card-wide">
            <h3>Created · last 14 days</h3>
            <div class="chart-wrap chart-line">
                <canvas id="chart-activity" aria-label="Items created trend"></canvas>
            </div>
        </article>
        <article class="metrics-card">
            <h3>Library size</h3>
            <p class="metric-value metric-value-sm"><?= (int) $library['total'] ?></p>
            <p class="muted tiny">Active items (excluding folders)</p>
            <ul class="metrics-kv" style="margin-top:1rem">
                <?php foreach (array_slice($library['by_type'], 0, 6, true) as $type => $count): ?>
                    <li>
                        <span><?= e(item_type_label($type)) ?></span>
                        <strong><?= (int) $count ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </article>
    </div>
</section>

<script type="application/json" id="metrics-data"><?= json_encode($chartPayload, JSON_UNESCAPED_UNICODE) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js" defer></script>
<script src="<?= e(url('/assets/js/metrics.js')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/js/metrics.js') ?>" defer></script>
