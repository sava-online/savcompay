<?php
namespace App\Models;

class Tag extends Model
{
    protected static string $table = 'tags';

    public static function findBySlug(string $slug): ?array
    {
        return self::db()->fetch("SELECT * FROM tags WHERE slug = ?", [$slug]);
    }

    public static function findOrCreate(string $name): int
    {
        $slug = self::slugify($name);
        $existing = self::db()->fetch("SELECT id FROM tags WHERE slug = ?", [$slug]);
        if ($existing) {
            return (int)$existing['id'];
        }
        return self::db()->insert("INSERT INTO tags (name, slug) VALUES (?, ?)", [$name, $slug]);
    }

    public static function slugify(string $name): string
    {
        $text = mb_strtolower(trim($name), 'UTF-8');
        $text = preg_replace('/[^a-z0-9а-яё]+/u', '-', $text);
        return trim($text, '-') ?: 'tag';
    }

    public static function attach(int $videoId, array $tagIds): void
    {
        foreach ($tagIds as $tagId) {
            self::db()->execute(
                "INSERT IGNORE INTO video_tags (video_id, tag_id) VALUES (?, ?)",
                [$videoId, $tagId]
            );
        }
    }

    public static function forVideo(int $videoId): array
    {
        return self::db()->fetchAll(
            "SELECT t.* FROM tags t
             JOIN video_tags vt ON vt.tag_id = t.id
             WHERE vt.video_id = ? ORDER BY t.name ASC",
            [$videoId]
        );
    }

    public static function detachAll(int $videoId): void
    {
        self::db()->execute("DELETE FROM video_tags WHERE video_id = ?", [$videoId]);
    }
}
