<?php
namespace App\Services;

/**
 * Daily rate limiter backed by small JSON files under storage/cache/ratelimit.
 * Keyed by an arbitrary identifier (user id or guest IP).
 */
class RateLimiter
{
    private static function path(string $key): string
    {
        $dir = STORAGE_PATH . '/cache/ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/' . md5($key) . '.json';
    }

    /** Returns true if the request is allowed (and records the hit). */
    public static function hit(string $key, int $limit): bool
    {
        $file = self::path($key);
        $today = date('Y-m-d');
        $data = ['day' => $today, 'count' => 0];
        if (file_exists($file)) {
            $decoded = json_decode((string)file_get_contents($file), true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
        if ($data['day'] !== $today) {
            $data = ['day' => $today, 'count' => 0];
        }
        if ($data['count'] >= $limit) {
            return false;
        }
        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        return true;
    }

    public static function remaining(string $key, int $limit): int
    {
        $file = self::path($key);
        $today = date('Y-m-d');
        if (!file_exists($file)) {
            return $limit;
        }
        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data) || ($data['day'] ?? '') !== $today) {
            return $limit;
        }
        return max(0, $limit - (int)$data['count']);
    }
}
