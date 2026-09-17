<?php

declare(strict_types=1);

final class MetricsService
{
    public static function snapshot(): array
    {
        $uid = Auth::id();
        $pdo = Database::pdo();

        $taskByStatus = self::mapCount($pdo, "
            SELECT status, COUNT(*) AS c FROM items
            WHERE user_id = :uid AND type = 'task' AND deleted_at IS NULL AND archived_at IS NULL
            GROUP BY status
        ", $uid);

        // Normalize legacy inbox → todo
        if (!empty($taskByStatus['inbox'])) {
            $taskByStatus['todo'] = (int) ($taskByStatus['todo'] ?? 0) + (int) $taskByStatus['inbox'];
            unset($taskByStatus['inbox']);
        }

        $openStatuses = ['todo', 'doing', 'blocked'];
        $openTasks = 0;
        foreach ($openStatuses as $s) {
            $openTasks += (int) ($taskByStatus[$s] ?? 0);
        }
        $doneTasks = (int) ($taskByStatus['done'] ?? 0);
        $totalTasks = array_sum(array_map('intval', $taskByStatus));

        $st = $pdo->prepare(
            "SELECT COUNT(*) FROM items
             WHERE user_id = :uid AND type = 'task' AND deleted_at IS NULL AND archived_at IS NULL
               AND due_date IS NOT NULL AND due_date < CURDATE()
               AND status NOT IN ('done','cancelled')"
        );
        $st->execute(['uid' => $uid]);
        $overdue = (int) $st->fetchColumn();

        $dueWeek = $pdo->prepare(
            "SELECT COUNT(*) FROM items
             WHERE user_id = :uid AND type = 'task' AND deleted_at IS NULL AND archived_at IS NULL
               AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
               AND status NOT IN ('done','cancelled')"
        );
        $dueWeek->execute(['uid' => $uid]);
        $dueThisWeek = (int) $dueWeek->fetchColumn();

        $doneWeek = $pdo->prepare(
            "SELECT COUNT(*) FROM items
             WHERE user_id = :uid AND type = 'task' AND deleted_at IS NULL
               AND status = 'done' AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        $doneWeek->execute(['uid' => $uid]);
        $doneThisWeek = (int) $doneWeek->fetchColumn();

        $tasksByWorkspace = [];
        $wsStmt = $pdo->prepare(
            "SELECT COALESCE(w.name, 'Unassigned') AS label,
                    COALESCE(w.color, '#788FA0') AS color,
                    COUNT(*) AS c
             FROM items i
             LEFT JOIN workspaces w ON w.id = i.workspace_id
             WHERE i.user_id = :uid AND i.type = 'task' AND i.deleted_at IS NULL AND i.archived_at IS NULL
               AND i.status NOT IN ('done','cancelled')
             GROUP BY i.workspace_id, w.name, w.color
             ORDER BY c DESC"
        );
        $wsStmt->execute(['uid' => $uid]);
        foreach ($wsStmt->fetchAll() ?: [] as $row) {
            $tasksByWorkspace[] = [
                'label' => $row['label'],
                'color' => $row['color'],
                'count' => (int) $row['c'],
            ];
        }

        $priorityStmt = $pdo->prepare(
            "SELECT COALESCE(priority, 'normal') AS priority, COUNT(*) AS c
             FROM items
             WHERE user_id = :uid AND type = 'task' AND deleted_at IS NULL AND archived_at IS NULL
               AND status NOT IN ('done','cancelled')
             GROUP BY COALESCE(priority, 'normal')"
        );
        $priorityStmt->execute(['uid' => $uid]);
        $byPriority = [];
        foreach ($priorityStmt->fetchAll() ?: [] as $row) {
            $byPriority[$row['priority']] = (int) $row['c'];
        }

        // Completion trend: tasks marked done in last 14 days by day
        $trendStmt = $pdo->prepare(
            "SELECT DATE(updated_at) AS d, COUNT(*) AS c
             FROM items
             WHERE user_id = :uid AND type = 'task' AND status = 'done'
               AND deleted_at IS NULL
               AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
             GROUP BY DATE(updated_at)
             ORDER BY d"
        );
        $trendStmt->execute(['uid' => $uid]);
        $doneByDay = [];
        foreach ($trendStmt->fetchAll() ?: [] as $row) {
            $doneByDay[$row['d']] = (int) $row['c'];
        }
        $completionTrend = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $completionTrend[] = [
                'date' => $d,
                'label' => date('j M', strtotime($d)),
                'count' => (int) ($doneByDay[$d] ?? 0),
            ];
        }

        // Everything else
        $byType = self::mapCount($pdo, "
            SELECT type, COUNT(*) AS c FROM items
            WHERE user_id = :uid AND deleted_at IS NULL AND archived_at IS NULL
              AND type <> 'folder'
            GROUP BY type
            ORDER BY c DESC
        ", $uid);

        $createdTrendStmt = $pdo->prepare(
            "SELECT DATE(created_at) AS d, COUNT(*) AS c
             FROM items
             WHERE user_id = :uid AND deleted_at IS NULL
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
             GROUP BY DATE(created_at)
             ORDER BY d"
        );
        $createdTrendStmt->execute(['uid' => $uid]);
        $createdByDay = [];
        foreach ($createdTrendStmt->fetchAll() ?: [] as $row) {
            $createdByDay[$row['d']] = (int) $row['c'];
        }
        $activityTrend = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $activityTrend[] = [
                'date' => $d,
                'label' => date('j M', strtotime($d)),
                'count' => (int) ($createdByDay[$d] ?? 0),
            ];
        }

        $itemsByWorkspace = [];
        $allWs = $pdo->prepare(
            "SELECT COALESCE(w.name, 'Unassigned') AS label,
                    COALESCE(w.color, '#788FA0') AS color,
                    COUNT(*) AS c
             FROM items i
             LEFT JOIN workspaces w ON w.id = i.workspace_id
             WHERE i.user_id = :uid AND i.deleted_at IS NULL AND i.archived_at IS NULL
               AND i.type <> 'folder'
             GROUP BY i.workspace_id, w.name, w.color
             ORDER BY c DESC"
        );
        $allWs->execute(['uid' => $uid]);
        foreach ($allWs->fetchAll() ?: [] as $row) {
            $itemsByWorkspace[] = [
                'label' => $row['label'],
                'color' => $row['color'],
                'count' => (int) $row['c'],
            ];
        }

        $totalItems = array_sum(array_map('intval', $byType));

        return [
            'tasks' => [
                'by_status' => $taskByStatus,
                'open' => $openTasks,
                'done' => $doneTasks,
                'total' => $totalTasks,
                'overdue' => $overdue,
                'due_this_week' => $dueThisWeek,
                'done_this_week' => $doneThisWeek,
                'by_workspace' => $tasksByWorkspace,
                'by_priority' => $byPriority,
                'completion_trend' => $completionTrend,
            ],
            'library' => [
                'by_type' => $byType,
                'total' => $totalItems,
                'by_workspace' => $itemsByWorkspace,
                'activity_trend' => $activityTrend,
            ],
        ];
    }

    private static function mapCount(PDO $pdo, string $sql, int $uid): array
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $uid]);
        $out = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $key = (string) ($row['status'] ?? $row['type'] ?? '');
            if ($key === '') {
                continue;
            }
            $out[$key] = (int) $row['c'];
        }
        return $out;
    }
}
