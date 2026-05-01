<?php

declare(strict_types=1);

namespace Tests;

use App\Core\TenantContext;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected const FIXTURE_USERNAME = 'fixture-user';
    protected const FIXTURE_EMAIL    = 'fixture@test.example';
    protected const FIXTURE_PASS     = 'fixture-plaintext-value';
    protected const FIXTURE_PASS_ALT = 'fixture-alternate-value';

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::reset();
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    protected function setTenant(int $id): void
    {
        TenantContext::set($id);
    }

    /**
     * Assert that a JSON string contains a key with an expected value.
     */
    protected function assertJsonContains(string $json, string $key, mixed $expected): void
    {
        $data = json_decode($json, true);
        $this->assertArrayHasKey($key, $data, "Key [{$key}] not found in JSON response.");
        $this->assertSame($expected, $data[$key]);
    }

    protected function assertJsonKey(string $json, string $key): void
    {
        $data = json_decode($json, true);
        $this->assertArrayHasKey($key, $data, "Key [{$key}] not found in JSON response.");
    }
}
