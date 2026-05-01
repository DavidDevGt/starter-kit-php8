<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\DTOs\UserDTO;
use Tests\TestCase;

class UserDTOTest extends TestCase
{
    private array $validRecord = [
        'id'         => 42,
        'username'   => 'jdoe',
        'email'      => 'jdoe@example.com',
        'role_id'    => 2,
        'company_id' => 7,
        'active'     => 1,
        'created_at' => '2025-01-01 00:00:00',
    ];

    public function test_from_array_maps_all_fields(): void
    {
        $dto = UserDTO::fromArray($this->validRecord);

        $this->assertSame(42,                    $dto->id);
        $this->assertSame('jdoe',                $dto->username);
        $this->assertSame('jdoe@example.com',    $dto->email);
        $this->assertSame(2,                     $dto->roleId);
        $this->assertSame(7,                     $dto->tenantId);
        $this->assertTrue($dto->active);
        $this->assertSame('2025-01-01 00:00:00', $dto->createdAt);
    }

    public function test_to_array_returns_expected_keys(): void
    {
        $dto  = UserDTO::fromArray($this->validRecord);
        $data = $dto->toArray();

        $this->assertArrayHasKey('id',         $data);
        $this->assertArrayHasKey('username',   $data);
        $this->assertArrayHasKey('email',      $data);
        $this->assertArrayHasKey('role_id',    $data);
        $this->assertArrayHasKey('company_id', $data);
        $this->assertArrayHasKey('active',     $data);
        $this->assertArrayHasKey('created_at', $data);
    }

    public function test_to_array_does_not_expose_password(): void
    {
        $record              = $this->validRecord;
        $record['password']  = 'should-not-appear-in-output';
        $dto                 = UserDTO::fromArray($record);

        $this->assertArrayNotHasKey('password', $dto->toArray());
    }

    public function test_active_flag_is_cast_to_bool(): void
    {
        $record           = $this->validRecord;
        $record['active'] = 0;
        $dto              = UserDTO::fromArray($record);

        $this->assertFalse($dto->active);
        $this->assertIsBool($dto->active);
    }
}
