<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Application;
use App\Core\Session;
use App\Core\Response;

/**
 * Requires the authenticated user to have the admin role.
 */
class AdminMiddleware
{
    public function handle(Request $request, Application $app): bool
    {
        $user = Session::get('user');
        if ($user === null || ($user['role'] ?? '') !== 'admin') {
            if ($request->isAjax()) {
                Response::json(['error' => 'admin_required'], 403);
            }
            set_flash('error', 'Доступ запрещён.');
            Response::redirect(url(''));
        }
        return true;
    }
}
