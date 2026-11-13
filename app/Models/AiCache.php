<?php
namespace App\Models;

class AiCache extends Model
{
    protected static string $table = 'ai_cache';

    public static function get(string $hash): ?array
    {
        $row = self::db()->fetch(
            "SELECT * FROM ai_cache WHERE hash = ? AND (expires_at IS NULL OR expires_at > NOW())",
            [$hash]
        );
        return $row;
    }

    public static function set(string $hash, string $provider, string $model, string $requestJson, string $responseJson, ?string $expiresAt = null): void
    {
        self::db()->execute(
            "INSERT INTO ai_cache (hash, provider, model, request_json, response_json, created_at, expires_at)
             VALUES (?, ?, ?, ?, ?, NOW(), ?)
             ON DUPLICATE KEY UPDATE response_json = VALUES(response_json), expires_at = VALUES(expires_at)",
            [$hash, $provider, $model, $requestJson, $responseJson, $expiresAt]
        );
    }
}
