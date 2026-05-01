<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

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

    private function activeRecord(): array
    {
        return [
            'id'                     => 1,
            'company_id'             => 10,
            'plan_id'                => 2,
            'plan_code'              => 'starter',
            'plan_name'              => 'Starter',
            'max_users'              => 5,
            'status'                 => 'active',
            'trial_ends_at'          => null,
            'current_period_end'     => date('Y-m-d H:i:s', strtotime('+30 days')),
            'stripe_customer_id'     => 'cus_abc',
            'stripe_subscription_id' => 'sub_abc',
        ];
    }

    public function test_is_active_returns_true_for_active_subscription(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->with(10)->andReturn($this->activeRecord());
        $this->assertTrue($this->service->isActive(10));
    }

    public function test_is_active_returns_false_for_cancelled(): void
    {
        $record           = $this->activeRecord();
        $record['status'] = 'cancelled';
        $this->subRepo->shouldReceive('findByTenantId')->andReturn($record);

        $this->assertFalse($this->service->isActive(10));
    }

    public function test_is_active_returns_false_when_no_subscription(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->andReturn(null);
        $this->assertFalse($this->service->isActive(10));
    }

    public function test_can_add_user_when_below_limit(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->andReturn($this->activeRecord());
        $this->userRepo->shouldReceive('countActive')->with(10)->andReturn(3);

        $this->assertTrue($this->service->canAddUser(10));
    }

    public function test_cannot_add_user_when_at_limit(): void
    {
        $this->subRepo->shouldReceive('findByTenantId')->andReturn($this->activeRecord());
        $this->userRepo->shouldReceive('countActive')->with(10)->andReturn(5);

        $this->assertFalse($this->service->canAddUser(10));
    }

    public function test_handle_invoice_paid_updates_subscription(): void
    {
        $this->subRepo
            ->shouldReceive('updateByStripeSubscriptionId')
            ->once()
            ->with('sub_abc', Mockery::on(fn($d) => $d['status'] === 'active'));

        $this->service->handleWebhookEvent([
            'type' => 'invoice.paid',
            'data' => ['object' => [
                'subscription' => 'sub_abc',
                'period_start' => time(),
                'period_end'   => time() + 86400 * 30,
            ]],
        ]);
    }

    public function test_handle_subscription_deleted_cancels(): void
    {
        $this->subRepo
            ->shouldReceive('updateByStripeSubscriptionId')
            ->once()
            ->with('sub_abc', ['status' => 'cancelled']);

        $this->service->handleWebhookEvent([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['id' => 'sub_abc']],
        ]);
    }
}
