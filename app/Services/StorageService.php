<?php
namespace App\Services;

use App\Core\Env;

/**
 * Safe video file storage. Validates uploads, generates random filenames,
 * stores files outside the web root, and removes them on failure.
 */
class StorageService
{
    /** Validate an uploaded file. Returns ['ok'=>bool,'error'=>?,'ext'=>?,'mime'=>?]. */
    public static function validateUpload(array $file): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['ok' => false, 'error' => 'Некорректный файл.'];
        }
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['ok' => false, 'error' => 'Файл слишком большой.'];
            case UPLOAD_ERR_NO_FILE:
                return ['ok' => false, 'error' => 'Файл не был отправлен.'];
            default:
                return ['ok' => false, 'error' => 'Ошибка загрузки.'];
        }

        $maxBytes = Env::int('VIDEO_MAX_MB', 1024) * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            return ['ok' => false, 'error' => 'Превышен максимальный размер файла.'];
        }
        if ($file['size'] <= 0) {
            return ['ok' => false, 'error' => 'Пустой файл.'];
        }

        $allowedExt = array_map('trim', explode(',', Env::get('ALLOWED_VIDEO_EXTENSIONS', 'mp4,webm')));
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return ['ok' => false, 'error' => 'Недопустимое расширение файла.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMime = array_map('trim', explode(',', Env::get('ALLOWED_VIDEO_MIME', 'video/mp4,video/webm')));
        if (!in_array($mime, $allowedMime, true)) {
            return ['ok' => false, 'error' => 'Недопустимый тип файла (' . e($mime) . ').'];
        }

        if (preg_match('#^(application/(x-)?(executable|octet-stream|javascript|x-php))|text/(x-)?(php|html)#i', $mime)) {
            return ['ok' => false, 'error' => 'Опасный тип файла.'];
        }

        return ['ok' => true, 'ext' => $ext, 'mime' => $mime];
    }

    /** Store a validated uploaded temp file. Returns a storage-relative path. */
    public static function store(int $userId, string $tmpPath, string $ext): string
    {
        $dir = STORAGE_PATH . '/videos/' . $userId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($tmpPath, $dest)) {
            if (!rename($tmpPath, $dest)) {
                throw new \RuntimeException('Не удалось сохранить файл.');
            }
        }
        return 'videos/' . $userId . '/' . $name;
    }

    public static function absolutePath(string $storagePath): string
    {
        return STORAGE_PATH . '/' . ltrim($storagePath, '/');
    }

    public static function delete(string $storagePath): void
    {
        $abs = self::absolutePath($storagePath);
        if ($abs && file_exists($abs)) {
            @unlink($abs);
        }
        $dir = dirname($abs);
        if (is_dir($dir) && !(new \FilesystemIterator($dir))->valid()) {
            @rmdir($dir);
        }
    }
}
