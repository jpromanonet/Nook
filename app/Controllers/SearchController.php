<?php

declare(strict_types=1);

final class SearchController
{
    public static function index(): void
    {
        Auth::requireLogin();
        $q = trim((string) input('q', ''));
        $type = null_if_blank((string) input('type', ''));
        $workspaceId = null_if_blank((string) input('workspace_id', ''));
        $tag = null_if_blank((string) input('tag', ''));
        $status = null_if_blank((string) input('status', ''));

        $items = [];
        $workspaces = [];
        if ($q !== '' || $type || $workspaceId || $tag) {
            $filters = [];
            if ($type) {
                $filters['type'] = $type;
            }
            if ($workspaceId) {
                $filters['workspace_id'] = (int) $workspaceId;
            }
            if ($tag) {
                $filters['tag'] = $tag;
            }
            if ($status) {
                $filters['status'] = $status;
            }
            $items = $q !== '' ? SearchService::query($q, $filters) : ItemService::list($filters + ['limit' => 40]);
            $workspaces = $q !== '' ? SearchService::workspaces($q) : [];
        }

        view('search/index', [
            'title' => 'Search',
            'q' => $q,
            'type' => $type,
            'workspaceId' => $workspaceId,
            'tag' => $tag,
            'status' => $status,
            'items' => $items,
            'workspaceHits' => $workspaces,
            'workspaces' => WorkspaceService::all(),
        ]);
    }

    public static function json(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $q = trim((string) input('q', ''));
        $items = $q === '' ? [] : SearchService::query($q, [], 12);
        $workspaces = $q === '' ? [] : SearchService::workspaces($q, 6);
        $out = [];
        foreach ($workspaces as $ws) {
            $out[] = [
                'kind' => 'workspace',
                'id' => (int) $ws['id'],
                'title' => $ws['name'],
                'subtitle' => workspace_types()[$ws['type']] ?? $ws['type'],
                'url' => url('/workspaces/' . $ws['id']),
            ];
        }
        foreach ($items as $item) {
            $out[] = [
                'kind' => $item['type'],
                'id' => (int) $item['id'],
                'title' => $item['title'],
                'subtitle' => item_type_label($item['type']) . ($item['workspace_name'] ? ' · ' . $item['workspace_name'] : ''),
                'url' => url('/items/' . $item['id']),
            ];
        }
        echo json_encode(['results' => $out], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
