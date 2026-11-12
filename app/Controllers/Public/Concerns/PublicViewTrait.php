<?php

declare(strict_types=1);

namespace App\Controllers\Public\Concerns;

use App\Core\Response;
use App\Core\View;

trait PublicViewTrait
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     * @param list<string> $styles
     * @param list<string> $scripts
     */
    protected function renderPage(
        string $template,
        array $data = [],
        array $meta = [],
        string $active = '',
        array $styles = [],
        array $scripts = [],
        string $bodyClass = ''
    ): Response {
        $content = View::render($template, $data);

        $html = View::layout('layouts/public', $content, [
            'title' => (string) ($meta['title'] ?? 'NewJeans'),
            'description' => (string) ($meta['description'] ?? ''),
            'active' => $active,
            'styles' => $styles,
            'scripts' => $scripts,
            'bodyClass' => $bodyClass,
        ]);

        return Response::html($html, (int) ($meta['status'] ?? 200));
    }
}
