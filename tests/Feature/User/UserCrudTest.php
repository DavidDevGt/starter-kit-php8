<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Core\Container;
use App\Core\Request;
use App\Core\Router;
use App\Core\TenantContext;
use App\Http\Controllers\UserController;
use App\Repositories\UserRepository;
use App\Services\SubscriptionService;
use Mockery;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    private Router    $router;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router    = new Router();
        $this->container = new Container();
        TenantContext::set(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeRequest(string $method, string $path, array $body = [], int $userId = 1): Request
    {
        $request = new Request(
            body:   $body,
            query:  [],
            server: ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $path, 'HTTP_ACCEPT' => 'application/json'],
        );
        return $request->setAttribute('user_id', $userId);
    }

    private function bind(UserRepository $users, SubscriptionService $subs): void
    {
        $this->container->bind(UserRepository::class,      fn() => $users);
        $this->container->bind(SubscriptionService::class, fn() => $subs);
    }

    private function userRecord(array $overrides = []): array
    {
        return array_merge([
            'id'         => 10,
            'username'   => self::FIXTURE_USERNAME,
            'email'      => self::FIXTURE_EMAIL,
            'role_id'    => 2,
            'company_id' => 1,
            'active'     => 1,
            'created_at' => '2025-01-01 00:00:00',
        ], $overrides);
    }

    // ── GET /api/v1/users/{id} ────────────────────────────────────────────────

    public function test_show_returns_200_for_tenant_owned_user(): void
    {
        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findById')->with(10)->andReturn($this->userRecord());

        $subs = Mockery::mock(SubscriptionService::class);

        $this->bind($users, $subs);
        $this->router->get('/api/v1/users/{id}', [UserController::class, 'show']);

        $response = $this->router->dispatch(
            $this->makeRequest('GET', '/api/v1/users/10'),
            $this->container,
        );

        $this->assertSame(200, $response->getStatus());
        $this->assertJsonKey($response->getBody(), 'data');
    }

    public function test_show_returns_404_for_cross_tenant_user(): void
    {
        $users = Mockery::mock(UserRepository::class);
        // company_id = 99 ≠ TenantContext (1) → 404
        $users->shouldReceive('findById')->with(20)->andReturn($this->userRecord(['id' => 20, 'company_id' => 99]));

        $subs = Mockery::mock(SubscriptionService::class);

        $this->bind($users, $subs);
        $this->router->get('/api/v1/users/{id}', [UserController::class, 'show']);

        $response = $this->router->dispatch(
            $this->makeRequest('GET', '/api/v1/users/20'),
            $this->container,
        );

        $this->assertSame(404, $response->getStatus());
    }

    public function test_show_returns_404_when_user_not_found(): void
    {
        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findById')->andReturn(null);

        $subs = Mockery::mock(SubscriptionService::class);

        $this->bind($users, $subs);
        $this->router->get('/api/v1/users/{id}', [UserController::class, 'show']);

        $response = $this->router->dispatch(
            $this->makeRequest('GET', '/api/v1/users/999'),
            $this->container,
        );

        $this->assertSame(404, $response->getStatus());
    }

    // ── POST /api/v1/users ────────────────────────────────────────────────────

    public function test_store_creates_user_and_returns_201(): void
    {
        $requester = $this->userRecord(['id' => 1, 'role_id' => 1]); // admin

        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findById')->with(1)->andReturn($requester);
        $users->shouldReceive('create')->once()->andReturn(99);

        $subs = Mockery::mock(SubscriptionService::class);
        $subs->shouldReceive('canAddUser')->with(1)->andReturn(true);

        $this->bind($users, $subs);
        $this->router->post('/api/v1/users', [UserController::class, 'store']);

        $request = new Request(
            body:   [
                'username' => self::FIXTURE_USERNAME,
                'email'    => self::FIXTURE_EMAIL,
                'password' => self::FIXTURE_PASS,
                'role_id'  => 2,
            ],
            query:  [],
            server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/users', 'HTTP_ACCEPT' => 'application/json'],
        );
        $request = $request->setAttribute('user_id', 1);

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(201, $response->getStatus());
        $this->assertJsonContains($response->getBody(), 'success', true);
        $this->assertJsonContains($response->getBody(), 'id', 99);
    }

    public function test_store_returns_402_when_at_plan_limit(): void
    {
        $requester = $this->userRecord(['id' => 1, 'role_id' => 1]);

        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findById')->with(1)->andReturn($requester);

        $subs = Mockery::mock(SubscriptionService::class);
        $subs->shouldReceive('canAddUser')->with(1)->andReturn(false);

        $this->bind($users, $subs);
        $this->router->post('/api/v1/users', [UserController::class, 'store']);

        $request = new Request(
            body:   ['username' => self::FIXTURE_USERNAME, 'email' => self::FIXTURE_EMAIL, 'password' => self::FIXTURE_PASS, 'role_id' => 2],
            query:  [],
            server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/users'],
        );
        $request = $request->setAttribute('user_id', 1);

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(402, $response->getStatus());
        $data = json_decode($response->getBody(), true);
        $this->assertSame('user_limit_reached', $data['error']);
    }

    public function test_store_returns_422_for_missing_fields(): void
    {
        $users = Mockery::mock(UserRepository::class);
        $subs  = Mockery::mock(SubscriptionService::class);

        $this->bind($users, $subs);
        $this->router->post('/api/v1/users', [UserController::class, 'store']);

        $request = new Request(
            body:   ['username' => self::FIXTURE_USERNAME], // missing email, password, role_id
            query:  [],
            server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/users'],
        );
        $request = $request->setAttribute('user_id', 1);

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(422, $response->getStatus());
    }

    public function test_store_blocks_privilege_escalation_to_admin(): void
    {
        // Requester is role_id=2 (non-admin), trying to create role_id=1 (admin)
        $requester = $this->userRecord(['id' => 1, 'role_id' => 2]);

        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findById')->with(1)->andReturn($requester);
        $users->shouldNotReceive('create');

        $subs = Mockery::mock(SubscriptionService::class);
        $subs->shouldReceive('canAddUser')->andReturn(true);

        $this->bind($users, $subs);
        $this->router->post('/api/v1/users', [UserController::class, 'store']);

        $request = new Request(
            body:   ['username' => self::FIXTURE_USERNAME, 'email' => self::FIXTURE_EMAIL, 'password' => self::FIXTURE_PASS, 'role_id' => 1],
            query:  [],
            server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/users'],
        );
        $request = $request->setAttribute('user_id', 1);

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(403, $response->getStatus());
    }

    // ── PUT /api/v1/users/{id} ────────────────────────────────────────────────

    public function test_update_returns_200_for_tenant_owned_user(): void
    {
        $record = $this->userRecord();

        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findById')->with(10)->andReturn($record);
        $users->shouldReceive('update')->with(10, Mockery::any())->andReturn(true);

        $subs = Mockery::mock(SubscriptionService::class);

        $this->bind($users, $subs);
        $this->router->put('/api/v1/users/{id}', [UserController::class, 'update']);

        $request = new Request(
            body:   ['email' => self::FIXTURE_EMAIL],
            query:  [],
            server: ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/users/10'],
        );

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(200, $response->getStatus());
        $this->assertJsonContains($response->getBody(), 'success', true);
    }

    public function test_update_returns_404_for_cross_tenant_user(): void
    {
        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findById')->with(20)->andReturn($this->userRecord(['id' => 20, 'company_id' => 99]));
        $users->shouldNotReceive('update');

        $subs = Mockery::mock(SubscriptionService::class);

        $this->bind($users, $subs);
        $this->router->put('/api/v1/users/{id}', [UserController::class, 'update']);

        $request = new Request(
            body:   ['email' => self::FIXTURE_EMAIL],
            query:  [],
            server: ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/users/20'],
        );

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(404, $response->getStatus());
    }

    public function test_update_rejects_short_password(): void
    {
        $record = $this->userRecord();

        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findById')->with(10)->andReturn($record);
        $users->shouldNotReceive('update');

        $subs = Mockery::mock(SubscriptionService::class);

        $this->bind($users, $subs);
        $this->router->put('/api/v1/users/{id}', [UserController::class, 'update']);

        $request = new Request(
            body:   ['password' => 'abc'],
            query:  [],
            server: ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/users/10'],
        );

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(422, $response->getStatus());
    }

    // ── DELETE /api/v1/users/{id} ─────────────────────────────────────────────

    public function test_destroy_soft_deletes_tenant_owned_user(): void
    {
        $record = $this->userRecord();

        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findById')->with(10)->andReturn($record);
        $users->shouldReceive('delete')->with(10)->once();

        $subs = Mockery::mock(SubscriptionService::class);

        $this->bind($users, $subs);
        $this->router->delete('/api/v1/users/{id}', [UserController::class, 'destroy']);

        $request = new Request(
            body:   [],
            query:  [],
            server: ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/api/v1/users/10'],
        );

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(200, $response->getStatus());
    }

    public function test_destroy_returns_404_for_cross_tenant_user(): void
    {
        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findById')->with(20)->andReturn($this->userRecord(['id' => 20, 'company_id' => 99]));
        $users->shouldNotReceive('delete');

        $subs = Mockery::mock(SubscriptionService::class);

        $this->bind($users, $subs);
        $this->router->delete('/api/v1/users/{id}', [UserController::class, 'destroy']);

        $request = new Request(
            body:   [],
            query:  [],
            server: ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/api/v1/users/20'],
        );

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(404, $response->getStatus());
    }
}
