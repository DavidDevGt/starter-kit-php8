<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TenantDTO;
use App\Repositories\SubscriptionRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use InvalidArgumentException;
use RuntimeException;

class TenantService
{
    private const TRIAL_DAYS   = 14;
    private const FREE_PLAN_ID = 1;

    public function __construct(
        private readonly TenantRepository        $tenants,
        private readonly UserRepository          $users,
        private readonly SubscriptionRepository  $subscriptions,
    ) {}

    public function register(array $data): TenantDTO
    {
        $this->validateRegistration($data);

        $tenantId = $this->tenants->create([
            'code'                    => $this->generateCode($data['company_name']),
            'company_name'            => $data['company_name'],
            'company_nit'             => $data['nit'],
            'company_email_principal' => $data['email'],
            'company_country_id'      => (int) ($data['country_id'] ?? 1),
        ]);

        $this->users->create([
            'username'   => $data['username'],
            'password'   => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'email'      => $data['email'],
            'role_id'    => 1,
            'company_id' => $tenantId,
        ]);

        $this->subscriptions->create([
            'company_id'    => $tenantId,
            'plan_id'       => self::FREE_PLAN_ID,
            'status'        => 'trial',
            'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+' . self::TRIAL_DAYS . ' days')),
        ]);

        $record = $this->tenants->findById($tenantId);
        return TenantDTO::fromArray($record);
    }

    public function update(int $tenantId, array $data): bool
    {
        $allowed = ['company_name', 'company_address', 'company_phone_principal', 'company_email_principal'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        return $this->tenants->update($tenantId, $filtered);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function validateRegistration(array $data): void
    {
        $required = ['company_name', 'nit', 'email', 'username', 'password'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required.");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        if (strlen($data['password']) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters.');
        }

        if ($this->tenants->findByNit($data['nit'])) {
            throw new RuntimeException('A company with this NIT is already registered.');
        }

        if ($this->tenants->findByName($data['company_name'])) {
            throw new RuntimeException('A company with this name is already registered.');
        }
    }

    private function generateCode(string $name): string
    {
        $clean = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $code  = substr($clean, 0, 6);
        return $code . random_int(100, 999);
    }
}
