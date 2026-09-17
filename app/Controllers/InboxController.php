<?php

declare(strict_types=1);

final class InboxController
{
    public static function index(): void
    {
        Auth::requireLogin();
        $items = ItemService::list([
            'inbox' => true,
            'order' => 'i.created_at DESC',
            'limit' => 100,
        ]);
        view('inbox/index', [
            'title' => 'Inbox',
            'items' => $items,
            'workspaces' => WorkspaceService::all(),
        ]);
    }

    public static function classify(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        $workspaceId = null_if_blank((string) input('workspace_id', ''));
        $type = null_if_blank((string) input('type', ''));
        try {
            ItemService::classify((int) $id, $workspaceId !== null ? (int) $workspaceId : null, $type);
            flash('success', 'Placed.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/inbox');
    }
}
