<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\LoginDTO;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\TenantService;
use InvalidArgumentException;
use RuntimeException;

class AuthController
{
    public function __construct(
        private readonly AuthService   $auth,
        private readonly TenantService $tenants,
    ) {}

    public function showLogin(Request $request): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/dashboard');
        }

        return Response::view(__DIR__ . '/../../../public/views/auth/login.php', [
            'csrf' => CsrfMiddleware::token(),
        ]);
    }

    public function showRegister(Request $request): Response
    {
        return Response::view(__DIR__ . '/../../../public/views/auth/register.php', [
            'csrf' => CsrfMiddleware::token(),
        ]);
    }

    public function login(Request $request): Response
    {
        $data = $request->only('username', 'password');

        try {
            $dto = LoginDTO::fromArray($data);
        } catch (InvalidArgumentException $e) {
            return Response::validationError(['message' => $e->getMessage()]);
        }

        $user = $this->auth->attempt($dto);

        if (!$user) {
            return Response::json(['success' => false, 'message' => 'Invalid credentials.'], 401);
        }

        $this->auth->login($user);

        return Response::json(['success' => true, 'redirect' => '/dashboard']);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();
        return Response::redirect('/login');
    }

    public function register(Request $request): Response
    {
        $data = $request->only('company_name', 'nit', 'email', 'username', 'password', 'country_id');

        try {
            $this->tenants->register($data);
        } catch (InvalidArgumentException $e) {
            return Response::validationError(['message' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            return Response::json(['error' => $e->getMessage()], 409);
        }

        return Response::json([
            'success'  => true,
            'message'  => 'Account created. Your 14-day trial has started.',
            'redirect' => '/login',
        ], 201);
    }
}
