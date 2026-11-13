<?php
namespace App\Controllers;

use App\Core\Application;
use App\Core\Response;

/**
 * Base controller. Provides shortcut helpers for views, JSON, redirects.
 */
abstract class BaseController
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected function view(string $name, array $data = []): void
    {
        $this->app->view($name, $data);
    }

    protected function render(string $name, array $data = []): string
    {
        return $this->app->render($name, $data);
    }

    protected function json($data, int $code = 200): void
    {
        Response::json($data, $code);
    }

    protected function redirect(string $url, int $code = 302): void
    {
        Response::redirect($url, $code);
    }

    protected function abort(int $code, string $message = ''): void
    {
        Response::abort($code, $message);
    }

    /** Current authenticated user or null. */
    protected function user(): ?array
    {
        return auth_user();
    }
}
