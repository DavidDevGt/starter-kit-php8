<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;

/** @var \App\Core\Router $router */

// ── Public ────────────────────────────────────────────────────────────────────

$router->get('/login',    [AuthController::class, 'showLogin']);
$router->get('/register', [AuthController::class, 'showRegister']);

// ── Auth mutations (CSRF required) ────────────────────────────────────────────

$router->group(['middleware' => [CsrfMiddleware::class, RateLimitMiddleware::class]], function ($r) {
    $r->post('/login', [AuthController::class, 'login']);
});

$router->group(['middleware' => [CsrfMiddleware::class]], function ($r) {
    $r->post('/register', [AuthController::class, 'register']);
    $r->post('/logout',   [AuthController::class, 'logout']);
});

// ── Authenticated web views ───────────────────────────────────────────────────
// Individual module pages are served as PHP includes from /modules/.
// The router below catches the SPA shell route — actual module content
// is loaded client-side via AJAX calls to /api/v1/*.

$router->get('/', function ($req) {
    return \App\Core\Response::redirect('/dashboard');
});

$router->get('/dashboard', function ($req) {
    $auth = \App\Core\Application::getInstance()->container()->make(\App\Services\AuthService::class);
    if (!$auth->check()) {
        return \App\Core\Response::redirect('/login');
    }
    return \App\Core\Response::view(__DIR__ . '/../public/views/shell.php', [
        'csrf' => \App\Middleware\CsrfMiddleware::token(),
    ]);
});

$router->get('/billing/expired', function ($req) {
    return \App\Core\Response::view(__DIR__ . '/../public/views/billing/expired.php');
});

$router->get('/billing/upgrade', function ($req) {
    return \App\Core\Response::view(__DIR__ . '/../public/views/billing/upgrade.php');
});
