<div class="modal-root" id="command-palette" hidden>
    <div class="modal-card command-card">
        <form action="<?= e(url('/search')) ?>" method="get" class="command-form" data-search-json="<?= e(url('/search.json')) ?>">
            <span class="search-ico"><?= icon('search', 18) ?></span>
            <input type="search" name="q" id="command-input" placeholder="Search workspaces, notes, tasks…" autocomplete="off">
        </form>
        <div class="command-results" id="command-results"></div>
    </div>
</div>
