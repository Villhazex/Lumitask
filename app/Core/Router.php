<?php

namespace App\Core;

class Router
{
    /** @var array<string,array<string,callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): self
    {
        return $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): self
    {
        return $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): self
    {
        $path = $this->normalize($path);
        $this->routes[$method][$path] = $handler;

        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = $this->normalize(parse_url($uri, PHP_URL_PATH) ?: '/');

        $handler = $this->routes[$method][$uri] ?? null;

        if (!$handler && $method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            $handler = $this->routes[$override][$uri] ?? null;
        }

        if (!$handler) {
            Response::json(['error' => 'Not Found', 'path' => $uri], 404);
        }

        $handler();
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
