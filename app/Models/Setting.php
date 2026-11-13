<?php
namespace App\Models;

class Setting extends Model
{
    protected static string $table = 'settings';

    public static function get(string $key, $default = null)
    {
        $row = self::db()->fetch("SELECT value FROM settings WHERE `key` = ?", [$key]);
        return $row ? $row['value'] : $default;
    }

    public static function all(): array
    {
        $rows = self::db()->fetchAll("SELECT `key`, value FROM settings");
        $out = [];
        foreach ($rows as $r) {
            $out[$r['key']] = $r['value'];
        }
        return $out;
    }

    public static function set(string $key, $value): void
    {
        self::db()->execute(
            "INSERT INTO settings (`key`, value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)",
            [$key, $value]
        );
    }
}
