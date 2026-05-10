<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Core\Response;
use Tests\TestCase;

/**
 * Verifies that security-critical Response behaviors hold under adversarial inputs.
 */
class ResponseSecurityTest extends TestCase
{
    // ── Open redirect prevention ──────────────────────────────────────────────

    /** @dataProvider externalUrlProvider */
    public function test_redirect_blocks_external_url(string $url): void
    {
        $response = Response::redirect($url);
        $location = $response->getHeaders()['Location'];

        $this->assertSame('/', $location, "External URL [{$url}] was not blocked.");
    }

    public static function externalUrlProvider(): array
    {
        return [
            'http scheme'       => ['http://attacker.example.com'],
            'https scheme'      => ['https://attacker.example.com/phish'],
            'protocol relative' => ['//attacker.example.com'],
            'javascript scheme' => ['javascript:alert(document.cookie)'],
            'data scheme'       => ['data:text/html,<script>alert(1)</script>'],
            'ftp scheme'        => ['ftp://files.example.com'],
        ];
    }

    /** @dataProvider safeRedirectProvider */
    public function test_redirect_allows_safe_relative_url(string $url): void
    {
        $response = Response::redirect($url);
        $location = $response->getHeaders()['Location'];

        $this->assertSame($url, $location, "Safe URL [{$url}] was incorrectly blocked.");
    }

    public static function safeRedirectProvider(): array
    {
        return [
            'root'          => ['/'],
            'login'         => ['/login'],
            'dashboard'     => ['/dashboard'],
            'nested path'   => ['/api/v1/users'],
            'with query'    => ['/search?q=test'],
            'billing'       => ['/billing/upgrade'],
        ];
    }

    // ── Path traversal prevention in view() ───────────────────────────────────

    /** @dataProvider pathTraversalProvider */
    public function test_view_blocks_path_traversal(string $path): void
    {
        $response = Response::view($path);
        $this->assertSame(500, $response->getStatus(), "Path [{$path}] was not blocked.");
    }

    public static function pathTraversalProvider(): array
    {
        return [
            'absolute system path'    => ['/etc/passwd'],
            'traversal above root'    => [__DIR__ . '/../../../../etc/shadow'],
            'nonexistent file'        => ['/tmp/does-not-exist-' . uniqid() . '.php'],
            'dot-dot traversal'       => ['../../../etc/hostname'],
        ];
    }

    // ── JSON encoding ─────────────────────────────────────────────────────────

    public function test_json_response_escapes_unicode_correctly(): void
    {
        $response = Response::json(['message' => 'Héllo wörld']);
        $this->assertStringContainsString('Héllo', $response->getBody());
    }

    public function test_json_content_type_header_present(): void
    {
        $response = Response::json(['ok' => true]);
        $this->assertStringContainsString('application/json', $response->getHeaders()['Content-Type']);
    }

    // ── withHeader() immutability ─────────────────────────────────────────────

    public function test_with_header_does_not_mutate_original(): void
    {
        $original = Response::json(['a' => 1]);
        $original->withHeader('X-Test', 'value');

        $this->assertArrayNotHasKey('X-Test', $original->getHeaders());
    }

    // ── HTTP status factories ─────────────────────────────────────────────────

    /** @dataProvider statusFactoryProvider */
    public function test_factory_returns_correct_status(int $expected, Response $response): void
    {
        $this->assertSame($expected, $response->getStatus());
    }

    public static function statusFactoryProvider(): array
    {
        return [
            '200 json'     => [200, Response::json([])],
            '404 notFound' => [404, Response::notFound()],
            '401 unauth'   => [401, Response::unauthorized()],
            '403 forbidden'=> [403, Response::forbidden()],
            '422 validate' => [422, Response::validationError([])],
            '500 server'   => [500, Response::serverError()],
        ];
    }
}
