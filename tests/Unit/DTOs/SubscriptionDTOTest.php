<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\DTOs\SubscriptionDTO;
use Tests\TestCase;

class SubscriptionDTOTest extends TestCase
{
    private function makeRecord(array $overrides = []): array
    {
        return array_merge([
            'id'                     => 1,
            'company_id'             => 10,
            'plan_id'                => 1,
            'plan_code'              => 'starter',
            'plan_name'              => 'Starter',
            'max_users'              => 5,
            'status'                 => 'active',
            'trial_ends_at'          => null,
            'current_period_end'     => null,
            'stripe_customer_id'     => null,
            'stripe_subscription_id' => null,
        ], $overrides);
    }

    public function test_active_subscription_is_active(): void
    {
        $dto = SubscriptionDTO::fromArray($this->makeRecord(['status' => 'active']));
        $this->assertTrue($dto->isActive());
    }

    public function test_cancelled_subscription_is_not_active(): void
    {
        $dto = SubscriptionDTO::fromArray($this->makeRecord(['status' => 'cancelled']));
        $this->assertFalse($dto->isActive());
    }

    public function test_trial_within_window_is_active(): void
    {
        $future = date('Y-m-d H:i:s', strtotime('+7 days'));
        $dto    = SubscriptionDTO::fromArray($this->makeRecord([
            'status'        => 'trial',
            'trial_ends_at' => $future,
        ]));

        $this->assertTrue($dto->isActive());
    }

    public function test_expired_trial_is_not_active(): void
    {
        $past = date('Y-m-d H:i:s', strtotime('-1 day'));
        $dto  = SubscriptionDTO::fromArray($this->makeRecord([
            'status'        => 'trial',
            'trial_ends_at' => $past,
        ]));

        $this->assertFalse($dto->isActive());
    }

    public function test_days_until_expiry_on_active_trial(): void
    {
        $future = date('Y-m-d H:i:s', strtotime('+5 days'));
        $dto    = SubscriptionDTO::fromArray($this->makeRecord([
            'status'        => 'trial',
            'trial_ends_at' => $future,
        ]));

        $days = $dto->daysUntilTrialExpiry();
        $this->assertSame(5, $days);
    }

    public function test_days_until_expiry_is_null_for_non_trial(): void
    {
        $dto = SubscriptionDTO::fromArray($this->makeRecord(['status' => 'active']));
        $this->assertNull($dto->daysUntilTrialExpiry());
    }

    public function test_to_array_contains_is_active(): void
    {
        $dto  = SubscriptionDTO::fromArray($this->makeRecord());
        $data = $dto->toArray();

        $this->assertArrayHasKey('is_active', $data);
        $this->assertTrue($data['is_active']);
    }
}
