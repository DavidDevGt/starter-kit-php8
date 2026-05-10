<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Request;
use Tests\TestCase;

class RequestTest extends TestCase
{
    private function makeRequest(array $server = [], array $body = [], array $query = []): Request
    {
        return new Request(body: $body, query: $query, server: $server);
    }

    // ── ip() ─────────────────────────────────────────────────────────────────

    public function test_ip_returns_remote_addr_when_no_proxy_configured(): void
    {
        $_ENV['TRUSTED_PROXIES'] = '';

        $request = $this->makeRequest([
            'REMOTE_ADDR'          => '1.2.3.4',
            'HTTP_X_FORWARDED_FOR' => '9.9.9.9',
        ]);

        $this->assertSame('1.2.3.4', $request->ip());
    }

    public function test_ip_uses_forwarded_for_when_remote_addr_is_trusted_proxy(): void
    {
        $_ENV['TRUSTED_PROXIES'] = '10.0.0.1';

        $request = $this->makeRequest([
            'REMOTE_ADDR'          => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '5.6.7.8',
        ]);

        $this->assertSame('5.6.7.8', $request->ip());

        unset($_ENV['TRUSTED_PROXIES']);
    }

    public function test_ip_ignores_forwarded_for_when_remote_addr_not_in_trusted_list(): void
    {
        $_ENV['TRUSTED_PROXIES'] = '10.0.0.1';

        $request = $this->makeRequest([
            'REMOTE_ADDR'          => '3.3.3.3',
            'HTTP_X_FORWARDED_FOR' => '5.6.7.8',
        ]);

        $this->assertSame('3.3.3.3', $request->ip());

        unset($_ENV['TRUSTED_PROXIES']);
    }

    public function test_ip_rejects_invalid_forwarded_for_value(): void
    {
        $_ENV['TRUSTED_PROXIES'] = '10.0.0.1';

        $request = $this->makeRequest([
            'REMOTE_ADDR'          => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => 'not-an-ip',
        ]);

        $this->assertSame('10.0.0.1', $request->ip());

        unset($_ENV['TRUSTED_PROXIES']);
    }

    public function test_ip_falls_back_to_default_when_remote_addr_missing(): void
    {
        $_ENV['TRUSTED_PROXIES'] = '';

        $request = $this->makeRequest([]);

        $this->assertSame('0.0.0.0', $request->ip());
    }

    public function test_ip_uses_first_forwarded_for_when_multiple_proxies(): void
    {
        $_ENV['TRUSTED_PROXIES'] = '10.0.0.1';

        $request = $this->makeRequest([
            'REMOTE_ADDR'          => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '5.6.7.8, 10.0.0.2',
        ]);

        $this->assertSame('5.6.7.8', $request->ip());

        unset($_ENV['TRUSTED_PROXIES']);
    }

    // ── method() ─────────────────────────────────────────────────────────────

    public function test_method_is_uppercased(): void
    {
        $request = $this->makeRequest(['REQUEST_METHOD' => 'post']);
        $this->assertSame('POST', $request->method());
    }

    public function test_method_defaults_to_get(): void
    {
        $request = $this->makeRequest([]);
        $this->assertSame('GET', $request->method());
    }

    // ── only() ───────────────────────────────────────────────────────────────

    public function test_only_filters_to_specified_keys(): void
    {
        $request = $this->makeRequest(body: ['username' => 'alice', 'password' => 'secret', 'extra' => 'x']);
        $result  = $request->only('username', 'password');

        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('password', $result);
        $this->assertArrayNotHasKey('extra', $result);
    }

    public function test_only_prefers_body_over_query(): void
    {
        $request = $this->makeRequest(
            body:  ['key' => 'body-val'],
            query: ['key' => 'query-val'],
        );

        $this->assertSame('body-val', $request->only('key')['key']);
    }

    // ── setAttribute / getAttribute ───────────────────────────────────────────

    public function test_set_and_get_attribute_returns_immutable_clone(): void
    {
        $original = $this->makeRequest();
        $clone    = $original->setAttribute('user_id', 42);

        $this->assertNull($original->getAttribute('user_id'));
        $this->assertSame(42, $clone->getAttribute('user_id'));
    }

    public function test_get_attribute_returns_default_when_missing(): void
    {
        $request = $this->makeRequest();
        $this->assertSame('fallback', $request->getAttribute('missing', 'fallback'));
    }

    // ── header() ─────────────────────────────────────────────────────────────

    public function test_header_returns_value_by_http_name(): void
    {
        $request = $this->makeRequest(['HTTP_ACCEPT' => 'application/json']);
        $this->assertSame('application/json', $request->header('Accept'));
    }

    public function test_bearer_token_extracted_correctly(): void
    {
        $request = $this->makeRequest(['HTTP_AUTHORIZATION' => 'Bearer abc123token']);
        $this->assertSame('abc123token', $request->bearerToken());
    }

    public function test_bearer_token_returns_null_when_no_authorization(): void
    {
        $request = $this->makeRequest([]);
        $this->assertNull($request->bearerToken());
    }

    // ── path() ───────────────────────────────────────────────────────────────

    public function test_path_strips_query_string(): void
    {
        $request = $this->makeRequest(['REQUEST_URI' => '/api/v1/users?page=2']);
        $this->assertSame('/api/v1/users', $request->path());
    }

    public function test_path_defaults_to_slash(): void
    {
        $request = $this->makeRequest([]);
        $this->assertSame('/', $request->path());
    }
}
