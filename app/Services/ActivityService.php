<?php

declare(strict_types=1);

final class ActivityService
{
    public static function log(string $action, string $summary, ?int $workspaceId = null, ?int $itemId = null): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO activity_log (user_id, workspace_id, item_id, action, summary)
             VALUES (:uid, :wid, :iid, :action, :summary)'
        );
        $stmt->execute([
            'uid' => Auth::id(),
            'wid' => $workspaceId,
            'iid' => $itemId,
            'action' => $action,
            'summary' => mb_substr($summary, 0, 255),
        ]);
    }

    public static function forWorkspace(int $workspaceId, int $limit = 12): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM activity_log
             WHERE user_id = :uid AND workspace_id = :wid
             ORDER BY created_at DESC LIMIT :lim'
        );
        $stmt->bindValue('uid', Auth::id(), PDO::PARAM_INT);
        $stmt->bindValue('wid', $workspaceId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function recent(int $limit = 8): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT a.*, w.name AS workspace_name, w.color AS workspace_color
             FROM activity_log a
             LEFT JOIN workspaces w ON w.id = a.workspace_id
             WHERE a.user_id = :uid
             ORDER BY a.created_at DESC LIMIT :lim'
        );
        $stmt->bindValue('uid', Auth::id(), PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
