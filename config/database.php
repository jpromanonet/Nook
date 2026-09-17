<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'host' => nook_env('DB_HOST', '127.0.0.1') ?? '127.0.0.1',
    'port' => (int) (nook_env('DB_PORT', '3306') ?? '3306'),
    'name' => nook_env('DB_NAME', 'nook') ?? 'nook',
    'user' => nook_env('DB_USER', 'root') ?? 'root',
    'pass' => nook_env('DB_PASS', '') ?? '',
    'charset' => 'utf8mb4',
];
