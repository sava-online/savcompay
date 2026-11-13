<?php
namespace App\Controllers;

use App\Core\Request;
use App\Models\Video;
use App\Services\StreamingService;
use App\Services\StorageService;
use App\Services\ThumbnailService;

class StreamController extends BaseController
{
    public function stream(Request $request): void
    {
        $id = (int)$request->params['video_id'];
        $video = Video::findById($id);
        if (!$video || !StreamingService::canAccess($video, $this->user())) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Видео недоступно.';
            exit;
        }
        $abs = StorageService::absolutePath($video['storage_path']);
        StreamingService::serve($abs, $video['mime_type']);
    }

    public function thumbnail(Request $request): void
    {
        $name = basename((string)$request->params['name']);
        $abs = ThumbnailService::absolutePath('thumbnails/' . $name);
        if (!file_exists($abs)) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=86400');
        readfile($abs);
        exit;
    }

    public function avatar(Request $request): void
    {
        $name = (string)$request->params['name'];
        $initials = mb_strtoupper(mb_substr(preg_replace('/[^a-zа-яё0-9]/iu', '', $name), 0, 2, 'UTF-8'), 'UTF-8') ?: 'GT';
        $hue = abs(crc32($name)) % 360;
        $c1 = "hsl($hue, 70%, 62%)";
        $c2 = "hsl(" . (($hue + 40) % 360) . ", 70%, 52%)";
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
    <stop offset="0%" stop-color="{$c1}"/><stop offset="100%" stop-color="{$c2}"/>
  </linearGradient></defs>
  <rect width="120" height="120" rx="60" fill="url(#g)"/>
  <text x="50%" y="54%" text-anchor="middle" dominant-baseline="middle"
        font-family="Segoe UI, system-ui, sans-serif" font-size="48" font-weight="700" fill="#fff">{$initials}</text>
</svg>
SVG;
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=86400');
        echo $svg;
        exit;
    }
}
