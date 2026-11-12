<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Concerns;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Upload;
use App\Core\View;
use RuntimeException;

trait AdminFormTrait
{
    protected function renderAdmin(string $template, array $data, string $active): Response
    {
        $content = View::render($template, $data);

        return Response::html(View::layout('layouts/admin', $content, [
            'title' => (string) ($data['title'] ?? 'Admin'),
            'user' => Auth::user(),
            'active' => $active,
        ]));
    }

    protected function csrfValid(Request $request): bool
    {
        $token = $request->post('_token');

        return is_string($token) && Csrf::validate($token);
    }

    protected function csrfFailure(): Response
    {
        return Response::html('Invalid or expired security token.', 419);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $errors
     */
    protected function backWithErrors(array $input, array $errors, string $path): never
    {
        set_old($input);
        set_form_errors($errors);
        flash('error', 'Please fix the highlighted fields.');
        redirect($path);
    }

    protected function notFound(string $message = 'Not found.'): Response
    {
        return Response::html('<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<title>404 - Not Found</title></head><body>'
            . '<main style="font-family:system-ui,sans-serif;padding:4rem;text-align:center">'
            . '<h1>404</h1><p>' . e($message) . '</p>'
            . '<p><a href="' . e(url('/admin')) . '">Back to dashboard</a></p></main></body></html>', 404);
    }

    /**
     * Store an optional upload and keep the current path when no file is sent.
     *
     * @param array<string, string> $errors
     * @return array{path: string, new_upload: ?string}
     */
    protected function resolveImage(
        string $field,
        string $dir,
        bool $isEdit,
        string $current,
        array &$errors,
        bool $required = true
    ): array {
        $file = $_FILES[$field] ?? null;
        $hasUpload = is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($hasUpload) {
            try {
                $path = Upload::store($file, $dir);

                return ['path' => $path, 'new_upload' => $path];
            } catch (RuntimeException $exception) {
                $errors[$field] = $exception->getMessage();

                return ['path' => $current, 'new_upload' => null];
            }
        }

        if (!$isEdit && $required && $current === '') {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }

        return ['path' => $current, 'new_upload' => null];
    }

    protected function normalizeDateTime(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    protected function removeUpload(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        @unlink(base_path('public/assets/' . ltrim($path, '/')));
    }
}
