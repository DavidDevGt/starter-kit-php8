<?php

declare(strict_types=1);

class CreateSubscriptionsTable
{
    public function up(mysqli $conn): void
    {
        $conn->query(
            'CREATE TABLE IF NOT EXISTS subscription (
                id                     INT AUTO_INCREMENT PRIMARY KEY,
                company_id             INT          NOT NULL UNIQUE,
                plan_id                INT          NOT NULL DEFAULT 1,
                status                 ENUM("trial","active","past_due","cancelled") DEFAULT "trial",
                trial_ends_at          TIMESTAMP NULL,
                current_period_start   TIMESTAMP NULL,
                current_period_end     TIMESTAMP NULL,
                stripe_customer_id     VARCHAR(255),
                stripe_subscription_id VARCHAR(255),
                created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE,
                FOREIGN KEY (plan_id)    REFERENCES plan(id),
                INDEX idx_stripe_sub (stripe_subscription_id),
                INDEX idx_status     (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        // Permission table — tenant-aware access control per user + module
        $conn->query('DROP TABLE IF EXISTS permission');

        $conn->query(
            'CREATE TABLE permission (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                company_id   INT NOT NULL,
                user_id      INT NOT NULL,
                module_id    INT NOT NULL,
                active       BOOLEAN DEFAULT TRUE,
                granted_by   INT NOT NULL DEFAULT 0,
                created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id)    REFERENCES user(id)    ON DELETE CASCADE,
                FOREIGN KEY (module_id)  REFERENCES module(id)  ON DELETE CASCADE,
                UNIQUE KEY  uq_user_module (user_id, module_id),
                INDEX idx_company_user (company_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(mysqli $conn): void
    {
        $conn->query('DROP TABLE IF EXISTS permission');
        $conn->query('DROP TABLE IF EXISTS subscription');
    }
}
