<?php
namespace App\Services;

/**
 * Generates a calm glass-style placeholder thumbnail (SVG) when no real
 * frame extraction is available (e.g. FFmpeg absent). Deterministic per id.
 */
class ThumbnailService
{
    private static array $palette = [
        ['#7c5cff', '#43c6ff'],
        ['#ff7eb3', '#ff758c'],
        ['#43e97b', '#38f9d7'],
        ['#fa709a', '#fee140'],
        ['#30cfd0', '#330867'],
        ['#a18cd1', '#fbc2eb'],
    ];

    public static function createPlaceholder(int $videoId, string $title): string
    {
        $dir = STORAGE_PATH . '/thumbnails';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pair = self::$palette[$videoId % count(self::$palette)];
        $initials = self::initials($title);
        $svg = self::svg($pair[0], $pair[1], $initials);
        $file = $dir . '/' . $videoId . '.svg';
        file_put_contents($file, $svg);
        return 'thumbnails/' . $videoId . '.svg';
    }

    public static function absolutePath(string $thumbnailPath): string
    {
        return STORAGE_PATH . '/' . ltrim($thumbnailPath, '/');
    }

    private static function initials(string $title): string
    {
        $words = preg_split('/\s+/', trim($title));
        $out = '';
        $i = 0;
        foreach ($words as $w) {
            if ($i++ >= 2) {
                break;
            }
            $out .= mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8');
        }
        return $out ?: 'GT';
    }

    private static function svg(string $c1, string $c2, string $text): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$c1}"/>
      <stop offset="100%" stop-color="{$c2}"/>
    </linearGradient>
  </defs>
  <rect width="640" height="360" fill="url(#g)"/>
  <circle cx="520" cy="60" r="120" fill="#ffffff" opacity="0.08"/>
  <circle cx="80" cy="320" r="160" fill="#ffffff" opacity="0.06"/>
  <text x="32" y="320" font-family="Segoe UI, system-ui, sans-serif" font-size="120"
        font-weight="700" fill="#ffffff" opacity="0.85">{$text}</text>
</svg>
SVG;
    }
}
