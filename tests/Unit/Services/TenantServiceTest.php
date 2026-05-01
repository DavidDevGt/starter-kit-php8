<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\SubscriptionRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use App\Services\TenantService;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    private TenantRepository       $tenantRepo;
    private UserRepository         $userRepo;
    private SubscriptionRepository $subRepo;
    private TenantService          $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantRepo = Mockery::mock(TenantRepository::class);
        $this->userRepo   = Mockery::mock(UserRepository::class);
        $this->subRepo    = Mockery::mock(SubscriptionRepository::class);

        $this->service = new TenantService(
            $this->tenantRepo,
            $this->userRepo,
            $this->subRepo,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function validRegistrationData(): array
    {
        return [
            'company_name' => 'Acme Corp',
            'nit'          => '123456-7',
            'email'        => 'admin@acme.com',
            'username'     => 'acme_admin',
            'password'     => self::FIXTURE_PASS,
        ];
    }

    public function test_register_creates_tenant_user_and_subscription(): void
    {
        $this->tenantRepo->shouldReceive('findByNit')->once()->andReturn(null);
        $this->tenantRepo->shouldReceive('findByName')->once()->andReturn(null);
        $this->tenantRepo->shouldReceive('create')->once()->andReturn(10);
        $this->tenantRepo->shouldReceive('findById')->once()->with(10)->andReturn([
            'id'                    => 10,
            'code'                  => 'ACME123',
            'company_name'          => 'Acme Corp',
            'company_nit'           => '123456-7',
            'company_email_principal' => 'admin@acme.com',
            'company_country_id'    => 1,
            'active'                => 1,
            'created_at'            => '2025-01-01 00:00:00',
        ]);
        $this->userRepo->shouldReceive('create')->once()->andReturn(1);
        $this->subRepo->shouldReceive('create')->once()->andReturn(1);

        $dto = $this->service->register($this->validRegistrationData());

        $this->assertSame(10, $dto->id);
        $this->assertSame('Acme Corp', $dto->name);
    }

    public function test_register_throws_on_missing_required_field(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/required/");

        $data = $this->validRegistrationData();
        unset($data['nit']);

        $this->service->register($data);
    }

    public function test_register_throws_on_invalid_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/email/");

        $data          = $this->validRegistrationData();
        $data['email'] = 'not-an-email';

        $this->service->register($data);
    }

    public function test_register_throws_on_short_password(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/8 characters/");

        $data             = $this->validRegistrationData();
        $data['password'] = 'short';

        $this->service->register($data);
    }

    public function test_register_throws_if_nit_already_exists(): void
    {
        $this->tenantRepo->shouldReceive('findByNit')->once()->andReturn(['id' => 5]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches("/NIT/");

        $this->service->register($this->validRegistrationData());
    }

    public function test_register_throws_if_company_name_already_exists(): void
    {
        $this->tenantRepo->shouldReceive('findByNit')->once()->andReturn(null);
        $this->tenantRepo->shouldReceive('findByName')->once()->andReturn(['id' => 7]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches("/name/");

        $this->service->register($this->validRegistrationData());
    }
}
