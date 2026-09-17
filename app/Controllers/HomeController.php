<?php

declare(strict_types=1);

final class HomeController
{
    public static function index(): void
    {
        if (Auth::check()) {
            if (!(int) ($_SESSION['user']['onboarding_completed'] ?? 0)) {
                redirect('/onboarding');
            }
            redirect('/home');
        }
        view('home/landing', ['title' => 'Everything has a place'], 'layouts/auth');
    }

    public static function home(): void
    {
        Auth::requireLogin();
        UserService::refreshSession();
        $workspaces = WorkspaceService::all();
        $recent = ItemService::list(['limit' => 8, 'order' => 'i.updated_at DESC']);
        $today = DailyService::todaySnapshot();
        view('home/index', [
            'title' => 'Home',
            'workspaces' => $workspaces,
            'recent' => $recent,
            'today' => $today,
        ]);
    }
}
