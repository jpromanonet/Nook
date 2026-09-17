<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'name' => nook_env('APP_NAME', 'Nook') ?? 'Nook',
    'env' => nook_env('APP_ENV', 'local') ?? 'local',
    'debug' => filter_var(nook_env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'url' => nook_env('APP_URL', '') ?? '',
    'timezone' => 'America/Argentina/Buenos_Aires',
    'session_name' => 'nook_session',
    'session_lifetime' => 86400,
    'session_remember' => 2592000,
    'session_idle' => 86400,
    'version' => '0.1.0',
    'mail_from' => nook_env('MAIL_FROM', 'nook@localhost') ?? 'nook@localhost',
    'mail_from_name' => nook_env('MAIL_FROM_NAME', 'Nook') ?? 'Nook',
    'min_password' => 8,
    'max_upload' => 200 * 1024 * 1024,
    'max_avatar' => 2 * 1024 * 1024,
];
