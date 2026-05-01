<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Contracts\RepositoryInterface;
use App\Core\TenantContext;
use mysqli;

abstract class BaseRepository implements RepositoryInterface
{
    protected mysqli $conn;

    protected string $table;

    /**
     * When true, every query automatically appends `AND company_id = ?`
     * using the value from TenantContext. Set to false for global tables
     * (company, plan, country, etc.).
     */
    protected bool $tenantScoped = true;

    public function __construct(protected readonly Database $db)
    {
        $this->conn = $db->connect();
    }

    // ── RepositoryInterface ──────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE id = ?" . $this->tenantWhere();
        $stmt = $this->conn->prepare($sql . ' LIMIT 1');

        $this->tenantScoped
            ? $stmt->bind_param('ii', $id, ...[$this->tid()])
            : $stmt->bind_param('i', $id);

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findAll(): array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE active = 1" . $this->tenantAnd();
        $stmt = $this->conn->prepare($sql);

        if ($this->tenantScoped) {
            $tid = $this->tid();
            $stmt->bind_param('i', $tid);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create(array $data): int
    {
        if ($this->tenantScoped) {
            $data['company_id'] = $this->tid();
        }

        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $values       = array_values($data);

        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})"
        );
        $stmt->bind_param(str_repeat('s', count($values)), ...$values);
        $stmt->execute();

        return $this->conn->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        $setParts = array_map(static fn($k) => "{$k} = ?", array_keys($data));
        $values   = array_values($data);
        $values[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts)
            . " WHERE id = ?" . $this->tenantWhere();

        $types = str_repeat('s', count($data)) . 'i';

        if ($this->tenantScoped) {
            $values[] = $this->tid();
            $types   .= 'i';
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }

    /** Soft-delete: sets active = 0 */
    public function delete(int $id): bool
    {
        return $this->update($id, ['active' => 0]);
    }

    // ── Additional finders ───────────────────────────────────────────────────

    public function findBy(string $column, mixed $value): ?array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE {$column} = ?" . $this->tenantAnd() . ' LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if ($this->tenantScoped) {
            $tid = $this->tid();
            $stmt->bind_param('si', $value, $tid);
        } else {
            $stmt->bind_param('s', $value);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function where(string $column, mixed $value): array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE {$column} = ? AND active = 1" . $this->tenantAnd();
        $stmt = $this->conn->prepare($sql);

        if ($this->tenantScoped) {
            $tid = $this->tid();
            $stmt->bind_param('si', $value, $tid);
        } else {
            $stmt->bind_param('s', $value);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ── Connection access ────────────────────────────────────────────────────

    public function connection(): mysqli
    {
        return $this->conn;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function tenantWhere(): string
    {
        return $this->tenantScoped ? ' AND company_id = ?' : '';
    }

    private function tenantAnd(): string
    {
        return $this->tenantScoped ? ' AND company_id = ?' : '';
    }

    private function tid(): int
    {
        return TenantContext::id();
    }
}
