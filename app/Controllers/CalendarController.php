<?php

declare(strict_types=1);

final class CalendarController
{
    public static function index(): void
    {
        Auth::requireLogin();
        $viewMode = (string) input('view', 'month');
        if (!in_array($viewMode, ['month', 'week', 'agenda'], true)) {
            $viewMode = 'month';
        }
        $cursor = (string) input('date', date('Y-m-01'));
        $ts = strtotime($cursor) ?: time();
        $year = (int) date('Y', $ts);
        $month = (int) date('n', $ts);

        if ($viewMode === 'week') {
            $start = date('Y-m-d', strtotime('monday this week', $ts) ?: $ts);
            $end = date('Y-m-d', strtotime($start . ' +6 days') ?: $ts);
        } elseif ($viewMode === 'agenda') {
            $start = date('Y-m-d', $ts);
            $end = date('Y-m-d', strtotime($start . ' +45 days') ?: $ts);
        } else {
            $start = date('Y-m-01', $ts);
            $end = date('Y-m-t', $ts);
        }

        $items = ItemService::list([
            'due_from' => $start,
            'due_to' => $end,
            'order' => 'COALESCE(i.start_date, i.due_date), i.title',
            'limit' => 400,
        ]);

        $byDay = [];
        foreach ($items as $item) {
            $day = $item['start_date'] ?: $item['due_date'];
            if (!$day) {
                continue;
            }
            $byDay[$day][] = $item;
        }

        view('calendar/index', [
            'title' => 'Calendar',
            'viewMode' => $viewMode,
            'year' => $year,
            'month' => $month,
            'cursor' => date('Y-m-d', $ts),
            'start' => $start,
            'end' => $end,
            'items' => $items,
            'byDay' => $byDay,
            'workspaces' => WorkspaceService::all(),
        ]);
    }
}
