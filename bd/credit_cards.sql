-- Tabela de cartões de crédito
CREATE TABLE IF NOT EXISTS credit_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_name VARCHAR(100) NOT NULL,
    card_limit DECIMAL(10,2) NOT NULL,
    available_limit DECIMAL(10,2) NOT NULL,
    used_limit DECIMAL(10,2) DEFAULT 0.00,
    closing_day INT NOT NULL,
    due_day INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de transações dos cartões
CREATE TABLE IF NOT EXISTS card_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES credit_cards(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
