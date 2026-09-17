<?php

declare(strict_types=1);

final class MailService
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $from = (string) app_config('mail_from', 'nook@localhost');
        $fromName = (string) app_config('mail_from_name', 'Nook');
        $headers = [
            'From: ' . $fromName . ' <' . $from . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $logDir = storage_path('mail');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $log = $logDir . DIRECTORY_SEPARATOR . 'outbox.log';
        $entry = sprintf(
            "[%s]\nTo: %s\nSubject: %s\n%s\n\n",
            date('c'),
            $to,
            $subject,
            $body
        );
        @file_put_contents($log, $entry, FILE_APPEND);

        $ok = false;
        try {
            $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
        } catch (Throwable $e) {
            $ok = false;
        }
        return (bool) $ok;
    }
}
