<?php

declare(strict_types=1);

final class TodayController
{
    public static function index(): void
    {
        Auth::requireLogin();
        $date = (string) input('date', date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $snapshot = DailyService::todaySnapshot();
        if ($date !== date('Y-m-d')) {
            $snapshot['date'] = $date;
            $snapshot['plan'] = DailyService::forDate($date);
            $snapshot['due'] = ItemService::list(['type' => 'task', 'due_on' => $date, 'limit' => 30]);
            $snapshot['events'] = ItemService::list([
                'type' => 'event',
                'due_from' => $date,
                'due_to' => $date,
                'limit' => 20,
            ]);
        }
        view('today/index', [
            'title' => 'Today',
            'snapshot' => $snapshot,
        ]);
    }

    public static function save(): void
    {
        Auth::requireLogin();
        require_csrf();
        $date = (string) input('note_date', date('Y-m-d'));
        DailyService::save($date, $_POST);
        flash('success', 'Saved.');
        redirect('/today?date=' . urlencode($date));
    }
}
