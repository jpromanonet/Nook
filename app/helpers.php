<?php

declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_path(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $appUrl = (string) app_config('url', '');
    if ($appUrl !== '') {
        $urlPath = parse_url($appUrl, PHP_URL_PATH);
        if (is_string($urlPath) && $urlPath !== '' && $urlPath !== '/') {
            $cached = rtrim($urlPath, '/');
            return $cached;
        }
        $cached = '';
        return $cached;
    }

    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === '/' || $script === '\\' || $script === '.') {
        $cached = '';
        return $cached;
    }
    $cached = rtrim($script, '/');
    return $cached;
}

function url(string $path = '/'): string
{
    $extraQuery = [];
    $hash = '';
    if (str_contains($path, '#')) {
        [$path, $hash] = explode('#', $path, 2);
        $hash = '#' . $hash;
    }
    if (str_contains($path, '?')) {
        [$path, $qs] = explode('?', $path, 2);
        parse_str($qs, $extraQuery);
    }

    $path = '/' . ltrim($path, '/');
    if ($path === '//') {
        $path = '/';
    }

    $base = base_path();

    if (
        str_starts_with($path, '/assets/')
        || preg_match('#^/[^/]+\.php$#', $path) === 1
    ) {
        $suffix = $extraQuery ? ('?' . http_build_query($extraQuery)) : '';
        return $base . $path . $suffix . $hash;
    }

    $script = $base . '/index.php';
    $params = $extraQuery;
    if ($path !== '/') {
        $params = array_merge(['r' => $path], $params);
    }
    $suffix = $params ? ('?' . http_build_query($params)) : '';
    return $script . $suffix . $hash;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function view(string $template, array $vars = [], ?string $layout = 'layouts/main'): void
{
    extract($vars, EXTR_SKIP);
    $appName = (string) app_config('name', 'Nook');
    $appVersion = (string) app_config('version', '0.1.0');
    $user = Auth::check() ? Auth::user() : null;
    $templateFile = dirname(__DIR__) . '/app/Views/' . $template . '.php';
    if (!is_file($templateFile)) {
        throw new RuntimeException('View not found: ' . $template);
    }
    if ($layout === null) {
        require $templateFile;
        return;
    }
    $layoutFile = dirname(__DIR__) . '/app/Views/' . $layout . '.php';
    if (!is_file($layoutFile)) {
        throw new RuntimeException('Layout not found: ' . $layout);
    }
    require $layoutFile;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function show_back_button(): bool
{
    $path = current_path();
    return !in_array($path, ['/', '/home'], true);
}

function back_fallback_url(): string
{
    $path = current_path();

    if (preg_match('#^/items/(\d+)/edit$#', $path, $m)) {
        return url('/items/' . $m[1]);
    }
    if (preg_match('#^/items/(\d+)$#', $path, $m)) {
        $item = ItemService::find((int) $m[1]);
        if ($item && !empty($item['workspace_id'])) {
            $type = (string) ($item['type'] ?? '');
            $module = match (true) {
                in_array($type, ['document', 'folder'], true) => 'documents',
                $type === 'file' => 'files',
                in_array($type, ['image', 'audio', 'video'], true) => 'media',
                $type === 'task' => 'tasks',
                in_array($type, ['note', 'idea'], true) => 'notes',
                $type === 'link' => 'links',
                $type === 'account' => 'accounts',
                default => '',
            };
            if ($module !== '') {
                return url('/workspaces/' . (int) $item['workspace_id'] . '/' . $module);
            }
            return url('/workspaces/' . (int) $item['workspace_id']);
        }
        return url('/home');
    }
    if (preg_match('#^/workspaces/(\d+)/(documents|tasks|notes|files|media|links|accounts|archive)$#', $path, $m)) {
        if (null_if_blank((string) input('folder', '')) !== null) {
            return url('/workspaces/' . $m[1] . '/' . $m[2]);
        }
        return url('/workspaces/' . $m[1]);
    }
    if (preg_match('#^/workspaces/(\d+)/edit$#', $path, $m)) {
        return url('/workspaces/' . $m[1]);
    }
    if (preg_match('#^/workspaces/(create)$#', $path) || $path === '/workspaces') {
        return url('/home');
    }
    if (str_starts_with($path, '/settings')) {
        return url('/home');
    }

    return url('/home');
}

function verify_csrf(?string $token = null): bool
{
    $token = $token ?? ($_POST['_csrf'] ?? null);
    return is_string($token)
        && isset($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

function require_csrf(): void
{
    $wantsJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json'));

    if (request_body_truncated()) {
        http_response_code(413);
        $max = format_ini_bytes(ini_get('post_max_size') ?: '8M');
        $msg = 'El archivo supera el límite del servidor (~' . $max . ').';
        if ($wantsJson) {
            json_response(['ok' => false, 'error' => $msg], 413);
        }
        flash('error', $msg);
        redirect(Auth::check() ? '/home' : '/login');
    }
    $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!verify_csrf(is_string($token) ? $token : null)) {
        http_response_code(419);
        $msg = 'Sesión desfasada. Recargá e intentá de nuevo.';
        if ($wantsJson) {
            json_response(['ok' => false, 'error' => $msg], 419);
        }
        flash('error', $msg);
        redirect(Auth::check() ? '/home' : '/login');
    }
}

function request_body_truncated(): bool
{
    $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($length <= 0) {
        return false;
    }
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method !== 'POST' && $method !== 'PUT') {
        return false;
    }
    $postMax = ini_bytes((string) (ini_get('post_max_size') ?: '8M'));
    return $length > $postMax && empty($_POST) && empty($_FILES);
}

function ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    if (function_exists('ini_parse_quantity')) {
        return (int) ini_parse_quantity($value);
    }
    if (!preg_match('/^(\d+)([KMG])?$/i', $value, $m)) {
        return (int) $value;
    }
    $n = (int) $m[1];
    return match (strtoupper($m[2] ?? '')) {
        'G' => $n * 1024 * 1024 * 1024,
        'M' => $n * 1024 * 1024,
        'K' => $n * 1024,
        default => $n,
    };
}

function format_ini_bytes(string $value): string
{
    $bytes = ini_bytes($value);
    if ($bytes >= 1024 * 1024) {
        return (int) round($bytes / (1024 * 1024)) . ' MB';
    }
    if ($bytes >= 1024) {
        return (int) round($bytes / 1024) . ' KB';
    }
    return $bytes . ' B';
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function take_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($flashes) ? $flashes : [];
}

function current_path(): string
{
    if (class_exists('Router') && Router::$current !== '') {
        return Router::$current;
    }
    $current = $_GET['r'] ?? '/';
    if ($current === '' || $current === false) {
        $current = '/';
    }
    $current = '/' . trim((string) $current, '/');
    return $current === '//' ? '/' : $current;
}

function nav_active(string $prefix, bool $exact = false): string
{
    $current = current_path();
    if ($exact) {
        return $current === $prefix ? 'is-active' : '';
    }
    if ($prefix === '/') {
        return $current === '/' ? 'is-active' : '';
    }
    return str_starts_with($current, $prefix) ? 'is-active' : '';
}

function input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function null_if_blank(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

function truncate(?string $text, int $len = 140): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $len) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $len - 1)) . '…';
}

function format_dt(?string $dt): string
{
    if ($dt === null || $dt === '') {
        return '—';
    }
    $ts = strtotime($dt);
    if ($ts === false) {
        return $dt;
    }
    return date('d M Y H:i', $ts);
}

function format_date(?string $dt): string
{
    if ($dt === null || $dt === '') {
        return '—';
    }
    $ts = strtotime($dt);
    if ($ts === false) {
        return $dt;
    }
    return date('d M Y', $ts);
}

function format_day_long(?string $dt = null): string
{
    $ts = $dt ? strtotime($dt) : time();
    if ($ts === false) {
        return '';
    }
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    return $days[(int) date('w', $ts)] . ' · ' . $months[(int) date('n', $ts) - 1] . ' ' . date('j', $ts);
}

function greeting_for_hour(?int $hour = null): string
{
    $hour = $hour ?? (int) date('G');
    if ($hour < 12) {
        return 'Good morning';
    }
    if ($hour < 18) {
        return 'Good afternoon';
    }
    return 'Good evening';
}

function avatar_url(?string $avatar): ?string
{
    if ($avatar === null || $avatar === '') {
        return null;
    }
    return url('/avatar/' . rawurlencode(basename($avatar)));
}

function user_initials(?string $name): string
{
    $name = trim((string) $name);
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $initials !== '' ? $initials : '?';
}

function first_name(?string $name): string
{
    $name = trim((string) $name);
    if ($name === '') {
        return '';
    }
    return explode(' ', $name)[0];
}

function workspace_types(): array
{
    return [
        'company' => 'Company',
        'employment' => 'Employment',
        'startup' => 'Startup',
        'product' => 'Product',
        'project' => 'Project',
        'podcast' => 'Podcast',
        'book' => 'Book',
        'content' => 'Content',
        'research' => 'Research',
        'personal' => 'Personal',
        'other' => 'Other',
    ];
}

function workspace_statuses(): array
{
    return [
        'active' => 'Active',
        'paused' => 'Paused',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ];
}

function workspace_colors(): array
{
    return [
        '#71806A' => 'Moss',
        '#C67D67' => 'Terracotta',
        '#D5B66A' => 'Mustard',
        '#788FA0' => 'Dusty blue',
        '#887286' => 'Plum',
        '#30332F' => 'Ink',
    ];
}

function item_types(): array
{
    return [
        'document' => 'Document',
        'folder' => 'Folder',
        'note' => 'Note',
        'task' => 'Task',
        'idea' => 'Idea',
        'file' => 'File',
        'image' => 'Image',
        'audio' => 'Audio',
        'video' => 'Video',
        'link' => 'Link',
        'account' => 'Account',
        'event' => 'Event',
        'decision' => 'Decision',
    ];
}

function item_type_label(string $type): string
{
    return item_types()[$type] ?? $type;
}

function item_type_icon(string $type): string
{
    return match ($type) {
        'document' => 'doc',
        'folder' => 'folder',
        'note' => 'note',
        'task' => 'task',
        'idea' => 'idea',
        'file' => 'file',
        'image' => 'image',
        'audio' => 'audio',
        'video' => 'video',
        'link' => 'link',
        'account' => 'account',
        'event' => 'event',
        'decision' => 'decision',
        default => 'item',
    };
}

function task_statuses(): array
{
    return [
        'todo' => 'To do',
        'doing' => 'Doing',
        'blocked' => 'Blocked',
        'done' => 'Done',
        'cancelled' => 'Cancelled',
    ];
}

function priorities(): array
{
    return [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];
}

function link_categories(): array
{
    return [
        'website' => 'Website',
        'social' => 'Social',
        'infrastructure' => 'Infrastructure',
        'analytics' => 'Analytics',
        'repository' => 'Repository',
        'design' => 'Design',
        'documentation' => 'Documentation',
        'administration' => 'Administration',
        'research' => 'Research',
        'other' => 'Other',
    ];
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'active', 'done', 'completed' => 'badge-moss',
        'doing', 'todo' => 'badge-mustard',
        'paused', 'blocked' => 'badge-blue',
        'cancelled', 'archived' => 'badge-ink',
        'urgent', 'high' => 'badge-terra',
        default => 'badge-ink',
    };
}

function item_meta(array $item): array
{
    $raw = $item['meta'] ?? null;
    if (is_array($raw)) {
        return $raw;
    }
    if (!is_string($raw) || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function tags_to_string(array $tags): string
{
    $names = [];
    foreach ($tags as $tag) {
        if (is_array($tag)) {
            $names[] = (string) ($tag['name'] ?? '');
        } else {
            $names[] = (string) $tag;
        }
    }
    return implode(', ', array_filter($names));
}

function parse_tags_input(string $raw): array
{
    $parts = preg_split('/[,]+/', $raw) ?: [];
    $out = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $out[] = $part;
        }
    }
    return array_values(array_unique($out));
}

function storage_path(string $sub = ''): string
{
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
    return $sub === '' ? $base : $base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sub);
}

function icon(string $name, int $size = 16): string
{
    $icons = [
        'nook' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H18a2 2 0 0 1 2 2v14a1 1 0 0 1-1.4.9L12 17.2 5.4 19.9A1 1 0 0 1 4 19V5.5Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M9 8h6M9 11.5h4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'home' => '<path d="M4 11.5 12 4l8 7.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-8.5Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'today' => '<rect x="4" y="5" width="16" height="15" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 3.5v3M16 3.5v3M4 9h16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'inbox' => '<path d="M4 13 6.2 5.8A2 2 0 0 1 8.1 4.5h7.8a2 2 0 0 1 1.9 1.3L20 13v6a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 19v-6Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M4 13h4.2a2 2 0 0 0 1.8 1.1h4a2 2 0 0 0 1.8-1.1H20" fill="none" stroke="currentColor" stroke-width="1.6"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 3.5v3M16 3.5v3M4 9.5h16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'search' => '<circle cx="11" cy="11" r="6" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="m16 16 4 4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'star' => '<path d="m12 3.5 2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 16.3 7.2 18.4l.9-5.4L4.2 9.2l5.4-.8L12 3.5Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'settings' => '<circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 3.5v2.2M12 18.3v2.2M4.8 6.5l1.6 1.6M17.6 16l1.6 1.6M3.5 12h2.2M18.3 12h2.2M4.8 17.5l1.6-1.6M17.6 8.1l1.6-1.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'plus' => '<path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'workspace' => '<rect x="4" y="4" width="7" height="7" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="4" width="7" height="7" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.6"/><rect x="4" y="13" width="7" height="7" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="13" width="7" height="7" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.6"/>',
        'doc' => '<path d="M7 4.5h7l5 5V19a1.5 1.5 0 0 1-1.5 1.5H7A1.5 1.5 0 0 1 5.5 19V6A1.5 1.5 0 0 1 7 4.5Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M14 4.5V10h5.5M8.5 13h7M8.5 16.5h5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'folder' => '<path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h4l2 2h7A1.5 1.5 0 0 1 20 10.5v7A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5v-9Z" fill="none" stroke="currentColor" stroke-width="1.6"/>',
        'note' => '<path d="M7 4h8.5L19 7.5V19a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M15.5 4v4H19M8.5 12h7M8.5 15.5h5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'task' => '<rect x="4.5" y="4.5" width="15" height="15" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="m8 12 2.6 2.6L16 9" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
        'idea' => '<path d="M12 3.5a6 6 0 0 1 3.5 10.8V16a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-1.7A6 6 0 0 1 12 3.5Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10 20h4M11 17.5v2.5M13 17.5v2.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'file' => '<path d="M7 3.5h7l5 5V20A1.5 1.5 0 0 1 17.5 21.5h-10A1.5 1.5 0 0 1 6 20V5A1.5 1.5 0 0 1 7 3.5Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M14 3.5V9h5.2" fill="none" stroke="currentColor" stroke-width="1.5"/>',
        'image' => '<rect x="4" y="5" width="16" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="9" cy="10" r="1.4" fill="currentColor"/><path d="m5.5 16.5 4-4 3 3 2.2-2.2 3.8 3.2" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'audio' => '<path d="M9 18V6l10-2v12" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="7" cy="18" r="2.5" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="17" cy="16" r="2.5" fill="none" stroke="currentColor" stroke-width="1.6"/>',
        'video' => '<rect x="3.5" y="6" width="13" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="m16.5 10.5 4-2.5v8l-4-2.5v-3Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'link' => '<path d="M10 13.5a4 4 0 0 0 6 0l2-2a4 4 0 1 0-5.6-5.6l-1.1 1.1" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M14 10.5a4 4 0 0 0-6 0l-2 2a4 4 0 1 0 5.6 5.6l1.1-1.1" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'account' => '<circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M5.5 19.2a6.5 6.5 0 0 1 13 0" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'event' => '<rect x="4" y="5" width="16" height="15" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 3.5v3M16 3.5v3M4 9.5h16M12 13v4M10 15h4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'decision' => '<path d="m12 3.5 8 4.5v8l-8 4.5-8-4.5v-8l8-4.5Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v5M12 16h.01" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        'item' => '<rect x="5" y="5" width="14" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/>',
        'pin' => '<path d="M15 4.5 19.5 9l-6 1.5-5 5-1.5-1.5 5-5L15 4.5Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m8.5 15.5-4 4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'menu' => '<path d="M4.5 7h15M4.5 12h15M4.5 17h15" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        'logout' => '<path d="M10 5.5H7A1.5 1.5 0 0 0 5.5 7v10A1.5 1.5 0 0 0 7 18.5h3M13 8l4 4-4 4M10 12h7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'sun' => '<circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M12 2v2.2M12 19.8V22M4.93 4.93l1.56 1.56M17.51 17.51l1.56 1.56M2 12h2.2M19.8 12H22M4.93 19.07l1.56-1.56M17.51 6.49l1.56-1.56" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>',
        'moon' => '<path d="M20.2 14.2A7.8 7.8 0 0 1 9.8 3.8 8.2 8.2 0 1 0 20.2 14.2Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>',
        'close' => '<path d="m7 7 10 10M17 7 7 17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        'archive' => '<path d="M4.5 7.5h15v3h-15zM6.5 10.5v8h11v-8M10 14h4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'more' => '<circle cx="6" cy="12" r="1.4" fill="currentColor"/><circle cx="12" cy="12" r="1.4" fill="currentColor"/><circle cx="18" cy="12" r="1.4" fill="currentColor"/>',
        'check' => '<path d="m5.5 12.5 4 4 9-9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'chevron' => '<path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
        'back' => '<path d="M15 6 9 12l6 6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12h10" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        'board' => '<path d="M5 4.5h4.5v15H5zM10.75 4.5h4.5v10h-4.5zM16.5 4.5H21v7h-4.5z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'metrics' => '<path d="M4.5 18.5V14M9.5 18.5V8M14.5 18.5v-6M19.5 18.5V5.5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M4 19.5h16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
    ];
    $body = $icons[$name] ?? $icons['item'];
    return '<svg class="nk-icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true">' . $body . '</svg>';
}

function relative_day(?string $dt): string
{
    if ($dt === null || $dt === '') {
        return '';
    }
    $ts = strtotime($dt);
    if ($ts === false) {
        return '';
    }
    $today = strtotime(date('Y-m-d'));
    $that = strtotime(date('Y-m-d', $ts));
    $diff = (int) (($that - $today) / 86400);
    if ($diff === 0) {
        return 'Today';
    }
    if ($diff === 1) {
        return 'Tomorrow';
    }
    if ($diff === -1) {
        return 'Yesterday';
    }
    if ($diff < -1 && $diff > -7) {
        return abs($diff) . ' days ago';
    }
    return format_date($dt);
}

function is_overdue(?string $date, ?string $status = null): bool
{
    if ($date === null || $date === '') {
        return false;
    }
    if (in_array($status, ['done', 'cancelled', 'archived'], true)) {
        return false;
    }
    return $date < date('Y-m-d');
}
