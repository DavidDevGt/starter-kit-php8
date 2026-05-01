<?php

declare(strict_types=1);

class CreateTenantsTable
{
    public function up(mysqli $conn): void
    {
        $conn->query('DROP TABLE IF EXISTS company');

        $conn->query(
            'CREATE TABLE company (
                id                        INT AUTO_INCREMENT PRIMARY KEY,
                code                      VARCHAR(20)  NOT NULL UNIQUE,
                company_name              VARCHAR(255) NOT NULL UNIQUE,
                company_nit               VARCHAR(20)  NOT NULL UNIQUE,
                company_address           TEXT,
                company_postal_code       VARCHAR(10),
                company_phone_principal   VARCHAR(45),
                company_phone_secondary   VARCHAR(45),
                company_email_principal   VARCHAR(100),
                company_email_secondary   VARCHAR(100),
                company_website           VARCHAR(100),
                company_logo_url          VARCHAR(500),
                company_country_id        INT NOT NULL DEFAULT 1,
                active                    BOOLEAN DEFAULT TRUE,
                created_at                TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at                TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_code (code),
                INDEX idx_nit  (company_nit)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(mysqli $conn): void
    {
        $conn->query('DROP TABLE IF EXISTS company');
    }
}
