<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Application;
use App\Core\Session;
use App\Core\Response;

/**
 * Requires an authenticated user. Redirects browsers to /login,
 * returns 401 JSON for AJAX calls.
 */
class AuthMiddleware
{
    public function handle(Request $request, Application $app): bool
    {
        if (Session::get('user_id') === null) {
            if ($request->isAjax()) {
                Response::json(['error' => 'auth_required'], 401);
            }
            set_flash('error', 'Требуется вход в аккаунт.');
            Response::redirect(url('login'));
        }
        return true;
    }
}
