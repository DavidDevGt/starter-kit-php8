<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\DTOs\UserDTO;
use App\Repositories\UserRepository;
use App\Services\SubscriptionService;

class UserController
{
    public function __construct(
        private readonly UserRepository      $users,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id();
        $users    = $this->users->allWithRole($tenantId);

        return Response::json(['data' => $users]);
    }

    public function show(Request $request): Response
    {
        $id       = (int) $request->getAttribute('id');
        $tenantId = TenantContext::id();
        $user     = $this->users->findById($id);

        // Defense-in-depth: verify tenant ownership even though the repository auto-scopes
        if (!$user || (int) $user['company_id'] !== $tenantId) {
            return Response::notFound('User not found.');
        }

        return Response::json(['data' => UserDTO::fromArray($user)->toArray()]);
    }

    public function store(Request $request): Response
    {
        $data   = $request->only('username', 'email', 'password', 'role_id');
        $errors = $this->validate($data, [
            'username' => ['required', 'max:100'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:8', 'max:255'],
            'role_id'  => ['required'],
        ]);

        if ($errors) {
            return Response::validationError($errors);
        }

        $tenantId      = TenantContext::id();
        $requesterId   = (int) $request->getAttribute('user_id');
        $requester     = $this->users->findById($requesterId);
        $requesterRole = (int) ($requester['role_id'] ?? PHP_INT_MAX);
        $requestedRole = (int) $data['role_id'];

        // Prevent privilege escalation: cannot assign a role equal to or higher than own
        if ($requestedRole <= $requesterRole && $requesterRole !== 1) {
            return Response::forbidden('Cannot assign a role with equal or higher privilege.');
        }

        if (!$this->subscriptions->canAddUser($tenantId)) {
            return Response::json([
                'error'       => 'user_limit_reached',
                'message'     => 'User limit reached for your plan. Please upgrade.',
                'upgrade_url' => '/billing/upgrade',
            ], 402);
        }

        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $id = $this->users->create($data);

        return Response::json(['success' => true, 'id' => $id], 201);
    }

    public function update(Request $request): Response
    {
        $id       = (int) $request->getAttribute('id');
        $tenantId = TenantContext::id();
        $user     = $this->users->findById($id);

        if (!$user || (int) $user['company_id'] !== $tenantId) {
            return Response::notFound('User not found.');
        }

        $data = $request->only('username', 'email', 'role_id', 'password');

        if (isset($data['password'])) {
            // Use mb_strlen for correct multibyte character counting
            if (mb_strlen((string) $data['password'], 'UTF-8') < 8) {
                return Response::validationError(['password' => ['Minimum 8 characters.']]);
            }
            if (mb_strlen((string) $data['password'], 'UTF-8') > 255) {
                return Response::validationError(['password' => ['Maximum 255 characters.']]);
            }
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $updated = $this->users->update($id, $data);

        if (!$updated) {
            return Response::notFound('User not found or no changes made.');
        }

        return Response::json(['success' => true]);
    }

    public function destroy(Request $request): Response
    {
        $id       = (int) $request->getAttribute('id');
        $tenantId = TenantContext::id();
        $user     = $this->users->findById($id);

        if (!$user || (int) $user['company_id'] !== $tenantId) {
            return Response::notFound('User not found.');
        }

        $this->users->delete($id);

        return Response::json(['success' => true]);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    private function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && empty($data[$field])) {
                    $errors[$field][] = "The {$field} field is required.";
                }

                if ($rule === 'email' && !empty($data[$field])
                    && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "The {$field} must be a valid email.";
                }

                if (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (!empty($data[$field]) && mb_strlen((string) $data[$field], 'UTF-8') < $min) {
                        $errors[$field][] = "The {$field} must be at least {$min} characters.";
                    }
                }

                if (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (!empty($data[$field]) && mb_strlen((string) $data[$field], 'UTF-8') > $max) {
                        $errors[$field][] = "The {$field} must not exceed {$max} characters.";
                    }
                }
            }
        }

        return $errors;
    }
}
