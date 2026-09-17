<?php

declare(strict_types=1);

final class ItemService
{
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT i.*, w.name AS workspace_name, w.color AS workspace_color
             FROM items i
             LEFT JOIN workspaces w ON w.id = i.workspace_id
             WHERE i.id = :id AND i.user_id = :uid AND i.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'uid' => Auth::id()]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['tags'] = TagService::forItem((int) $row['id']);
        $row['meta_decoded'] = item_meta($row);
        return $row;
    }

    public static function require(int $id): array
    {
        $row = self::find($id);
        if (!$row) {
            http_response_code(404);
            view('partials/404', ['title' => 'No encontrado']);
            exit;
        }
        return $row;
    }

    public static function list(array $filters = []): array
    {
        $where = ['i.user_id = :uid', 'i.deleted_at IS NULL'];
        $params = ['uid' => Auth::id()];

        if (!empty($filters['workspace_id'])) {
            $where[] = 'i.workspace_id = :wid';
            $params['wid'] = (int) $filters['workspace_id'];
        } elseif (($filters['inbox'] ?? false) === true) {
            $where[] = 'i.workspace_id IS NULL';
        }

        if (!empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $parts = [];
                foreach (array_values($filters['type']) as $i => $type) {
                    $key = 'type' . $i;
                    $parts[] = ':' . $key;
                    $params[$key] = $type;
                }
                $where[] = 'i.type IN (' . implode(',', $parts) . ')';
            } else {
                $where[] = 'i.type = :type';
                $params['type'] = $filters['type'];
            }
        }

        if (!empty($filters['status'])) {
            $where[] = 'i.status = :status';
            $params['status'] = $filters['status'];
        }

        if (($filters['archived'] ?? null) === true) {
            $where[] = 'i.archived_at IS NOT NULL';
        } elseif (($filters['archived'] ?? null) !== 'any') {
            $where[] = 'i.archived_at IS NULL';
        }

        if (!empty($filters['favorite'])) {
            $where[] = 'i.is_favorite = 1';
        }
        if (!empty($filters['pinned'])) {
            $where[] = 'i.is_pinned = 1';
        }
        if (array_key_exists('parent_id', $filters) && $filters['parent_id'] !== null && $filters['parent_id'] !== '') {
            $where[] = 'i.parent_id = :pid';
            $params['pid'] = (int) $filters['parent_id'];
        } elseif (($filters['root_only'] ?? false) && empty($filters['q'])) {
            $where[] = 'i.parent_id IS NULL';
        }

        if (!empty($filters['folder_scope'])) {
            $scope = (string) $filters['folder_scope'];
            $params['fscope'] = $scope;
            $folderMatch = "(i.type = 'folder' AND (
                JSON_UNQUOTE(JSON_EXTRACT(i.meta, '$.scope')) = :fscope
                OR (JSON_EXTRACT(i.meta, '$.scope') IS NULL AND :fscope_docs = 'documents')
            ))";
            $params['fscope_docs'] = $scope;
            if ($scope === 'media') {
                $where[] = "({$folderMatch} OR i.type IN ('image','audio','video') OR (i.type = 'file' AND (
                    i.mime_type LIKE 'image/%' OR i.mime_type LIKE 'audio/%' OR i.mime_type LIKE 'video/%'
                )))";
            } elseif ($scope === 'files') {
                $where[] = "({$folderMatch} OR (i.type = 'file' AND NOT (
                    COALESCE(i.mime_type, '') LIKE 'image/%'
                    OR COALESCE(i.mime_type, '') LIKE 'audio/%'
                    OR COALESCE(i.mime_type, '') LIKE 'video/%'
                )))";
            } else {
                $where[] = "({$folderMatch} OR i.type = 'document')";
            }
        }
        if (!empty($filters['due_on'])) {
            $where[] = 'i.due_date = :due_on';
            $params['due_on'] = $filters['due_on'];
        }
        if (!empty($filters['due_before'])) {
            $where[] = 'i.due_date < :due_before AND i.due_date IS NOT NULL';
            $params['due_before'] = $filters['due_before'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(i.title LIKE :q1 OR i.description LIKE :q2 OR i.content LIKE :q3)';
            $like = '%' . $filters['q'] . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }
        if (!empty($filters['due_from'])) {
            $where[] = '(i.due_date BETWEEN :due_from AND :due_to OR i.start_date BETWEEN :due_from2 AND :due_to2)';
            $params['due_from'] = $filters['due_from'];
            $params['due_to'] = $filters['due_to'] ?? $filters['due_from'];
            $params['due_from2'] = $params['due_from'];
            $params['due_to2'] = $params['due_to'];
        }
        if (!empty($filters['tag'])) {
            $where[] = 'EXISTS (
                SELECT 1 FROM item_tags it
                INNER JOIN tags t ON t.id = it.tag_id
                WHERE it.item_id = i.id AND t.user_id = :uid_tag AND t.slug = :tag
            )';
            $params['uid_tag'] = Auth::id();
            $params['tag'] = $filters['tag'];
        }

        $order = $filters['order'] ?? 'i.updated_at DESC';
        $limit = (int) ($filters['limit'] ?? 80);

        $sql = 'SELECT i.*, w.name AS workspace_name, w.color AS workspace_color
                FROM items i
                LEFT JOIN workspaces w ON w.id = i.workspace_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ' . $order . '
                LIMIT ' . $limit;
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
        return TagService::attachToItems($rows);
    }

    public static function create(array $data, ?array $file = null): int
    {
        $type = (string) ($data['type'] ?? 'note');
        if (!isset(item_types()[$type])) {
            throw new InvalidArgumentException('Tipo de elemento inválido.');
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = self::defaultTitle($type);
        }

        $workspaceId = null_if_blank($data['workspace_id'] ?? null);
        if ($workspaceId !== null) {
            WorkspaceService::require((int) $workspaceId);
            $workspaceId = (int) $workspaceId;
        } else {
            throw new InvalidArgumentException('Elegí un workspace.');
        }

        $parentId = null_if_blank($data['parent_id'] ?? null);
        if ($parentId !== null) {
            $parent = self::require((int) $parentId);
            $parentId = (int) $parent['id'];
            if ($workspaceId === null && !empty($parent['workspace_id'])) {
                $workspaceId = (int) $parent['workspace_id'];
            }
        }

        $status = self::normalizeStatus($type, (string) ($data['status'] ?? ''));
        $meta = self::metaFromInput($type, $data);
        $upload = null;
        $maxUpload = (int) app_config('max_upload', 200 * 1024 * 1024);
        if ($file && !empty($file['name'])) {
            $upload = UploadService::store(
                $file,
                'files',
                UploadService::fileMap(),
                $maxUpload
            );
        } elseif (!empty($data['chunk_upload_id'])) {
            $upload = UploadService::assembleChunks(
                (string) $data['chunk_upload_id'],
                'files',
                UploadService::fileMap(),
                $maxUpload
            );
        }
        if ($upload) {
            $mediaType = UploadService::itemTypeForMime($upload['mime']);
            if ($mediaType !== null) {
                $type = $mediaType;
            }
            if (in_array($title, [
                self::defaultTitle('file'),
                self::defaultTitle('image'),
                self::defaultTitle('audio'),
                self::defaultTitle('video'),
            ], true)) {
                $title = $upload['original'];
            }
        }

        $sortOrder = self::nextSortOrder($workspaceId, $parentId);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO items
                (user_id, workspace_id, parent_id, type, title, description, content, status, priority,
                 start_date, due_date, is_favorite, is_pinned, sort_order, meta, stored_filename, original_filename, mime_type, file_size)
             VALUES
                (:uid, :wid, :pid, :type, :title, :description, :content, :status, :priority,
                 :start_date, :due_date, :fav, :pin, :sort_order, :meta, :stored, :original, :mime, :size)'
        );
        $stmt->execute([
            'uid' => Auth::id(),
            'wid' => $workspaceId,
            'pid' => $parentId,
            'type' => $type,
            'title' => $title,
            'description' => null_if_blank($data['description'] ?? null),
            'content' => null_if_blank($data['content'] ?? null),
            'status' => $status,
            'priority' => self::normalizePriority($data['priority'] ?? null),
            'start_date' => null_if_blank($data['start_date'] ?? null),
            'due_date' => null_if_blank($data['due_date'] ?? null),
            'fav' => empty($data['is_favorite']) ? 0 : 1,
            'pin' => empty($data['is_pinned']) ? 0 : 1,
            'sort_order' => $sortOrder,
            'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'stored' => $upload['stored'] ?? null,
            'original' => $upload['original'] ?? null,
            'mime' => $upload['mime'] ?? null,
            'size' => $upload['size'] ?? null,
        ]);
        $id = (int) Database::pdo()->lastInsertId();
        TagService::sync($id, parse_tags_input((string) ($data['tags'] ?? '')));
        ActivityService::log(
            'item.created',
            'Created ' . $type . ' "' . $title . '"',
            $workspaceId,
            $id
        );
        return $id;
    }

    public static function update(int $id, array $data, ?array $file = null): void
    {
        $item = self::require($id);
        $type = (string) ($data['type'] ?? $item['type']);
        if (!isset(item_types()[$type])) {
            $type = $item['type'];
        }
        $title = trim((string) ($data['title'] ?? $item['title']));
        if ($title === '') {
            $title = $item['title'];
        }
        $workspaceId = null_if_blank($data['workspace_id'] ?? $item['workspace_id']);
        if ($workspaceId !== null) {
            WorkspaceService::require((int) $workspaceId);
            $workspaceId = (int) $workspaceId;
        } else {
            throw new InvalidArgumentException('Elegí un workspace.');
        }

        $upload = null;
        $maxUpload = (int) app_config('max_upload', 200 * 1024 * 1024);
        if ($file && !empty($file['name'])) {
            $upload = UploadService::store(
                $file,
                'files',
                UploadService::fileMap(),
                $maxUpload
            );
        } elseif (!empty($data['chunk_upload_id'])) {
            $upload = UploadService::assembleChunks(
                (string) $data['chunk_upload_id'],
                'files',
                UploadService::fileMap(),
                $maxUpload
            );
        }
        if ($upload) {
            if (!empty($item['stored_filename'])) {
                UploadService::delete('files', (string) $item['stored_filename']);
            }
            $mediaType = UploadService::itemTypeForMime($upload['mime']);
            if ($mediaType !== null) {
                $type = $mediaType;
            }
        }

        $meta = self::metaFromInput($type, $data, item_meta($item));
        $stmt = Database::pdo()->prepare(
            'UPDATE items SET
                workspace_id = :wid, type = :type, title = :title, description = :description,
                content = :content, status = :status, priority = :priority, start_date = :start_date,
                due_date = :due_date, meta = :meta,
                stored_filename = COALESCE(:stored, stored_filename),
                original_filename = COALESCE(:original, original_filename),
                mime_type = COALESCE(:mime, mime_type),
                file_size = COALESCE(:size, file_size)
             WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'wid' => $workspaceId,
            'type' => $type,
            'title' => $title,
            'description' => null_if_blank($data['description'] ?? null),
            'content' => array_key_exists('content', $data) ? null_if_blank($data['content']) : $item['content'],
            'status' => self::normalizeStatus($type, (string) ($data['status'] ?? $item['status'])),
            'priority' => self::normalizePriority($data['priority'] ?? $item['priority']),
            'start_date' => null_if_blank($data['start_date'] ?? null),
            'due_date' => null_if_blank($data['due_date'] ?? null),
            'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'stored' => $upload['stored'] ?? null,
            'original' => $upload['original'] ?? null,
            'mime' => $upload['mime'] ?? null,
            'size' => $upload['size'] ?? null,
            'id' => $id,
            'uid' => Auth::id(),
        ]);
        TagService::sync($id, parse_tags_input((string) ($data['tags'] ?? tags_to_string($item['tags'] ?? []))));
        ActivityService::log('item.updated', 'Updated "' . $title . '"', $workspaceId, $id);
    }

    public static function toggle(int $id, string $field): void
    {
        if (!in_array($field, ['is_favorite', 'is_pinned'], true)) {
            return;
        }
        $item = self::require($id);
        $next = ((int) $item[$field]) ? 0 : 1;
        $stmt = Database::pdo()->prepare(
            "UPDATE items SET {$field} = :v WHERE id = :id AND user_id = :uid"
        );
        $stmt->execute(['v' => $next, 'id' => $id, 'uid' => Auth::id()]);
    }

    public static function archive(int $id): void
    {
        $item = self::require($id);
        $stmt = Database::pdo()->prepare(
            "UPDATE items SET archived_at = NOW(), status = IF(type = 'task', status, 'archived')
             WHERE id = :id AND user_id = :uid"
        );
        $stmt->execute(['id' => $id, 'uid' => Auth::id()]);
        ActivityService::log('item.archived', 'Archived "' . $item['title'] . '"', $item['workspace_id'] ? (int) $item['workspace_id'] : null, $id);
    }

    public static function restore(int $id): void
    {
        $item = self::require($id);
        $stmt = Database::pdo()->prepare(
            "UPDATE items SET archived_at = NULL, deleted_at = NULL,
                    status = IF(type = 'task' AND status = 'archived', 'todo', IF(status = 'archived', 'active', status))
             WHERE id = :id AND user_id = :uid"
        );
        $stmt->execute(['id' => $id, 'uid' => Auth::id()]);
        ActivityService::log('item.restored', 'Restored "' . $item['title'] . '"', $item['workspace_id'] ? (int) $item['workspace_id'] : null, $id);
    }

    public static function delete(int $id): void
    {
        $item = self::require($id);
        $stmt = Database::pdo()->prepare(
            'UPDATE items SET deleted_at = NOW() WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute(['id' => $id, 'uid' => Auth::id()]);
        ActivityService::log('item.deleted', 'Deleted "' . $item['title'] . '"', $item['workspace_id'] ? (int) $item['workspace_id'] : null, $id);
    }

    public static function setStatus(int $id, string $status): void
    {
        $item = self::require($id);
        if ($item['type'] !== 'task') {
            throw new InvalidArgumentException('Solo las tareas tienen estado de tablero.');
        }
        $status = self::normalizeStatus('task', $status);
        $stmt = Database::pdo()->prepare(
            'UPDATE items SET status = :status WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute(['status' => $status, 'id' => $id, 'uid' => Auth::id()]);
    }

    public static function moveTask(int $id, string $status, ?int $workspaceId): void
    {
        $item = self::require($id);
        if ($item['type'] !== 'task') {
            throw new InvalidArgumentException('Solo se pueden mover tareas.');
        }
        $status = self::normalizeStatus('task', $status);
        if ($workspaceId !== null) {
            WorkspaceService::require($workspaceId);
        }
        $stmt = Database::pdo()->prepare(
            'UPDATE items SET status = :status, workspace_id = :wid WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'status' => $status,
            'wid' => $workspaceId,
            'id' => $id,
            'uid' => Auth::id(),
        ]);
        ActivityService::log(
            'item.moved',
            'Moved task "' . $item['title'] . '"',
            $workspaceId,
            $id
        );
    }

    public static function convert(int $id, string $toType): void
    {
        $item = self::require($id);
        if (!isset(item_types()[$toType])) {
            throw new InvalidArgumentException('Tipo inválido.');
        }
        $status = $toType === 'task' ? 'todo' : 'active';
        $stmt = Database::pdo()->prepare(
            'UPDATE items SET type = :type, status = :status WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute(['type' => $toType, 'status' => $status, 'id' => $id, 'uid' => Auth::id()]);
        ActivityService::log('item.converted', 'Converted "' . $item['title'] . '" to ' . $toType, $item['workspace_id'] ? (int) $item['workspace_id'] : null, $id);
    }

    public static function classify(int $id, ?int $workspaceId, ?string $toType = null): void
    {
        $item = self::require($id);
        if ($workspaceId !== null) {
            WorkspaceService::require($workspaceId);
        }
        $type = $toType && isset(item_types()[$toType]) ? $toType : $item['type'];
        $status = $type === 'task' && $item['status'] === 'inbox' ? 'todo' : $item['status'];
        $stmt = Database::pdo()->prepare(
            'UPDATE items SET workspace_id = :wid, type = :type, status = :status WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'wid' => $workspaceId,
            'type' => $type,
            'status' => $status,
            'id' => $id,
            'uid' => Auth::id(),
        ]);
    }

    public static function setChecklist(int $id, array $items): void
    {
        $row = self::require($id);
        $meta = item_meta($row);
        $clean = [];
        foreach ($items as $entry) {
            $text = trim((string) ($entry['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $clean[] = ['text' => $text, 'done' => !empty($entry['done'])];
        }
        $meta['checklist'] = $clean;
        $stmt = Database::pdo()->prepare(
            'UPDATE items SET meta = :meta WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            'id' => $id,
            'uid' => Auth::id(),
        ]);
    }

    public static function toggleChecklistItem(int $id, int $index): void
    {
        $row = self::require($id);
        $meta = item_meta($row);
        $list = $meta['checklist'] ?? [];
        if (!isset($list[$index])) {
            return;
        }
        $list[$index]['done'] = empty($list[$index]['done']);
        $meta['checklist'] = $list;
        $stmt = Database::pdo()->prepare(
            'UPDATE items SET meta = :meta WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            'id' => $id,
            'uid' => Auth::id(),
        ]);
    }

    public static function children(int $parentId): array
    {
        return self::list(['parent_id' => $parentId, 'order' => "i.type = 'folder' DESC, i.sort_order ASC, i.title"]);
    }

    public static function nextSortOrder(?int $workspaceId, ?int $parentId): int
    {
        $sql = 'SELECT COALESCE(MAX(sort_order), 0) + 10 AS next_order
                FROM items
                WHERE user_id = :uid AND deleted_at IS NULL';
        $params = ['uid' => Auth::id()];
        if ($workspaceId === null) {
            $sql .= ' AND workspace_id IS NULL';
        } else {
            $sql .= ' AND workspace_id = :wid';
            $params['wid'] = $workspaceId;
        }
        if ($parentId === null) {
            $sql .= ' AND parent_id IS NULL';
        } else {
            $sql .= ' AND parent_id = :pid';
            $params['pid'] = $parentId;
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) ($stmt->fetchColumn() ?: 10);
    }

    public static function reorder(array $orderedIds, ?int $parentId = null): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'UPDATE items SET sort_order = :ord
             WHERE id = :id AND user_id = :uid AND deleted_at IS NULL
               AND ((parent_id IS NULL AND :pid_null = 1) OR parent_id = :pid)'
        );
        $ord = 10;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $stmt->execute([
                'ord' => $ord,
                'id' => $id,
                'uid' => Auth::id(),
                'pid_null' => $parentId === null ? 1 : 0,
                'pid' => $parentId,
            ]);
            $ord += 10;
        }
    }

    public static function moveToFolder(int $id, ?int $folderId): void
    {
        $item = self::require($id);
        if ($folderId !== null) {
            $folder = self::require($folderId);
            if ($folder['type'] !== 'folder') {
                throw new InvalidArgumentException('El destino no es una carpeta.');
            }
            if ((int) ($folder['workspace_id'] ?? 0) !== (int) ($item['workspace_id'] ?? 0)) {
                throw new InvalidArgumentException('La carpeta pertenece a otro workspace.');
            }
            // Prevent moving a folder into itself or a descendant.
            if ($item['type'] === 'folder') {
                $cursor = $folder;
                while (!empty($cursor['parent_id'])) {
                    if ((int) $cursor['parent_id'] === $id) {
                        throw new InvalidArgumentException('No se puede mover una carpeta dentro de sí misma.');
                    }
                    $cursor = self::require((int) $cursor['parent_id']);
                }
                if ((int) $folder['id'] === $id) {
                    throw new InvalidArgumentException('No se puede mover una carpeta dentro de sí misma.');
                }
            }
        }

        $sort = self::nextSortOrder(
            $item['workspace_id'] !== null ? (int) $item['workspace_id'] : null,
            $folderId
        );
        $stmt = Database::pdo()->prepare(
            'UPDATE items SET parent_id = :pid, sort_order = :ord WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'pid' => $folderId,
            'ord' => $sort,
            'id' => $id,
            'uid' => Auth::id(),
        ]);
        ActivityService::log(
            'item.moved',
            'Moved "' . $item['title'] . '"',
            $item['workspace_id'] ? (int) $item['workspace_id'] : null,
            $id
        );
    }

    public static function breadcrumbs(?int $folderId): array
    {
        $crumbs = [];
        $seen = [];
        while ($folderId) {
            if (isset($seen[$folderId])) {
                break;
            }
            $seen[$folderId] = true;
            $folder = self::find($folderId);
            if (!$folder || $folder['type'] !== 'folder') {
                break;
            }
            array_unshift($crumbs, $folder);
            $folderId = $folder['parent_id'] ? (int) $folder['parent_id'] : null;
        }
        return $crumbs;
    }

    public static function modulePath(string $scope, int $workspaceId, ?int $folderId = null): string
    {
        $base = match ($scope) {
            'media' => '/workspaces/' . $workspaceId . '/media',
            'files' => '/workspaces/' . $workspaceId . '/files',
            default => '/workspaces/' . $workspaceId . '/documents',
        };
        if ($folderId) {
            $base .= '?folder=' . $folderId;
        }
        return $base;
    }

    private static function defaultTitle(string $type): string
    {
        return match ($type) {
            'note' => 'Untitled note',
            'document' => 'Untitled document',
            'task' => 'Untitled task',
            'idea' => 'Untitled idea',
            'link' => 'Untitled link',
            'account' => 'Untitled account',
            'event' => 'Untitled event',
            'decision' => 'Untitled decision',
            'folder' => 'Untitled folder',
            'image' => 'Untitled image',
            'audio' => 'Untitled audio',
            'video' => 'Untitled video',
            default => 'Untitled',
        };
    }

    private static function normalizeStatus(string $type, string $status): string
    {
        if ($type === 'task') {
            if ($status === 'inbox') {
                return 'todo';
            }
            return isset(task_statuses()[$status]) ? $status : 'todo';
        }
        return in_array($status, ['active', 'archived'], true) ? $status : 'active';
    }

    private static function normalizePriority(mixed $priority): ?string
    {
        $priority = is_string($priority) ? $priority : '';
        if ($priority === '') {
            return 'normal';
        }
        return isset(priorities()[$priority]) ? $priority : 'normal';
    }

    private static function metaFromInput(string $type, array $data, array $existing = []): array
    {
        $meta = $existing;
        if ($type === 'link') {
            $meta['url'] = null_if_blank($data['url'] ?? ($existing['url'] ?? null));
            $meta['category'] = (string) ($data['category'] ?? ($existing['category'] ?? 'other'));
        }
        if ($type === 'account') {
            foreach (['platform', 'username', 'handle', 'account_email', 'url', 'owner', 'recovery_email', 'vault', 'vault_entry'] as $key) {
                $meta[$key] = null_if_blank($data[$key] ?? ($existing[$key] ?? null));
            }
            $meta['two_fa'] = !empty($data['two_fa']) || !empty($existing['two_fa']);
        }
        if ($type === 'event') {
            $meta['location'] = null_if_blank($data['location'] ?? ($existing['location'] ?? null));
            $meta['all_day'] = !empty($data['all_day']);
        }
        if ($type === 'decision') {
            foreach (['context', 'alternatives', 'reason', 'consequences'] as $key) {
                $meta[$key] = null_if_blank($data[$key] ?? ($existing[$key] ?? null));
            }
        }
        if ($type === 'task') {
            $meta['recurring'] = null_if_blank($data['recurring'] ?? ($existing['recurring'] ?? null));
        }
        if ($type === 'folder') {
            $scope = (string) ($data['scope'] ?? ($existing['scope'] ?? 'documents'));
            if (!in_array($scope, ['documents', 'files', 'media'], true)) {
                $scope = 'documents';
            }
            $meta['scope'] = $scope;
        }
        if (isset($data['checklist_text'])) {
            $lines = preg_split('/\r\n|\r|\n/', (string) $data['checklist_text']) ?: [];
            $old = $existing['checklist'] ?? [];
            $oldMap = [];
            foreach ($old as $entry) {
                $oldMap[(string) ($entry['text'] ?? '')] = !empty($entry['done']);
            }
            $list = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $done = false;
                if (preg_match('/^\[x\]\s+/i', $line)) {
                    $done = true;
                    $line = preg_replace('/^\[x\]\s+/i', '', $line) ?? $line;
                } elseif (preg_match('/^\[ \]\s+/', $line)) {
                    $line = preg_replace('/^\[ \]\s+/', '', $line) ?? $line;
                } elseif (isset($oldMap[$line])) {
                    $done = $oldMap[$line];
                }
                $list[] = ['text' => $line, 'done' => $done];
            }
            $meta['checklist'] = $list;
        }
        return $meta;
    }
}
