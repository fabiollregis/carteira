-- Adicionar coluna used_limit se não existir
ALTER TABLE credit_cards
ADD COLUMN IF NOT EXISTS used_limit DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Atualizar os valores de used_limit para cartões existentes
UPDATE credit_cards c
SET c.used_limit = (
    SELECT COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.amount ELSE 0 END), 0)
    FROM card_transactions t
    WHERE t.card_id = c.id
);
