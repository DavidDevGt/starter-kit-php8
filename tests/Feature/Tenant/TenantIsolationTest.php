<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Core\Container;
use App\Core\Request;
use App\Core\Router;
use App\Core\TenantContext;
use App\DTOs\SubscriptionDTO;
use App\Http\Controllers\UserController;
use App\Repositories\UserRepository;
use App\Services\SubscriptionService;
use Mockery;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
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

    private function makeRequest(string $method, string $path): Request
    {
        return new Request(
            body:   [],
            query:  [],
            server: [
                'REQUEST_METHOD' => $method,
                'REQUEST_URI'    => $path,
                'HTTP_ACCEPT'    => 'application/json',
            ],
        );
    }

    public function test_user_from_different_tenant_gets_404(): void
    {
        // Authenticated as tenant 1
        TenantContext::set(1);

        $userRepo = Mockery::mock(UserRepository::class);
        // findById enforces tenant scope — returns null for user from tenant 2
        $userRepo->shouldReceive('findById')->with(20)->andReturn(null);

        $subService = Mockery::mock(SubscriptionService::class);

        $this->container->bind(UserRepository::class,      fn() => $userRepo);
        $this->container->bind(SubscriptionService::class, fn() => $subService);

        $this->router->get('/api/v1/users/{id}', [UserController::class, 'show']);

        $response = $this->router->dispatch(
            $this->makeRequest('GET', '/api/v1/users/20'),
            $this->container,
        );

        $this->assertSame(404, $response->getStatus());
    }

    public function test_list_returns_only_tenant_users(): void
    {
        TenantContext::set(1);

        $userRepo = Mockery::mock(UserRepository::class);
        $userRepo->shouldReceive('allWithRole')
            ->with(1)
            ->andReturn([
                ['id' => 10, 'username' => 'alice', 'company_id' => 1],
                ['id' => 11, 'username' => 'bob',   'company_id' => 1],
            ]);

        $subService = Mockery::mock(SubscriptionService::class);

        $this->container->bind(UserRepository::class,      fn() => $userRepo);
        $this->container->bind(SubscriptionService::class, fn() => $subService);

        $this->router->get('/api/v1/users', [UserController::class, 'index']);

        $response = $this->router->dispatch(
            $this->makeRequest('GET', '/api/v1/users'),
            $this->container,
        );

        $this->assertSame(200, $response->getStatus());

        $data = json_decode($response->getBody(), true);
        $this->assertCount(2, $data['data']);

        $userIds = array_column($data['data'], 'id');
        $this->assertContains(10, $userIds);
        $this->assertContains(11, $userIds);
        $this->assertNotContains(20, $userIds);
    }

    public function test_cannot_create_user_when_at_plan_limit(): void
    {
        TenantContext::set(1);

        $userRepo = Mockery::mock(UserRepository::class);

        $subService = Mockery::mock(SubscriptionService::class);
        $subService->shouldReceive('canAddUser')->with(1)->andReturn(false);

        $this->container->bind(UserRepository::class,      fn() => $userRepo);
        $this->container->bind(SubscriptionService::class, fn() => $subService);

        $this->router->post('/api/v1/users', [UserController::class, 'store']);

        $request = new Request(
            body:   ['username' => self::FIXTURE_USERNAME, 'email' => self::FIXTURE_EMAIL, 'password' => self::FIXTURE_PASS, 'role_id' => 2],
            query:  [],
            server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/users'],
        );

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(402, $response->getStatus());

        $data = json_decode($response->getBody(), true);
        $this->assertSame('user_limit_reached', $data['error']);
    }

    public function test_tenant_context_isolates_between_requests(): void
    {
        // Simulate request A
        TenantContext::set(1);
        $this->assertSame(1, TenantContext::id());

        // Simulate request B (reset between requests)
        TenantContext::reset();
        TenantContext::set(2);
        $this->assertSame(2, TenantContext::id());
    }
}
