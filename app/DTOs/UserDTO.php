<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UserDTO
{
    public function __construct(
        public int    $id,
        public string $username,
        public string $email,
        public int    $roleId,
        public int    $tenantId,
        public bool   $active,
        public string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:        (int)  $data['id'],
            username:         $data['username'],
            email:            $data['email'],
            roleId:    (int)  $data['role_id'],
            tenantId:  (int)  $data['company_id'],
            active:    (bool) $data['active'],
            createdAt:        $data['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'username'   => $this->username,
            'email'      => $this->email,
            'role_id'    => $this->roleId,
            'company_id' => $this->tenantId,
            'active'     => $this->active,
            'created_at' => $this->createdAt,
        ];
    }
}
