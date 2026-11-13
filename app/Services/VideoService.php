<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Env;
use App\Models\Video;
use App\Models\Tag;
use App\Models\Channel;

/**
 * Orchestrates video upload: validation, storage, DB row, tags, thumbnail,
 * optional duration probe, all inside a transaction.
 */
class VideoService
{
    public const MAX_TAGS = 10;

    public static function handleUpload(array $post, array $file, array $user): int
    {
        $validation = StorageService::validateUpload($file);
        if (!$validation['ok']) {
            throw new \Exception($validation['error']);
        }

        $db = Database::instance();
        $channel = Channel::findByUserId($user['id']);
        if (!$channel) {
            throw new \Exception('Канал не найден.');
        }

        $db->beginTransaction();
        $storagePath = null;
        try {
            $storagePath = StorageService::store($user['id'], $file['tmp_name'], $validation['ext']);
            $slug = Video::uniqueSlug(Video::slugify($post['title']));

            $requireApproval = Env::bool('REQUIRE_VIDEO_APPROVAL', false);
            $status = $requireApproval ? Video::STATUS_PENDING : Video::STATUS_PUBLISHED;

            $videoId = Video::create([
                'user_id'           => $user['id'],
                'channel_id'        => $channel['id'],
                'category_id'       => !empty($post['category_id']) ? (int)$post['category_id'] : null,
                'title'             => $post['title'],
                'slug'              => $slug,
                'description'       => $post['description'] ?? null,
                'original_filename' => $file['name'],
                'storage_path'      => $storagePath,
                'mime_type'         => $validation['mime'],
                'size_bytes'        => (int)$file['size'],
                'visibility'        => $post['visibility'] ?? Video::VIS_PUBLIC,
                'status'            => $status,
            ]);

            $duration = self::probeDuration(StorageService::absolutePath($storagePath));
            if ($duration !== null) {
                Video::update($videoId, ['duration_seconds' => $duration]);
            }
            if ($status === Video::STATUS_PUBLISHED) {
                Video::update($videoId, ['published_at' => date('Y-m-d H:i:s')]);
            }

            foreach (self::parseTags($post['tags'] ?? '') as $tagId) {
                Tag::attach($videoId, $tagId);
            }

            $thumb = ThumbnailService::createPlaceholder($videoId, $post['title']);
            Video::update($videoId, ['thumbnail_path' => $thumb]);

            $db->commit();
            return $videoId;
        } catch (\Throwable $e) {
            $db->rollBack();
            if ($storagePath) {
                StorageService::delete($storagePath);
            }
            throw $e;
        }
    }

    public static function parseTags(string $raw): array
    {
        $names = preg_split('/[,\n]/', $raw ?? '');
        $ids = [];
        if (is_array($names)) {
            foreach ($names as $n) {
                $n = trim($n);
                if ($n === '') {
                    continue;
                }
                $ids[] = Tag::findOrCreate(mb_substr($n, 0, 60, 'UTF-8'));
                if (count($ids) >= self::MAX_TAGS) {
                    break;
                }
            }
        }
        return array_values(array_unique($ids));
    }

    /** Probe duration in seconds using ffprobe, or null if unavailable. */
    public static function probeDuration(string $absolutePath): ?int
    {
        $probe = Env::get('FFPROBE_PATH', 'ffprobe');
        if (!$probe || !function_exists('proc_open')) {
            return null;
        }
        $cmd = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
            escapeshellarg($probe),
            escapeshellarg($absolutePath)
        );
        $out = @shell_exec($cmd);
        if ($out === null) {
            return null;
        }
        $sec = (float)$out;
        return $sec > 0 ? (int)round($sec) : null;
    }
}
