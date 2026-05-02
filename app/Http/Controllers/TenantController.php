<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\DTOs\TenantDTO;
use App\Repositories\TenantRepository;
use App\Services\SubscriptionService;
use App\Services\TenantService;

class TenantController
{
    public function __construct(
        private readonly TenantRepository    $tenants,
        private readonly TenantService       $tenantService,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function show(Request $request): Response
    {
        $tenantId = TenantContext::id();
        $record   = $this->tenants->findById($tenantId);

        if (!$record) {
            return Response::notFound('Tenant not found.');
        }

        $sub = $this->subscriptions->getForTenant($tenantId);

        return Response::json([
            'data' => array_merge(
                TenantDTO::fromArray($record)->toArray(),
                ['subscription' => $sub?->toArray()],
            ),
        ]);
    }

    public function update(Request $request): Response
    {
        $tenantId = TenantContext::id();
        $data     = $request->only(
            'company_name',
            'company_address',
            'company_phone_principal',
            'company_email_principal',
        );

        $updated = $this->tenantService->update($tenantId, $data);

        return Response::json(['success' => $updated]);
    }

    public function stripeWebhook(Request $request): Response
    {
        $payload   = file_get_contents('php://input') ?: '';
        $signature = $request->header('Stripe-Signature') ?? '';
        $secret    = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        if (!$this->verifyStripeSignature($payload, $signature, $secret)) {
            return Response::json(['error' => 'Invalid signature.'], 400);
        }

        $event = json_decode($payload, true);

        if (!$event) {
            return Response::json(['error' => 'Invalid payload.'], 400);
        }

        $this->subscriptions->handleWebhookEvent($event);

        return Response::json(['received' => true]);
    }

    private function verifyStripeSignature(string $payload, string $signature, string $secret): bool
    {
        if (!$secret || !$signature) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$k, $v]   = explode('=', $part, 2);
            $parts[$k] = $v;
        }

        $timestamp = $parts['t']  ?? '';
        $sigV1     = $parts['v1'] ?? '';

        // Reject missing or non-numeric timestamp
        if (!$timestamp || !ctype_digit($timestamp)) {
            return false;
        }

        // Reject webhooks older than 5 minutes (Stripe recommendation) to prevent replay attacks
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $computed = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return hash_equals($computed, $sigV1);
    }
}
