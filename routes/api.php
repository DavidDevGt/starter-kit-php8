<?php

declare(strict_types=1);

use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\TenantMiddleware;

/** @var \App\Core\Router $router */

// ── Stripe webhook — no auth, verified by signature ──────────────────────────

$router->post('/webhooks/stripe', [TenantController::class, 'stripeWebhook']);

// ── Authenticated + active-subscription API ───────────────────────────────────

$router->group([
    'prefix'     => '/api/v1',
    'middleware' => [AuthMiddleware::class, TenantMiddleware::class, CsrfMiddleware::class],
], function ($r): void {

    // Users
    $r->get('/users',          [UserController::class, 'index']);
    $r->post('/users',         [UserController::class, 'store']);
    $r->get('/users/{id}',     [UserController::class, 'show']);
    $r->put('/users/{id}',     [UserController::class, 'update']);
    $r->delete('/users/{id}',  [UserController::class, 'destroy']);

    // Tenant profile + subscription
    $r->get('/tenant',    [TenantController::class, 'show']);
    $r->patch('/tenant',  [TenantController::class, 'update']);

    // Modules available for current tenant's plan
    $r->get('/modules', function ($req) {
        $tenantId = \App\Core\TenantContext::id();
        $db       = \App\Core\Application::getInstance()->container()->make(\App\Config\Database::class);
        $conn     = $db->connect();

        $sub = \App\Core\Application::getInstance()->container()
            ->make(\App\Repositories\SubscriptionRepository::class)
            ->findByTenantId($tenantId);

        if (!$sub) {
            return \App\Core\Response::json(['data' => []]);
        }

        $planId = (int) $sub['plan_id'];
        $stmt   = $conn->prepare(
            'SELECT m.* FROM module m
             INNER JOIN plan_module pm ON pm.module_id = m.id
             WHERE pm.plan_id = ? AND m.active = 1
             ORDER BY m.position'
        );
        $stmt->bind_param('i', $planId);
        $stmt->execute();
        $modules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return \App\Core\Response::json(['data' => $modules]);
    });
});
