<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Core\Container;
use App\Core\Request;
use App\Core\Router;
use App\DTOs\UserDTO;
use App\Http\Controllers\AuthController;
use App\Services\AuthService;
use App\Services\TenantService;
use Mockery;
use Tests\TestCase;

class LoginTest extends TestCase
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

    private function makePostRequest(string $path, array $body): Request
    {
        return new Request(
            body:   $body,
            query:  [],
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI'    => $path,
                'CONTENT_TYPE'   => 'application/json',
                'HTTP_ACCEPT'    => 'application/json',
            ],
        );
    }

    private function fixtureUserDto(): UserDTO
    {
        return new UserDTO(
            id:        1,
            username:  self::FIXTURE_USERNAME,
            email:     self::FIXTURE_EMAIL,
            roleId:    1,
            tenantId:  5,
            active:    true,
            createdAt: '2025-01-01 00:00:00',
        );
    }

    private function bindMocks(?AuthService $auth = null, ?TenantService $tenants = null): void
    {
        $this->container->bind(AuthService::class,   fn() => $auth   ?? Mockery::mock(AuthService::class));
        $this->container->bind(TenantService::class, fn() => $tenants ?? Mockery::mock(TenantService::class));
    }

    public function test_valid_credentials_return_200_with_redirect(): void
    {
        $auth = Mockery::mock(AuthService::class);
        $auth->shouldReceive('attempt')->once()->andReturn($this->fixtureUserDto());
        $auth->shouldReceive('login')->once();
        $auth->shouldReceive('check')->andReturn(false);

        $this->bindMocks(auth: $auth);
        $this->router->post('/login', [AuthController::class, 'login']);

        $response = $this->router->dispatch(
            $this->makePostRequest('/login', [
                'username' => self::FIXTURE_USERNAME,
                'password' => self::FIXTURE_PASS,
            ]),
            $this->container,
        );

        $this->assertSame(200, $response->getStatus());
        $this->assertJsonContains($response->getBody(), 'success', true);
        $this->assertJsonContains($response->getBody(), 'redirect', '/dashboard');
    }

    public function test_invalid_credentials_return_401(): void
    {
        $auth = Mockery::mock(AuthService::class);
        $auth->shouldReceive('attempt')->once()->andReturn(null);
        $auth->shouldReceive('check')->andReturn(false);

        $this->bindMocks(auth: $auth);
        $this->router->post('/login', [AuthController::class, 'login']);

        $response = $this->router->dispatch(
            $this->makePostRequest('/login', [
                'username' => self::FIXTURE_USERNAME,
                'password' => self::FIXTURE_PASS_ALT,
            ]),
            $this->container,
        );

        $this->assertSame(401, $response->getStatus());
        $this->assertJsonContains($response->getBody(), 'success', false);
    }

    public function test_missing_password_returns_422(): void
    {
        $auth = Mockery::mock(AuthService::class);
        $auth->shouldReceive('check')->andReturn(false);

        $this->bindMocks(auth: $auth);
        $this->router->post('/login', [AuthController::class, 'login']);

        $response = $this->router->dispatch(
            $this->makePostRequest('/login', ['username' => self::FIXTURE_USERNAME]),
            $this->container,
        );

        $this->assertSame(422, $response->getStatus());
        $this->assertJsonKey($response->getBody(), 'errors');
    }
}
