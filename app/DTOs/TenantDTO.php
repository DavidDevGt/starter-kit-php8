<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class TenantDTO
{
    public function __construct(
        public int    $id,
        public string $code,
        public string $name,
        public string $nit,
        public string $email,
        public int    $countryId,
        public bool   $active,
        public string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:        (int)  $data['id'],
            code:             $data['code'],
            name:             $data['company_name'],
            nit:              $data['company_nit'],
            email:            $data['company_email_principal'] ?? '',
            countryId: (int)  $data['company_country_id'],
            active:    (bool) $data['active'],
            createdAt:        $data['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'code'                  => $this->code,
            'company_name'          => $this->name,
            'company_nit'           => $this->nit,
            'company_email_principal' => $this->email,
            'company_country_id'    => $this->countryId,
            'active'                => $this->active,
            'created_at'            => $this->createdAt,
        ];
    }
}
