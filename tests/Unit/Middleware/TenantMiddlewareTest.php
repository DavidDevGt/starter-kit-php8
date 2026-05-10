<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\TenantMiddleware;
use App\Services\SubscriptionService;
use Mockery;
use Tests\TestCase;

class TenantMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeRequest(int $companyId = 1): Request
    {
        $request = new Request(
            body:   [],
            query:  [],
            server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/users', 'HTTP_ACCEPT' => 'application/json'],
        );
        return $request->setAttribute('company_id', $companyId);
    }

    private function passThrough(): callable
    {
        return static fn(Request $req) => Response::json(['passed' => true]);
    }

    // ── Active subscription ───────────────────────────────────────────────────

    public function test_active_subscription_passes_request_through(): void
    {
        $subs = Mockery::mock(SubscriptionService::class);
        $subs->shouldReceive('isActive')->with(1)->andReturn(true);

        $middleware = new TenantMiddleware($subs);
        $response   = $middleware->process($this->makeRequest(1), $this->passThrough());

        $this->assertSame(200, $response->getStatus());
        $this->assertJsonContains($response->getBody(), 'passed', true);
    }

    // ── Inactive / expired subscription ──────────────────────────────────────

    public function test_inactive_subscription_returns_402(): void
    {
        $subs = Mockery::mock(SubscriptionService::class);
        $subs->shouldReceive('isActive')->with(2)->andReturn(false);

        $middleware = new TenantMiddleware($subs);
        $response   = $middleware->process($this->makeRequest(2), $this->passThrough());

        $this->assertSame(402, $response->getStatus());
    }

    public function test_inactive_response_contains_upgrade_url(): void
    {
        $subs = Mockery::mock(SubscriptionService::class);
        $subs->shouldReceive('isActive')->andReturn(false);

        $middleware = new TenantMiddleware($subs);
        $response   = $middleware->process($this->makeRequest(), $this->passThrough());

        $data = json_decode($response->getBody(), true);

        $this->assertSame('subscription_required', $data['error']);
        $this->assertArrayHasKey('upgrade_url', $data);
    }

    public function test_inactive_response_does_not_call_next(): void
    {
        $subs = Mockery::mock(SubscriptionService::class);
        $subs->shouldReceive('isActive')->andReturn(false);

        $nextCalled = false;
        $next       = static function (Request $req) use (&$nextCalled): Response {
            $nextCalled = true;
            return Response::json(['ok' => true]);
        };

        $middleware = new TenantMiddleware($subs);
        $middleware->process($this->makeRequest(), $next);

        $this->assertFalse($nextCalled);
    }

    // ── isActive called with correct tenant ───────────────────────────────────

    public function test_uses_company_id_attribute_from_request(): void
    {
        $subs = Mockery::mock(SubscriptionService::class);
        $subs->shouldReceive('isActive')->with(42)->once()->andReturn(true);

        $middleware = new TenantMiddleware($subs);
        $response   = $middleware->process($this->makeRequest(42), $this->passThrough());

        $this->assertSame(200, $response->getStatus());
    }
}
