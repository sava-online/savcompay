<?php
namespace App\Models;

class WatchHistory extends Model
{
    protected static string $table = 'watch_history';

    /** Insert or update progress for a user/video. */
    public static function upsert(int $userId, int $videoId, int $progress): void
    {
        $exists = self::db()->column(
            "SELECT 1 FROM watch_history WHERE user_id = ? AND video_id = ?",
            [$userId, $videoId]
        );
        if ($exists) {
            self::db()->execute(
                "UPDATE watch_history SET progress_seconds = ?, updated_at = NOW() WHERE user_id = ? AND video_id = ?",
                [$progress, $userId, $videoId]
            );
        } else {
            self::db()->execute(
                "INSERT INTO watch_history (user_id, video_id, progress_seconds, updated_at) VALUES (?, ?, ?, NOW())",
                [$userId, $videoId, $progress]
            );
        }
    }
}
