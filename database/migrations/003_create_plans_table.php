<?php

declare(strict_types=1);

class CreatePlansTable
{
    public function up(mysqli $conn): void
    {
        $conn->query(
            'CREATE TABLE IF NOT EXISTS plan (
                id               INT AUTO_INCREMENT PRIMARY KEY,
                code             VARCHAR(20)    NOT NULL UNIQUE,
                name             VARCHAR(100)   NOT NULL,
                max_users        INT            NOT NULL DEFAULT 1,
                price_monthly    DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                stripe_price_id  VARCHAR(255),
                active           BOOLEAN DEFAULT TRUE,
                created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $conn->query(
            "INSERT IGNORE INTO plan (code, name, max_users, price_monthly) VALUES
                ('free',       'Free',       1,  0.00),
                ('starter',    'Starter',    5,  29.00),
                ('pro',        'Pro',        20, 79.00),
                ('enterprise', 'Enterprise', 999, 0.00)"
        );

        // Plan ↔ Module mapping (controls which modules each plan unlocks)
        $conn->query(
            'CREATE TABLE IF NOT EXISTS plan_module (
                plan_id   INT NOT NULL,
                module_id INT NOT NULL,
                PRIMARY KEY (plan_id, module_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        // Module table (global — same for all tenants, filtered by plan)
        $conn->query(
            'CREATE TABLE IF NOT EXISTS module (
                id               INT AUTO_INCREMENT PRIMARY KEY,
                position         INT          NOT NULL,
                name             VARCHAR(255) NOT NULL UNIQUE,
                primary_module   BOOLEAN      NOT NULL DEFAULT TRUE,
                father_module_id INT          NOT NULL DEFAULT 0,
                route            VARCHAR(255) NOT NULL,
                active           BOOLEAN DEFAULT TRUE,
                created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        // Seed base modules
        $conn->query(
            "INSERT IGNORE INTO module (position, name, primary_module, father_module_id, route) VALUES
                (1, 'Dashboard',   TRUE, 0, '/modules/dashboard/index.php'),
                (2, 'Usuarios',    TRUE, 0, '/modules/usuarios/index.php'),
                (3, 'Roles',       TRUE, 0, '/modules/roles/index.php'),
                (4, 'Módulos',     TRUE, 0, '/modules/modulos/index.php'),
                (5, 'Clientes',    TRUE, 0, '/modules/clientes/index.php'),
                (6, 'Productos',   TRUE, 0, '/modules/productos/index.php'),
                (7, 'Facturación', TRUE, 0, '/modules/facturacion/index.php'),
                (8, 'Reportes',    TRUE, 0, '/modules/reportes/index.php')"
        );

        // Free plan: Dashboard only
        $conn->query("INSERT IGNORE INTO plan_module (plan_id, module_id) VALUES (1,1),(1,2)");
        // Starter: + Clientes, Productos
        $conn->query("INSERT IGNORE INTO plan_module (plan_id, module_id) VALUES (2,1),(2,2),(2,3),(2,4),(2,5),(2,6)");
        // Pro: all
        $conn->query("INSERT IGNORE INTO plan_module (plan_id, module_id) VALUES (3,1),(3,2),(3,3),(3,4),(3,5),(3,6),(3,7),(3,8)");
        // Enterprise: all
        $conn->query("INSERT IGNORE INTO plan_module (plan_id, module_id) VALUES (4,1),(4,2),(4,3),(4,4),(4,5),(4,6),(4,7),(4,8)");
    }

    public function down(mysqli $conn): void
    {
        $conn->query('DROP TABLE IF EXISTS plan_module');
        $conn->query('DROP TABLE IF EXISTS module');
        $conn->query('DROP TABLE IF EXISTS plan');
    }
}
