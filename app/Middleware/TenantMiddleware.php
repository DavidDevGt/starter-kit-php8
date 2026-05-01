<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\SubscriptionService;

/**
 * Blocks access for tenants with an inactive or expired subscription.
 * Must run AFTER AuthMiddleware (needs company_id attribute).
 */
class TenantMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function process(Request $request, callable $next): Response
    {
        $tenantId = (int) $request->getAttribute('company_id');

        if (!$this->subscriptions->isActive($tenantId)) {
            return Response::json([
                'error'       => 'subscription_required',
                'message'     => 'Your subscription is inactive. Please renew your plan.',
                'upgrade_url' => '/billing/upgrade',
            ], 402);
        }

        return $next($request);
    }
}
