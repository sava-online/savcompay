<?php
namespace App\Models;

class AiRequest extends Model
{
    protected static string $table = 'ai_requests';

    public static function log(array $data): void
    {
        self::db()->execute(
            "INSERT INTO ai_requests (user_id, endpoint, provider, model, input_hash, status, error_message, response_time_ms, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['user_id'] ?? null,
                $data['endpoint'],
                $data['provider'],
                $data['model'],
                $data['input_hash'],
                $data['status'] ?? 'ok',
                $data['error_message'] ?? null,
                $data['response_time_ms'] ?? null,
            ]
        );
    }

    public static function recent(int $limit = 100): array
    {
        return self::db()->fetchAll(
            "SELECT * FROM ai_requests ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }
}
