<?php

declare(strict_types=1);

final class MetricsController
{
    public static function index(): void
    {
        Auth::requireLogin();
        view('metrics/index', [
            'title' => 'Metrics',
            'metrics' => MetricsService::snapshot(),
        ]);
    }
}
