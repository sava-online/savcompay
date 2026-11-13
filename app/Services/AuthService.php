<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Models\User;
use App\Models\Channel;

/**
 * Authentication: registration, login, logout, session user.
 */
class AuthService
{
    public static function register(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $userId = User::create([
            'username'      => $data['username'],
            'email'         => $data['email'],
            'password_hash' => $hash,
            'bio'           => $data['bio'] ?? null,
            'role'          => User::ROLE_USER,
        ]);
        // Each user gets an auto-created channel.
        Channel::createForUser($userId, $data['channel_name'] ?? $data['username']);
        return $userId;
    }

    /** Returns the user array on success, or null on failure. */
    public static function attempt(string $login, string $password): ?array
    {
        $user = User::findByEmail($login) ?? User::findByUsername($login);
        if (!$user) {
            return null;
        }
        if (!(int)$user['is_active']) {
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }
        return $user;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('user', $user);
        User::setLastLogin($user['id']);
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function current(): ?array
    {
        return Session::get('user');
    }

    public static function isAdmin(): bool
    {
        $u = self::current();
        return $u !== null && ($u['role'] ?? '') === 'admin';
    }

    /** Refresh the session user after a profile change. */
    public static function refresh(int $userId): void
    {
        $user = User::findById($userId);
        if ($user) {
            Session::set('user', $user);
        }
    }
}
