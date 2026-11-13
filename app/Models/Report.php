<?php
namespace App\Models;

class Report extends Model
{
    protected static string $table = 'reports';

    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    public static function create(array $data): int
    {
        $sql = "INSERT INTO reports (reporter_user_id, video_id, comment_id, reason, details, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'open', NOW())";
        return self::db()->insert($sql, [
            $data['reporter_user_id'] ?? null,
            $data['video_id'] ?? null,
            $data['comment_id'] ?? null,
            $data['reason'],
            $data['details'] ?? null,
        ]);
    }

    public static function allOpen(): array
    {
        return self::db()->fetchAll(
            "SELECT r.*, u.username AS reporter, v.title AS video_title, c.body AS comment_body
             FROM reports r
             LEFT JOIN users u ON u.id = r.reporter_user_id
             LEFT JOIN videos v ON v.id = r.video_id
             LEFT JOIN comments c ON c.id = r.comment_id
             WHERE r.status = 'open'
             ORDER BY r.created_at DESC"
        );
    }

    public static function resolve(int $id): void
    {
        self::db()->execute("UPDATE reports SET status = ? WHERE id = ?", [self::STATUS_RESOLVED, $id]);
    }

    public static function dismiss(int $id): void
    {
        self::db()->execute("UPDATE reports SET status = ? WHERE id = ?", [self::STATUS_DISMISSED, $id]);
    }
}
