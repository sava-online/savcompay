<?php
namespace App\Models;

class Channel extends Model
{
    protected static string $table = 'channels';

    public static function createForUser(int $userId, string $name, string $description = ''): int
    {
        $slug = self::uniqueSlug(self::slugify($name) ?: 'channel');
        $sql = "INSERT INTO channels (user_id, name, slug, description, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        return self::db()->insert($sql, [$userId, $name, $slug, $description ?: null]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return self::db()->fetch("SELECT * FROM channels WHERE slug = ?", [$slug]);
    }

    public static function findByUserId(int $userId): ?array
    {
        return self::db()->fetch("SELECT * FROM channels WHERE user_id = ?", [$userId]);
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
        $sql = "UPDATE channels SET " . implode(', ', $sets) . " WHERE id = ?";
        return self::db()->execute($sql, $params);
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9а-яё]+/u', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'channel';
    }

    public static function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 1;
        while (self::db()->fetch("SELECT id FROM channels WHERE slug = ?", [$slug])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
