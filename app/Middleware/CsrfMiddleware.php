<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS    = ['GET', 'HEAD', 'OPTIONS'];
    private const EXCLUDED_PATHS  = ['/webhooks/stripe'];

    public function process(Request $request, callable $next): Response
    {
        if (in_array($request->method(), self::SAFE_METHODS, true)) {
            return $next($request);
        }

        if (in_array($request->path(), self::EXCLUDED_PATHS, true)) {
            return $next($request);
        }

        $token = $request->input('_csrf')
            ?? $request->header('X-CSRF-Token')
            ?? $request->header('X-Csrf-Token');

        if (!$token || !$this->validate((string) $token)) {
            return Response::json(['error' => 'CSRF token mismatch.'], 419);
        }

        return $next($request);
    }

    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    private function validate(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
