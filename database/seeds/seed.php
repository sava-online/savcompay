<?php
/**
 * GlassTube demo data seeder.
 *
 * Usage:
 *   php database/seeds/seed.php
 *
 * Creates an admin, a demo user, channels, a few demo videos (without real
 * video files — the watch page shows a placeholder), tags and comments.
 * Safe to re-run: skips if the admin already exists.
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__, 2));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('APP_PATH', ROOT_PATH . '/app');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, 4) !== 0) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

require_once APP_PATH . '/Core/Env.php';
\App\Core\Env::load(ROOT_PATH . '/.env');

if (!defined('STORAGE_PATH')) {
    $sp = \App\Core\Env::get('STORAGE_PATH', '../storage');
    define('STORAGE_PATH', realpath(PUBLIC_PATH . '/' . ltrim($sp, '/')) ?: PUBLIC_PATH . '/' . ltrim($sp, '/'));
}

use App\Models\User;
use App\Models\Channel;
use App\Models\Video;
use App\Models\Tag;
use App\Models\Comment;
use App\Services\ThumbnailService;

$db = \App\Core\Database::instance();

function out(string $s): void
{
    echo $s . PHP_EOL;
}

// ---- Admin ----
if (User::findByUsername('admin')) {
    out('Demo data already exists. Skipping.');
    exit(0);
}

$adminId = User::create([
    'username' => 'admin',
    'email'    => 'admin@glasstube.local',
    'password_hash' => password_hash('admin1234', PASSWORD_DEFAULT),
    'role' => User::ROLE_ADMIN,
    'bio' => 'Администратор GlassTube.',
]);
Channel::createForUser($adminId, 'GlassTube Official');

// ---- Demo user ----
$demoId = User::create([
    'username' => 'demo',
    'email'    => 'demo@glasstube.local',
    'password_hash' => password_hash('demo1234', PASSWORD_DEFAULT),
    'role' => User::ROLE_USER,
    'bio' => 'Демонстрационный автор.',
]);
$demoChannel = Channel::findByUserId($demoId);

// ---- Demo videos ----
$demos = [
    ['Как устроен спокойный интерфейс', 'Разбираем принципы Liquid Glass и тихого дизайна.', 'technology', ['дизайн', 'код']],
    ['Лёгкая музыка для фокуса', 'Мягкий инструментальный сет для работы и отдыха.', 'music', ['музыка', 'калм']],
    ['Введение в астрономию', 'Короткая лекция о планетах и звёздах.', 'science', ['обучение', 'природа']],
    ['Рисуем утро на планшете', 'Расслабленный процесс цифровой живописи.', 'art', ['творчество', 'дизайн']],
    ['Обзор спокойных приложений', 'Что помогает не отвлекаться в течение дня.', 'reviews', ['обучение']],
];

$cats = [];
foreach ((\App\Models\Category::all()) as $c) {
    $cats[$c['slug']] = $c['id'];
}

$created = [];
foreach ($demos as $i => $d) {
    $slug = Video::uniqueSlug(Video::slugify($d[0]));
    $catId = $cats[$d[2]] ?? null;
    $tagIds = [];
    foreach ($d[3] as $t) {
        $tagIds[] = Tag::findOrCreate($t);
    }
    $videoId = Video::create([
        'user_id' => $demoId,
        'channel_id' => $demoChannel['id'],
        'category_id' => $catId,
        'title' => $d[0],
        'slug' => $slug,
        'description' => $d[1],
        'original_filename' => 'demo-' . ($i + 1) . '.mp4',
        'storage_path' => 'videos/0/demo-' . ($i + 1) . '.mp4',
        'mime_type' => 'video/mp4',
        'size_bytes' => 0,
        'visibility' => Video::VIS_PUBLIC,
        'status' => Video::STATUS_PUBLISHED,
    ]);
    Video::update($videoId, [
        'published_at' => date('Y-m-d H:i:s', time() - $i * 3600),
        'duration_seconds' => 120 + $i * 30,
        'view_count' => rand(20, 900),
        'thumbnail_path' => ThumbnailService::createPlaceholder($videoId, $d[0]),
    ]);
    foreach ($tagIds as $tid) {
        Tag::attach($videoId, $tid);
    }
    $created[] = $videoId;
}

// ---- Comments ----
$first = $created[0] ?? null;
if ($first) {
    Comment::add($first, $demoId, 'Очень спокойно и понятно, спасибо!');
    Comment::add($first, $adminId, 'Отличная первая демо-лекция.');
}

out('Done. Created admin (admin@glasstube.local / admin1234) and demo (demo@glasstube.local / demo1234).');
out('Demo videos: ' . count($created) . '. Note: video files are not included; the player shows a placeholder.');
