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
        $id   = (int) $request->getAttribute('id');
        $user = $this->users->findById($id);

        if (!$user) {
            return Response::notFound('User not found.');
        }

        return Response::json(['data' => UserDTO::fromArray($user)->toArray()]);
    }

    public function store(Request $request): Response
    {
        $data   = $request->only('username', 'email', 'password', 'role_id');
        $errors = $this->validate($data, [
            'username' => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:8'],
            'role_id'  => ['required'],
        ]);

        if ($errors) {
            return Response::validationError($errors);
        }

        $tenantId = TenantContext::id();

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
        $id   = (int) $request->getAttribute('id');
        $data = $request->only('username', 'email', 'role_id', 'password');

        if (isset($data['password'])) {
            if (strlen($data['password']) < 8) {
                return Response::validationError(['password' => ['Minimum 8 characters.']]);
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
        $id      = (int) $request->getAttribute('id');
        $deleted = $this->users->delete($id);

        if (!$deleted) {
            return Response::notFound('User not found.');
        }

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
                    if (!empty($data[$field]) && strlen((string) $data[$field]) < $min) {
                        $errors[$field][] = "The {$field} must be at least {$min} characters.";
                    }
                }
            }
        }

        return $errors;
    }
}
