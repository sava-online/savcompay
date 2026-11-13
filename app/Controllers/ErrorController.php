<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Env;

class ErrorController extends BaseController
{
    public function notFound(Request $request): void
    {
        http_response_code(404);
        echo $this->render('errors/404', [
            'title'      => 'Страница не найдена',
            'page_title' => '404 — не найдено',
        ]);
    }

    public function serverError(\Throwable $e): void
    {
        http_response_code(500);
        $debug = Env::bool('APP_DEBUG', false);
        echo $this->render('errors/500', [
            'title'   => 'Ошибка сервера',
            'debug'   => $debug,
            'message' => $debug ? $e->getMessage() : 'Что-то пошло не так. Попробуйте позже.',
        ]);
    }
}
