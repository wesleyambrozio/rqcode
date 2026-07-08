<?php

declare(strict_types=1);

function env(string $key, mixed $default = null): mixed
{
    static $loaded = false;

    if (!$loaded) {
        $path = dirname(__DIR__, 2) . '/.env';
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$name, $value] = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }
        $loaded = true;
    }

    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function config(string $key, mixed $default = null): mixed
{
    [$file, $name] = array_pad(explode('.', $key, 2), 2, null);
    $config = require dirname(__DIR__, 2) . "/config/{$file}.php";

    return $name ? ($config[$name] ?? $default) : $config;
}

function view(string $template, array $data = [], string $layout = 'main'): void
{
    App\Core\View::render($template, $data, $layout);
}

function redirect(string $path): never
{
    header("Location: {$path}");
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(mixed $value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function csrf_token(): string
{
    $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
