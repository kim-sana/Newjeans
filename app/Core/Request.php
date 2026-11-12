<?php

namespace App\Core;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $body = []
    ) {
    }

    public static function capture(): self
    {
        return self::create(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            $_GET,
            $_POST
        );
    }

    public static function create(string $method, string $uri, array $query = [], array $body = []): self
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return new self(strtoupper($method), $path, $query, $body);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $this->body)) {
                $result[$key] = $this->body[$key];
            } elseif (array_key_exists($key, $this->query)) {
                $result[$key] = $this->query[$key];
            }
        }

        return $result;
    }
}
