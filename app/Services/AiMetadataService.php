<?php
namespace App\Services;

/**
 * Thin wrapper around AiService for video metadata generation.
 * Keeps a clear, single-purpose API used by the upload/AI controllers.
 */
class AiMetadataService
{
    public static function generate(array $input, ?int $userId = null): array
    {
        return (new AiService($userId))->generateMetadata($input);
    }
}
