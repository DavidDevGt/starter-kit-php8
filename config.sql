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

CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order INT NOT NULL,
    name VARCHAR(255) NOT NULL UNIQUE,
    primary_module BOOLEAN NOT NULL, -- TRUE: primary, FALSE: secondary
    father_module_id INT NOT NULL DEFAULT 0, -- 0: primary module, "father_module_id": secondary module
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

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
    company_phone1 VARCHAR(45),
    company_phone2 VARCHAR(45),
    company_phone3 VARCHAR(45),
    company_email1 VARCHAR(100),
    company_email2 VARCHAR(100),
    company_website VARCHAR(50),
    company_logo_url VARCHAR(255),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);