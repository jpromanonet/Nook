<div class="modal-root" id="quickadd" hidden>
    <div class="modal-card">
        <div class="modal-head">
            <h2>Add</h2>
            <button type="button" class="icon-btn" data-close-modal><?= icon('close') ?></button>
        </div>
        <div class="quick-grid">
            <?php
            $quick = [
                ['task', 'Task'],
                ['note', 'Note'],
                ['document', 'Document'],
                ['idea', 'Idea'],
                ['file', 'File'],
                ['image', 'Image'],
                ['audio', 'Audio'],
                ['video', 'Video'],
                ['link', 'Link'],
                ['event', 'Event'],
                ['decision', 'Decision'],
            ];
            foreach ($quick as [$type, $label]):
            ?>
                <a class="quick-tile" href="<?= e(url('/items/create?type=' . $type)) ?>">
                    <?= icon(item_type_icon($type)) ?>
                    <span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
            <a class="quick-tile" href="<?= e(url('/workspaces/create')) ?>">
                <?= icon('workspace') ?>
                <span>Workspace</span>
            </a>
        </div>
        <p class="muted tiny">Shortcut <kbd>N</kbd> or <kbd>Ctrl N</kbd></p>
    </div>
</div>
