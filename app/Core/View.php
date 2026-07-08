<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__) . "/Views/pages/{$template}.php";

        ob_start();
        if (is_file($viewFile)) {
            require $viewFile;
        } else {
            echo '<p>View não encontrada.</p>';
        }
        $content = ob_get_clean();

        require dirname(__DIR__) . "/Views/layouts/{$layout}.php";
    }
}
