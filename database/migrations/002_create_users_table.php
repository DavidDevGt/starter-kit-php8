<?php

declare(strict_types=1);

class CreateUsersTable
{
    public function up(mysqli $conn): void
    {
        // Role table (global — no tenant scope)
        $conn->query(
            'CREATE TABLE IF NOT EXISTS role (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                code        VARCHAR(10)  NOT NULL UNIQUE,
                name        VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                active      BOOLEAN DEFAULT TRUE,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $conn->query(
            "INSERT IGNORE INTO role (code, name, description) VALUES
                ('ADMIN', 'Administrador', 'Full system access'),
                ('GER',   'Gerente',       'Management access'),
                ('OFI',   'Oficina',        'Office access'),
                ('BOD',   'Bodega',         'Warehouse access'),
                ('VEN',   'Vendedor',       'Sales access')"
        );

        // User table — now with company_id (tenant key)
        $conn->query('DROP TABLE IF EXISTS user');

        $conn->query(
            'CREATE TABLE user (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                company_id  INT          NOT NULL,
                username    VARCHAR(100) NOT NULL,
                password    VARCHAR(255) NOT NULL,
                email       VARCHAR(255) NOT NULL,
                role_id     INT          NOT NULL DEFAULT 1,
                active      BOOLEAN DEFAULT TRUE,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id)    REFERENCES role(id),
                UNIQUE KEY  uq_username_tenant (username, company_id),
                INDEX idx_company (company_id),
                INDEX idx_email   (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        // Session logs — now tenant-aware
        $conn->query('DROP TABLE IF EXISTS session_logs');

        $conn->query(
            'CREATE TABLE session_logs (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                company_id    INT          NOT NULL,
                user_id       INT          NOT NULL,
                session_token VARCHAR(255) NOT NULL,
                ip_address    VARCHAR(45),
                user_agent    TEXT,
                session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                session_end   TIMESTAMP NULL,
                FOREIGN KEY (user_id)    REFERENCES user(id)    ON DELETE CASCADE,
                FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE,
                UNIQUE KEY  uq_session_token (session_token),
                INDEX idx_user_session (user_id, session_token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        // Rate limit log for login throttling
        $conn->query(
            'CREATE TABLE IF NOT EXISTS rate_limit_log (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                `key`      VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_key_time (`key`, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(mysqli $conn): void
    {
        $conn->query('DROP TABLE IF EXISTS session_logs');
        $conn->query('DROP TABLE IF EXISTS rate_limit_log');
        $conn->query('DROP TABLE IF EXISTS user');
        $conn->query('DROP TABLE IF EXISTS role');
    }
}
