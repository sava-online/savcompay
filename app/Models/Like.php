<?php
namespace App\Models;

class Like extends Model
{
    protected static string $table = 'likes';

    public static function exists(int $userId, int $videoId): bool
    {
        return (bool)self::db()->column(
            "SELECT 1 FROM likes WHERE user_id = ? AND video_id = ?",
            [$userId, $videoId]
        );
    }

    /** Toggle like; updates video.like_count. Returns true if now liked. */
    public static function toggle(int $userId, int $videoId): bool
    {
        $db = self::db();
        if (self::exists($userId, $videoId)) {
            $db->execute("DELETE FROM likes WHERE user_id = ? AND video_id = ?", [$userId, $videoId]);
            $db->execute("UPDATE videos SET like_count = GREATEST(0, like_count - 1) WHERE id = ?", [$videoId]);
            return false;
        }
        $db->execute("INSERT INTO likes (user_id, video_id, created_at) VALUES (?, ?, NOW())", [$userId, $videoId]);
        $db->execute("UPDATE videos SET like_count = like_count + 1 WHERE id = ?", [$videoId]);
        return true;
    }
}
