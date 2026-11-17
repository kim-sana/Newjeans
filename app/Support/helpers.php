<?php

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);

        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return base_path('config' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, '/\\')));
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, '/\\')));
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        static $loaded = [];

        if ($key === null) {
            return $loaded;
        }

        [$file, $nested] = array_pad(explode('.', $key, 2), 2, null);

        if (!array_key_exists($file, $loaded)) {
            $path = config_path($file . '.php');
            $loaded[$file] = is_file($path) ? require $path : [];
        }

        $value = $loaded[$file];

        if ($nested === null) {
            return $value;
        }

        foreach (explode('.', $nested) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $base = (string) config('app.url', '');
        $path = ltrim($path, '/');

        return $path === '' ? $base . '/' : $base . '/' . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path = ''): string
    {
        $path = preg_replace('#^/?(public/)?assets/?#i', '', $path) ?? $path;

        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $location, int $status = 302): never
    {
        $target = preg_match('#^https?://#i', $location) === 1 ? $location : url($location);

        if (!headers_sent()) {
            header('Location: ' . $target, true, $status);
        }

        exit;
    }
}

if (!function_exists('session_start_safe')) {
    function session_start_safe(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (!headers_sent()) {
            $config = (array) config('app.session', []);

            session_name((string) ($config['name'] ?? 'newjeans_session'));
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => (bool) ($config['httponly'] ?? true),
                'samesite' => (string) ($config['samesite'] ?? 'Lax'),
                'secure' => (bool) ($config['secure'] ?? false),
            ]);
        }

        session_start();
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null): ?string
    {
        session_start_safe();

        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;

            return null;
        }

        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        session_start_safe();

        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('set_old')) {
    function set_old(array $input): void
    {
        session_start_safe();

        $_SESSION['_old'] = $input;
    }
}

if (!function_exists('set_form_errors')) {
    function set_form_errors(array $errors): void
    {
        session_start_safe();

        $_SESSION['_form_errors'] = $errors;
    }
}

if (!function_exists('form_errors')) {
    /**
     * @return array<string, string>
     */
    function form_errors(): array
    {
        session_start_safe();

        $errors = $_SESSION['_form_errors'] ?? [];
        unset($_SESSION['_form_errors']);

        return is_array($errors) ? $errors : [];
    }
}

if (!function_exists('clear_form_state')) {
    function clear_form_state(): void
    {
        session_start_safe();

        unset($_SESSION['_old'], $_SESSION['_form_errors']);
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $value, string $format = 'M j, Y'): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? '' : date($format, $timestamp);
    }
}

if (!function_exists('datetime_attr')) {
    function datetime_attr(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? '' : date('c', $timestamp);
    }
}

if (!function_exists('excerpt')) {
    function excerpt(string $text, int $limit = 150): string
    {
        $clean = trim((string) preg_replace('/\s+/u', ' ', $text));

        if ($clean === '') {
            return '';
        }

        $length = function_exists('mb_strlen') ? mb_strlen($clean, 'UTF-8') : strlen($clean);

        if ($length <= $limit) {
            return $clean;
        }

        $cut = function_exists('mb_substr')
            ? mb_substr($clean, 0, $limit, 'UTF-8')
            : substr($clean, 0, $limit);

        $lastSpace = function_exists('mb_strrpos')
            ? mb_strrpos($cut, ' ', 0, 'UTF-8')
            : strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace > 0) {
            $cut = function_exists('mb_substr')
                ? mb_substr($cut, 0, $lastSpace, 'UTF-8')
                : substr($cut, 0, $lastSpace);
        }

        return rtrim($cut) . '…';
    }
}

if (!function_exists('reading_time')) {
    function reading_time(string $text): int
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $words = array_filter($words, static fn (string $word): bool => $word !== '');

        return max(1, (int) round(count($words) / 200));
    }
}

if (!function_exists('paragraphs')) {
    /**
     * @return list<string>
     */
    function paragraphs(string $text): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($text));
        $blocks = preg_split('/\n\s*\n/', $normalized) ?: [];
        $result = [];

        foreach ($blocks as $block) {
            $block = trim((string) preg_replace('/\n+/', ' ', trim($block)));

            if ($block !== '') {
                $result[] = $block;
            }
        }

        return $result;
    }
}

if (!function_exists('money')) {
    function money(mixed $amount): string
    {
        if ($amount === null || $amount === '' || !is_numeric($amount)) {
            return '$0.00';
        }

        return '$' . number_format((float) $amount, 2);
    }
}
