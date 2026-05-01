<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\SubscriptionDTO;
use App\Repositories\SubscriptionRepository;
use App\Repositories\UserRepository;

class SubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions,
        private readonly UserRepository         $users,
    ) {}

    public function isActive(int $tenantId): bool
    {
        $record = $this->subscriptions->findByTenantId($tenantId);

        if (!$record) {
            return false;
        }

        return SubscriptionDTO::fromArray($record)->isActive();
    }

    public function getForTenant(int $tenantId): ?SubscriptionDTO
    {
        $record = $this->subscriptions->findByTenantId($tenantId);
        return $record ? SubscriptionDTO::fromArray($record) : null;
    }

    public function canAddUser(int $tenantId): bool
    {
        $sub = $this->getForTenant($tenantId);

        if (!$sub) {
            return false;
        }

        $currentCount = $this->users->countActive($tenantId);
        return $currentCount < $sub->maxUsers;
    }

    // ── Stripe webhook handlers ───────────────────────────────────────────────

    public function handleWebhookEvent(array $event): void
    {
        match ($event['type']) {
            'invoice.paid'                    => $this->onInvoicePaid($event['data']['object']),
            'customer.subscription.deleted'   => $this->onSubscriptionDeleted($event['data']['object']),
            'customer.subscription.updated'   => $this->onSubscriptionUpdated($event['data']['object']),
            default                           => null,
        };
    }

    private function onInvoicePaid(array $invoice): void
    {
        $this->subscriptions->updateByStripeSubscriptionId(
            $invoice['subscription'],
            [
                'status'               => 'active',
                'current_period_start' => date('Y-m-d H:i:s', $invoice['period_start']),
                'current_period_end'   => date('Y-m-d H:i:s', $invoice['period_end']),
            ],
        );
    }

    private function onSubscriptionDeleted(array $subscription): void
    {
        $this->subscriptions->updateByStripeSubscriptionId(
            $subscription['id'],
            ['status' => 'cancelled'],
        );
    }

    private function onSubscriptionUpdated(array $subscription): void
    {
        $this->subscriptions->updateByStripeSubscriptionId(
            $subscription['id'],
            ['status' => $subscription['status']],
        );
    }
}
