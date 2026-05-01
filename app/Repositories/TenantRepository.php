<?php

declare(strict_types=1);

namespace App\Repositories;

class TenantRepository extends BaseRepository
{
    protected string $table        = 'company';
    protected bool   $tenantScoped = false;

    public function findByCode(string $code): ?array
    {
        return $this->findBy('code', $code);
    }

    public function findByNit(string $nit): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE company_nit = ? LIMIT 1"
        );
        $stmt->bind_param('s', $nit);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE company_name = ? LIMIT 1"
        );
        $stmt->bind_param('s', $name);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }
}
