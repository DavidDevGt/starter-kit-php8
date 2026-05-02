<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private array $attributes = [];

    public function __construct(
        private readonly array $body,
        private readonly array $query,
        private readonly array $server,
        private readonly array $files = [],
    ) {}

    public static function fromGlobals(): self
    {
        $body = $_POST;

        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $raw  = file_get_contents('php://input');
            $body = json_decode($raw ?: '{}', true) ?? [];
        }

        return new self(
            body:   $body,
            query:  $_GET,
            server: $_SERVER,
            files:  $_FILES,
        );
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH) ?: '/';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function only(string ...$keys): array
    {
        $all = array_merge($this->query, $this->body);
        return array_intersect_key($all, array_flip($keys));
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body) || array_key_exists($key, $this->query);
    }

    public function isJson(): bool
    {
        return str_contains($this->server['CONTENT_TYPE'] ?? '', 'application/json');
    }

    public function ip(): string
    {
        // Only trust X-Forwarded-For when TRUSTED_PROXIES is explicitly configured,
        // otherwise X-Forwarded-For is attacker-controlled and bypasses rate limiting.
        $trustedProxies = array_filter(
            explode(',', $_ENV['TRUSTED_PROXIES'] ?? ''),
            static fn(string $s) => $s !== '',
        );

        $remoteAddr = $this->server['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($trustedProxies && in_array($remoteAddr, $trustedProxies, true)) {
            $forwarded = trim(explode(',', $this->server['HTTP_X_FORWARDED_FOR'] ?? '')[0]);
            if ($forwarded && filter_var($forwarded, FILTER_VALIDATE_IP)) {
                return $forwarded;
            }
        }

        return $remoteAddr;
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization') ?? '';
        return str_starts_with($header, 'Bearer ')
            ? substr($header, 7)
            : null;
    }

    public function setAttribute(string $key, mixed $value): self
    {
        $clone             = clone $this;
        $clone->attributes = $this->attributes;
        $clone->attributes[$key] = $value;
        return $clone;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
