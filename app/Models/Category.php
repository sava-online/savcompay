<?php
namespace App\Models;

class Category extends Model
{
    protected static string $table = 'categories';

    public static function all(): array
    {
        return self::db()->fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
    }

    public static function findBySlug(string $slug): ?array
    {
        return self::db()->fetch("SELECT * FROM categories WHERE slug = ?", [$slug]);
    }

    public static function findById(int $id): ?array
    {
        return self::db()->fetch("SELECT * FROM categories WHERE id = ?", [$id]);
    }
}
