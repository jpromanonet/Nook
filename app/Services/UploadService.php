<?php

declare(strict_types=1);

final class UploadService
{
    public static function detectMime(string $tmp, ?string $originalName = null): string
    {
        $mime = '';
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) ($finfo->file($tmp) ?: '');
        }
        if ($mime === '' && function_exists('mime_content_type')) {
            $mime = (string) (mime_content_type($tmp) ?: '');
        }
        if (str_starts_with($mime, 'image/')) {
            $info = @getimagesize($tmp);
            if (is_array($info) && !empty($info['mime'])) {
                $mime = (string) $info['mime'];
            }
        }

        // Some browsers/servers report media as octet-stream; fall back to extension.
        if (
            $originalName
            && ($mime === '' || $mime === 'application/octet-stream' || $mime === 'inode/x-empty')
        ) {
            $fromExt = self::mimeFromExtension($originalName);
            if ($fromExt !== null) {
                $mime = $fromExt;
            }
        }

        return $mime;
    }

    public static function mimeFromExtension(string $filename): ?string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'json' => 'application/json',
            'zip' => 'application/zip',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'oga' => 'audio/ogg',
            'm4a' => 'audio/mp4',
            'aac' => 'audio/aac',
            'flac' => 'audio/flac',
            'wma' => 'audio/x-ms-wma',
            'mp4' => 'video/mp4',
            'm4v' => 'video/x-m4v',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'mkv' => 'video/x-matroska',
            'avi' => 'video/x-msvideo',
            'ogv' => 'video/ogg',
        ];
        return $map[$ext] ?? null;
    }

    public static function avatarMap(): array
    {
        return [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
    }

    public static function fileMap(): array
    {
        return [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/csv' => 'csv',
            'text/plain' => 'txt',
            'text/markdown' => 'md',
            'application/json' => 'json',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            'audio/mpeg' => 'mp3',
            'audio/mp3' => 'mp3',
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
            'audio/wave' => 'wav',
            'audio/ogg' => 'ogg',
            'audio/mp4' => 'm4a',
            'audio/x-m4a' => 'm4a',
            'audio/aac' => 'aac',
            'audio/flac' => 'flac',
            'audio/x-flac' => 'flac',
            'audio/x-ms-wma' => 'wma',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            'video/x-m4v' => 'm4v',
            'video/x-matroska' => 'mkv',
            'video/x-msvideo' => 'avi',
            'video/avi' => 'avi',
            'video/ogg' => 'ogv',
        ];
    }

    public static function isImageMime(string $mime): bool
    {
        return str_starts_with($mime, 'image/');
    }

    public static function isAudioMime(string $mime): bool
    {
        return str_starts_with($mime, 'audio/');
    }

    public static function isVideoMime(string $mime): bool
    {
        return str_starts_with($mime, 'video/');
    }

    public static function isMediaMime(string $mime): bool
    {
        return self::isImageMime($mime)
            || self::isAudioMime($mime)
            || self::isVideoMime($mime);
    }

    public static function itemTypeForMime(string $mime): ?string
    {
        if (self::isImageMime($mime)) {
            return 'image';
        }
        if (self::isAudioMime($mime)) {
            return 'audio';
        }
        if (self::isVideoMime($mime)) {
            return 'video';
        }
        return null;
    }

    public static function store(array $file, string $subdir, array $allowed, int $maxBytes): array
    {
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('Elegí un archivo.');
        }
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new InvalidArgumentException('El archivo supera el límite permitido.');
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('No se pudo subir el archivo.');
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            $mb = max(1, (int) round($maxBytes / (1024 * 1024)));
            throw new InvalidArgumentException('El archivo es demasiado grande (máx. ' . $mb . ' MB).');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('Archivo temporal inválido.');
        }

        $original = basename((string) ($file['name'] ?? 'upload'));
        $mime = self::detectMime($tmp, $original);
        if (!isset($allowed[$mime])) {
            throw new InvalidArgumentException('Tipo de archivo no permitido.');
        }

        if (self::isImageMime($mime) && $mime !== 'image/svg+xml') {
            $info = @getimagesize($tmp);
            if (!is_array($info)) {
                throw new InvalidArgumentException('La imagen no es válida.');
            }
            $w = (int) ($info[0] ?? 0);
            $h = (int) ($info[1] ?? 0);
            if ($w < 16 || $h < 16 || $w > 8000 || $h > 8000) {
                throw new InvalidArgumentException('Dimensiones de imagen no razonables.');
            }
        }

        $dir = storage_path($subdir);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo crear el directorio de almacenamiento.');
        }

        $ext = $allowed[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;

        $moved = @move_uploaded_file($tmp, $dest);
        if (!$moved) {
            $moved = @copy($tmp, $dest);
            if ($moved) {
                @unlink($tmp);
            }
        }
        if (!$moved || !is_file($dest)) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }
        @chmod($dest, 0644);

        return [
            'stored' => $filename,
            'original' => $original,
            'mime' => $mime,
            'size' => (int) ($file['size'] ?? filesize($dest)),
        ];
    }

    public static function delete(?string $subdir, ?string $filename): void
    {
        if ($subdir === null || $filename === null || $filename === '') {
            return;
        }
        $path = storage_path($subdir) . DIRECTORY_SEPARATOR . basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function absolute(string $subdir, string $filename): string
    {
        return storage_path($subdir) . DIRECTORY_SEPARATOR . basename($filename);
    }

    public static function formatSize(?int $bytes): string
    {
        if ($bytes === null || $bytes <= 0) {
            return '';
        }
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return number_format($bytes / (1024 * 1024), 1) . ' MB';
    }
}
