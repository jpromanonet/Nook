<?php

declare(strict_types=1);

final class BoardController
{
    /** @return list<string> */
    private static function columns(): array
    {
        return ['todo', 'doing', 'blocked', 'done'];
    }

    public static function index(): void
    {
        Auth::requireLogin();
        $workspaces = WorkspaceService::all();
        $tasks = ItemService::list([
            'type' => 'task',
            'order' => "FIELD(i.status,'doing','todo','blocked','done','cancelled'), i.due_date, i.title",
            'limit' => 500,
        ]);

        $columns = self::columns();
        $rows = [];
        foreach ($workspaces as $ws) {
            $rows[] = [
                'id' => (int) $ws['id'],
                'name' => $ws['name'],
                'color' => $ws['color'] ?: '#71806A',
            ];
        }

        $grid = [];
        foreach ($rows as $row) {
            $key = (string) $row['id'];
            $grid[$key] = [];
            foreach ($columns as $col) {
                $grid[$key][$col] = [];
            }
        }

        foreach ($tasks as $task) {
            if ($task['workspace_id'] === null) {
                continue;
            }
            $status = (string) ($task['status'] ?? 'todo');
            if ($status === 'inbox') {
                $status = 'todo';
            }
            if ($status === 'cancelled' || !in_array($status, $columns, true)) {
                continue;
            }
            $wid = (string) (int) $task['workspace_id'];
            if (!isset($grid[$wid])) {
                continue;
            }
            $grid[$wid][$status][] = $task;
        }

        view('board/index', [
            'title' => 'Board',
            'rows' => $rows,
            'columns' => $columns,
            'grid' => $grid,
            'columnLabels' => task_statuses(),
        ]);
    }

    public static function move(): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            $id = (int) ($_POST['id'] ?? 0);
            $status = (string) ($_POST['status'] ?? 'todo');
            $workspaceId = (int) ($_POST['workspace_id'] ?? 0);
            if ($workspaceId <= 0) {
                throw new InvalidArgumentException('Elegí un workspace.');
            }
            ItemService::moveTask($id, $status, $workspaceId);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                json_response(['ok' => true]);
            }
            flash('success', 'Moved.');
            redirect('/board');
        } catch (Throwable $e) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                json_response(['ok' => false, 'error' => $e->getMessage()], 400);
            }
            flash('error', $e->getMessage());
            redirect('/board');
        }
    }
}
