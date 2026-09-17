<?php
/** @var array|null $item */
/** @var string $type */
/** @var mixed $workspaceId */
/** @var mixed $parentId */
/** @var array $workspaces */
$it = $item ?? [];
$meta = $item ? item_meta($item) : [];
$action = $item ? url('/items/' . $item['id']) : url('/items');
$checklist = '';
foreach ($meta['checklist'] ?? [] as $row) {
    $checklist .= (!empty($row['done']) ? '[x] ' : '') . ($row['text'] ?? '') . "\n";
}
?>
<div class="page-head">
    <div>
        <h1><?= $item ? 'Edit' : 'New' ?> <?= e(strtolower(item_type_label($type))) ?></h1>
    </div>
</div>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="card stack form-wide">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= e($type) ?>">
    <?php if (!empty($parentId)): ?>
        <input type="hidden" name="parent_id" value="<?= e((string) $parentId) ?>">
    <?php endif; ?>

    <label class="field">
        <span>Title</span>
        <input type="text" name="title" value="<?= e($it['title'] ?? '') ?>" required>
    </label>

    <div class="form-grid">
        <label class="field">
            <span>Workspace</span>
            <select name="workspace_id">
                <option value="">Inbox</option>
                <?php foreach ($workspaces as $ws): ?>
                    <option value="<?= (int) $ws['id'] ?>" <?= (string) ($workspaceId ?? '') === (string) $ws['id'] ? 'selected' : '' ?>><?= e($ws['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if ($type === 'task'): ?>
            <label class="field">
                <span>Status</span>
                <select name="status">
                    <?php foreach (task_statuses() as $k => $label): ?>
                        <option value="<?= e($k) ?>" <?= ($it['status'] ?? 'todo') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Priority</span>
                <select name="priority">
                    <?php foreach (priorities() as $k => $label): ?>
                        <option value="<?= e($k) ?>" <?= ($it['priority'] ?? 'normal') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label class="field"><span>Start date</span><input type="date" name="start_date" value="<?= e($it['start_date'] ?? '') ?>"></label>
        <label class="field"><span>Due date</span><input type="date" name="due_date" value="<?= e($it['due_date'] ?? '') ?>"></label>
    </div>

    <?php if (!in_array($type, ['file', 'image', 'audio', 'video', 'folder'], true)): ?>
        <label class="field">
            <span>Description</span>
            <textarea name="description" rows="2"><?= e($it['description'] ?? '') ?></textarea>
        </label>
    <?php endif; ?>

    <?php if (in_array($type, ['document', 'note', 'idea', 'task'], true)): ?>
        <label class="field">
            <span><?= $type === 'document' ? 'Content (Markdown)' : 'Notes' ?></span>
            <textarea name="content" rows="<?= $type === 'document' ? 16 : 8 ?>"><?= e($it['content'] ?? '') ?></textarea>
        </label>
    <?php endif; ?>

    <?php if (in_array($type, ['task', 'document'], true)): ?>
        <label class="field">
            <span>Checklist (one per line, use [x] for done)</span>
            <textarea name="checklist_text" rows="6"><?= e(trim($checklist)) ?></textarea>
        </label>
    <?php endif; ?>

    <?php if ($type === 'link'): ?>
        <label class="field"><span>URL</span><input type="url" name="url" value="<?= e($meta['url'] ?? '') ?>" required></label>
        <label class="field">
            <span>Category</span>
            <select name="category">
                <?php foreach (link_categories() as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= ($meta['category'] ?? 'other') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>

    <?php if ($type === 'account'): ?>
        <p class="muted">Nook stores a reference, not the password. Keep secrets in Bitwarden or your vault.</p>
        <div class="form-grid">
            <label class="field"><span>Platform</span><input type="text" name="platform" value="<?= e($meta['platform'] ?? '') ?>"></label>
            <label class="field"><span>Username</span><input type="text" name="username" value="<?= e($meta['username'] ?? '') ?>"></label>
            <label class="field"><span>Handle</span><input type="text" name="handle" value="<?= e($meta['handle'] ?? '') ?>"></label>
            <label class="field"><span>Associated email</span><input type="email" name="account_email" value="<?= e($meta['account_email'] ?? '') ?>"></label>
            <label class="field"><span>URL</span><input type="url" name="url" value="<?= e($meta['url'] ?? '') ?>"></label>
            <label class="field"><span>Owner</span><input type="text" name="owner" value="<?= e($meta['owner'] ?? '') ?>"></label>
            <label class="field"><span>Recovery email</span><input type="email" name="recovery_email" value="<?= e($meta['recovery_email'] ?? '') ?>"></label>
            <label class="field"><span>Vault</span><input type="text" name="vault" value="<?= e($meta['vault'] ?? '') ?>" placeholder="Personal"></label>
            <label class="field"><span>Vault entry</span><input type="text" name="vault_entry" value="<?= e($meta['vault_entry'] ?? '') ?>"></label>
        </div>
        <label class="check"><input type="checkbox" name="two_fa" value="1" <?= !empty($meta['two_fa']) ? 'checked' : '' ?>> 2FA enabled</label>
        <label class="field"><span>Notes</span><textarea name="description" rows="3"><?= e($it['description'] ?? '') ?></textarea></label>
    <?php endif; ?>

    <?php if ($type === 'event'): ?>
        <label class="field"><span>Location</span><input type="text" name="location" value="<?= e($meta['location'] ?? '') ?>"></label>
        <label class="check"><input type="checkbox" name="all_day" value="1" <?= !empty($meta['all_day']) ? 'checked' : '' ?>> All day</label>
        <label class="field"><span>Notes</span><textarea name="content" rows="4"><?= e($it['content'] ?? '') ?></textarea></label>
    <?php endif; ?>

    <?php if ($type === 'decision'): ?>
        <label class="field"><span>Context</span><textarea name="context" rows="3"><?= e($meta['context'] ?? '') ?></textarea></label>
        <label class="field"><span>Alternatives</span><textarea name="alternatives" rows="3"><?= e($meta['alternatives'] ?? '') ?></textarea></label>
        <label class="field"><span>Decision</span><textarea name="content" rows="3"><?= e($it['content'] ?? '') ?></textarea></label>
        <label class="field"><span>Reason</span><textarea name="reason" rows="3"><?= e($meta['reason'] ?? '') ?></textarea></label>
        <label class="field"><span>Consequences</span><textarea name="consequences" rows="3"><?= e($meta['consequences'] ?? '') ?></textarea></label>
    <?php endif; ?>

    <?php if (in_array($type, ['file', 'image', 'audio', 'video'], true)): ?>
        <?php
        $accept = match ($type) {
            'image' => 'image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif',
            'audio' => 'audio/*,.mp3,.wav,.ogg,.m4a,.aac,.flac',
            'video' => 'video/*,.mp4,.webm,.mov,.m4v,.mkv,.avi',
            default => '',
        };
        $hint = match ($type) {
            'image' => 'JPG, PNG, WEBP or GIF.',
            'audio' => 'MP3, WAV, OGG, M4A, AAC or FLAC. Max 200 MB.',
            'video' => 'MP4, WebM, MOV, MKV or AVI. Max 200 MB.',
            default => 'Documents, images, audio or video. Max 200 MB.',
        };
        ?>
        <label class="field">
            <span><?= $item ? 'Replace file' : 'File' ?></span>
            <input type="file" name="upload" <?= $accept !== '' ? 'accept="' . e($accept) . '"' : '' ?> <?= $item ? '' : 'required' ?>>
        </label>
        <p class="muted tiny"><?= e($hint) ?></p>
        <label class="field"><span>Description</span><textarea name="description" rows="2"><?= e($it['description'] ?? '') ?></textarea></label>
    <?php endif; ?>

    <label class="field">
        <span>Tags</span>
        <input type="text" name="tags" value="<?= e(tags_to_string($it['tags'] ?? [])) ?>" placeholder="research, writing">
    </label>

    <div class="row-actions">
        <label class="check"><input type="checkbox" name="is_favorite" value="1" <?= !empty($it['is_favorite']) ? 'checked' : '' ?>> Favorite</label>
        <label class="check"><input type="checkbox" name="is_pinned" value="1" <?= !empty($it['is_pinned']) ? 'checked' : '' ?>> Pin</label>
    </div>
    <button class="btn btn-primary" type="submit">Save</button>
</form>
