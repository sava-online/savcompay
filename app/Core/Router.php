<?php
namespace App\Core;

/**
 * Simple regex router. Supports {param} placeholders and per-route middleware.
 * Enforces CSRF verification on POST requests before invoking the controller.
 */
class Router
{
    private array $routes = [];

    public function add(string $method, string $path, string $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method'     => strtoupper($method),
            'path'       => $path,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function compile(string $path): string
    {
        $pattern = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            fn($m) => '(?P<' . $m[1] . '>[^/]+)', $path);
        return '#^' . $pattern . '$#u';
    }

    public function dispatch(Request $request, Application $app): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            $regex = $this->compile($route['path']);
            if (!preg_match($regex, $request->uri, $matches)) {
                continue;
            }
            $params = [];
            foreach ($matches as $k => $v) {
                if (is_string($k)) {
                    $params[$k] = urldecode($v);
                }
            }
            $request->params = $params;

            foreach ($route['middleware'] as $mwClass) {
                $mw = new $mwClass();
                if (!$mw->handle($request, $app)) {
                    return;
                }
            }

            if ($request->isPost() && !Csrf::verify()) {
                if ($request->isAjax()) {
                    Response::json(['error' => 'csrf_invalid'], 419);
                }
                set_flash('error', 'Ошибка безопасности (недействительный CSRF-токен).');
                Response::redirect($request->uri);
            }

            [$ctrl, $method] = explode('@', $route['handler'], 2);
            $class = 'App\\Controllers\\' . $ctrl;
            if (!class_exists($class)) {
                throw new \RuntimeException("Controller not found: $class");
            }
            $controller = new $class($app);
            if (!method_exists($controller, $method)) {
                throw new \RuntimeException("Method $class::$method not found");
            }
            $controller->$method($request);
            return;
        }

        $ctrl = new \App\Controllers\ErrorController($app);
        $ctrl->notFound($request);
    }
}
