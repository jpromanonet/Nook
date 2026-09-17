<?php
/** @var string $templateFile */
/** @var string $appName */
/** @var string $appVersion */
/** @var string $title */
/** @var array|null $user */
$flashes = take_flashes();
$themePref = Auth::check() ? Auth::theme() : 'system';
$htmlTheme = $themePref === 'dark' ? 'dark' : 'light';
$workspacesNav = [];
if (Auth::check()) {
    UserService::refreshSession();
    $user = Auth::user();
    try {
        $workspacesNav = WorkspaceService::all();
    } catch (Throwable $e) {
        $workspacesNav = [];
    }
}
$userAvatar = Auth::check() ? avatar_url($user['avatar'] ?? null) : null;
$userName = Auth::check() ? (string) ($user['name'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?= e($htmlTheme) ?>" data-theme-pref="<?= e($themePref) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') !== '' ? $title . ' · ' . $appName : $appName) ?></title>
    <script>
    (function () {
      try {
        var root = document.documentElement;
        var pref = localStorage.getItem('nook-theme') || root.getAttribute('data-theme-pref') || 'system';
        var theme = pref;
        if (pref === 'system') {
          theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        if (theme !== 'dark' && theme !== 'light') theme = 'light';
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-theme-pref', pref);
      } catch (e) {}
    })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/css/app.css') ?>">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%2371806A' d='M6 3h12a2 2 0 0 1 2 2v16l-8-3-8 3V5a2 2 0 0 1 2-2z'/%3E%3C/svg%3E">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?= e(url('/home')) ?>">
            <span class="brand-mark"><?= icon('nook', 20) ?></span>
            <span>
                <strong>Nook</strong>
                <small>Everything has a place.</small>
            </span>
        </a>
        <nav class="sidebar-nav">
            <a class="<?= e(nav_active('/home', true) ?: nav_active('/', true)) ?>" href="<?= e(url('/home')) ?>"><?= icon('home') ?> Home</a>
            <a class="<?= e(nav_active('/today')) ?>" href="<?= e(url('/today')) ?>"><?= icon('today') ?> Today</a>
            <a class="<?= e(nav_active('/board')) ?>" href="<?= e(url('/board')) ?>"><?= icon('board') ?> Board</a>
            <a class="<?= e(nav_active('/calendar')) ?>" href="<?= e(url('/calendar')) ?>"><?= icon('calendar') ?> Calendar</a>
            <a class="<?= e(nav_active('/metrics')) ?>" href="<?= e(url('/metrics')) ?>"><?= icon('metrics') ?> Metrics</a>
            <a class="<?= e(nav_active('/search')) ?>" href="<?= e(url('/search')) ?>"><?= icon('search') ?> Search</a>
            <a class="<?= e(nav_active('/favorites')) ?>" href="<?= e(url('/favorites')) ?>"><?= icon('star') ?> Favorites</a>
        </nav>
        <div class="sidebar-section">
            <div class="sidebar-heading">
                <span>Workspaces</span>
                <a href="<?= e(url('/workspaces/create')) ?>" title="New workspace"><?= icon('plus', 14) ?></a>
            </div>
            <nav
                class="workspace-list"
                data-ws-sortable
                data-reorder-url="<?= e(url('/workspaces/reorder')) ?>"
                data-csrf="<?= e(csrf_token()) ?>"
            >
                <?php foreach ($workspacesNav as $ws): ?>
                    <div class="ws-row" draggable="true" data-id="<?= (int) $ws['id'] ?>">
                        <button type="button" class="ws-drag" aria-label="Drag to reorder" title="Drag">⋮⋮</button>
                        <a class="<?= e(nav_active('/workspaces/' . $ws['id'])) ?>" href="<?= e(url('/workspaces/' . $ws['id'])) ?>">
                            <span class="dot" style="background:<?= e($ws['color']) ?>"></span>
                            <span class="ws-name"><?= e($ws['name']) ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
                <?php if (!$workspacesNav): ?>
                    <p class="muted tiny">No workspaces yet.</p>
                <?php endif; ?>
            </nav>
        </div>
        <div class="sidebar-foot">
            <form method="post" action="<?= e(url('/logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger btn-block"><?= icon('logout', 14) ?> Sign out</button>
            </form>
        </div>
    </aside>
    <div class="sidebar-backdrop" aria-hidden="true"></div>

    <div class="main-col">
        <header class="topbar">
            <button type="button" class="icon-btn sidebar-toggle" id="sidebar-toggle" aria-label="Menu"><?= icon('menu', 18) ?></button>
            <form class="top-search" action="<?= e(url('/search')) ?>" method="get" data-command-search>
                <span class="search-ico"><?= icon('search', 16) ?></span>
                <input type="search" name="q" placeholder="Search everything…" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off" id="global-search">
                <kbd>Ctrl K</kbd>
            </form>
            <div class="topbar-actions">
                <button type="button" class="btn btn-primary" data-open-quickadd><?= icon('plus', 14) ?> Add</button>
                <button
                    type="button"
                    class="icon-btn theme-toggle"
                    data-theme-toggle
                    data-theme-url="<?= e(url('/settings/theme')) ?>"
                    data-csrf="<?= e(csrf_token()) ?>"
                    aria-label="Cambiar tema"
                    title="Cambiar tema"
                >
                    <span class="theme-icon theme-icon-sun" aria-hidden="true"><?= icon('sun', 18) ?></span>
                    <span class="theme-icon theme-icon-moon" aria-hidden="true"><?= icon('moon', 18) ?></span>
                </button>
                <a class="avatar-link" href="<?= e(url('/settings/profile')) ?>" title="<?= e($userName) ?>">
                    <span class="avatar">
                        <?php if ($userAvatar): ?>
                            <img src="<?= e($userAvatar) ?>" alt="">
                        <?php else: ?>
                            <?= e(user_initials($userName)) ?>
                        <?php endif; ?>
                    </span>
                </a>
            </div>
        </header>

        <main class="content">
            <?php if (show_back_button()): ?>
                <div class="page-back-row">
                    <a
                        class="btn btn-ghost page-back"
                        href="<?= e(back_fallback_url()) ?>"
                        data-back
                    ><?= icon('back', 16) ?> Back</a>
                </div>
            <?php endif; ?>
            <?php if ($flashes): ?>
                <div class="flash-stack">
                    <?php foreach ($flashes as $flash): ?>
                        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php require $templateFile; ?>
        </main>
    </div>
</div>

<nav class="mobile-nav">
    <a class="<?= e(nav_active('/home', true)) ?>" href="<?= e(url('/home')) ?>"><?= icon('home') ?><span>Home</span></a>
    <a class="<?= e(nav_active('/today')) ?>" href="<?= e(url('/today')) ?>"><?= icon('today') ?><span>Today</span></a>
    <a class="<?= e(nav_active('/board')) ?>" href="<?= e(url('/board')) ?>"><?= icon('board') ?><span>Board</span></a>
    <a href="<?= e(url('/search')) ?>"><?= icon('search') ?><span>Search</span></a>
    <button type="button" data-open-quickadd><?= icon('plus') ?><span>Add</span></button>
</nav>

<?php require dirname(__DIR__) . '/partials/quickadd.php'; ?>
<?php require dirname(__DIR__) . '/partials/command.php'; ?>
<script src="<?= e(url('/assets/js/app.js')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/js/app.js') ?>"></script>
</body>
</html>
