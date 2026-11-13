<?php
namespace App\Models;

class Video extends Model
{
    protected static string $table = 'videos';

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_PENDING = 'pending_review';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_FAILED = 'failed';

    public const VIS_PUBLIC = 'public';
    public const VIS_UNLISTED = 'unlisted';
    public const VIS_PRIVATE = 'private';

    public static function create(array $data): int
    {
        $sql = "INSERT INTO videos
            (user_id, channel_id, category_id, title, slug, description, original_filename,
             storage_path, mime_type, size_bytes, visibility, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        return self::db()->insert($sql, [
            $data['user_id'],
            $data['channel_id'],
            $data['category_id'] ?? null,
            $data['title'],
            $data['slug'],
            $data['description'] ?? null,
            $data['original_filename'] ?? null,
            $data['storage_path'],
            $data['mime_type'],
            $data['size_bytes'] ?? 0,
            $data['visibility'] ?? self::VIS_PUBLIC,
            $data['status'] ?? self::STATUS_PROCESSING,
        ]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return self::db()->fetch("SELECT * FROM videos WHERE slug = ?", [$slug]);
    }

    /** Video with joined channel + category, used for the watch page. */
    public static function withRelations(int $id): ?array
    {
        $sql = "SELECT v.*, c.name AS channel_name, c.slug AS channel_slug, c.avatar_path AS channel_avatar,
                       cat.name AS category_name, cat.slug AS category_slug,
                       u.username AS owner_username
                FROM videos v
                JOIN channels c ON c.id = v.channel_id
                JOIN users u ON u.id = v.user_id
                LEFT JOIN categories cat ON cat.id = v.category_id
                WHERE v.id = ?";
        return self::db()->fetch($sql, [$id]);
    }

    public static function update(int $id, array $fields): int
    {
        $sets = [];
        $params = [];
        foreach ($fields as $k => $v) {
            $sets[] = "$k = ?";
            $params[] = $v;
        }
        $params[] = $id;
        $sql = "UPDATE videos SET " . implode(', ', $sets) . " WHERE id = ?";
        return self::db()->execute($sql, $params);
    }

    public static function incrementViews(int $id): void
    {
        self::db()->execute("UPDATE videos SET view_count = view_count + 1 WHERE id = ?", [$id]);
    }

    public static function delete(int $id): void
    {
        self::db()->execute("DELETE FROM videos WHERE id = ?", [$id]);
    }

    /**
     * List published (or permitted) videos with filters.
     * Whitelists sort column/order to avoid SQL injection.
     */
    public static function list(array $opts = []): array
    {
        $where = [];
        $params = [];

        $where[] = "v.status = ?";
        $params[] = self::STATUS_PUBLISHED;

        if (($opts['visibility'] ?? null) === 'public') {
            $where[] = "v.visibility = ?";
            $params[] = self::VIS_PUBLIC;
        }

        if (!empty($opts['category_id'])) {
            $where[] = "v.category_id = ?";
            $params[] = $opts['category_id'];
        }
        if (!empty($opts['channel_id'])) {
            $where[] = "v.channel_id = ?";
            $params[] = $opts['channel_id'];
        }
        if (!empty($opts['user_id'])) {
            $where[] = "v.user_id = ?";
            $params[] = $opts['user_id'];
        }
        if (!empty($opts['tag_id'])) {
            $where[] = "v.id IN (SELECT video_id FROM video_tags WHERE tag_id = ?)";
            $params[] = $opts['tag_id'];
        }
        if (!empty($opts['q'])) {
            $like = '%' . $opts['q'] . '%';
            $where[] = "(v.title LIKE ? OR v.description LIKE ?)";
            $params[] = $like;
            $params[] = $like;
        }

        $orderMap = [
            'new'       => 'v.published_at DESC',
            'popular'   => 'v.view_count DESC',
            'views'     => 'v.view_count DESC',
            'date'      => 'v.published_at DESC',
            'title'     => 'v.title ASC',
        ];
        $order = $orderMap[$opts['sort'] ?? 'new'] ?? 'v.published_at DESC';

        $limit = (int)($opts['limit'] ?? 12);
        $offset = (int)($opts['offset'] ?? 0);
        $limit = max(1, min($limit, 60));
        $offset = max(0, $offset);

        $sql = "SELECT v.*, c.name AS channel_name, c.slug AS channel_slug, c.avatar_path AS channel_avatar,
                       cat.name AS category_name, cat.slug AS category_slug
                FROM videos v
                JOIN channels c ON c.id = v.channel_id
                LEFT JOIN categories cat ON cat.id = v.category_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY $order
                LIMIT $limit OFFSET $offset";

        return self::db()->fetchAll($sql, $params);
    }

    public static function countList(array $opts = []): int
    {
        $where = ["v.status = ?"];
        $params = [self::STATUS_PUBLISHED];
        if (!empty($opts['category_id'])) { $where[] = "v.category_id = ?"; $params[] = $opts['category_id']; }
        if (!empty($opts['channel_id'])) { $where[] = "v.channel_id = ?"; $params[] = $opts['channel_id']; }
        if (!empty($opts['user_id'])) { $where[] = "v.user_id = ?"; $params[] = $opts['user_id']; }
        if (!empty($opts['tag_id'])) { $where[] = "v.id IN (SELECT video_id FROM video_tags WHERE tag_id = ?)"; $params[] = $opts['tag_id']; }
        if (!empty($opts['q'])) { $like='%'.$opts['q'].'%'; $where[]="(v.title LIKE ? OR v.description LIKE ?)"; $params[]=$like; $params[]=$like; }
        if (($opts['visibility'] ?? null) === 'public') { $where[]="v.visibility = ?"; $params[]=self::VIS_PUBLIC; }

        return (int)self::db()->column("SELECT COUNT(*) FROM videos v WHERE " . implode(' AND ', $where), $params);
    }

    /** Full search across title, description, channel name and tags. */
    public static function search(string $q, array $opts = []): array
    {
        $where = ["v.status = ?", "v.visibility = ?"];
        $params = [self::STATUS_PUBLISHED, self::VIS_PUBLIC];
        $like = '%' . $q . '%';
        $where[] = "(v.title LIKE ? OR v.description LIKE ? OR c.name LIKE ?
                     OR v.id IN (SELECT vt.video_id FROM video_tags vt JOIN tags t ON t.id = vt.tag_id WHERE t.name LIKE ?))";
        $params = array_merge($params, [$like, $like, $like, $like]);

        if (!empty($opts['category_id'])) {
            $where[] = "v.category_id = ?";
            $params[] = $opts['category_id'];
        }

        $limit = max(1, min((int)($opts['limit'] ?? 24), 60));
        $offset = max(0, (int)($opts['offset'] ?? 0));

        $sql = "SELECT v.*, c.name AS channel_name, c.slug AS channel_slug, cat.name AS category_name, cat.slug AS category_slug
                FROM videos v
                JOIN channels c ON c.id = v.channel_id
                LEFT JOIN categories cat ON cat.id = v.category_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY v.published_at DESC
                LIMIT ? OFFSET ?";
        return self::db()->fetchAll($sql, array_merge($params, [$limit, $offset]));
    }

    public static function searchCount(string $q, array $opts = []): int
    {
        $where = ["v.status = ?", "v.visibility = ?"];
        $params = [self::STATUS_PUBLISHED, self::VIS_PUBLIC];
        $like = '%' . $q . '%';
        $where[] = "(v.title LIKE ? OR v.description LIKE ? OR c.name LIKE ?
                     OR v.id IN (SELECT vt.video_id FROM video_tags vt JOIN tags t ON t.id = vt.tag_id WHERE t.name LIKE ?))";
        $params = array_merge($params, [$like, $like, $like, $like]);
        if (!empty($opts['category_id'])) {
            $where[] = "v.category_id = ?";
            $params[] = $opts['category_id'];
        }
        return (int)self::db()->column(
            "SELECT COUNT(*) FROM videos v JOIN channels c ON c.id = v.channel_id WHERE " . implode(' AND ', $where),
            $params
        );
    }

    /** Videos from channels the user subscribes to. */
    public static function forSubscriptions(int $userId, int $limit = 24): array
    {
        $sql = "SELECT v.*, c.name AS channel_name, c.slug AS channel_slug, c.avatar_path AS channel_avatar,
                       cat.name AS category_name, cat.slug AS category_slug
                FROM videos v
                JOIN channels c ON c.id = v.channel_id
                LEFT JOIN categories cat ON cat.id = v.category_id
                WHERE v.status = ? AND v.visibility = ?
                  AND c.id IN (SELECT channel_id FROM subscriptions WHERE subscriber_user_id = ?)
                ORDER BY v.published_at DESC
                LIMIT ?";
        return self::db()->fetchAll($sql, [self::STATUS_PUBLISHED, self::VIS_PUBLIC, $userId, $limit]);
    }

    public static function continueWatching(int $userId, int $limit = 12): array
    {
        $sql = "SELECT v.*, wh.progress_seconds, c.name AS channel_name, c.slug AS channel_slug,
                       cat.name AS category_name, cat.slug AS category_slug
                FROM watch_history wh
                JOIN videos v ON v.id = wh.video_id
                JOIN channels c ON c.id = v.channel_id
                LEFT JOIN categories cat ON cat.id = v.category_id
                WHERE wh.user_id = ? AND v.status = ? AND wh.progress_seconds < (COALESCE(v.duration_seconds, 0) - 5)
                ORDER BY wh.updated_at DESC LIMIT ?";
        return self::db()->fetchAll($sql, [$userId, self::STATUS_PUBLISHED, $limit]);
    }

    public static function allForAdmin(): array
    {
        return self::db()->fetchAll(
            "SELECT v.*, c.name AS channel_name, u.username AS owner_username
             FROM videos v JOIN channels c ON c.id=v.channel_id JOIN users u ON u.id=v.user_id
             ORDER BY v.created_at DESC"
        );
    }

    /** All videos of a channel (any status) — for the channel owner. */
    public static function allByChannel(int $channelId): array
    {
        return self::db()->fetchAll(
            "SELECT v.*, c.name AS channel_name, c.slug AS channel_slug, cat.name AS category_name, cat.slug AS category_slug
             FROM videos v
             JOIN channels c ON c.id = v.channel_id
             LEFT JOIN categories cat ON cat.id = v.category_id
             WHERE v.channel_id = ?
             ORDER BY v.created_at DESC",
            [$channelId]
        );
    }

    public static function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 1;
        while (self::db()->fetch("SELECT id FROM videos WHERE slug = ?", [$slug])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/[^a-z0-9а-яё]+/u', '-', $text);
        return trim($text, '-') ?: 'video';
    }
}
