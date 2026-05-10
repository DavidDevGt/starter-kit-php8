<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use Tests\TestCase;

class CsrfMiddlewareTest extends TestCase
{
    private CsrfMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION = [];
        $this->middleware = new CsrfMiddleware();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    private function makeRequest(
        string $method,
        string $path = '/login',
        array $body = [],
        array $headers = [],
    ): Request {
        $server = array_merge(
            ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $path, 'CONTENT_TYPE' => 'application/json'],
            $headers,
        );
        return new Request(body: $body, query: [], server: $server);
    }

    private function passThrough(): callable
    {
        return static fn(Request $req) => Response::json(['passed' => true]);
    }

    // ── Safe methods bypass ───────────────────────────────────────────────────

    public function test_get_request_bypasses_csrf_check(): void
    {
        $response = $this->middleware->process($this->makeRequest('GET'), $this->passThrough());
        $this->assertSame(200, $response->getStatus());
    }

    public function test_head_request_bypasses_csrf_check(): void
    {
        $response = $this->middleware->process($this->makeRequest('HEAD'), $this->passThrough());
        $this->assertSame(200, $response->getStatus());
    }

    public function test_options_request_bypasses_csrf_check(): void
    {
        $response = $this->middleware->process($this->makeRequest('OPTIONS'), $this->passThrough());
        $this->assertSame(200, $response->getStatus());
    }

    // ── Webhook exclusion ─────────────────────────────────────────────────────

    public function test_stripe_webhook_bypasses_csrf_check(): void
    {
        $response = $this->middleware->process(
            $this->makeRequest('POST', '/webhooks/stripe'),
            $this->passThrough(),
        );

        $this->assertSame(200, $response->getStatus());
    }

    // ── Missing token ─────────────────────────────────────────────────────────

    public function test_post_without_token_returns_419(): void
    {
        $_SESSION['csrf_token'] = 'some-token';

        $response = $this->middleware->process($this->makeRequest('POST'), $this->passThrough());

        $this->assertSame(419, $response->getStatus());
    }

    public function test_post_with_no_session_token_returns_419(): void
    {
        // No session token set at all — should never pass even with a body token
        $response = $this->middleware->process(
            $this->makeRequest('POST', body: ['_csrf' => 'any-value']),
            $this->passThrough(),
        );

        $this->assertSame(419, $response->getStatus());
    }

    // ── Invalid token ─────────────────────────────────────────────────────────

    public function test_post_with_wrong_token_returns_419(): void
    {
        $_SESSION['csrf_token'] = 'correct-csrf-token-value';

        $response = $this->middleware->process(
            $this->makeRequest('POST', body: ['_csrf' => 'wrong-csrf-token-value']),
            $this->passThrough(),
        );

        $this->assertSame(419, $response->getStatus());
    }

    // ── Valid token via body ──────────────────────────────────────────────────

    public function test_post_with_correct_body_token_passes_through(): void
    {
        $_SESSION['csrf_token'] = 'valid-csrf-token-xyz';

        $response = $this->middleware->process(
            $this->makeRequest('POST', body: ['_csrf' => 'valid-csrf-token-xyz']),
            $this->passThrough(),
        );

        $this->assertSame(200, $response->getStatus());
    }

    // ── Valid token via header ────────────────────────────────────────────────

    public function test_post_with_correct_header_token_passes_through(): void
    {
        $_SESSION['csrf_token'] = 'header-csrf-token-abc';

        $response = $this->middleware->process(
            $this->makeRequest('POST', headers: ['HTTP_X_CSRF_TOKEN' => 'header-csrf-token-abc']),
            $this->passThrough(),
        );

        $this->assertSame(200, $response->getStatus());
    }

    // ── PUT / DELETE also checked ─────────────────────────────────────────────

    public function test_put_without_token_returns_419(): void
    {
        $_SESSION['csrf_token'] = 'some-token';

        $response = $this->middleware->process($this->makeRequest('PUT'), $this->passThrough());

        $this->assertSame(419, $response->getStatus());
    }

    public function test_delete_without_token_returns_419(): void
    {
        $_SESSION['csrf_token'] = 'some-token';

        $response = $this->middleware->process($this->makeRequest('DELETE'), $this->passThrough());

        $this->assertSame(419, $response->getStatus());
    }

    // ── token() helper ────────────────────────────────────────────────────────

    public function test_token_generates_and_stores_in_session(): void
    {
        $token = CsrfMiddleware::token();

        $this->assertNotEmpty($token);
        $this->assertSame($token, $_SESSION['csrf_token']);
    }

    public function test_token_is_idempotent_within_same_session(): void
    {
        $first  = CsrfMiddleware::token();
        $second = CsrfMiddleware::token();

        $this->assertSame($first, $second);
    }
}
