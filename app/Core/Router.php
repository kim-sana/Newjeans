<?php

namespace App\Core;

use Closure;

final class Router
{
    /** @var array<string, list<array{pattern: string, handler: callable|array}>> */
    private array $routes = [];

    private ?Closure $notFoundHandler = null;

    public function get(string $path, callable|array $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable|array $handler): self
    {
        $this->routes[strtoupper($method)][] = [
            'pattern' => $this->normalize($path),
            'handler' => $handler,
        ];

        return $this;
    }

    public function notFound(callable $handler): self
    {
        $this->notFoundHandler = $handler(...);

        return $this;
    }

    public function dispatch(string $method, string $uri): Response
    {
        $method = strtoupper($method);
        $request = Request::create($method, $uri, $_GET, $_POST);
        $path = $request->path();

        foreach ($this->routes[$method] ?? [] as $route) {
            $params = $this->match($route['pattern'], $path);

            if ($params === null) {
                continue;
            }

            return $this->toResponse($this->invoke($route['handler'], $request, $params));
        }

        if ($this->notFoundHandler !== null) {
            return $this->toResponse(($this->notFoundHandler)($request));
        }

        return Response::text('404 Not Found', 404);
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }

        $patternSegments = explode('/', trim($pattern, '/'));
        $pathSegments = explode('/', trim($path, '/'));

        if (count($patternSegments) !== count($pathSegments)) {
            return null;
        }

        $params = [];

        foreach ($patternSegments as $index => $segment) {
            $actual = $pathSegments[$index];

            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $segment, $matches) === 1) {
                $params[$matches[1]] = rawurldecode($actual);
                continue;
            }

            if ($segment !== $actual) {
                return null;
            }
        }

        return $params;
    }

    private function invoke(callable|array $handler, Request $request, array $params): mixed
    {
        if (is_array($handler) && isset($handler[0]) && is_string($handler[0]) && class_exists($handler[0])) {
            $handler = [new $handler[0](), $handler[1]];
        }

        return $handler($request, $params);
    }

    private function toResponse(mixed $result): Response
    {
        return match (true) {
            $result instanceof Response => $result,
            $result === null => Response::html(''),
            is_string($result) => Response::html($result),
            default => Response::text((string) $result),
        };
    }
}
