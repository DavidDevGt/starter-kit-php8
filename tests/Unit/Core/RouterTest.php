<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Container;
use App\Core\Request;
use App\Core\Router;
use Tests\TestCase;

class RouterTest extends TestCase
{
    private Router    $router;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router    = new Router();
        $this->container = new Container();
    }

    private function makeRequest(string $method, string $path): Request
    {
        return new Request(
            body:   [],
            query:  [],
            server: ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $path],
        );
    }

    public function test_matches_get_route(): void
    {
        $this->router->get('/ping', fn($req) => \App\Core\Response::json(['pong' => true]));

        $response = $this->router->dispatch($this->makeRequest('GET', '/ping'), $this->container);

        $this->assertSame(200, $response->getStatus());
    }

    public function test_returns_404_for_unknown_route(): void
    {
        $response = $this->router->dispatch($this->makeRequest('GET', '/unknown'), $this->container);

        $this->assertSame(404, $response->getStatus());
    }

    public function test_extracts_route_parameters(): void
    {
        $captured = null;

        $this->router->get('/users/{id}', function ($req) use (&$captured) {
            $captured = $req->getAttribute('id');
            return \App\Core\Response::json([]);
        });

        $this->router->dispatch($this->makeRequest('GET', '/users/42'), $this->container);

        $this->assertSame('42', $captured);
    }

    public function test_group_prefix_is_applied(): void
    {
        $this->router->group(['prefix' => '/api/v1'], function ($r) {
            $r->get('/users', fn($req) => \App\Core\Response::json(['ok' => true]));
        });

        $response = $this->router->dispatch(
            $this->makeRequest('GET', '/api/v1/users'),
            $this->container,
        );

        $this->assertSame(200, $response->getStatus());
    }

    public function test_method_mismatch_returns_404(): void
    {
        $this->router->get('/users', fn($req) => \App\Core\Response::json([]));

        $response = $this->router->dispatch($this->makeRequest('POST', '/users'), $this->container);

        $this->assertSame(404, $response->getStatus());
    }

    public function test_middleware_runs_before_handler(): void
    {
        $log = [];

        $middleware = new class($log) implements \App\Contracts\MiddlewareInterface {
            public function __construct(private array &$log) {}
            public function process(Request $req, callable $next): \App\Core\Response {
                $this->log[] = 'middleware';
                return $next($req);
            }
        };

        $this->container->bind(get_class($middleware), fn() => $middleware);

        $this->router->group(['middleware' => [get_class($middleware)]], function ($r) use (&$log) {
            $r->get('/protected', function ($req) use (&$log) {
                $log[] = 'handler';
                return \App\Core\Response::json([]);
            });
        });

        $this->router->dispatch($this->makeRequest('GET', '/protected'), $this->container);

        $this->assertSame(['middleware', 'handler'], $log);
    }
}
