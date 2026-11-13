<?php
namespace App\Services;

/**
 * Thin wrapper around AiService for text moderation.
 * Provides a helper to decide whether a comment should be hidden/pending.
 */
class AiModerationService
{
    public static function moderate(string $text, string $context = 'comment', ?int $userId = null): array
    {
        return (new AiService($userId))->moderate($text, $context);
    }

    /** Determine comment status based on moderation result. */
    public static function statusFor(array $result): string
    {
        if (empty($result['safe'])) {
            return \App\Models\Comment::STATUS_HIDDEN;
        }
        return \App\Models\Comment::STATUS_VISIBLE;
    }
}
