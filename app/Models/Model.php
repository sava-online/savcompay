<?php
namespace App\Models;

use App\Core\Database;

/**
 * Base model. Provides a PDO-backed data layer via App\Core\Database.
 * Subclasses must declare `protected static string $table` and may override
 * any of these methods. All queries use prepared statements.
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    protected static function db(): Database
    {
        return Database::instance();
    }

    public static function find($id): ?array
    {
        $row = self::db()->fetch(
            "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?",
            [$id]
        );
        return $row === null ? null : $row;
    }

    public static function findById(int $id): ?array
    {
        return self::find($id);
    }

    public static function all(): array
    {
        return self::db()->fetchAll(
            "SELECT * FROM " . static::$table . " ORDER BY " . static::$primaryKey . " ASC"
        );
    }

    public static function create(array $fields): int
    {
        $cols = array_keys($fields);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = "INSERT INTO " . static::$table . " (" . implode(', ', $cols)
            . ") VALUES (" . implode(', ', $placeholders) . ")";
        return self::db()->insert($sql, array_values($fields));
    }

    public static function update(int $id, array $fields)
    {
        $sets = [];
        $params = [];
        foreach ($fields as $k => $v) {
            $sets[] = "$k = ?";
            $params[] = $v;
        }
        $params[] = $id;
        $sql = "UPDATE " . static::$table . " SET " . implode(', ', $sets)
            . " WHERE " . static::$primaryKey . " = ?";
        return self::db()->execute($sql, $params);
    }

    public static function delete(int $id)
    {
        return self::db()->execute(
            "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?",
            [$id]
        );
    }

    protected static function fetch(string $sql, array $params = []): ?array
    {
        return self::db()->fetch($sql, $params);
    }

    protected static function fetchAll(string $sql, array $params = []): array
    {
        return self::db()->fetchAll($sql, $params);
    }

    protected static function column(string $sql, array $params = []): mixed
    {
        return self::db()->column($sql, $params);
    }

    protected static function execute(string $sql, array $params = []): int
    {
        return self::db()->execute($sql, $params);
    }

    protected static function insert(string $sql, array $params = []): int
    {
        return self::db()->insert($sql, $params);
    }
}
