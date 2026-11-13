<?php
namespace App\Core;

/**
 * PHP template renderer. Buffers output and returns a string.
 */
class View
{
    public static function render(string $name, array $data = []): string
    {
        $file = APP_PATH . '/Views/' . ltrim($name, '/') . '.php';
        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: $name");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return ob_get_clean();
    }
}
