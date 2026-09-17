<?php
/** @var array $item */
$meta = item_meta($item);
$wid = $item['workspace_id'] ?? null;
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><?= icon(item_type_icon($item['type'])) ?> <?= e(item_type_label($item['type'])) ?><?= !empty($item['workspace_name']) ? ' · ' . e($item['workspace_name']) : '' ?></p>
        <h1><?= e($item['title']) ?></h1>
        <p class="muted tiny">Created <?= e(format_dt($item['created_at'])) ?> · Updated <?= e(format_dt($item['updated_at'])) ?></p>
    </div>
    <div class="row-actions">
        <form method="post" action="<?= e(url('/items/' . $item['id'] . '/favorite')) ?>"><?= csrf_field() ?><button class="btn btn-ghost" type="submit"><?= !empty($item['is_favorite']) ? '★' : '☆' ?></button></form>
        <form method="post" action="<?= e(url('/items/' . $item['id'] . '/pin')) ?>"><?= csrf_field() ?><button class="btn btn-ghost" type="submit"><?= !empty($item['is_pinned']) ? 'Pinned' : 'Pin' ?></button></form>
        <a class="btn btn-secondary" href="<?= e(url('/items/' . $item['id'] . '/edit')) ?>">Edit</a>
    </div>
</div>

<?php if (!empty($item['tags'])): ?>
    <div class="chip-row">
        <?php foreach ($item['tags'] as $tag): ?>
            <a class="chip" href="<?= e(url('/search?tag=' . urlencode($tag['slug']))) ?>"><?= e($tag['name']) ?></a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="split-2">
    <article class="card prose">
        <?php if ($item['type'] === 'task'): ?>
            <p><span class="badge <?= e(status_badge_class($item['status'])) ?>"><?= e(task_statuses()[$item['status']] ?? $item['status']) ?></span>
            <span class="badge <?= e(status_badge_class($item['priority'] ?? 'normal')) ?>"><?= e(priorities()[$item['priority'] ?? 'normal'] ?? '') ?></span>
            <?php if ($item['due_date']): ?> · due <?= e(format_date($item['due_date'])) ?><?php endif; ?></p>
        <?php endif; ?>

        <?php if (!empty($item['description'])): ?>
            <p><?= nl2br(e($item['description'])) ?></p>
        <?php endif; ?>

        <?php if (!empty($item['content']) && $item['type'] !== 'decision'): ?>
            <div class="md"><?= Markdown::render($item['content']) ?></div>
        <?php endif; ?>

        <?php if ($item['type'] === 'link' && !empty($meta['url'])): ?>
            <p><a href="<?= e($meta['url']) ?>" target="_blank" rel="noopener"><?= e($meta['url']) ?></a></p>
            <p class="muted"><?= e(link_categories()[$meta['category'] ?? 'other'] ?? '') ?></p>
        <?php endif; ?>

        <?php if ($item['type'] === 'account'): ?>
            <dl class="meta-dl">
                <?php foreach (['platform' => 'Platform', 'username' => 'Username', 'handle' => 'Handle', 'account_email' => 'Email', 'url' => 'URL', 'owner' => 'Owner', 'recovery_email' => 'Recovery email', 'vault' => 'Vault', 'vault_entry' => 'Entry'] as $k => $label): ?>
                    <?php if (!empty($meta[$k])): ?>
                        <div><dt><?= e($label) ?></dt><dd><?= $k === 'url' ? '<a href="' . e($meta[$k]) . '" target="_blank" rel="noopener">' . e($meta[$k]) . '</a>' : e((string) $meta[$k]) ?></dd></div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <div><dt>2FA</dt><dd><?= !empty($meta['two_fa']) ? 'Enabled' : '—' ?></dd></div>
            </dl>
            <p class="muted tiny">Credentials stored in your vault — never in Nook.</p>
        <?php endif; ?>

        <?php if ($item['type'] === 'decision'): ?>
            <?php foreach (['context' => 'Context', 'alternatives' => 'Alternatives'] as $k => $label): ?>
                <?php if (!empty($meta[$k])): ?><h3><?= e($label) ?></h3><p><?= nl2br(e((string) $meta[$k])) ?></p><?php endif; ?>
            <?php endforeach; ?>
            <?php if (!empty($item['content'])): ?><h3>Decision</h3><p><?= nl2br(e($item['content'])) ?></p><?php endif; ?>
            <?php foreach (['reason' => 'Reason', 'consequences' => 'Consequences'] as $k => $label): ?>
                <?php if (!empty($meta[$k])): ?><h3><?= e($label) ?></h3><p><?= nl2br(e((string) $meta[$k])) ?></p><?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($item['stored_filename'])): ?>
            <?php
            $mime = (string) ($item['mime_type'] ?? '');
            $fileUrl = url('/items/' . $item['id'] . '/file');
            ?>
            <?php if (str_starts_with($mime, 'image/') || $item['type'] === 'image'): ?>
                <img class="preview" src="<?= e($fileUrl) ?>" alt="">
            <?php elseif (str_starts_with($mime, 'audio/') || $item['type'] === 'audio'): ?>
                <audio class="media-player" controls preload="metadata" src="<?= e($fileUrl) ?>">
                    Your browser does not support audio playback.
                </audio>
            <?php elseif (str_starts_with($mime, 'video/') || $item['type'] === 'video'): ?>
                <video class="media-player preview" controls preload="metadata" src="<?= e($fileUrl) ?>">
                    Your browser does not support video playback.
                </video>
            <?php endif; ?>
            <p><a class="btn btn-secondary" href="<?= e($fileUrl) ?>"><?= e($item['original_filename'] ?: 'Download file') ?></a></p>
            <p class="muted tiny"><?= e($mime) ?><?= !empty($item['file_size']) ? ' · ' . e(UploadService::formatSize((int) $item['file_size'])) : '' ?></p>
        <?php endif; ?>

        <?php if (!empty($meta['checklist'])): ?>
            <h3>Checklist</h3>
            <ul class="check-list">
                <?php foreach ($meta['checklist'] as $i => $row): ?>
                    <li class="<?= !empty($row['done']) ? 'is-done' : '' ?>">
                        <form method="post" action="<?= e(url('/items/' . $item['id'] . '/checklist/' . $i)) ?>">
                            <?= csrf_field() ?>
                            <button type="submit"><?= !empty($row['done']) ? '☑' : '☐' ?> <?= e($row['text'] ?? '') ?></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($children)): ?>
            <h3>Inside</h3>
            <ul class="item-list compact">
                <?php foreach ($children as $child): ?>
                    <li>
                        <a href="<?= e(url('/items/' . $child['id'])) ?>">
                            <span class="type-ico"><?= icon(item_type_icon($child['type'])) ?></span>
                            <strong><?= e($child['title']) ?></strong>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn-ghost" href="<?= e(url('/items/create?type=document&parent_id=' . $item['id'] . '&workspace_id=' . $wid)) ?>">+ Nested document</a>
        <?php endif; ?>
    </article>

    <aside class="stack">
        <section class="card">
            <h3>Place</h3>
            <?php if ($wid): ?>
                <p><a href="<?= e(url('/workspaces/' . $wid)) ?>"><?= e($item['workspace_name']) ?></a></p>
            <?php else: ?>
                <p class="muted">No workspace assigned.</p>
            <?php endif; ?>
            <?php if ($item['type'] === 'note'): ?>
                <form method="post" action="<?= e(url('/items/' . $item['id'] . '/convert')) ?>" class="stack">
                    <?= csrf_field() ?>
                    <label class="field">
                        <span>Convert to</span>
                        <select name="type">
                            <option value="document">Document</option>
                            <option value="task">Task</option>
                            <option value="decision">Decision</option>
                        </select>
                    </label>
                    <button class="btn btn-secondary" type="submit">Convert</button>
                </form>
            <?php endif; ?>
        </section>
        <section class="card">
            <?php if (empty($item['archived_at'])): ?>
                <form method="post" action="<?= e(url('/items/' . $item['id'] . '/archive')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-ghost" type="submit">Archive</button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e(url('/items/' . $item['id'] . '/restore')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-secondary" type="submit">Restore</button>
                </form>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/items/' . $item['id'] . '/delete')) ?>" onsubmit="return confirm('Remove this item?');">
                <?= csrf_field() ?>
                <button class="btn btn-danger" type="submit">Delete</button>
            </form>
        </section>
    </aside>
</div>
