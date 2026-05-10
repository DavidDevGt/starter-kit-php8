<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Core\Container;
use App\Core\Request;
use App\Core\Router;
use App\DTOs\TenantDTO;
use App\Http\Controllers\AuthController;
use App\Services\AuthService;
use App\Services\TenantService;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    private Router    $router;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router    = new Router();
        $this->container = new Container();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeRequest(array $body): Request
    {
        return new Request(
            body:   $body,
            query:  [],
            server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/register', 'HTTP_ACCEPT' => 'application/json'],
        );
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Acme Corp',
            'nit'          => '12345678',
            'email'        => self::FIXTURE_EMAIL,
            'username'     => self::FIXTURE_USERNAME,
            'password'     => self::FIXTURE_PASS,
            'country_id'   => 1,
        ], $overrides);
    }

    private function fakeTenantDTO(): TenantDTO
    {
        return new TenantDTO(
            id:        1,
            code:      'ACM001',
            name:      'Acme Corp',
            nit:       '12345678',
            email:     self::FIXTURE_EMAIL,
            countryId: 1,
            active:    true,
            createdAt: '2025-01-01 00:00:00',
        );
    }

    private function bind(?AuthService $auth = null, ?TenantService $tenants = null): void
    {
        $this->container->bind(AuthService::class,   fn() => $auth    ?? Mockery::mock(AuthService::class));
        $this->container->bind(TenantService::class, fn() => $tenants ?? Mockery::mock(TenantService::class));
        $this->router->post('/register', [AuthController::class, 'register']);
    }

    // ── Successful registration ───────────────────────────────────────────────

    public function test_successful_registration_returns_201(): void
    {
        $tenants = Mockery::mock(TenantService::class);
        $tenants->shouldReceive('register')->once()->andReturn($this->fakeTenantDTO());

        $this->bind(tenants: $tenants);

        $response = $this->router->dispatch($this->makeRequest($this->validPayload()), $this->container);

        $this->assertSame(201, $response->getStatus());
        $this->assertJsonContains($response->getBody(), 'success', true);
        $this->assertJsonKey($response->getBody(), 'redirect');
    }

    public function test_successful_registration_response_includes_trial_message(): void
    {
        $tenants = Mockery::mock(TenantService::class);
        $tenants->shouldReceive('register')->once()->andReturn($this->fakeTenantDTO());

        $this->bind(tenants: $tenants);

        $response = $this->router->dispatch($this->makeRequest($this->validPayload()), $this->container);
        $data     = json_decode($response->getBody(), true);

        $this->assertStringContainsString('trial', strtolower($data['message']));
    }

    // ── Duplicate NIT / email ─────────────────────────────────────────────────

    public function test_duplicate_nit_returns_409(): void
    {
        $tenants = Mockery::mock(TenantService::class);
        $tenants->shouldReceive('register')->andThrow(new RuntimeException('NIT already registered.'));

        $this->bind(tenants: $tenants);

        $response = $this->router->dispatch($this->makeRequest($this->validPayload()), $this->container);

        $this->assertSame(409, $response->getStatus());
    }

    public function test_duplicate_email_returns_409(): void
    {
        $tenants = Mockery::mock(TenantService::class);
        $tenants->shouldReceive('register')->andThrow(new RuntimeException('Email already in use.'));

        $this->bind(tenants: $tenants);

        $response = $this->router->dispatch($this->makeRequest($this->validPayload()), $this->container);

        $this->assertSame(409, $response->getStatus());
    }

    // ── Validation failures ───────────────────────────────────────────────────

    public function test_missing_required_field_returns_422(): void
    {
        $tenants = Mockery::mock(TenantService::class);
        $tenants->shouldReceive('register')->andThrow(new InvalidArgumentException('company_name is required.'));

        $this->bind(tenants: $tenants);

        $response = $this->router->dispatch(
            $this->makeRequest($this->validPayload(['company_name' => ''])),
            $this->container,
        );

        $this->assertSame(422, $response->getStatus());
    }

    public function test_weak_password_returns_422(): void
    {
        $tenants = Mockery::mock(TenantService::class);
        $tenants->shouldReceive('register')->andThrow(new InvalidArgumentException('Password too weak.'));

        $this->bind(tenants: $tenants);

        $response = $this->router->dispatch(
            $this->makeRequest($this->validPayload(['password' => 'abc'])),
            $this->container,
        );

        $this->assertSame(422, $response->getStatus());
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_logout_destroys_session_and_redirects(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['user_id']    = 1;
        $_SESSION['company_id'] = 5;

        $auth = Mockery::mock(AuthService::class);
        $auth->shouldReceive('logout')->once();

        $this->container->bind(AuthService::class,   fn() => $auth);
        $this->container->bind(TenantService::class, fn() => Mockery::mock(TenantService::class));
        $this->router->post('/logout', [AuthController::class, 'logout']);

        $request = new Request(
            body:   [],
            query:  [],
            server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/logout'],
        );

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(302, $response->getStatus());
        $this->assertSame('/login', $response->getHeaders()['Location']);

        $_SESSION = [];
    }
}
