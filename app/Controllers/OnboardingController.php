<?php

declare(strict_types=1);

final class OnboardingController
{
    public static function index(): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }
        $user = UserService::find(Auth::id()) ?? Auth::user();
        $workspaces = WorkspaceService::all();
        $step = 1;
        if (!empty($user['name'])) {
            $step = 2;
        }
        if ($workspaces) {
            $step = 3;
        }
        if ((int) ($user['onboarding_completed'] ?? 0) === 1) {
            redirect('/home');
        }
        view('onboarding/index', [
            'title' => 'Welcome to Nook',
            'user' => $user,
            'workspaces' => $workspaces,
            'step' => $step,
        ], 'layouts/auth');
    }

    public static function saveName(): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }
        require_csrf();
        try {
            UserService::updateName(Auth::id(), (string) input('name', ''));
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/onboarding');
    }

    public static function saveWorkspace(): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }
        require_csrf();
        try {
            WorkspaceService::create([
                'name' => (string) input('name', ''),
                'type' => (string) input('type', 'project'),
                'description' => (string) input('description', ''),
            ]);
            flash('success', 'Your first workspace is ready.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/onboarding');
    }

    public static function saveTask(): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }
        require_csrf();
        $title = trim((string) input('title', ''));
        $workspaces = WorkspaceService::all();
        $ws = $workspaces[0] ?? null;
        if ($title !== '' && $ws) {
            ItemService::create([
                'type' => 'task',
                'title' => $title,
                'workspace_id' => $ws['id'],
                'status' => 'todo',
                'due_date' => date('Y-m-d'),
            ]);
        }
        UserService::completeOnboarding(Auth::id());
        flash('success', 'Everything has a place.');
        redirect('/home');
    }

    public static function finish(): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }
        require_csrf();
        UserService::completeOnboarding(Auth::id());
        redirect('/home');
    }
}
