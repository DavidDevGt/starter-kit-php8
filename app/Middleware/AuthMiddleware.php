<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'], $_SESSION['company_id'])) {
            return $this->unauthenticated($request);
        }

        TenantContext::set((int) $_SESSION['company_id']);

        $request = $request
            ->setAttribute('user_id',   (int)    $_SESSION['user_id'])
            ->setAttribute('company_id', (int)   $_SESSION['company_id'])
            ->setAttribute('username',            $_SESSION['username'] ?? '');

        return $next($request);
    }

    private function unauthenticated(Request $request): Response
    {
        if ($this->expectsJson($request)) {
            return Response::unauthorized('Authentication required.');
        }

        return Response::redirect('/login');
    }

    private function expectsJson(Request $request): bool
    {
        return str_contains($request->header('Accept') ?? '', 'application/json')
            || $request->isJson();
    }
}
