<?php

declare(strict_types=1);

require_once __DIR__ . '/config/env.php';

header('Content-Type: text/html; charset=utf-8');

$host = nook_env('DB_HOST', '127.0.0.1');
$port = (int) nook_env('DB_PORT', '3306');
$name = nook_env('DB_NAME', 'nook');
$user = nook_env('DB_USER', 'root');
$pass = nook_env('DB_PASS', '') ?? '';
$sqlFile = __DIR__ . '/sql/schema.sql';

$ok = false;
$message = null;

try {
    if (!is_file($sqlFile)) {
        throw new RuntimeException('No está sql/schema.sql');
    }
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $safeName = str_replace('`', '', (string) $name);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$safeName}`");

    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if (!$tables) {
        $sql = (string) file_get_contents($sqlFile);
        foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            if (preg_match('/^(SET|CREATE DATABASE|USE)\b/i', $stmt)) {
                continue;
            }
            $pdo->exec($stmt);
        }
        $message = 'Base creada e importada.';
    } else {
        $message = 'La base ya existía.';
    }
    $ok = true;
} catch (Throwable $e) {
    $ok = false;
    $message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Instalar Nook</title>
    <style>
        body { font-family: "IBM Plex Sans", system-ui, sans-serif; background:#F3EDE2; color:#30332F; max-width:42rem; margin:3rem auto; padding:1rem; }
        .card { background:#FFF9F0; border:1px solid #d8d0c3; border-radius:10px; padding:1.5rem; }
        .ok { color:#71806A; } .err { color:#C67D67; }
        a.btn { background:#71806A; color:#FFF9F0; padding:.6rem 1rem; border-radius:8px; text-decoration:none; display:inline-block; }
    </style>
</head>
<body>
<div class="card">
    <h1>Nook</h1>
    <?php if ($ok): ?>
        <p class="ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
        <p><a class="btn" href="index.php">Entrar</a></p>
    <?php else: ?>
        <p class="err"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
        <p>Revisá <code>.env</code>.</p>
    <?php endif; ?>
</div>
</body>
</html>
