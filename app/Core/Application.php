<?php
namespace App\Core;

/**
 * Application container: holds the request and router, provides layout rendering.
 */
class Application
{
    public Request $request;
    public Router $router;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->router = new Router();
    }

    /** Render a view wrapped in the main glass layout. */
    public function render(string $view, array $data = []): string
    {
        $content = View::render($view, $data);
        $layoutData = array_merge($data, ['content' => $content]);
        return View::render('layouts/main', $layoutData);
    }

    /** Render and output a page (used by controllers). */
    public function view(string $view, array $data = []): void
    {
        echo $this->render($view, $data);
    }

    public function run(): void
    {
        $this->router->dispatch($this->request, $this);
    }
}
