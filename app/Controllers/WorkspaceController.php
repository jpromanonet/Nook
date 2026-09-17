<?php

declare(strict_types=1);

final class WorkspaceController
{
    public static function index(): void
    {
        Auth::requireLogin();
        view('workspaces/index', [
            'title' => 'Workspaces',
            'workspaces' => WorkspaceService::all(true),
        ]);
    }

    public static function create(): void
    {
        Auth::requireLogin();
        view('workspaces/form', [
            'title' => 'New workspace',
            'workspace' => null,
        ]);
    }

    public static function store(): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            $id = WorkspaceService::create($_POST);
            flash('success', 'Workspace created.');
            redirect('/workspaces/' . $id);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/workspaces/create');
        }
    }

    public static function show(string $id): void
    {
        Auth::requireLogin();
        $data = WorkspaceService::overview((int) $id);
        view('workspaces/show', [
            'title' => $data['workspace']['name'],
            'module' => 'overview',
            ...$data,
        ]);
    }

    public static function edit(string $id): void
    {
        Auth::requireLogin();
        view('workspaces/form', [
            'title' => 'Edit workspace',
            'workspace' => WorkspaceService::require((int) $id),
        ]);
    }

    public static function update(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            WorkspaceService::update((int) $id, $_POST);
            flash('success', 'Saved.');
            redirect('/workspaces/' . $id);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/workspaces/' . $id . '/edit');
        }
    }

    public static function archive(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        WorkspaceService::archive((int) $id);
        flash('success', 'Archived.');
        redirect('/workspaces');
    }

    public static function destroy(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            WorkspaceService::delete((int) $id);
            flash('success', 'Workspace deleted.');
            redirect('/home');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/workspaces/' . $id . '/edit');
        }
    }

    public static function module(string $id): void
    {
        Auth::requireLogin();
        $workspace = WorkspaceService::require((int) $id);
        $path = current_path();
        $module = 'documents';
        foreach (['documents', 'tasks', 'notes', 'files', 'media', 'links', 'accounts', 'archive'] as $name) {
            if (str_ends_with($path, '/' . $name)) {
                $module = $name;
                break;
            }
        }

        $filters = [
            'workspace_id' => (int) $id,
            'limit' => 200,
        ];
        $empty = ['title' => '', 'body' => '', 'type' => 'note'];
        $folderable = in_array($module, ['documents', 'files', 'media'], true);
        $folderId = null;
        $currentFolder = null;
        $breadcrumbs = [];

        if ($folderable) {
            $rawFolder = null_if_blank((string) input('folder', ''));
            if ($rawFolder !== null) {
                $currentFolder = ItemService::require((int) $rawFolder);
                if ($currentFolder['type'] !== 'folder'
                    || (int) ($currentFolder['workspace_id'] ?? 0) !== (int) $id) {
                    flash('error', 'Carpeta no encontrada.');
                    redirect('/workspaces/' . $id . '/' . $module);
                }
                $folderId = (int) $currentFolder['id'];
                $breadcrumbs = ItemService::breadcrumbs($folderId);
                $filters['parent_id'] = $folderId;
            } else {
                $filters['root_only'] = true;
            }
            $filters['order'] = "i.type = 'folder' DESC, i.sort_order ASC, i.title ASC";
        }

        switch ($module) {
            case 'documents':
                $filters['folder_scope'] = 'documents';
                $empty = [
                    'title' => 'Nothing here yet.',
                    'body' => 'This is where the knowledge behind this workspace will live.',
                    'type' => 'document',
                ];
                break;
            case 'tasks':
                $filters['type'] = 'task';
                $filters['order'] = "FIELD(i.status,'doing','todo','blocked','done','cancelled'), i.due_date";
                $empty = [
                    'title' => 'No tasks here.',
                    'body' => 'Enjoy the quiet, or add something when you\'re ready.',
                    'type' => 'task',
                ];
                break;
            case 'notes':
                $filters['type'] = ['note', 'idea'];
                $empty = [
                    'title' => 'No notes yet.',
                    'body' => 'A quiet place for thoughts, meetings and drafts.',
                    'type' => 'note',
                ];
                break;
            case 'files':
                $filters['folder_scope'] = 'files';
                $empty = [
                    'title' => 'No files yet.',
                    'body' => 'PDFs, docs and archives will live here.',
                    'type' => 'file',
                ];
                break;
            case 'media':
                $filters['folder_scope'] = 'media';
                $empty = [
                    'title' => 'No media yet.',
                    'body' => 'Images, audio and video for this workspace.',
                    'type' => 'image',
                ];
                break;
            case 'links':
                $filters['type'] = 'link';
                $empty = [
                    'title' => 'No links yet.',
                    'body' => 'Keep websites, repos and tools close.',
                    'type' => 'link',
                ];
                break;
            case 'accounts':
                $filters['type'] = 'account';
                $empty = [
                    'title' => 'No accounts yet.',
                    'body' => 'References only — passwords stay in your vault.',
                    'type' => 'account',
                ];
                break;
            case 'archive':
                $filters['archived'] = true;
                $empty = [
                    'title' => 'Archive is empty.',
                    'body' => 'Things you set aside will wait here.',
                    'type' => 'note',
                ];
                break;
        }

        if ($module === 'archive') {
            $items = ItemService::list([
                'workspace_id' => (int) $id,
                'archived' => true,
                'limit' => 200,
                'order' => 'i.archived_at DESC',
            ]);
        } else {
            $items = ItemService::list($filters);
        }

        $folders = [];
        $files = [];
        if ($folderable) {
            foreach ($items as $row) {
                if ($row['type'] === 'folder') {
                    $folders[] = $row;
                } else {
                    $files[] = $row;
                }
            }
        }

        $layout = (string) input('layout', $module === 'media' ? 'grid' : 'list');
        if (!in_array($layout, ['grid', 'list'], true)) {
            $layout = $module === 'media' ? 'grid' : 'list';
        }

        view('workspaces/module', [
            'title' => $workspace['name'] . ' · ' . ucfirst($module),
            'workspace' => $workspace,
            'module' => $module,
            'counts' => WorkspaceService::counts((int) $id),
            'items' => $items,
            'folders' => $folders,
            'fileItems' => $folderable ? $files : $items,
            'empty' => $empty,
            'layoutMode' => $layout,
            'folderable' => $folderable,
            'folderId' => $folderId,
            'currentFolder' => $currentFolder,
            'breadcrumbs' => $breadcrumbs,
            'folderScope' => $folderable ? $module : null,
        ]);
    }
}
