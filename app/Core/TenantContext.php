<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Request-scoped tenant identifier.
 * Set once by AuthMiddleware; read by every Repository query.
 * Reset between requests in test suite via TenantContext::reset().
 */
final class TenantContext
{
    private static ?int $tenantId = null;

    public static function set(int $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    public static function id(): int
    {
        if (self::$tenantId === null) {
            throw new RuntimeException(
                'TenantContext not initialized. Ensure AuthMiddleware ran before this call.'
            );
        }

        return self::$tenantId;
    }

    public static function isSet(): bool
    {
        return self::$tenantId !== null;
    }

    public static function reset(): void
    {
        self::$tenantId = null;
    }
}
