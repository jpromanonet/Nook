<?php

declare(strict_types=1);

final class Schema
{
    public static function ensure(): void
    {
        $pdo = Database::pdo();
        $exists = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$exists) {
            $sqlFile = dirname(__DIR__, 2) . '/sql/schema.sql';
            if (!is_file($sqlFile)) {
                throw new RuntimeException('Missing sql/schema.sql');
            }

            $sql = (string) file_get_contents($sqlFile);
            foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '' || str_starts_with($stmt, '--')) {
                    continue;
                }
                if (preg_match('/^(SET|CREATE DATABASE|USE)\b/i', $stmt)) {
                    continue;
                }
                $pdo->exec($stmt);
            }
        }

        self::migrate($pdo);
    }

    private static function migrate(PDO $pdo): void
    {
        $sort = $pdo->query("SHOW COLUMNS FROM items LIKE 'sort_order'")->fetch();
        if (!$sort) {
            $pdo->exec('ALTER TABLE items ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER is_pinned');
            $pdo->exec('UPDATE items SET sort_order = id WHERE sort_order = 0');
        }

        $wsSort = $pdo->query("SHOW COLUMNS FROM workspaces LIKE 'sort_order'")->fetch();
        if (!$wsSort) {
            $pdo->exec('ALTER TABLE workspaces ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER end_date');
            $pdo->exec('UPDATE workspaces SET sort_order = id WHERE sort_order = 0');
        }
    }
}
