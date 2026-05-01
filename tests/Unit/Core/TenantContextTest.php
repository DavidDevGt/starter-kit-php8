<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\TenantContext;
use RuntimeException;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    public function test_set_and_get_tenant_id(): void
    {
        TenantContext::set(42);
        $this->assertSame(42, TenantContext::id());
    }

    public function test_is_set_returns_true_after_set(): void
    {
        TenantContext::set(1);
        $this->assertTrue(TenantContext::isSet());
    }

    public function test_is_set_returns_false_initially(): void
    {
        // setUp calls reset()
        $this->assertFalse(TenantContext::isSet());
    }

    public function test_id_throws_when_not_initialized(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/TenantContext not initialized/');

        TenantContext::id();
    }

    public function test_reset_clears_tenant_id(): void
    {
        TenantContext::set(99);
        TenantContext::reset();

        $this->assertFalse(TenantContext::isSet());
    }

    public function test_overwriting_tenant_id(): void
    {
        TenantContext::set(1);
        TenantContext::set(2);

        $this->assertSame(2, TenantContext::id());
    }
}
