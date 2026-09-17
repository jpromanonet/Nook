<?php

declare(strict_types=1);

final class TagService
{
    public static function slug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? $slug;
        return trim($slug, '-') ?: 'tag';
    }

    public static function sync(int $itemId, array $names): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM item_tags WHERE item_id = :id')->execute(['id' => $itemId]);
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $slug = self::slug($name);
            $find = $pdo->prepare('SELECT id FROM tags WHERE user_id = :uid AND slug = :slug LIMIT 1');
            $find->execute(['uid' => Auth::id(), 'slug' => $slug]);
            $row = $find->fetch();
            if (!$row) {
                $ins = $pdo->prepare('INSERT INTO tags (user_id, name, slug) VALUES (:uid, :name, :slug)');
                $ins->execute(['uid' => Auth::id(), 'name' => $name, 'slug' => $slug]);
                $tagId = (int) $pdo->lastInsertId();
            } else {
                $tagId = (int) $row['id'];
            }
            $link = $pdo->prepare('INSERT IGNORE INTO item_tags (item_id, tag_id) VALUES (:iid, :tid)');
            $link->execute(['iid' => $itemId, 'tid' => $tagId]);
        }
    }

    public static function forItem(int $itemId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.* FROM tags t
             INNER JOIN item_tags it ON it.tag_id = t.id
             WHERE it.item_id = :id AND t.user_id = :uid
             ORDER BY t.name'
        );
        $stmt->execute(['id' => $itemId, 'uid' => Auth::id()]);
        return $stmt->fetchAll() ?: [];
    }

    public static function attachToItems(array $items): array
    {
        if (!$items) {
            return $items;
        }
        $ids = array_map(static fn ($row) => (int) $row['id'], $items);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT it.item_id, t.id, t.name, t.slug
                FROM item_tags it
                INNER JOIN tags t ON t.id = it.tag_id
                WHERE t.user_id = ? AND it.item_id IN (' . $in . ')
                ORDER BY t.name';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(array_merge([Auth::id()], $ids));
        $map = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $map[(int) $row['item_id']][] = $row;
        }
        foreach ($items as &$item) {
            $item['tags'] = $map[(int) $item['id']] ?? [];
        }
        return $items;
    }
}
