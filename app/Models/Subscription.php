<?php
namespace App\Models;

class Subscription extends Model
{
    protected static string $table = 'subscriptions';

    public static function exists(int $userId, int $channelId): bool
    {
        return (bool)self::db()->column(
            "SELECT 1 FROM subscriptions WHERE subscriber_user_id = ? AND channel_id = ?",
            [$userId, $channelId]
        );
    }

    public static function toggle(int $userId, int $channelId): bool
    {
        if (self::exists($userId, $channelId)) {
            self::db()->execute(
                "DELETE FROM subscriptions WHERE subscriber_user_id = ? AND channel_id = ?",
                [$userId, $channelId]
            );
            return false;
        }
        self::db()->execute(
            "INSERT INTO subscriptions (subscriber_user_id, channel_id, created_at) VALUES (?, ?, NOW())",
            [$userId, $channelId]
        );
        return true;
    }

    public static function countForChannel(int $channelId): int
    {
        return (int)self::db()->column(
            "SELECT COUNT(*) FROM subscriptions WHERE channel_id = ?",
            [$channelId]
        );
    }
}
