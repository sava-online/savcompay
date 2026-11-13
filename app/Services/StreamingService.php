<?php
namespace App\Services;

/**
 * Video streaming with HTTP Range support (206 Partial Content / 416).
 * Also enforces access rules based on video status/visibility and the viewer.
 */
class StreamingService
{
    public static function canAccess(array $video, ?array $user): bool
    {
        $status  = $video['status'];
        $vis     = $video['visibility'];
        $ownerId = (int)$video['user_id'];
        $isAdmin = $user !== null && ($user['role'] ?? '') === 'admin';
        $isOwner = $user !== null && (int)$user['id'] === $ownerId;

        if ($status === 'blocked' || $status === 'failed') {
            return $isAdmin;
        }
        if ($status === 'processing' || $status === 'pending_review') {
            return $isOwner || $isAdmin;
        }
        if ($vis === 'private') {
            return $isOwner || $isAdmin;
        }
        // published + public/unlisted are reachable by anyone (unlisted via link)
        return true;
    }

    public static function serve(string $absolutePath, string $mime): void
    {
        if (!file_exists($absolutePath) || !is_readable($absolutePath)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Видеофайл не найден.';
            exit;
        }

        $size = filesize($absolutePath);
        $fp = @fopen($absolutePath, 'rb');
        if ($fp === false) {
            http_response_code(500);
            echo 'Ошибка чтения файла.';
            exit;
        }

        header('Accept-Ranges: bytes');
        header('Content-Type: ' . $mime);
        header('Cache-Control: no-store, must-revalidate');

        $range = $_SERVER['HTTP_RANGE'] ?? '';
        if ($range && preg_match('/bytes=(\d*)-(\d*)/i', $range, $m)) {
            $start = $m[1] === '' ? 0 : (int)$m[1];
            $end   = $m[2] === '' ? $size - 1 : (int)$m[2];
            if ($start < 0 || $end >= $size || $start > $end) {
                http_response_code(416);
                header('Content-Range: bytes */' . $size);
                fclose($fp);
                exit;
            }
            $length = $end - $start + 1;
            http_response_code(206);
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
            header('Content-Length: ' . $length);
            fseek($fp, $start);
            $left = $length;
            while ($left > 0 && !feof($fp)) {
                $read = min(8192, $left);
                echo fread($fp, $read);
                $left -= $read;
                if (connection_status() !== 0) {
                    break;
                }
            }
            fclose($fp);
            exit;
        }

        http_response_code(200);
        header('Content-Length: ' . $size);
        fpassthru($fp);
        fclose($fp);
        exit;
    }
}
