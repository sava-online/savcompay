<?php
namespace App\Models;

class Comment extends Model
{
    protected static string $table = 'comments';

    public const STATUS_VISIBLE = 'visible';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FLAGGED = 'flagged';

    public static function add(int $videoId, int $userId, string $body, ?int $parentId = null): int
    {
        $sql = "INSERT INTO comments (video_id, user_id, parent_id, body, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";
        return self::db()->insert($sql, [$videoId, $userId, $parentId, $body, self::STATUS_VISIBLE]);
    }

    public static function findById(int $id): ?array
    {
        return self::db()->fetch("SELECT * FROM comments WHERE id = ?", [$id]);
    }

    public static function listByVideo(int $videoId, int $page = 1, int $perPage = 20): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT c.*, u.username, u.avatar_path, ch.name AS channel_name
                FROM comments c
                JOIN users u ON u.id = c.user_id
                LEFT JOIN channels ch ON ch.user_id = u.id
                WHERE c.video_id = ? AND c.status = ?
                ORDER BY c.created_at ASC
                LIMIT ? OFFSET ?";
        return self::db()->fetchAll($sql, [$videoId, self::STATUS_VISIBLE, $perPage, $offset]);
    }

    public static function countByVideo(int $videoId): int
    {
        return (int)self::db()->column(
            "SELECT COUNT(*) FROM comments WHERE video_id = ? AND status = ?",
            [$videoId, self::STATUS_VISIBLE]
        );
    }

    public static function updateStatus(int $id, string $status): int
    {
        return self::db()->execute("UPDATE comments SET status = ? WHERE id = ?", [$status, $id]);
    }

    public static function delete(int $id): void
    {
        self::db()->execute("DELETE FROM comments WHERE id = ?", [$id]);
    }

    public static function allForAdmin(): array
    {
        return self::db()->fetchAll(
            "SELECT c.*, u.username, v.title AS video_title, v.id AS video_id
             FROM comments c JOIN users u ON u.id=c.user_id JOIN videos v ON v.id=c.video_id
             ORDER BY c.created_at DESC"
        );
    }
}
