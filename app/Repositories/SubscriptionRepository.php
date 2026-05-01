<?php

declare(strict_types=1);

namespace App\Repositories;

class SubscriptionRepository extends BaseRepository
{
    protected string $table        = 'subscription';
    protected bool   $tenantScoped = false;

    public function findByTenantId(int $tenantId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT s.*, p.code AS plan_code, p.name AS plan_name, p.max_users
             FROM {$this->table} s
             LEFT JOIN plan p ON p.id = s.plan_id
             WHERE s.company_id = ?
             LIMIT 1"
        );
        $stmt->bind_param('i', $tenantId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function updateByStripeSubscriptionId(string $stripeSubId, array $data): bool
    {
        $setParts = array_map(static fn($k) => "{$k} = ?", array_keys($data));
        $values   = array_values($data);
        $values[] = $stripeSubId;

        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $setParts)
            . " WHERE stripe_subscription_id = ?"
        );
        $stmt->bind_param(str_repeat('s', count($values)), ...$values);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }

    public function updateStatusByTenantId(int $tenantId, string $status): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET status = ? WHERE company_id = ?"
        );
        $stmt->bind_param('si', $status, $tenantId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
