<?php
namespace App\Core;

/**
 * Minimal .env loader. Reads KEY=VALUE pairs, supports # comments and quotes.
 * Values are exposed via Env::get() and also populated into $_ENV / getenv().
 */
class Env
{
    private static array $store = [];

    public static function load(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if (($v !== '' && $v[0] === '"' && substr($v, -1) === '"')
                || ($v !== '' && $v[0] === "'" && substr($v, -1) === "'")) {
                $v = substr($v, 1, -1);
            }
            self::$store[$k] = $v;
            $_ENV[$k] = $v;
            if (function_exists('putenv')) {
                putenv("$k=$v");
            }
        }
        return true;
    }

    public static function get(string $key, $default = null)
    {
        if (array_key_exists($key, self::$store)) {
            return self::$store[$key];
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (function_exists('getenv')) {
            $g = getenv($key);
            return $g === false ? $default : $g;
        }
        return $default;
    }

    /** Override a value at runtime (e.g. settings loaded from DB). */
    public static function set(string $key, $value): void
    {
        self::$store[$key] = $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        if ($v === null) {
            return $default;
        }
        return in_array(strtolower((string)$v), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::get($key);
        return is_numeric($v) ? (int)$v : $default;
    }
}
