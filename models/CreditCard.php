<?php
class CreditCard {
    private $conn;
    private $table_name = "credit_cards";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar novo cartão
    public function create($user_id, $card_name, $card_limit, $closing_day, $due_day) {
        $query = "INSERT INTO " . $this->table_name . " 
                (user_id, card_name, card_limit, available_limit, closing_day, due_day) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id, $card_name, $card_limit, $card_limit, $closing_day, $due_day]);
    }

    // Listar cartões do usuário
    public function getUserCards($user_id) {
        $query = "SELECT c.*, 
                  (c.card_limit - c.available_limit) as used_limit,
                  COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.amount ELSE 0 END), 0) as total_pending
                FROM " . $this->table_name . " c
                LEFT JOIN card_transactions t ON c.id = t.card_id
                WHERE c.user_id = ?
                GROUP BY c.id, c.card_limit, c.available_limit";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->get_result();
    }

    // Adicionar transação
    public function addTransaction($card_id, $description, $amount, $transaction_date, $due_date) {
        try {
            // Inserir a transação
            $query = "INSERT INTO card_transactions (card_id, description, amount, transaction_date, due_date, status) 
                     VALUES (?, ?, ?, ?, ?, 'pending')";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt->execute([$card_id, $description, $amount, $transaction_date, $due_date])) {
                throw new Exception("Erro ao inserir transação");
            }

            $transaction_id = $this->conn->insert_id;

            // Atualizar o limite disponível do cartão
            $query = "UPDATE credit_cards 
                     SET available_limit = available_limit - ?
                     WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt->execute([$amount, $card_id])) {
                throw new Exception("Erro ao atualizar limite do cartão");
            }

            return $transaction_id;
        } catch (Exception $e) {
            error_log("Erro ao adicionar transação: " . $e->getMessage());
            throw $e;
        }
    }

    // Pagar transação
    public function payTransaction($transaction_id) {
        $this->conn->begin_transaction();
        
        try {
            // Buscar informações da transação
            $query = "SELECT ct.*, cc.id as card_id, ct.amount, cc.user_id 
                    FROM card_transactions ct 
                    JOIN credit_cards cc ON ct.card_id = cc.id 
                    WHERE ct.id = ? AND ct.status = 'pending'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$transaction_id]);
            $transaction = $stmt->get_result()->fetch_assoc();

            if (!$transaction) {
                throw new Exception("Transação não encontrada ou já paga");
            }

            // Atualizar status da transação
            $query = "UPDATE card_transactions SET status = 'paid' WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$transaction_id]);

            // Restaurar limite do cartão
            $query = "UPDATE credit_cards 
                    SET available_limit = available_limit + ? 
                    WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$transaction['amount'], $transaction['card_id']]);

            // Garantir que existe a categoria Cartão de Crédito
            $this->ensureCreditCardCategory($transaction['user_id']);

            // Lançar como despesa na tabela transactions
            $query = "INSERT INTO transactions (
                user_id, 
                category_id, 
                description, 
                amount, 
                type, 
                payment_method, 
                transaction_date,
                notes
            ) VALUES (
                ?, 
                (SELECT id FROM categories WHERE name = 'Cartão de Crédito' AND user_id = ? LIMIT 1), 
                ?, 
                ?, 
                'despesa', 
                'credito', 
                CURDATE(),
                ?
            )";
            
            $description = "Pagamento fatura - " . $transaction['description'];
            $notes = "Pagamento da fatura do cartão referente à transação: " . $transaction['description'];
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $transaction['user_id'],
                $transaction['user_id'],
                $description,
                $transaction['amount'],
                $notes
            ]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    // Listar transações do cartão
    public function getCardTransactions($card_id) {
        $query = "SELECT * FROM card_transactions WHERE card_id = ? ORDER BY due_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$card_id]);
        return $stmt->get_result();
    }

    // Excluir cartão e suas transações
    public function deleteCard($card_id, $user_id) {
        // Iniciar transação
        $this->conn->begin_transaction();
        
        try {
            // Verificar se o cartão pertence ao usuário
            $query = "SELECT id FROM " . $this->table_name . " WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id, $user_id]);
            
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Cartão não encontrado ou não pertence ao usuário");
            }

            // Excluir todas as transações do cartão
            $query = "DELETE FROM card_transactions WHERE card_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id]);

            // Excluir o cartão
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    // Excluir transação
    public function deleteTransaction($transaction_id, $user_id) {
        try {
            $this->conn->begin_transaction();

            // Primeiro, verifica se a transação pertence a um cartão do usuário
            $query = "SELECT ct.*, cc.user_id 
                     FROM card_transactions ct 
                     INNER JOIN credit_cards cc ON ct.card_id = cc.id 
                     WHERE ct.id = ? AND cc.user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$transaction_id, $user_id]);
            $transaction = $stmt->get_result()->fetch_assoc();

            if (!$transaction) {
                $this->conn->rollback();
                return false;
            }

            // Se a transação estiver pendente, restaura o limite do cartão
            if ($transaction['status'] === 'pending') {
                $updateLimit = "UPDATE credit_cards 
                              SET available_limit = available_limit + ? 
                              WHERE id = ?";
                $stmt = $this->conn->prepare($updateLimit);
                $stmt->execute([$transaction['amount'], $transaction['card_id']]);
            }

            // Exclui a transação
            $deleteQuery = "DELETE FROM card_transactions WHERE id = ?";
            $stmt = $this->conn->prepare($deleteQuery);
            $success = $stmt->execute([$transaction_id]);

            if ($success) {
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            throw new Exception("Erro ao excluir transação: " . $e->getMessage());
        }
    }

    // Buscar transação por ID
    public function getTransactionById($transaction_id) {
        try {
            $query = "SELECT t.* FROM card_transactions t 
                     INNER JOIN credit_cards c ON t.card_id = c.id 
                     WHERE t.id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$transaction_id]);
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Erro ao buscar transação: " . $e->getMessage());
            return false;
        }
    }

    // Debugar valores do cartão
    public function debugCardValues($card_id) {
        try {
            $query = "SELECT 
                        c.card_limit,
                        c.available_limit,
                        COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.amount ELSE 0 END), 0) as pending_amount,
                        GROUP_CONCAT(
                            CONCAT(
                                t.description, ': R$', 
                                t.amount, ' (', 
                                t.status, ')'
                            )
                        ) as transactions
                     FROM credit_cards c
                     LEFT JOIN card_transactions t ON c.id = t.card_id
                     WHERE c.id = ?
                     GROUP BY c.id, c.card_limit, c.available_limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id]);
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Erro ao debugar valores do cartão: " . $e->getMessage());
            return false;
        }
    }

    // Corrigir limite disponível
    public function fixAvailableLimit($card_id) {
        try {
            $this->conn->begin_transaction();

            // Buscar o limite total e calcular o usado
            $query = "SELECT 
                        c.card_limit,
                        COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.amount ELSE 0 END), 0) as used_amount
                     FROM credit_cards c
                     LEFT JOIN card_transactions t ON c.id = t.card_id
                     WHERE c.id = ?
                     GROUP BY c.id, c.card_limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id]);
            $result = $stmt->get_result()->fetch_assoc();

            if (!$result) {
                throw new Exception("Cartão não encontrado");
            }

            // Atualizar o limite disponível
            $available_limit = $result['card_limit'] - $result['used_amount'];
            $query = "UPDATE credit_cards SET available_limit = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$available_limit, $card_id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Erro ao corrigir limite disponível: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar limites do cartão
    public function updateCardLimits($card_id) {
        try {
            $this->conn->begin_transaction();

            // Primeiro, vamos buscar o cartão e suas transações
            $query = "SELECT 
                        c.id,
                        c.card_limit,
                        COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.amount ELSE 0 END), 0) as total_pending
                     FROM credit_cards c
                     LEFT JOIN card_transactions t ON c.id = t.card_id
                     WHERE c.id = ?
                     GROUP BY c.id, c.card_limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id]);
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!$result) {
                throw new Exception("Cartão não encontrado");
            }

            // Calcular o limite disponível (limite total - soma das transações pendentes)
            $available_limit = $result['card_limit'] - $result['total_pending'];

            // Atualizar o cartão com o limite disponível e usado
            $query = "UPDATE credit_cards 
                     SET available_limit = ?,
                         used_limit = ?
                     WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$available_limit, $result['total_pending'], $card_id]);

            // Registrar no log para debug
            error_log(sprintf(
                "Atualizando limites - Cartão ID: %d, Limite Total: %.2f, Total Pendente: %.2f, Disponível: %.2f",
                $card_id,
                $result['card_limit'],
                $result['total_pending'],
                $available_limit
            ));

            $this->conn->commit();
            return [
                'success' => true,
                'card_limit' => $result['card_limit'],
                'total_pending' => $result['total_pending'],
                'available_limit' => $available_limit
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Erro ao atualizar limites do cartão: " . $e->getMessage());
            return false;
        }
    }

    public function listCards() {
        try {
            $user_id = $_SESSION['user_id'];
            
            // Busca os cartões com os limites calculados e totais de transações
            $query = "SELECT 
                        c.*,
                        COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.amount ELSE 0 END), 0) as used_limit,
                        c.card_limit - COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.amount ELSE 0 END), 0) as available_limit,
                        COUNT(CASE WHEN t.status = 'pending' THEN 1 END) as pending_transactions,
                        (
                            SELECT GROUP_CONCAT(
                                CONCAT(
                                    t2.description, ': R$ ', 
                                    FORMAT(t2.amount, 2, 'pt_BR'),
                                    ' (', DATE_FORMAT(t2.transaction_date, '%d/%m/%Y'), ')'
                                ) SEPARATOR '\n'
                            )
                            FROM card_transactions t2
                            WHERE t2.card_id = c.id 
                            AND t2.status = 'pending'
                            ORDER BY t2.transaction_date ASC
                        ) as transaction_list,
                        (
                            SELECT COUNT(*)
                            FROM card_transactions t3
                            WHERE t3.card_id = c.id 
                            AND t3.status = 'pending'
                        ) as total_transactions
                     FROM credit_cards c
                     LEFT JOIN card_transactions t ON c.id = t.card_id
                     WHERE c.user_id = ?
                     GROUP BY c.id, c.user_id, c.card_name, c.card_limit, c.available_limit, 
                             c.closing_day, c.due_day, c.created_at";
                     
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            
            $result = $stmt->get_result();
            $cards = [];
            
            while ($card = $result->fetch_assoc()) {
                // Buscar transações detalhadas para cada cartão
                $card['transactions'] = $this->getCardTransactionsDetailed($card['id']);
                $cards[] = $card;
            }
            
            return $cards;
        } catch (Exception $e) {
            error_log("Erro ao listar cartões: " . $e->getMessage());
            return false;
        }
    }

    // Listar transações detalhadas do cartão
    public function getCardTransactionsDetailed($card_id) {
        try {
            $query = "SELECT 
                        t.*,
                        DATE_FORMAT(t.transaction_date, '%d/%m/%Y') as formatted_date,
                        DATE_FORMAT(t.due_date, '%d/%m/%Y') as formatted_due_date,
                        FORMAT(t.amount, 2, 'pt_BR') as formatted_amount
                     FROM card_transactions t
                     WHERE t.card_id = ? 
                     AND t.status = 'pending'
                     ORDER BY t.transaction_date ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id]);
            
            $transactions = [];
            $total = 0;
            
            while ($row = $stmt->get_result()->fetch_assoc()) {
                $transactions[] = $row;
                $total += $row['amount'];
            }
            
            return [
                'items' => $transactions,
                'total' => $total,
                'formatted_total' => 'R$ ' . number_format($total, 2, ',', '.')
            ];
        } catch (Exception $e) {
            error_log("Erro ao buscar transações detalhadas: " . $e->getMessage());
            return false;
        }
    }

    // Buscar cartão por ID
    public function getCardById($card_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id]);
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Erro ao buscar cartão: " . $e->getMessage());
            return false;
        }
    }

    // Garantir que existe a categoria Cartão de Crédito
    private function ensureCreditCardCategory($user_id) {
        $query = "INSERT IGNORE INTO categories (user_id, name, type, icon, color) 
                 VALUES (?, 'Cartão de Crédito', 'despesa', 'fas fa-credit-card', '#ff4444')";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id]);
    }

    // Pagar fatura do cartão
    public function payCard($card_id, $amount, $payment_date) {
        try {
            $this->conn->begin_transaction();

            // Buscar informações do cartão e suas transações pendentes
            $query = "SELECT c.*, 
                    COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.amount ELSE 0 END), 0) as total_pending
                    FROM credit_cards c
                    LEFT JOIN card_transactions t ON c.id = t.card_id
                    WHERE c.id = ?
                    GROUP BY c.id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id]);
            $card = $stmt->get_result()->fetch_assoc();

            if (!$card) {
                throw new Exception("Cartão não encontrado");
            }

            if ($amount <= 0) {
                throw new Exception("O valor do pagamento deve ser maior que zero");
            }

            if ($amount > $card['total_pending']) {
                throw new Exception("O valor do pagamento não pode ser maior que o valor da fatura");
            }

            // Garantir que existe a categoria Cartão de Crédito
            $this->ensureCreditCardCategory($card['user_id']);

            // Registrar o pagamento na tabela transactions
            $query = "INSERT INTO transactions (
                user_id,
                category_id,
                description,
                amount,
                type,
                payment_method,
                transaction_date,
                notes
            ) VALUES (
                ?,
                (SELECT id FROM categories WHERE name = 'Cartão de Crédito' AND user_id = ? LIMIT 1),
                ?,
                ?,
                'despesa',
                'credito',
                ?,
                ?
            )";

            $description = "Pagamento fatura - " . $card['card_name'];
            $notes = "Pagamento da fatura do cartão " . $card['card_name'];

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $card['user_id'],
                $card['user_id'],
                $description,
                $amount,
                $payment_date,
                $notes
            ]);

            // Atualizar o limite disponível do cartão
            $query = "UPDATE credit_cards 
                    SET available_limit = available_limit + ? 
                    WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$amount, $card_id]);

            // Se o pagamento for igual ao total pendente, marcar todas as transações como pagas
            // Se for menor, marcar as transações mais antigas como pagas até atingir o valor do pagamento
            $remaining_amount = $amount;
            $query = "SELECT id, amount FROM card_transactions 
                    WHERE card_id = ? AND status = 'pending' 
                    ORDER BY due_date ASC, transaction_date ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id]);
            $transactions = $stmt->get_result();

            while ($transaction = $transactions->fetch_assoc()) {
                if ($remaining_amount <= 0) break;

                if ($remaining_amount >= $transaction['amount']) {
                    // Pagar a transação inteira
                    $query = "UPDATE card_transactions SET status = 'paid' WHERE id = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([$transaction['id']]);
                    $remaining_amount -= $transaction['amount'];
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function updateCard($card_id, $card_name, $card_limit, $closing_day, $due_day) {
        try {
            // Buscar limite usado atual
            $query = "SELECT c.*, 
                     COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.amount ELSE 0 END), 0) as total_pending
                     FROM credit_cards c
                     LEFT JOIN card_transactions t ON c.id = t.card_id
                     WHERE c.id = ?
                     GROUP BY c.id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id]);
            $current_card = $stmt->get_result()->fetch_assoc();

            // Verificar se o novo limite é maior ou igual ao valor usado
            if ($card_limit < $current_card['total_pending']) {
                throw new Exception(sprintf(
                    'O novo limite (R$ %s) não pode ser menor que o valor usado (R$ %s)',
                    number_format($card_limit, 2, ',', '.'),
                    number_format($current_card['total_pending'], 2, ',', '.')
                ));
            }

            // Calcular novo limite disponível
            $available_limit = $card_limit - $current_card['total_pending'];

            // Atualizar cartão
            $query = "UPDATE credit_cards 
                     SET card_name = ?, 
                         card_limit = ?, 
                         available_limit = ?,
                         closing_day = ?, 
                         due_day = ?
                     WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                $card_name,
                $card_limit,
                $available_limit,
                $closing_day,
                $due_day,
                $card_id
            ]);
        } catch (Exception $e) {
            error_log("Erro ao atualizar cartão: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($card_id) {
        try {
            $this->conn->begin_transaction();

            // Primeiro excluir todas as transações do cartão
            $query = "DELETE FROM card_transactions WHERE card_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$card_id]);

            // Depois excluir o cartão
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $success = $stmt->execute([$card_id]);

            if ($success) {
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
?>
