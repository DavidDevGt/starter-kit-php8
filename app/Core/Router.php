<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array  $routes          = [];
    private array  $groupMiddleware = [];
    private string $groupPrefix     = '';

    // ── Route registration ────────────────────────────────────────────────────

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, callable|array $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function group(array $options, callable $callback): void
    {
        $prevPrefix     = $this->groupPrefix;
        $prevMiddleware = $this->groupMiddleware;

        $this->groupPrefix     .= $options['prefix'] ?? '';
        $this->groupMiddleware  = array_merge(
            $this->groupMiddleware,
            $options['middleware'] ?? [],
        );

        $callback($this);

        $this->groupPrefix     = $prevPrefix;
        $this->groupMiddleware = $prevMiddleware;
    }

    // ── Dispatch ──────────────────────────────────────────────────────────────

    public function dispatch(Request $request, Container $container): Response
    {
        $method = $request->method();
        $path   = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchPath($route['path'], $path);

            if ($params === null) {
                continue;
            }

            foreach ($params as $key => $value) {
                $request = $request->setAttribute($key, $value);
            }

            $middlewares = array_map(
                fn(string $m) => $container->make($m),
                $route['middleware'],
            );

            $handler  = $this->resolveHandler($route['handler'], $container);
            $pipeline = new Pipeline($middlewares, $handler);

            return $pipeline->handle($request);
        }

        return Response::notFound("Route [{$method}] {$path} not found.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = [
            'method'     => $method,
            'path'       => $this->groupPrefix . $path,
            'handler'    => $handler,
            'middleware' => $this->groupMiddleware,
        ];
    }

    private function matchPath(string $routePath, string $requestPath): ?array
    {
        // Cap request path length to prevent ReDoS via catastrophic backtracking
        if (strlen($requestPath) > 2048) {
            return null;
        }

        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]++)', $routePath);
        $pattern = "#^{$pattern}$#";

        if (@preg_match($pattern, $requestPath, $matches) !== 1) {
            return null;
        }

        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    private function resolveHandler(callable|array $handler, Container $container): callable
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = $container->make($class);
            return static fn(Request $req) => $instance->$method($req);
        }

        return $handler;
    }
}
