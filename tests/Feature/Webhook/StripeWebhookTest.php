<?php

declare(strict_types=1);

namespace Tests\Feature\Webhook;

use App\Core\Container;
use App\Core\Request;
use App\Core\Router;
use App\Http\Controllers\TenantController;
use App\Repositories\TenantRepository;
use App\Services\SubscriptionService;
use App\Services\TenantService;
use Mockery;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    private Router    $router;
    private Container $container;
    private string    $webhookSecret = 'whsec_test_secret_value_for_ci';

    protected function setUp(): void
    {
        parent::setUp();
        $this->router    = new Router();
        $this->container = new Container();
        $_ENV['STRIPE_WEBHOOK_SECRET'] = $this->webhookSecret;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        unset($_ENV['STRIPE_WEBHOOK_SECRET']);
        parent::tearDown();
    }

    private function sign(string $payload, int $timestamp): string
    {
        $computed = hash_hmac('sha256', "{$timestamp}.{$payload}", $this->webhookSecret);
        return "t={$timestamp},v1={$computed}";
    }

    private function makeWebhookRequest(string $payload, string $signature): Request
    {
        $request = new Request(
            body:   json_decode($payload, true) ?? [],
            query:  [],
            server: [
                'REQUEST_METHOD'        => 'POST',
                'REQUEST_URI'           => '/webhooks/stripe',
                'HTTP_STRIPE_SIGNATURE' => $signature,
                'CONTENT_TYPE'          => 'application/json',
            ],
        );
        // Inject raw body via attribute so Request::rawBody() returns it instead of php://input
        return $request->setAttribute('_raw_body', $payload);
    }

    private function invoicePayload(string $subId = 'sub_test123'): array
    {
        return [
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'subscription'  => $subId,
                    'period_start'  => time() - 2592000,
                    'period_end'    => time() + 2592000,
                ],
            ],
        ];
    }

    private function bindMocks(?SubscriptionService $subs = null): void
    {
        $this->container->bind(TenantRepository::class,    fn() => Mockery::mock(TenantRepository::class));
        $this->container->bind(TenantService::class,       fn() => Mockery::mock(TenantService::class));
        $this->container->bind(SubscriptionService::class, fn() => $subs ?? Mockery::mock(SubscriptionService::class));
        $this->router->post('/webhooks/stripe', [TenantController::class, 'stripeWebhook']);
    }

    // ── Signature verification ────────────────────────────────────────────────

    public function test_missing_signature_header_returns_400(): void
    {
        $this->bindMocks();

        $request = new Request(
            body:   $this->invoicePayload(),
            query:  [],
            server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/webhooks/stripe'],
        );

        $response = $this->router->dispatch($request, $this->container);

        $this->assertSame(400, $response->getStatus());
    }

    public function test_invalid_signature_returns_400(): void
    {
        $this->bindMocks();

        $payload   = json_encode($this->invoicePayload());
        $timestamp = time();
        $badSig    = "t={$timestamp},v1=badhash0000000000000000000000000000000000000000000000000000";

        $response = $this->router->dispatch(
            $this->makeWebhookRequest($payload, $badSig),
            $this->container,
        );

        $this->assertSame(400, $response->getStatus());
    }

    // ── Replay attack prevention ──────────────────────────────────────────────

    public function test_old_timestamp_returns_400(): void
    {
        $this->bindMocks();

        $payload   = json_encode($this->invoicePayload());
        $staleTime = time() - 400; // > 300 s threshold
        $signature = $this->sign($payload, $staleTime);

        $response = $this->router->dispatch(
            $this->makeWebhookRequest($payload, $signature),
            $this->container,
        );

        $this->assertSame(400, $response->getStatus());
    }

    public function test_future_timestamp_over_threshold_returns_400(): void
    {
        $this->bindMocks();

        $payload       = json_encode($this->invoicePayload());
        $futureTimestamp = time() + 400;
        $signature     = $this->sign($payload, $futureTimestamp);

        $response = $this->router->dispatch(
            $this->makeWebhookRequest($payload, $signature),
            $this->container,
        );

        $this->assertSame(400, $response->getStatus());
    }

    public function test_non_numeric_timestamp_returns_400(): void
    {
        $this->bindMocks();

        $payload  = json_encode($this->invoicePayload());
        $badSig   = 't=not-a-number,v1=somehashvalue';

        $response = $this->router->dispatch(
            $this->makeWebhookRequest($payload, $badSig),
            $this->container,
        );

        $this->assertSame(400, $response->getStatus());
    }

    // ── Valid webhook processing ──────────────────────────────────────────────

    public function test_valid_invoice_paid_event_returns_200(): void
    {
        $subs = Mockery::mock(SubscriptionService::class);
        $subs->shouldReceive('handleWebhookEvent')->once();

        $this->bindMocks($subs);

        $payload   = json_encode($this->invoicePayload());
        $signature = $this->sign($payload, time());

        $response = $this->router->dispatch(
            $this->makeWebhookRequest($payload, $signature),
            $this->container,
        );

        $this->assertSame(200, $response->getStatus());
        $this->assertJsonContains($response->getBody(), 'received', true);
    }

    public function test_valid_subscription_deleted_event_returns_200(): void
    {
        $subs = Mockery::mock(SubscriptionService::class);
        $subs->shouldReceive('handleWebhookEvent')->once();

        $this->bindMocks($subs);

        $payload = json_encode([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['id' => 'sub_del_abc']],
        ]);
        $signature = $this->sign($payload, time());

        $response = $this->router->dispatch(
            $this->makeWebhookRequest($payload, $signature),
            $this->container,
        );

        $this->assertSame(200, $response->getStatus());
    }

    public function test_timestamp_within_window_is_accepted(): void
    {
        $subs = Mockery::mock(SubscriptionService::class);
        $subs->shouldReceive('handleWebhookEvent')->once();

        $this->bindMocks($subs);

        $payload   = json_encode($this->invoicePayload());
        $timestamp = time() - 250; // within the 300 s window
        $signature = $this->sign($payload, $timestamp);

        $response = $this->router->dispatch(
            $this->makeWebhookRequest($payload, $signature),
            $this->container,
        );

        $this->assertSame(200, $response->getStatus());
    }
}
