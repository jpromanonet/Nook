<?php

declare(strict_types=1);

final class UploadController
{
    public static function chunk(): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            $uploadId = (string) ($_POST['upload_id'] ?? '');
            $index = (int) ($_POST['index'] ?? -1);
            $total = (int) ($_POST['total'] ?? 0);
            $original = (string) ($_POST['filename'] ?? 'upload.bin');
            $chunk = $_FILES['chunk'] ?? null;
            if (!is_array($chunk)) {
                throw new InvalidArgumentException('Falta el fragmento.');
            }
            $result = UploadService::storeChunk($uploadId, $index, $total, $chunk, $original);
            json_response(['ok' => true] + $result);
        } catch (Throwable $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
