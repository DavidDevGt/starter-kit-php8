<?php

declare(strict_types=1);

namespace App\DTOs;

use InvalidArgumentException;

final readonly class LoginDTO
{
    public function __construct(
        public string $username,
        public string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if ($username === '' || $password === '') {
            throw new InvalidArgumentException('Username and password are required.');
        }

        return new self(username: $username, password: $password);
    }
}
