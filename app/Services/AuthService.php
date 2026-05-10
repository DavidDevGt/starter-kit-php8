<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\TenantContext;
use App\DTOs\LoginDTO;
use App\DTOs\UserDTO;
use App\Repositories\UserRepository;

class AuthService
{
    public function __construct(private readonly UserRepository $users) {}

    public function attempt(LoginDTO $dto): ?UserDTO
    {
        $record = $this->users->findByUsername($dto->username);

        if (!$record) {
            return null;
        }

        if (!password_verify($dto->password, $record['password'])) {
            return null;
        }

        if (!(bool) $record['active']) {
            return null;
        }

        return UserDTO::fromArray($record);
    }

    public function login(UserDTO $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $token = $this->generateToken();
        $lifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? 120) * 60;

        $_SESSION['user_id']       = $user->id;
        $_SESSION['username']      = $user->username;
        $_SESSION['company_id']    = $user->tenantId;
        $_SESSION['session_token'] = $token;
        $_SESSION['_expires']      = time() + $lifetime;

        TenantContext::set($user->tenantId);

        $this->recordSessionStart(
            userId:   $user->id,
            token:    $token,
            tenantId: $user->tenantId,
        );
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = (int) ($_SESSION['user_id']       ?? 0);
        $token  =        $_SESSION['session_token'] ?? null;

        if ($userId && $token) {
            $this->recordSessionEnd($userId, $token);
        }

        session_destroy();
        TenantContext::reset();
    }

    public function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user_id'], $_SESSION['company_id']);
    }

    public function currentUser(): ?UserDTO
    {
        if (!$this->check()) {
            return null;
        }

        $record = $this->users->findById((int) $_SESSION['user_id']);
        return $record ? UserDTO::fromArray($record) : null;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function recordSessionStart(int $userId, string $token, int $tenantId): void
    {
        $ip        = $_SERVER['REMOTE_ADDR']     ?? '';
        $ua        = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $tokenHash = hash('sha256', $token);

        $stmt = $this->users->connection()->prepare(
            'INSERT INTO session_logs (user_id, session_token, ip_address, user_agent, company_id)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isssi', $userId, $tokenHash, $ip, $ua, $tenantId);
        $stmt->execute();
    }

    private function recordSessionEnd(int $userId, string $token): void
    {
        $tokenHash = hash('sha256', $token);

        $stmt = $this->users->connection()->prepare(
            'UPDATE session_logs SET session_end = NOW()
             WHERE user_id = ? AND session_token = ?'
        );
        $stmt->bind_param('is', $userId, $tokenHash);
        $stmt->execute();
    }
}
