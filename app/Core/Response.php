<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public function __construct(
        private readonly string $body    = '',
        private readonly int    $status  = 200,
        private readonly array  $headers = [],
    ) {}

    // ── Factories ────────────────────────────────────────────────────────────

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            body:    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            status:  $status,
            headers: ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }

    public static function redirect(string $url, int $status = 302): self
    {
        // Only allow relative URLs to prevent open redirect
        if (str_contains($url, '://') || str_starts_with($url, '//')) {
            $url = '/';
        }
        return new self(status: $status, headers: ['Location' => $url]);
    }

    public static function view(string $path, array $data = []): self
    {
        $realPath = realpath($path);
        $basePath = realpath(dirname(__DIR__, 2));

        // Prevent path traversal: resolved path must be inside the application root
        if ($realPath === false || $basePath === false || !str_starts_with($realPath, $basePath)) {
            return self::serverError('View not found.');
        }

        // Render in an isolated closure to avoid polluting the class scope via extract()
        $render = static function (string $__path, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            require $__path;
            return ob_get_clean() ?: '';
        };

        return new self(body: $render($realPath, $data));
    }

    public static function notFound(string $message = 'Not Found'): self
    {
        return self::json(['error' => $message], 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return self::json(['error' => $message], 401);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return self::json(['error' => $message], 403);
    }

    public static function validationError(array $errors): self
    {
        return self::json(['errors' => $errors], 422);
    }

    public static function serverError(string $message = 'Internal Server Error'): self
    {
        return self::json(['error' => $message], 500);
    }

    // ── Mutation (returns new instance — immutable) ──────────────────────────

    public function withHeader(string $key, string $value): self
    {
        $headers       = $this->headers;
        $headers[$key] = $value;
        return new self($this->body, $this->status, $headers);
    }

    // ── Emission ─────────────────────────────────────────────────────────────

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $key => $value) {
            // Strip CRLF and null bytes to prevent header injection
            $key   = preg_replace('/[\r\n\0]/', '', $key)   ?? '';
            $value = preg_replace('/[\r\n\0]/', '', $value) ?? '';
            header("{$key}: {$value}");
        }

        echo $this->body;
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getStatus(): int    { return $this->status; }
    public function getBody(): string   { return $this->body; }
    public function getHeaders(): array { return $this->headers; }
}
