<?php
/** @var array $snapshot */
$plan = $snapshot['plan'];
$name = first_name(Auth::user()['name'] ?? '');
$date = $snapshot['date'];
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><?= e(greeting_for_hour()) ?><?= $name !== '' ? ', ' . e($name) : '' ?></p>
        <h1>Today</h1>
        <p class="muted"><?= e(format_day_long($date)) ?></p>
    </div>
</div>

<?php if (!empty($snapshot['overdue'])): ?>
    <section class="card attention">
        <h2><?= count($snapshot['overdue']) ?> things need your attention</h2>
        <ul class="item-list compact">
            <?php foreach ($snapshot['overdue'] as $item): ?>
                <li>
                    <a href="<?= e(url('/items/' . $item['id'])) ?>">
                        <span class="type-ico"><?= icon('task') ?></span>
                        <span>
                            <strong><?= e($item['title']) ?></strong>
                            <span class="muted tiny"><?= e($item['workspace_name'] ?: '—') ?> · due <?= e(format_date($item['due_date'])) ?></span>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<div class="split-2 today-grid">
    <section class="card">
        <h2>Today</h2>
        <?php if (!$snapshot['events'] && !$snapshot['due']): ?>
            <p class="muted">Your day is clear.</p>
        <?php endif; ?>
        <?php foreach ($snapshot['events'] as $item): ?>
            <a class="agenda-row" href="<?= e(url('/items/' . $item['id'])) ?>">
                <span class="dot" style="background:<?= e($item['workspace_color'] ?: '#788FA0') ?>"></span>
                <span>
                    <strong><?= e($item['title']) ?></strong>
                    <span class="muted tiny"><?= e($item['workspace_name'] ?: 'Event') ?></span>
                </span>
            </a>
        <?php endforeach; ?>
        <?php foreach ($snapshot['due'] as $item): ?>
            <a class="agenda-row" href="<?= e(url('/items/' . $item['id'])) ?>">
                <span class="type-ico"><?= icon('task') ?></span>
                <span>
                    <strong><?= e($item['title']) ?></strong>
                    <span class="muted tiny"><?= e($item['workspace_name'] ?: '—') ?> · <?= e(task_statuses()[$item['status']] ?? $item['status']) ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </section>

    <section class="card daily-plan">
        <h2>Daily plan</h2>
        <form method="post" action="<?= e(url('/today')) ?>" class="stack daily-plan-form">
            <?= csrf_field() ?>
            <input type="hidden" name="note_date" value="<?= e($date) ?>">
            <label class="field"><span>Must do</span><textarea name="must_do" rows="2" placeholder="The few things that matter"><?= e($plan['must_do'] ?? '') ?></textarea></label>
            <label class="field"><span>Should do</span><textarea name="should_do" rows="2"><?= e($plan['should_do'] ?? '') ?></textarea></label>
            <label class="field"><span>If there’s time</span><textarea name="if_time" rows="1"><?= e($plan['if_time'] ?? '') ?></textarea></label>
            <label class="field"><span>Notes</span><textarea name="notes" rows="2"><?= e($plan['notes'] ?? '') ?></textarea></label>
            <label class="field"><span>Things learned</span><textarea name="learned" rows="1"><?= e($plan['learned'] ?? '') ?></textarea></label>
            <label class="field"><span>Completed</span><textarea name="completed" rows="1"><?= e($plan['completed'] ?? '') ?></textarea></label>
            <label class="field"><span>Moved to tomorrow</span><textarea name="moved_tomorrow" rows="1"><?= e($plan['moved_tomorrow'] ?? '') ?></textarea></label>
            <button class="btn btn-primary" type="submit">Save plan</button>
        </form>
    </section>
</div>
