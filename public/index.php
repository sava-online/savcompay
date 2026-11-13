<?php
/**
 * GlassTube front controller.
 *
 * Run locally:
 *   php -S localhost:8000 -t public public/index.php
 *
 * On Apache the public/.htaccess rewrites all requests to this file.
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');

// -----------------------------------------------------------
// Autoloader for the App\ namespace (no Composer required).
// -----------------------------------------------------------
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, 4) !== 0) {
        return;
    }
    $rel = substr($class, 4);
    $file = APP_PATH . '/' . str_replace('\\', '/', $rel) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// -----------------------------------------------------------
// Environment
// -----------------------------------------------------------
require_once APP_PATH . '/Core/Env.php';
if (!\App\Core\Env::load(ROOT_PATH . '/.env')) {
    // No .env present — continue with safe defaults (helpful for first run).
    if (PHP_SAPI !== 'cli') {
        // Still allow the app to boot; DB errors will surface clearly in debug mode.
    }
}

require_once APP_PATH . '/Core/Helpers.php';

// Apply persisted admin settings (DB) over env defaults, if available.
try {
    $map = [
        'enable_ai'             => 'ENABLE_AI',
        'require_video_approval' => 'REQUIRE_VIDEO_APPROVAL',
        'video_max_mb'          => 'VIDEO_MAX_MB',
        'ai_daily_limit_auth'   => 'AI_DAILY_LIMIT_AUTH',
        'registration_open'     => 'REGISTRATION_OPEN',
    ];
    foreach (\App\Models\Setting::all() as $row) {
        $k = $row['key'] ?? null;
        if ($k !== null && isset($map[$k])) {
            \App\Core\Env::set($map[$k], (string)($row['value'] ?? ''));
        }
    }
} catch (\Throwable $e) {
    // Settings table may not exist yet during first install — ignore.
}

// -----------------------------------------------------------
// Storage paths
// -----------------------------------------------------------
if (!defined('STORAGE_PATH')) {
    $sp = \App\Core\Env::get('STORAGE_PATH', '../storage');
    $candidate = realpath(PUBLIC_PATH . '/' . ltrim($sp, '/'));
    if ($candidate === false) {
        $candidate = PUBLIC_PATH . '/' . ltrim($sp, '/');
    }
    define('STORAGE_PATH', $candidate);
}
foreach (['videos', 'thumbnails', 'cache', 'cache/ratelimit'] as $sub) {
    $p = STORAGE_PATH . '/' . $sub;
    if (!is_dir($p)) {
        @mkdir($p, 0755, true);
    }
}

// -----------------------------------------------------------
// Session + request
// -----------------------------------------------------------
\App\Core\Session::start();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($uri === false || $uri === '') {
    $uri = '/';
}
$request = new \App\Core\Request($method, $uri);

// -----------------------------------------------------------
// Bootstrap and dispatch
// -----------------------------------------------------------
$app = new \App\Core\Application($request);
require APP_PATH . '/routes.php';
glasstube_routes($app);

try {
    $app->run();
} catch (\Throwable $e) {
    $log = sprintf(
        "[%s] %s in %s:%d\n%s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    $logDir = defined('STORAGE_PATH') ? STORAGE_PATH : __DIR__ . '/../storage';
    @file_put_contents($logDir . '/cache/error.log', $log, FILE_APPEND);
    if (\App\Core\Env::bool('APP_DEBUG', false)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "FATAL: " . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString() . "\n";
        return;
    }
    http_response_code(500);
    echo 'Внутренняя ошибка сервера. Подробности в storage/cache/error.log';
}
