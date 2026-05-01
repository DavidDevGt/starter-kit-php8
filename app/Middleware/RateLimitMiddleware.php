<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Config\Database;

/**
 * Sliding-window rate limiter backed by the `rate_limit_log` table.
 * In production, replace with a Redis-backed implementation.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Database $db,
        private readonly int $maxAttempts  = 5,
        private readonly int $windowSeconds = 600,
    ) {}

    public function process(Request $request, callable $next): Response
    {
        $key  = 'login:' . $request->ip();
        $conn = $this->db->connect();

        $stmt = $conn->prepare(
            'SELECT COUNT(*) AS attempts FROM rate_limit_log
             WHERE `key` = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)'
        );
        $stmt->bind_param('si', $key, $this->windowSeconds);
        $stmt->execute();
        $attempts = (int) $stmt->get_result()->fetch_assoc()['attempts'];

        if ($attempts >= $this->maxAttempts) {
            return Response::json(
                ['error' => 'Too many attempts. Please try again later.'],
                429,
            )->withHeader('Retry-After', (string) $this->windowSeconds);
        }

        $this->log($conn, $key);

        return $next($request);
    }

    private function log(\mysqli $conn, string $key): void
    {
        $stmt = $conn->prepare('INSERT INTO rate_limit_log (`key`) VALUES (?)');
        $stmt->bind_param('s', $key);
        $stmt->execute();
    }
}
