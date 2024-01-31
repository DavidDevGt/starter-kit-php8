CREATE DATABASE IF NOT EXISTS test_db;

USE test_db;

CREATE TABLE IF NOT EXISTS user (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE role (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(5) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Config initial data for admin user and roles
INSERT INTO role (code, name, description) VALUES ('ADMIN', 'Administrador', 'Descripción del rol de administrador');
INSERT INTO role (code, name, description) VALUES ('GER', 'Gerente', 'Descripción del rol de gerente');
INSERT INTO role (code, name, description) VALUES ('OFI', 'Oficina', 'Descripción del rol de oficina');
INSERT INTO role (code, name, description) VALUES ('BOD', 'Bodega', 'Desceripción del rol de bodega');
INSERT INTO role (code, name, description) VALUES ('VEN', 'Vendedor', 'Descripción del rol de vendedor');

INSERT INTO user (username, password, email, role_id) VALUES ('admin', 'admin', 'joshuexd1@gmail.com', 1);

CREATE TABLE module (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order INT NOT NULL,
    name VARCHAR(255) NOT NULL UNIQUE,
    primary_module BOOLEAN NOT NULL, -- TRUE: primary, FALSE: secondary
    father_module_id INT NOT NULL DEFAULT 0, -- 0: primary module, "father_module_id": secondary module
    route VARCHAR(255) NOT NULL, -- /modules/folder/file.php?md=$id
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO module (order, name, primary_module, father_module_id, route) VALUES (1, 'Clientes', TRUE, 0, '/modules/clientes/index.php');
INSERT INTO module (order, name, primary_module, father_module_id, route) VALUES (2, 'Productos', TRUE, 0, '/modules/productos/index.php');
INSERT INTO module (order, name, primary_module, father_module_id, route) VALUES (3, 'Inventario', TRUE, 0, '/modules/inventario/index.php');
INSERT INTO module (order, name, primary_module, father_module_id, route) VALUES (4, 'Pedidos', TRUE, 0, '/modules/pedidos/index.php');
INSERT INTO module (order, name, primary_module, father_module_id, route) VALUES (5, 'Facturacion', TRUE, 0, '/modules/facturacion/index.php');
INSERT INTO module (order, name, primary_module, father_module_id, route) VALUES (6, 'Finanzas', TRUE, 0, '/modules/finanzas/index.php');
INSERT INTO module (order, name, primary_module, father_module_id, route) VALUES (7, 'Reportes', TRUE, 0, '/modules/reportes/index.php');
INSERT INTO module (order, name, primary_module, father_module_id, route) VALUES (8, 'Sistemas', TRUE, 0, '/modules/sistemas/index.php');

CREATE TABLE permission (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_user_id INT NOT NULL DEFAULT 0 -- 0: system, "last_user_id": $user_id
);

CREATE TABLE country (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL UNIQUE,
    is_principal BOOLEAN DEFAULT FALSE, -- TRUE: principal, FALSE: secondary
    currency_symbol VARCHAR(5) NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO country (code, name, is_principal, currency_symbol) VALUES ('GT', 'Guatemala', TRUE, 'Q');
INSERT INTO country (code, name, is_principal, currency_symbol) VALUES ('US', 'Estados Unidos', FALSE, 'USD $');
INSERT INTO country (code, name, is_principal, currency_symbol) VALUES ('MX', 'México', FALSE, 'MXN $');
INSERT INTO country (code, name, is_principal, currency_symbol) VALUES ('HN', 'Honduras', FALSE, 'L');
INSERT INTO country (code, name, is_principal, currency_symbol) VALUES ('SV', 'El Salvador', FALSE, 'USD $');
INSERT INTO country (code, name, is_principal, currency_symbol) VALUES ('NI', 'Nicaragua', FALSE, 'C$');
INSERT INTO country (code, name, is_principal, currency_symbol) VALUES ('CR', 'Costa Rica', FALSE, '₡');
INSERT INTO country (code, name, is_principal, currency_symbol) VALUES ('PA', 'Panamá', FALSE, 'B/.');
INSERT INTO country (code, name, is_principal, currency_symbol) VALUES ('CH', 'China', FALSE, 'CNY ¥');

CREATE TABLE tax (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(45) NOT NULL,
    country_id INT NOT NULL DEFAULT 1, -- 1 = Guatemala
    value DECIMAL(4, 2) NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE company (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL UNIQUE,
    company_nit VARCHAR(20) NOT NULL UNIQUE,
    company_address TEXT,
    company_postal_code VARCHAR(5),
    company_phone_principal VARCHAR(45),
    company_phone_secondary VARCHAR(45),
    company_email_principal VARCHAR(100),
    company_email_secondary VARCHAR(100),
    company_website VARCHAR(50),
    company_logo_url VARCHAR(255),
    company_country_id INT NOT NULL DEFAULT 1, -- 1 = Guatemala
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE department (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR (10) NOT UNIQUE,
    name VARCHAR (255) NOT UNIQUE,
    country_id INT NOT NULL DEFAULT 1, -- 1 = Guatemala
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE municipality (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    code VARCHAR (10) NOT UNIQUE,
    name VARCHAR (255) NOT UNIQUE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE employee_data (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  name VARCHAR(255),
  last_name VARCHAR(255),
  phone VARCHAR(255),
  address TEXT,
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
