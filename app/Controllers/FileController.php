<?php

declare(strict_types=1);

final class FileController
{
    public static function avatar(string $file): void
    {
        Auth::requireLogin();
        $name = basename($file);
        $me = UserService::find(Auth::id());
        if (!$me || ($me['avatar'] ?? '') !== $name) {
            http_response_code(404);
            exit;
        }
        self::send('avatars', $name);
    }

    public static function item(string $id): void
    {
        Auth::requireLogin();
        $item = ItemService::require((int) $id);
        if (empty($item['stored_filename'])) {
            http_response_code(404);
            echo 'File not found';
            exit;
        }
        self::send('files', (string) $item['stored_filename'], $item['original_filename'] ?? null, $item['mime_type'] ?? null);
    }

    private static function send(string $subdir, string $filename, ?string $downloadName = null, ?string $mime = null): void
    {
        $path = UploadService::absolute($subdir, $filename);
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }
        $mime = $mime ?: (mime_content_type($path) ?: 'application/octet-stream');
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        $disp = str_starts_with($mime, 'image/') || str_starts_with($mime, 'audio/') || str_starts_with($mime, 'video/') || $mime === 'application/pdf'
            ? 'inline'
            : 'attachment';
        $name = $downloadName ?: $filename;
        header('Content-Disposition: ' . $disp . '; filename="' . str_replace('"', '', $name) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }
}
