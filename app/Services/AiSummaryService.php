<?php
namespace App\Services;

/**
 * Thin wrapper around AiService for video summarization.
 */
class AiSummaryService
{
    public static function summarize(array $video, ?int $userId = null): array
    {
        return (new AiService($userId))->summarize($video);
    }
}
