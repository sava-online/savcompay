<?php
namespace App\Models;

use App\Core\Database;

class User extends Model
{
    protected static string $table = 'users';

    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';

    public static function findByEmail(string $email): ?array
    {
        return self::db()->fetch("SELECT * FROM users WHERE email = ?", [$email]);
    }

    public static function findByUsername(string $username): ?array
    {
        return self::db()->fetch("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public static function create(array $data): int
    {
        $sql = "INSERT INTO users (username, email, password_hash, bio, role, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";
        return self::db()->insert($sql, [
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['bio'] ?? null,
            $data['role'] ?? self::ROLE_USER,
        ]);
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
        $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?";
        return self::db()->execute($sql, $params);
    }

    public static function setLastLogin(int $id): void
    {
        self::db()->execute("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$id]);
    }
}
