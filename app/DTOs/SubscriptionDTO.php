<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class SubscriptionDTO
{
    public function __construct(
        public int     $id,
        public int     $tenantId,
        public int     $planId,
        public string  $planCode,
        public string  $planName,
        public int     $maxUsers,
        public string  $status,
        public ?string $trialEndsAt,
        public ?string $currentPeriodEnd,
        public ?string $stripeCustomerId,
        public ?string $stripeSubscriptionId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:                   (int)  $data['id'],
            tenantId:             (int)  $data['company_id'],
            planId:               (int)  $data['plan_id'],
            planCode:                    $data['plan_code'] ?? '',
            planName:                    $data['plan_name'] ?? '',
            maxUsers:             (int) ($data['max_users'] ?? 1),
            status:                      $data['status'],
            trialEndsAt:                 $data['trial_ends_at'] ?? null,
            currentPeriodEnd:            $data['current_period_end'] ?? null,
            stripeCustomerId:            $data['stripe_customer_id'] ?? null,
            stripeSubscriptionId:        $data['stripe_subscription_id'] ?? null,
        );
    }

    public function isActive(): bool
    {
        if ($this->status === 'trial') {
            return $this->trialEndsAt !== null && strtotime($this->trialEndsAt) > time();
        }

        return $this->status === 'active';
    }

    public function daysUntilTrialExpiry(): ?int
    {
        if ($this->status !== 'trial' || $this->trialEndsAt === null) {
            return null;
        }

        $diff = strtotime($this->trialEndsAt) - time();
        return max(0, (int) ceil($diff / 86400));
    }

    public function toArray(): array
    {
        return [
            'plan_code'           => $this->planCode,
            'plan_name'           => $this->planName,
            'status'              => $this->status,
            'max_users'           => $this->maxUsers,
            'is_active'           => $this->isActive(),
            'trial_ends_at'       => $this->trialEndsAt,
            'current_period_end'  => $this->currentPeriodEnd,
            'days_until_expiry'   => $this->daysUntilTrialExpiry(),
        ];
    }
}
