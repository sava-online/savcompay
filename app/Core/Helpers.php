<?php
/**
 * Global helper functions used across views and controllers.
 * Loaded once from public/index.php.
 */

if (!function_exists('e')) {
    /** Escape HTML output (XSS protection). */
    function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /** Build an absolute or relative URL from APP_URL. */
    function url(string $path, bool $absolute = false): string
    {
        if ($absolute) {
            $base = rtrim(\App\Core\Env::get('APP_URL', ''), '/');
            return $base . '/' . ltrim($path, '/');
        }
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Core\Csrf::field();
    }
}

if (!function_exists('auth_user')) {
    /** Returns current user array from session, or null. */
    function auth_user(): ?array
    {
        return \App\Core\Session::get('user');
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        $u = auth_user();
        return $u !== null && ($u['role'] ?? '') === 'admin';
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return auth_user() !== null;
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        return \App\Core\Env::get($key, $default);
    }
}

if (!function_exists('flash')) {
    /** Get and clear a flash message. Returns ['type'=>, 'text'=>] or null. */
    function flash(): ?array
    {
        $msg = \App\Core\Session::get('flash');
        if ($msg !== null) {
            \App\Core\Session::forget('flash');
        }
        return $msg;
    }

    function set_flash(string $type, string $text): void
    {
        \App\Core\Session::set('flash', ['type' => $type, 'text' => $text]);
    }
}

if (!function_exists('old')) {
    /** Retrieve previously submitted field value (for form re-population). */
    function old(string $key, $default = '')
    {
        $old = \App\Core\Session::get('old_input', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('format_number')) {
    function format_number(int $n): string
    {
        if ($n >= 1_000_000) {
            return number_format($n / 1_000_000, 1, '.', ' ') . 'M';
        }
        if ($n >= 1_000) {
            return number_format($n / 1_000, 1, '.', ' ') . 'K';
        }
        return (string)$n;
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $dt): string
    {
        if (!$dt) {
            return '';
        }
        $time = strtotime($dt);
        $diff = time() - $time;
        if ($diff < 60) {
            return 'только что';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' мин назад';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' ч назад';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . ' дн назад';
        }
        return date('d.m.Y', $time);
    }
}

if (!function_exists('format_duration')) {
    function format_duration(?int $seconds): string
    {
        if ($seconds === null || $seconds < 0) {
            return '';
        }
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        if ($h > 0) {
            return sprintf('%d:%02d:%02d', $h, $m, $s);
        }
        return sprintf('%d:%02d', $m, $s);
    }
}

if (!function_exists('avatar_url')) {
    function avatar_url(?string $path): string
    {
        if ($path && file_exists(STORAGE_PATH . '/thumbnails/' . basename($path))) {
            return '/stream/avatar/' . rawurlencode(basename($path));
        }
        return asset('img/default-avatar.svg');
    }
}
