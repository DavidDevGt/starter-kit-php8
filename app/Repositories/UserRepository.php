<?php

declare(strict_types=1);

namespace App\Repositories;

class UserRepository extends BaseRepository
{
    protected string $table        = 'user';
    protected bool   $tenantScoped = true;

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE username = ? AND active = 1 LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE email = ? AND active = 1 LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function countActive(int $tenantId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total FROM {$this->table}
             WHERE company_id = ? AND active = 1"
        );
        $stmt->bind_param('i', $tenantId);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

    public function allWithRole(int $tenantId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.username, u.email, u.active, u.created_at,
                    r.name AS role_name, r.code AS role_code
             FROM {$this->table} u
             LEFT JOIN role r ON r.id = u.role_id
             WHERE u.company_id = ? AND u.active = 1
             ORDER BY u.created_at DESC"
        );
        $stmt->bind_param('i', $tenantId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updatePassword(int $id, string $hashedPassword, int $tenantId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET password = ?
             WHERE id = ? AND company_id = ?"
        );
        $stmt->bind_param('sii', $hashedPassword, $id, $tenantId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
