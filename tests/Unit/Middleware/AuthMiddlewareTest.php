<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    private AuthMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION = [];
        $this->middleware = new AuthMiddleware();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    private function makeRequest(string $accept = 'application/json'): Request
    {
        return new Request(
            body:   [],
            query:  [],
            server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/users', 'HTTP_ACCEPT' => $accept],
        );
    }

    private function passThrough(): callable
    {
        return static fn(Request $req) => Response::json(['passed' => true]);
    }

    // ── Unauthenticated ───────────────────────────────────────────────────────

    public function test_no_session_returns_401_for_json_requests(): void
    {
        $response = $this->middleware->process($this->makeRequest(), $this->passThrough());

        $this->assertSame(401, $response->getStatus());
    }

    public function test_no_session_redirects_browser_requests(): void
    {
        $response = $this->middleware->process($this->makeRequest('text/html'), $this->passThrough());

        $this->assertSame(302, $response->getStatus());
        $this->assertSame('/login', $response->getHeaders()['Location']);
    }

    // ── Authenticated ─────────────────────────────────────────────────────────

    public function test_valid_session_passes_request_through(): void
    {
        $_SESSION['user_id']    = 1;
        $_SESSION['company_id'] = 5;
        $_SESSION['username']   = self::FIXTURE_USERNAME;
        $_SESSION['_expires']   = time() + 3600;

        $response = $this->middleware->process($this->makeRequest(), $this->passThrough());

        $this->assertSame(200, $response->getStatus());
        $this->assertJsonContains($response->getBody(), 'passed', true);
    }

    public function test_valid_session_sets_user_id_attribute(): void
    {
        $_SESSION['user_id']    = 7;
        $_SESSION['company_id'] = 3;
        $_SESSION['username']   = self::FIXTURE_USERNAME;
        $_SESSION['_expires']   = time() + 3600;

        $captured = null;
        $this->middleware->process(
            $this->makeRequest(),
            static function (Request $req) use (&$captured): Response {
                $captured = $req;
                return Response::json(['ok' => true]);
            },
        );

        $this->assertSame(7, $captured->getAttribute('user_id'));
        $this->assertSame(3, $captured->getAttribute('company_id'));
    }

    // ── Session expiry ────────────────────────────────────────────────────────

    public function test_expired_session_returns_401(): void
    {
        $_SESSION['user_id']    = 1;
        $_SESSION['company_id'] = 5;
        $_SESSION['_expires']   = time() - 1; // already expired

        $response = $this->middleware->process($this->makeRequest(), $this->passThrough());

        $this->assertSame(401, $response->getStatus());
    }

    public function test_session_without_expires_key_passes_through(): void
    {
        // Sessions created before expiry tracking was added should still work
        $_SESSION['user_id']    = 1;
        $_SESSION['company_id'] = 5;
        $_SESSION['username']   = self::FIXTURE_USERNAME;
        // No _expires key

        $response = $this->middleware->process($this->makeRequest(), $this->passThrough());

        $this->assertSame(200, $response->getStatus());
    }

    // ── TenantContext ─────────────────────────────────────────────────────────

    public function test_sets_tenant_context_from_session(): void
    {
        $_SESSION['user_id']    = 1;
        $_SESSION['company_id'] = 99;
        $_SESSION['username']   = self::FIXTURE_USERNAME;
        $_SESSION['_expires']   = time() + 3600;

        $this->middleware->process($this->makeRequest(), $this->passThrough());

        $this->assertSame(99, \App\Core\TenantContext::id());
    }
}
