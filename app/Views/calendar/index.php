<?php
/** @var string $viewMode */
/** @var int $year */
/** @var int $month */
/** @var string $cursor */
/** @var array $byDay */
/** @var array $items */
$months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$prev = date('Y-m-d', strtotime($cursor . ' -1 month'));
$next = date('Y-m-d', strtotime($cursor . ' +1 month'));
if ($viewMode === 'week') {
    $prev = date('Y-m-d', strtotime($cursor . ' -7 days'));
    $next = date('Y-m-d', strtotime($cursor . ' +7 days'));
}
?>
<div class="page-head">
    <div>
        <h1>Calendar</h1>
        <p class="muted"><?= e($months[$month - 1] . ' ' . $year) ?></p>
    </div>
    <div class="row-actions">
        <a class="btn btn-ghost <?= $viewMode === 'month' ? 'is-on' : '' ?>" href="<?= e(url('/calendar?view=month&date=' . $cursor)) ?>">Month</a>
        <a class="btn btn-ghost <?= $viewMode === 'week' ? 'is-on' : '' ?>" href="<?= e(url('/calendar?view=week&date=' . $cursor)) ?>">Week</a>
        <a class="btn btn-ghost <?= $viewMode === 'agenda' ? 'is-on' : '' ?>" href="<?= e(url('/calendar?view=agenda&date=' . $cursor)) ?>">Agenda</a>
        <a class="btn btn-secondary" href="<?= e(url('/calendar?view=' . $viewMode . '&date=' . $prev)) ?>">←</a>
        <a class="btn btn-secondary" href="<?= e(url('/calendar?view=' . $viewMode . '&date=' . $next)) ?>">→</a>
        <a class="btn btn-primary" href="<?= e(url('/items/create?type=event')) ?>">+ Event</a>
    </div>
</div>

<?php if ($viewMode === 'agenda'): ?>
    <div class="stack">
        <?php if (!$items): ?>
            <div class="empty card"><p>Nothing on the horizon.</p></div>
        <?php endif; ?>
        <?php
        $last = '';
        foreach ($items as $item):
            $day = $item['start_date'] ?: $item['due_date'];
            if ($day !== $last):
                $last = $day;
        ?>
            <h3 class="day-label"><?= e(format_date($day)) ?></h3>
        <?php endif; ?>
            <a class="card agenda-row" href="<?= e(url('/items/' . $item['id'])) ?>">
                <span class="dot" style="background:<?= e($item['workspace_color'] ?: '#71806A') ?>"></span>
                <span>
                    <strong><?= e($item['title']) ?></strong>
                    <span class="muted tiny"><?= e(item_type_label($item['type'])) ?> · <?= e($item['workspace_name'] ?: 'Inbox') ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <?php
    $startTs = strtotime($viewMode === 'week' ? $start : date('Y-m-01', strtotime($cursor)));
    $gridStart = $viewMode === 'week' ? $startTs : strtotime('monday this week', $startTs);
    if ($viewMode !== 'week') {
        $firstDow = (int) date('N', $startTs);
        $gridStart = strtotime($cursor . ' first day of this month');
        $gridStart = strtotime('-' . ($firstDow - 1) . ' days', strtotime(date('Y-m-01', $startTs)));
    }
    $cells = $viewMode === 'week' ? 7 : 42;
    ?>
    <div class="cal-grid <?= $viewMode ?>">
        <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
            <div class="cal-dow"><?= $d ?></div>
        <?php endforeach; ?>
        <?php for ($i = 0; $i < $cells; $i++):
            $dayTs = strtotime('+' . $i . ' days', $gridStart);
            $key = date('Y-m-d', $dayTs);
            $inMonth = date('n', $dayTs) === (string) $month || $viewMode === 'week';
            $isToday = $key === date('Y-m-d');
            ?>
            <div class="cal-cell <?= $inMonth ? '' : 'is-out' ?> <?= $isToday ? 'is-today' : '' ?>">
                <div class="cal-num"><?= date('j', $dayTs) ?></div>
                <?php foreach (array_slice($byDay[$key] ?? [], 0, 3) as $item): ?>
                    <a class="cal-pill" style="background:<?= e($item['workspace_color'] ?: '#71806A') ?>" href="<?= e(url('/items/' . $item['id'])) ?>"><?= e(truncate($item['title'], 22)) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
    </div>
<?php endif; ?>
