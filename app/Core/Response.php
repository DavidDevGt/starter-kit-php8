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
        return new self(status: $status, headers: ['Location' => $url]);
    }

    public static function view(string $path, array $data = []): self
    {
        if (!file_exists($path)) {
            return self::serverError("View [{$path}] not found.");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $path;
        return new self(body: ob_get_clean() ?: '');
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
            header("{$key}: {$value}");
        }

        echo $this->body;
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getStatus(): int    { return $this->status; }
    public function getBody(): string   { return $this->body; }
    public function getHeaders(): array { return $this->headers; }
}
