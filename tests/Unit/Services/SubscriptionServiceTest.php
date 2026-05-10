<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\SubscriptionDTO;
use App\Repositories\SubscriptionRepository;
use App\Repositories\UserRepository;
use App\Services\SubscriptionService;
use Mockery;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    private SubscriptionRepository $subRepo;
    private UserRepository         $userRepo;
    private SubscriptionService    $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subRepo  = Mockery::mock(SubscriptionRepository::class);
        $this->userRepo = Mockery::mock(UserRepository::class);
        $this->service  = new SubscriptionService($this->subRepo, $this->userRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function activeRecord(array $overrides = []): array
    {
        return array_merge([
            'id'                   => 1,
            'company_id'           => 5,
            'plan_id'              => 2,
            'status'               => 'active',
            'trial_ends_at'        => null,
            'current_period_start' => date('Y-m-d H:i:s', strtotime('-30 days')),
            'current_period_end'   => date('Y-m-d H:i:s', strtotime('+30 days')),
            'max_users'            => 10,
        ], $overrides);
    }

    // ── isActive() ────────────────────────────────────────────────────────────

    public function test_is_active_returns_true_for_active_subscription(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->with(5)->andReturn($this->activeRecord());

        $this->assertTrue($this->service->isActive(5));
    }

    public function test_is_active_returns_false_when_no_subscription(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->with(99)->andReturn(null);

        $this->assertFalse($this->service->isActive(99));
    }

    public function test_is_active_returns_false_for_cancelled_subscription(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->andReturn(
            $this->activeRecord(['status' => 'cancelled'])
        );

        $this->assertFalse($this->service->isActive(5));
    }

    public function test_is_active_returns_true_for_valid_trial(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->andReturn(
            $this->activeRecord([
                'status'        => 'trial',
                'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            ])
        );

        $this->assertTrue($this->service->isActive(5));
    }

    public function test_is_active_returns_false_for_expired_trial(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->andReturn(
            $this->activeRecord([
                'status'        => 'trial',
                'trial_ends_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            ])
        );

        $this->assertFalse($this->service->isActive(5));
    }

    // ── canAddUser() ──────────────────────────────────────────────────────────

    public function test_can_add_user_returns_true_when_below_limit(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->with(5)->andReturn($this->activeRecord(['max_users' => 10]));
        $this->userRepo->shouldReceive('countActive')->with(5)->andReturn(7);

        $this->assertTrue($this->service->canAddUser(5));
    }

    public function test_can_add_user_returns_false_when_at_limit(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->with(5)->andReturn($this->activeRecord(['max_users' => 5]));
        $this->userRepo->shouldReceive('countActive')->with(5)->andReturn(5);

        $this->assertFalse($this->service->canAddUser(5));
    }

    public function test_can_add_user_returns_false_when_over_limit(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->with(5)->andReturn($this->activeRecord(['max_users' => 3]));
        $this->userRepo->shouldReceive('countActive')->with(5)->andReturn(4);

        $this->assertFalse($this->service->canAddUser(5));
    }

    public function test_can_add_user_returns_false_without_subscription(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->andReturn(null);

        $this->assertFalse($this->service->canAddUser(5));
    }

    // ── handleWebhookEvent() ──────────────────────────────────────────────────

    public function test_invoice_paid_updates_status_to_active(): void
    {
        $this->subRepo->shouldReceive('updateByStripeSubscriptionId')
            ->once()
            ->with('sub_abc123', Mockery::on(function (array $data): bool {
                return $data['status'] === 'active'
                    && array_key_exists('current_period_start', $data)
                    && array_key_exists('current_period_end', $data);
            }));

        $this->service->handleWebhookEvent([
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'subscription'  => 'sub_abc123',
                    'period_start'  => time() - 2592000,
                    'period_end'    => time() + 2592000,
                ],
            ],
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_subscription_deleted_sets_status_cancelled(): void
    {
        $this->subRepo->shouldReceive('updateByStripeSubscriptionId')
            ->once()
            ->with('sub_del456', ['status' => 'cancelled']);

        $this->service->handleWebhookEvent([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['id' => 'sub_del456']],
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_subscription_updated_syncs_status(): void
    {
        $this->subRepo->shouldReceive('updateByStripeSubscriptionId')
            ->once()
            ->with('sub_upd789', ['status' => 'past_due']);

        $this->service->handleWebhookEvent([
            'type' => 'customer.subscription.updated',
            'data' => ['object' => ['id' => 'sub_upd789', 'status' => 'past_due']],
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_unknown_event_type_is_silently_ignored(): void
    {
        $this->subRepo->shouldNotReceive('updateByStripeSubscriptionId');

        $this->service->handleWebhookEvent([
            'type' => 'payment_intent.created',
            'data' => ['object' => []],
        ]);

        $this->addToAssertionCount(1);
    }

    // ── getForTenant() ────────────────────────────────────────────────────────

    public function test_get_for_tenant_returns_dto_when_found(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->with(5)->andReturn($this->activeRecord());

        $result = $this->service->getForTenant(5);

        $this->assertInstanceOf(SubscriptionDTO::class, $result);
    }

    public function test_get_for_tenant_returns_null_when_not_found(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->andReturn(null);

        $this->assertNull($this->service->getForTenant(5));
    }
}
