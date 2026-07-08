<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, string $handler, bool $auth = false): void
    {
        $this->add('GET', $path, $handler, $auth);
    }

    public function post(string $path, string $handler, bool $auth = false): void
    {
        $this->add('POST', $path, $handler, $auth);
    }

    public function dispatch(string $method, string $path): void
    {
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $route = $this->routes[$method][$path] ?? null;

        if (!$route) {
            http_response_code(404);
            view('errors/404', ['title' => 'Página não encontrada']);
            return;
        }

        if ($route['auth'] && !Auth::check()) {
            redirect('/login');
        }

        if ($method === 'POST' && !hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) {
            http_response_code(419);
            exit('Sessão expirada. Volte e tente novamente.');
        }

        [$controller, $action] = explode('@', $route['handler']);
        $class = "App\\Controllers\\{$controller}";
        (new $class())->{$action}();
    }

    private function add(string $method, string $path, string $handler, bool $auth): void
    {
        $this->routes[$method][$path] = compact('handler', 'auth');
    }
}
