<div class="page-head">
    <div>
        <h1>Workspaces</h1>
        <p class="muted">Every context gets its own room.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/workspaces/create')) ?>">+ New</a>
</div>
<?php if (empty($workspaces)): ?>
    <div class="empty card">
        <p>No workspaces yet.</p>
        <a class="btn btn-primary" href="<?= e(url('/workspaces/create')) ?>">Create your first</a>
    </div>
<?php else: ?>
    <div class="ws-grid">
        <?php foreach ($workspaces as $ws): ?>
            <a class="card ws-card" href="<?= e(url('/workspaces/' . $ws['id'])) ?>">
                <span class="dot" style="background:<?= e($ws['color']) ?>"></span>
                <div>
                    <strong><?= e($ws['name']) ?></strong>
                    <p class="muted tiny"><?= e(workspace_types()[$ws['type']] ?? '') ?> · <?= e(workspace_statuses()[$ws['status']] ?? '') ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
