<?php

declare(strict_types=1);

final class WorkspaceService
{
    public static function all(bool $includeArchived = false): array
    {
        $sql = 'SELECT * FROM workspaces WHERE user_id = :uid';
        if (!$includeArchived) {
            $sql .= " AND status <> 'archived' AND archived_at IS NULL";
        }
        $sql .= ' ORDER BY name';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['uid' => Auth::id()]);
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM workspaces WHERE id = :id AND user_id = :uid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'uid' => Auth::id()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function require(int $id): array
    {
        $row = self::find($id);
        if (!$row) {
            http_response_code(404);
            view('partials/404', ['title' => 'Workspace no encontrado']);
            exit;
        }
        return $row;
    }

    public static function create(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('El workspace necesita un nombre.');
        }
        $type = (string) ($data['type'] ?? 'project');
        if (!isset(workspace_types()[$type])) {
            $type = 'other';
        }
        $status = (string) ($data['status'] ?? 'active');
        if (!isset(workspace_statuses()[$status])) {
            $status = 'active';
        }
        $color = (string) ($data['color'] ?? '#71806A');
        if (!isset(workspace_colors()[$color])) {
            $color = '#71806A';
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO workspaces
                (user_id, name, description, type, status, color, icon, website, repository, notes, start_date, end_date)
             VALUES
                (:uid, :name, :description, :type, :status, :color, :icon, :website, :repository, :notes, :start_date, :end_date)'
        );
        $stmt->execute([
            'uid' => Auth::id(),
            'name' => $name,
            'description' => null_if_blank($data['description'] ?? null),
            'type' => $type,
            'status' => $status,
            'color' => $color,
            'icon' => 'workspace',
            'website' => null_if_blank($data['website'] ?? null),
            'repository' => null_if_blank($data['repository'] ?? null),
            'notes' => null_if_blank($data['notes'] ?? null),
            'start_date' => null_if_blank($data['start_date'] ?? null),
            'end_date' => null_if_blank($data['end_date'] ?? null),
        ]);
        $id = (int) Database::pdo()->lastInsertId();
        ActivityService::log('workspace.created', 'Created workspace "' . $name . '"', $id);
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $row = self::require($id);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('El workspace necesita un nombre.');
        }
        $type = (string) ($data['type'] ?? $row['type']);
        if (!isset(workspace_types()[$type])) {
            $type = $row['type'];
        }
        $status = (string) ($data['status'] ?? $row['status']);
        if (!isset(workspace_statuses()[$status])) {
            $status = $row['status'];
        }
        $color = (string) ($data['color'] ?? $row['color']);
        if (!isset(workspace_colors()[$color])) {
            $color = $row['color'];
        }
        $archivedAt = $status === 'archived' ? date('Y-m-d H:i:s') : null;

        $stmt = Database::pdo()->prepare(
            'UPDATE workspaces SET
                name = :name, description = :description, type = :type, status = :status,
                color = :color, website = :website, repository = :repository, notes = :notes,
                start_date = :start_date, end_date = :end_date, archived_at = :archived_at
             WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'name' => $name,
            'description' => null_if_blank($data['description'] ?? null),
            'type' => $type,
            'status' => $status,
            'color' => $color,
            'website' => null_if_blank($data['website'] ?? null),
            'repository' => null_if_blank($data['repository'] ?? null),
            'notes' => null_if_blank($data['notes'] ?? null),
            'start_date' => null_if_blank($data['start_date'] ?? null),
            'end_date' => null_if_blank($data['end_date'] ?? null),
            'archived_at' => $archivedAt,
            'id' => $id,
            'uid' => Auth::id(),
        ]);
        ActivityService::log('workspace.updated', 'Updated workspace "' . $name . '"', $id);
    }

    public static function archive(int $id): void
    {
        $row = self::require($id);
        $stmt = Database::pdo()->prepare(
            "UPDATE workspaces SET status = 'archived', archived_at = NOW() WHERE id = :id AND user_id = :uid"
        );
        $stmt->execute(['id' => $id, 'uid' => Auth::id()]);
        ActivityService::log('workspace.archived', 'Archived workspace "' . $row['name'] . '"', $id);
    }

    public static function counts(int $id): array
    {
        self::require($id);
        $countsStmt = Database::pdo()->prepare(
            'SELECT type, COUNT(*) AS c FROM items
             WHERE user_id = :uid AND workspace_id = :wid AND deleted_at IS NULL AND archived_at IS NULL
             GROUP BY type'
        );
        $countsStmt->execute(['uid' => Auth::id(), 'wid' => $id]);
        $counts = [];
        foreach ($countsStmt->fetchAll() ?: [] as $row) {
            $counts[$row['type']] = (int) $row['c'];
        }
        return $counts;
    }

    public static function overview(int $id): array
    {
        $ws = self::require($id);
        $uid = Auth::id();
        $pdo = Database::pdo();

        $nextTasks = $pdo->prepare(
            "SELECT * FROM items
             WHERE user_id = :uid AND workspace_id = :wid AND type = 'task'
               AND deleted_at IS NULL AND archived_at IS NULL
               AND status NOT IN ('done', 'cancelled')
             ORDER BY (due_date IS NULL), due_date, priority = 'urgent' DESC, created_at
             LIMIT 6"
        );
        $nextTasks->execute(['uid' => $uid, 'wid' => $id]);

        $upcoming = $pdo->prepare(
            "SELECT * FROM items
             WHERE user_id = :uid AND workspace_id = :wid AND deleted_at IS NULL AND archived_at IS NULL
               AND (due_date >= CURDATE() OR start_date >= CURDATE())
             ORDER BY COALESCE(start_date, due_date)
             LIMIT 5"
        );
        $upcoming->execute(['uid' => $uid, 'wid' => $id]);

        $recent = $pdo->prepare(
            'SELECT * FROM items
             WHERE user_id = :uid AND workspace_id = :wid AND deleted_at IS NULL
             ORDER BY updated_at DESC LIMIT 6'
        );
        $recent->execute(['uid' => $uid, 'wid' => $id]);

        $pinned = $pdo->prepare(
            'SELECT * FROM items
             WHERE user_id = :uid AND workspace_id = :wid AND is_pinned = 1 AND deleted_at IS NULL AND archived_at IS NULL
             ORDER BY updated_at DESC LIMIT 8'
        );
        $pinned->execute(['uid' => $uid, 'wid' => $id]);

        return [
            'workspace' => $ws,
            'next_tasks' => $nextTasks->fetchAll() ?: [],
            'upcoming' => $upcoming->fetchAll() ?: [],
            'recent' => $recent->fetchAll() ?: [],
            'pinned' => $pinned->fetchAll() ?: [],
            'counts' => self::counts($id),
            'activity' => ActivityService::forWorkspace($id, 8),
        ];
    }
}
