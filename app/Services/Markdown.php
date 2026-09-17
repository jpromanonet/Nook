<?php

declare(strict_types=1);

final class Markdown
{
    public static function render(?string $text): string
    {
        $text = (string) $text;
        if ($text === '') {
            return '';
        }

        $placeholders = [];
        $text = preg_replace_callback('/```([\s\S]*?)```/', static function ($m) use (&$placeholders) {
            $key = '%%CODE' . count($placeholders) . '%%';
            $placeholders[$key] = '<pre class="md-pre"><code>' . e(trim($m[1])) . '</code></pre>';
            return $key;
        }, $text) ?? $text;

        $text = e($text);
        $text = preg_replace('/^###### (.+)$/m', 'h6::$1', $text) ?? $text;
        $text = preg_replace('/^##### (.+)$/m', 'h5::$1', $text) ?? $text;
        $text = preg_replace('/^#### (.+)$/m', 'h4::$1', $text) ?? $text;
        $text = preg_replace('/^### (.+)$/m', 'h3::$1', $text) ?? $text;
        $text = preg_replace('/^## (.+)$/m', 'h2::$1', $text) ?? $text;
        $text = preg_replace('/^# (.+)$/m', 'h1::$1', $text) ?? $text;
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text) ?? $text;
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;
        $text = preg_replace('/\[(.+?)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" rel="noopener noreferrer" target="_blank">$1</a>', $text) ?? $text;
        $text = preg_replace('/^- \[x\] (.+)$/mi', 'li-check::$1', $text) ?? $text;
        $text = preg_replace('/^- \[ \] (.+)$/mi', 'li-open::$1', $text) ?? $text;
        $text = preg_replace('/^- (.+)$/m', 'li::$1', $text) ?? $text;

        $lines = explode("\n", $text);
        $html = '';
        $inList = false;
        foreach ($lines as $line) {
            $line = rtrim($line);
            if (preg_match('/^h([1-6])::(.+)$/', $line, $m)) {
                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }
                $html .= '<h' . $m[1] . '>' . $m[2] . '</h' . $m[1] . '>';
                continue;
            }
            if (str_starts_with($line, 'li-check::')) {
                if (!$inList) {
                    $html .= '<ul class="md-list">';
                    $inList = true;
                }
                $html .= '<li class="is-done">' . substr($line, 11) . '</li>';
                continue;
            }
            if (str_starts_with($line, 'li-open::')) {
                if (!$inList) {
                    $html .= '<ul class="md-list">';
                    $inList = true;
                }
                $html .= '<li>' . substr($line, 10) . '</li>';
                continue;
            }
            if (str_starts_with($line, 'li::')) {
                if (!$inList) {
                    $html .= '<ul class="md-list">';
                    $inList = true;
                }
                $html .= '<li>' . substr($line, 4) . '</li>';
                continue;
            }
            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }
            if ($line === '') {
                $html .= '';
                continue;
            }
            $html .= '<p>' . $line . '</p>';
        }
        if ($inList) {
            $html .= '</ul>';
        }

        return strtr($html, $placeholders);
    }
}
