<?php

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = []): string
    {
        $template = preg_replace('/\.php$/i', '', ltrim($template, '/\\')) ?? $template;
        $path = base_path('templates/' . $template . '.php');

        if (!is_file($path)) {
            throw new RuntimeException('View not found: ' . $template);
        }

        extract($data, EXTR_SKIP);

        ob_start();

        try {
            require $path;
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        return (string) ob_get_clean();
    }

    public static function layout(string $layout, string $content, array $data = []): string
    {
        return self::render($layout, $data + ['content' => $content]);
    }
}
