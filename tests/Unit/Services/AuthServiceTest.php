<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\LoginDTO;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use Mockery;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private UserRepository $userRepo;
    private AuthService    $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepo = Mockery::mock(UserRepository::class);
        $this->service  = new AuthService($this->userRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function userRecord(array $overrides = []): array
    {
        return array_merge([
            'id'         => 1,
            'username'   => self::FIXTURE_USERNAME,
            'email'      => self::FIXTURE_EMAIL,
            'password'   => password_hash(self::FIXTURE_PASS, PASSWORD_BCRYPT),
            'role_id'    => 1,
            'company_id' => 5,
            'active'     => 1,
            'created_at' => '2025-01-01 00:00:00',
        ], $overrides);
    }

    public function test_attempt_returns_user_dto_with_valid_credentials(): void
    {
        $this->userRepo
            ->shouldReceive('findByUsername')
            ->once()
            ->with(self::FIXTURE_USERNAME)
            ->andReturn($this->userRecord());

        $dto    = LoginDTO::fromArray(['username' => self::FIXTURE_USERNAME, 'password' => self::FIXTURE_PASS]);
        $result = $this->service->attempt($dto);

        $this->assertNotNull($result);
        $this->assertSame(1,                     $result->id);
        $this->assertSame(self::FIXTURE_USERNAME, $result->username);
        $this->assertSame(5,                     $result->tenantId);
    }

    public function test_attempt_returns_null_for_wrong_password(): void
    {
        $this->userRepo
            ->shouldReceive('findByUsername')
            ->once()
            ->andReturn($this->userRecord());

        $dto    = LoginDTO::fromArray(['username' => self::FIXTURE_USERNAME, 'password' => self::FIXTURE_PASS_ALT]);
        $result = $this->service->attempt($dto);

        $this->assertNull($result);
    }

    public function test_attempt_returns_null_for_unknown_user(): void
    {
        $this->userRepo
            ->shouldReceive('findByUsername')
            ->once()
            ->andReturn(null);

        $dto    = LoginDTO::fromArray(['username' => self::FIXTURE_USERNAME, 'password' => self::FIXTURE_PASS_ALT]);
        $result = $this->service->attempt($dto);

        $this->assertNull($result);
    }

    public function test_attempt_returns_null_for_inactive_user(): void
    {
        $this->userRepo
            ->shouldReceive('findByUsername')
            ->once()
            ->andReturn($this->userRecord(['active' => 0]));

        $dto    = LoginDTO::fromArray(['username' => self::FIXTURE_USERNAME, 'password' => self::FIXTURE_PASS]);
        $result = $this->service->attempt($dto);

        $this->assertNull($result);
    }

    public function test_check_returns_false_without_session(): void
    {
        $result = $this->service->check();
        $this->assertFalse($result);
    }
}
