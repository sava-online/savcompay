<?php
namespace App\Core;

/**
 * CSRF protection. Token lives in the session; verified on POST via form
 * field "csrf_token" or header "X-CSRF-Token" for fetch() requests.
 */
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        $t = self::token();
        return '<input type="hidden" name="csrf_token" value="' . e($t) . '">';
    }

    public static function verify(?string $token = null): bool
    {
        if ($token === null) {
            $token = $_POST['csrf_token']
                ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        }
        $expected = self::token();
        return is_string($token) && $token !== '' && hash_equals($expected, $token);
    }
}
