<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Response;
use Tests\TestCase;

class ResponseTest extends TestCase
{
    // ── redirect() ────────────────────────────────────────────────────────────

    public function test_redirect_allows_relative_path(): void
    {
        $response = Response::redirect('/dashboard');
        $headers  = $response->getHeaders();

        $this->assertSame(302, $response->getStatus());
        $this->assertSame('/dashboard', $headers['Location']);
    }

    public function test_redirect_blocks_absolute_url_with_protocol(): void
    {
        $response = Response::redirect('https://evil.example.com/phishing');
        $headers  = $response->getHeaders();

        $this->assertSame('/', $headers['Location']);
    }

    public function test_redirect_blocks_protocol_relative_url(): void
    {
        $response = Response::redirect('//evil.example.com');
        $headers  = $response->getHeaders();

        $this->assertSame('/', $headers['Location']);
    }

    public function test_redirect_blocks_javascript_scheme(): void
    {
        $response = Response::redirect('javascript://alert(1)');
        $headers  = $response->getHeaders();

        $this->assertSame('/', $headers['Location']);
    }

    public function test_redirect_preserves_status_code(): void
    {
        $response = Response::redirect('/login', 301);
        $this->assertSame(301, $response->getStatus());
    }

    // ── json() ───────────────────────────────────────────────────────────────

    public function test_json_sets_correct_content_type(): void
    {
        $response = Response::json(['key' => 'value']);
        $headers  = $response->getHeaders();

        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('application/json', $headers['Content-Type']);
    }

    public function test_json_encodes_data(): void
    {
        $response = Response::json(['success' => true, 'count' => 3]);
        $data     = json_decode($response->getBody(), true);

        $this->assertTrue($data['success']);
        $this->assertSame(3, $data['count']);
    }

    public function test_json_accepts_custom_status(): void
    {
        $response = Response::json(['error' => 'Not Found'], 404);
        $this->assertSame(404, $response->getStatus());
    }

    // ── view() path traversal guard ───────────────────────────────────────────

    public function test_view_rejects_path_outside_application_root(): void
    {
        $response = Response::view('/etc/passwd');
        $this->assertSame(500, $response->getStatus());
    }

    public function test_view_rejects_path_traversal_sequence(): void
    {
        $response = Response::view(__DIR__ . '/../../../../etc/shadow');
        $this->assertSame(500, $response->getStatus());
    }

    public function test_view_rejects_nonexistent_file(): void
    {
        $response = Response::view(__DIR__ . '/does-not-exist.php');
        $this->assertSame(500, $response->getStatus());
    }

    // ── withHeader() immutability ─────────────────────────────────────────────

    public function test_with_header_returns_new_instance(): void
    {
        $original = Response::json(['ok' => true]);
        $modified = $original->withHeader('X-Custom', 'value');

        $this->assertNotSame($original, $modified);
        $this->assertArrayNotHasKey('X-Custom', $original->getHeaders());
        $this->assertSame('value', $modified->getHeaders()['X-Custom']);
    }

    // ── Factory helpers ───────────────────────────────────────────────────────

    public function test_not_found_returns_404(): void
    {
        $response = Response::notFound('Missing');
        $this->assertSame(404, $response->getStatus());
        $this->assertJsonContains($response->getBody(), 'error', 'Missing');
    }

    public function test_unauthorized_returns_401(): void
    {
        $response = Response::unauthorized();
        $this->assertSame(401, $response->getStatus());
    }

    public function test_forbidden_returns_403(): void
    {
        $response = Response::forbidden();
        $this->assertSame(403, $response->getStatus());
    }

    public function test_validation_error_returns_422(): void
    {
        $response = Response::validationError(['field' => ['Required.']]);
        $this->assertSame(422, $response->getStatus());
        $this->assertJsonKey($response->getBody(), 'errors');
    }

    public function test_server_error_returns_500(): void
    {
        $response = Response::serverError();
        $this->assertSame(500, $response->getStatus());
    }
}
