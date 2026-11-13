<?php
namespace App\Core;

/**
 * Small response helpers used by controllers.
 */
class Response
{
    public static function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public static function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }

    public static function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        if ($message !== '') {
            echo $message;
        }
        exit;
    }

    public static function noContent(int $code = 204): void
    {
        http_response_code($code);
        exit;
    }
}
