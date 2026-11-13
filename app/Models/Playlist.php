<?php
namespace App\Models;

class Playlist extends Model
{
    protected static string $table = 'playlists';

    public const VIS_PRIVATE = 'private';
    public const VIS_PUBLIC = 'public';

    public static function ensureWatchLater(int $userId): int
    {
        $row = self::db()->fetch(
            "SELECT id FROM playlists WHERE user_id = ? AND is_watch_later = 1",
            [$userId]
        );
        if ($row) {
            return (int)$row['id'];
        }
        $slug = self::slugify('watch-later-' . $userId);
        return self::db()->insert(
            "INSERT INTO playlists (user_id, name, slug, visibility, is_watch_later, created_at)
             VALUES (?, 'Смотреть позже', ?, 'private', 1, NOW())",
            [$userId, $slug]
        );
    }

    public static function createPlaylist(int $userId, string $name, string $visibility, ?string $description = null): int
    {
        $slug = self::uniqueSlug(self::slugify($name) ?: 'playlist');
        return self::db()->insert(
            "INSERT INTO playlists (user_id, name, slug, description, visibility, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$userId, $name, $slug, $description, $visibility]
        );
    }

    public static function findByUser(int $userId): array
    {
        return self::db()->fetchAll(
            "SELECT p.*, (SELECT COUNT(*) FROM playlist_items pi WHERE pi.playlist_id = p.id) AS item_count
             FROM playlists p WHERE p.user_id = ? ORDER BY p.is_watch_later DESC, p.created_at DESC",
            [$userId]
        );
    }

    public static function findById(int $id): ?array
    {
        return self::db()->fetch("SELECT * FROM playlists WHERE id = ?", [$id]);
    }

    public static function findVisible(int $id, ?int $userId): ?array
    {
        if ($userId !== null) {
            return self::db()->fetch(
                "SELECT * FROM playlists WHERE id = ? AND (visibility = 'public' OR user_id = ?)",
                [$id, $userId]
            );
        }
        return self::db()->fetch("SELECT * FROM playlists WHERE id = ? AND visibility = 'public'", [$id]);
    }

    public static function addItem(int $playlistId, int $videoId): void
    {
        $pos = (int)self::db()->column(
            "SELECT COALESCE(MAX(position),0)+1 FROM playlist_items WHERE playlist_id = ?",
            [$playlistId]
        );
        self::db()->execute(
            "INSERT IGNORE INTO playlist_items (playlist_id, video_id, position, created_at) VALUES (?, ?, ?, NOW())",
            [$playlistId, $videoId, $pos]
        );
    }

    public static function removeItem(int $playlistId, int $videoId): void
    {
        self::db()->execute(
            "DELETE FROM playlist_items WHERE playlist_id = ? AND video_id = ?",
            [$playlistId, $videoId]
        );
    }

    public static function items(int $playlistId): array
    {
        $sql = "SELECT v.*, pi.position, c.name AS channel_name, c.slug AS channel_slug,
                       cat.name AS category_name, cat.slug AS category_slug
                FROM playlist_items pi
                JOIN videos v ON v.id = pi.video_id
                JOIN channels c ON c.id = v.channel_id
                LEFT JOIN categories cat ON cat.id = v.category_id
                WHERE pi.playlist_id = ? AND v.status = ? AND v.visibility = ?
                ORDER BY pi.position ASC";
        return self::db()->fetchAll($sql, [$playlistId, Video::STATUS_PUBLISHED, Video::VIS_PUBLIC]);
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
        return self::db()->execute("UPDATE playlists SET " . implode(', ', $sets) . " WHERE id = ?", $params);
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/[^a-z0-9а-яё]+/u', '-', $text);
        return trim($text, '-') ?: 'playlist';
    }

    public static function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 1;
        while (self::db()->fetch("SELECT id FROM playlists WHERE slug = ?", [$slug])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
