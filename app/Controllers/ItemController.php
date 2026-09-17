<?php

declare(strict_types=1);

final class ItemController
{
    public static function create(): void
    {
        Auth::requireLogin();
        $type = (string) input('type', 'note');
        if (!isset(item_types()[$type])) {
            $type = 'note';
        }
        $workspaceId = null_if_blank((string) input('workspace_id', ''));
        $parentId = null_if_blank((string) input('parent_id', ''));
        view('items/form', [
            'title' => 'New ' . strtolower(item_type_label($type)),
            'item' => null,
            'type' => $type,
            'workspaceId' => $workspaceId,
            'parentId' => $parentId,
            'workspaces' => WorkspaceService::all(),
        ]);
    }

    public static function store(): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            $id = ItemService::create($_POST, $_FILES['upload'] ?? null);
            flash('success', 'Saved.');
            $item = ItemService::find($id);
            if ($item && empty($item['workspace_id']) && in_array($item['type'], ['note', 'task', 'idea', 'link'], true)) {
                redirect('/inbox');
            }
            redirect('/items/' . $id);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            $qs = http_build_query([
                'type' => (string) input('type', 'note'),
                'workspace_id' => (string) input('workspace_id', ''),
            ]);
            redirect('/items/create?' . $qs);
        }
    }

    public static function show(string $id): void
    {
        Auth::requireLogin();
        $item = ItemService::require((int) $id);
        $children = [];
        if (in_array($item['type'], ['folder', 'document'], true)) {
            $children = ItemService::children((int) $item['id']);
        }
        view('items/show', [
            'title' => $item['title'],
            'item' => $item,
            'children' => $children,
            'workspaces' => WorkspaceService::all(),
        ]);
    }

    public static function edit(string $id): void
    {
        Auth::requireLogin();
        $item = ItemService::require((int) $id);
        view('items/form', [
            'title' => 'Edit ' . strtolower(item_type_label($item['type'])),
            'item' => $item,
            'type' => $item['type'],
            'workspaceId' => $item['workspace_id'],
            'parentId' => $item['parent_id'],
            'workspaces' => WorkspaceService::all(),
        ]);
    }

    public static function update(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            ItemService::update((int) $id, $_POST, $_FILES['upload'] ?? null);
            flash('success', 'Saved.');
            redirect('/items/' . $id);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/items/' . $id . '/edit');
        }
    }

    public static function archive(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        ItemService::archive((int) $id);
        flash('success', 'Archived.');
        self::back($id);
    }

    public static function restore(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        ItemService::restore((int) $id);
        flash('success', 'Restored.');
        redirect('/items/' . $id);
    }

    public static function destroy(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        $item = ItemService::require((int) $id);
        ItemService::delete((int) $id);
        flash('success', 'Moved out of the way.');
        if (!empty($item['workspace_id'])) {
            redirect('/workspaces/' . $item['workspace_id']);
        }
        redirect('/inbox');
    }

    public static function favorite(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        ItemService::toggle((int) $id, 'is_favorite');
        self::back($id);
    }

    public static function pin(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        ItemService::toggle((int) $id, 'is_pinned');
        self::back($id);
    }

    public static function convert(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            ItemService::convert((int) $id, (string) input('type', 'document'));
            flash('success', 'Converted.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/items/' . $id);
    }

    public static function toggleChecklist(string $id, string $index): void
    {
        Auth::requireLogin();
        require_csrf();
        ItemService::toggleChecklistItem((int) $id, (int) $index);
        redirect('/items/' . $id);
    }

    private static function back(string $id): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if (is_string($ref) && $ref !== '') {
            header('Location: ' . $ref);
            exit;
        }
        redirect('/items/' . $id);
    }
}
