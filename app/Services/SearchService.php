<?php

declare(strict_types=1);

final class SearchService
{
    public static function query(string $q, array $filters = [], int $limit = 40): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $filters['q'] = $q;
        $filters['limit'] = $limit;
        $filters['order'] = 'i.updated_at DESC';
        $filters['archived'] = !empty($filters['archived']) ? true : false;
        return ItemService::list($filters);
    }

    public static function workspaces(string $q, int $limit = 8): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM workspaces
             WHERE user_id = :uid AND archived_at IS NULL
               AND (name LIKE :q1 OR description LIKE :q2)
             ORDER BY name LIMIT ' . $limit
        );
        $like = '%' . $q . '%';
        $stmt->execute([
            'uid' => Auth::id(),
            'q1' => $like,
            'q2' => $like,
        ]);
        return $stmt->fetchAll() ?: [];
    }
}
