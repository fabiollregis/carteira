-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS carteira CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carteira;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de categorias
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    type ENUM('receita', 'despesa') NOT NULL,
    icon VARCHAR(50) DEFAULT 'fas fa-tag',
    color VARCHAR(7) DEFAULT '#563d7c',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de transações
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('receita', 'despesa') NOT NULL,
    payment_method ENUM('dinheiro', 'pix', 'debito', 'credito', 'parcelado') NOT NULL,
    transaction_date DATE NOT NULL,
    installments INT DEFAULT 1,
    installment_number INT DEFAULT 1,
    notes TEXT,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_type ENUM('mensal', 'anual') NULL,
    recurring_day INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- Categorias padrão para novos usuários
INSERT INTO categories (user_id, name, type, icon, color) VALUES
-- Receitas
(1, 'Salário', 'receita', 'fas fa-money-bill-wave', '#28a745'),
(1, 'Freelance', 'receita', 'fas fa-laptop-code', '#17a2b8'),
(1, 'Investimentos', 'receita', 'fas fa-chart-line', '#28a745'),
(1, 'Outros', 'receita', 'fas fa-plus', '#6c757d'),

-- Despesas
(1, 'Alimentação', 'despesa', 'fas fa-utensils', '#dc3545'),
(1, 'Moradia', 'despesa', 'fas fa-home', '#fd7e14'),
(1, 'Transporte', 'despesa', 'fas fa-bus', '#fd7e14'),
(1, 'Saúde', 'despesa', 'fas fa-medkit', '#dc3545'),
(1, 'Educação', 'despesa', 'fas fa-book', '#fd7e14'),
(1, 'Lazer', 'despesa', 'fas fa-gamepad', '#dc3545'),
(1, 'Outros', 'despesa', 'fas fa-plus', '#6c757d');
