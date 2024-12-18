<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CreditCard.php';
require_once __DIR__ . '/../config/config.php';

class CreditCardController {
    private $db;
    private $creditCard;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->creditCard = new CreditCard($this->db);
    }

    public function addCard() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $user_id = $_SESSION['user_id'];
                $card_name = $_POST['card_name'];
                $card_limit = floatval($_POST['card_limit']);
                $closing_day = intval($_POST['closing_day']);
                $due_day = intval($_POST['due_day']);

                if ($this->creditCard->create($user_id, $card_name, $card_limit, $closing_day, $due_day)) {
                    $_SESSION['success_message'] = 'Cartão cadastrado com sucesso';
                } else {
                    $_SESSION['error_message'] = 'Erro ao cadastrar cartão';
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
            header('Location: ' . BASE_URL . '/views/credit_cards/index.php');
            exit;
        }
    }

    public function addTransaction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Validar e sanitizar os dados
                $card_id = filter_input(INPUT_POST, 'card_id', FILTER_SANITIZE_NUMBER_INT);
                $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
                $amount = str_replace(['R$', '.', ','], ['', '', '.'], filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_STRING));
                $transaction_date = filter_input(INPUT_POST, 'transaction_date', FILTER_SANITIZE_STRING);
                $installments = filter_input(INPUT_POST, 'installments', FILTER_SANITIZE_NUMBER_INT) ?: 1;

                // Validar se todos os campos necessários estão presentes
                if (!$card_id || !$description || !$amount || !$transaction_date) {
                    throw new Exception('Todos os campos são obrigatórios');
                }

                // Converter amount para float
                $amount = floatval($amount);
                if ($amount <= 0) {
                    throw new Exception('O valor da transação deve ser maior que zero');
                }

                // Verificar se o cartão pertence ao usuário
                $card = $this->creditCard->getCardById($card_id);
                if (!$card || $card['user_id'] != $_SESSION['user_id']) {
                    throw new Exception('Cartão não encontrado ou não pertence ao usuário');
                }

                // Verificar limite disponível total antes de começar
                $query = "SELECT c.card_limit, 
                         COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.amount ELSE 0 END), 0) as total_pending
                         FROM credit_cards c
                         LEFT JOIN card_transactions t ON c.id = t.card_id
                         WHERE c.id = ?
                         GROUP BY c.id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$card_id]);
                $card_info = $stmt->get_result()->fetch_assoc();

                $available_limit = $card_info['card_limit'] - $card_info['total_pending'];

                if ($amount > $available_limit) {
                    throw new Exception(sprintf(
                        'Limite indisponível. Valor total: R$ %s, Limite disponível: R$ %s',
                        number_format($amount, 2, ',', '.'),
                        number_format($available_limit, 2, ',', '.')
                    ));
                }

                // Calcular data de vencimento baseado nas regras do cartão
                $transaction_timestamp = strtotime($transaction_date);
                $transaction_month = date('m', $transaction_timestamp);
                $transaction_year = date('Y', $transaction_timestamp);

                // Se a data da transação é depois do fechamento, move para o próximo mês
                if (date('d', $transaction_timestamp) > $card['closing_day']) {
                    if ($transaction_month == 12) {
                        $transaction_month = 1;
                        $transaction_year++;
                    } else {
                        $transaction_month++;
                    }
                }

                // Valor de cada parcela
                $installment_amount = round($amount / $installments, 2);
                $total_amount = $installment_amount * $installments;
                
                // Ajustar o último valor para compensar arredondamentos
                $last_installment = $amount - ($installment_amount * ($installments - 1));

                // Iniciar transação do banco
                $this->db->begin_transaction();

                try {
                    // Adicionar cada parcela
                    for ($i = 0; $i < $installments; $i++) {
                        $current_month = $transaction_month + $i;
                        $current_year = $transaction_year;

                        // Ajustar a virada de ano
                        while ($current_month > 12) {
                            $current_month -= 12;
                            $current_year++;
                        }

                        // Data de vencimento
                        $due_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $card['due_day']);

                        // Descrição da parcela
                        $installment_description = $description;
                        if ($installments > 1) {
                            $installment_description .= " (" . ($i + 1) . "/{$installments})";
                        }

                        // Valor da parcela (último valor ajustado se necessário)
                        $current_amount = ($i == $installments - 1) ? $last_installment : $installment_amount;

                        // Adicionar a transação
                        $this->creditCard->addTransaction(
                            $card_id,
                            $installment_description,
                            $current_amount,
                            $transaction_date,
                            $due_date
                        );
                    }

                    $this->db->commit();
                    $_SESSION['success_message'] = 'Transação adicionada com sucesso';
                } catch (Exception $e) {
                    $this->db->rollback();
                    throw new Exception('Erro ao adicionar parcelas: ' . $e->getMessage());
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
            header('Location: ' . BASE_URL . '/views/credit_cards/index.php');
            exit;
        }
    }

    public function payTransaction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $transaction_id = $_POST['transaction_id'];

                if ($this->creditCard->payTransaction($transaction_id)) {
                    $_SESSION['success_message'] = 'Pagamento registrado com sucesso';
                } else {
                    $_SESSION['error_message'] = 'Erro ao registrar pagamento';
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
            header('Location: ' . BASE_URL . '/views/credit_cards/index.php');
            exit;
        }
    }

    public function deleteCard($card_id) {
        try {
            $user_id = $_SESSION['user_id'];
            
            // Verificar se o cartão pertence ao usuário
            $card = $this->creditCard->getCardById($card_id);
            if ($card && $card['user_id'] == $user_id) {
                if ($this->creditCard->delete($card_id)) {
                    $_SESSION['success_message'] = 'Cartão e suas transações foram excluídos com sucesso';
                } else {
                    $_SESSION['error_message'] = 'Erro ao excluir cartão e suas transações';
                }
            } else {
                $_SESSION['error_message'] = 'Cartão não encontrado ou sem permissão';
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Erro ao excluir: ' . $e->getMessage();
        }
        
        header('Location: ' . BASE_URL . '/views/credit_cards/index.php');
        exit;
    }

    public function deleteTransaction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_id'])) {
            try {
                $transaction_id = $_POST['transaction_id'];
                $user_id = $_SESSION['user_id'];

                if ($this->creditCard->deleteTransaction($transaction_id, $user_id)) {
                    $_SESSION['success_message'] = 'Transação excluída com sucesso';
                    echo json_encode(['success' => true, 'message' => 'Transação excluída com sucesso']);
                } else {
                    $_SESSION['error_message'] = 'Erro ao excluir transação. Verifique se ela existe e pertence a você.';
                    echo json_encode(['success' => false, 'message' => 'Erro ao excluir transação']);
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Erro ao excluir: ' . $e->getMessage();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $_SESSION['error_message'] = 'Requisição inválida';
            echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
        }
        exit;
    }

    public function listCards() {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }

        return $this->creditCard->getUserCards($_SESSION['user_id']);
    }

    public function listTransactions($card_id) {
        try {
            // Verificar se o cartão pertence ao usuário
            $card = $this->creditCard->getCardById($card_id);
            if (!$card || $card['user_id'] != $_SESSION['user_id']) {
                throw new Exception('Cartão não encontrado ou não pertence ao usuário');
            }

            // Buscar transações
            return $this->creditCard->getCardTransactions($card_id);
        } catch (Exception $e) {
            error_log("Erro ao listar transações: " . $e->getMessage());
            return [];
        }
    }

    public function payCard() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $card_id = filter_input(INPUT_POST, 'card_id', FILTER_SANITIZE_NUMBER_INT);
                $amount = filter_input(INPUT_POST, 'payment_amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $payment_date = filter_input(INPUT_POST, 'payment_date', FILTER_SANITIZE_STRING);

                // Validar se todos os campos necessários estão presentes
                if (!$card_id || !$amount || !$payment_date) {
                    throw new Exception('Todos os campos são obrigatórios');
                }

                // Verificar se o cartão pertence ao usuário
                $card = $this->creditCard->getCardById($card_id);
                if (!$card || $card['user_id'] != $_SESSION['user_id']) {
                    throw new Exception('Cartão não encontrado ou não pertence ao usuário');
                }

                if ($this->creditCard->payCard($card_id, $amount, $payment_date)) {
                    $_SESSION['success_message'] = 'Pagamento registrado com sucesso';
                } else {
                    $_SESSION['error_message'] = 'Erro ao registrar pagamento';
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
            header('Location: ' . BASE_URL . '/views/credit_cards/index.php');
            exit;
        }
    }

    public function getCard($card_id) {
        try {
            $card = $this->creditCard->getCardById($card_id);
            if (!$card || $card['user_id'] != $_SESSION['user_id']) {
                throw new Exception('Cartão não encontrado');
            }
            return $card;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function editCard() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $card_id = filter_input(INPUT_POST, 'card_id', FILTER_SANITIZE_NUMBER_INT);
                $card_name = filter_input(INPUT_POST, 'card_name', FILTER_SANITIZE_STRING);
                $card_limit = filter_input(INPUT_POST, 'card_limit', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $closing_day = filter_input(INPUT_POST, 'closing_day', FILTER_SANITIZE_NUMBER_INT);
                $due_day = filter_input(INPUT_POST, 'due_day', FILTER_SANITIZE_NUMBER_INT);

                // Validar dados
                if (!$card_id || !$card_name || !$card_limit || !$closing_day || !$due_day) {
                    throw new Exception('Todos os campos são obrigatórios');
                }

                if ($closing_day < 1 || $closing_day > 31 || $due_day < 1 || $due_day > 31) {
                    throw new Exception('Dias de fechamento e vencimento devem estar entre 1 e 31');
                }

                // Verificar se o cartão pertence ao usuário
                $card = $this->creditCard->getCardById($card_id);
                if (!$card || $card['user_id'] != $_SESSION['user_id']) {
                    throw new Exception('Cartão não encontrado ou não pertence ao usuário');
                }

                if ($this->creditCard->updateCard($card_id, $card_name, $card_limit, $closing_day, $due_day)) {
                    $_SESSION['success_message'] = 'Cartão atualizado com sucesso';
                } else {
                    throw new Exception('Erro ao atualizar cartão');
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
            header('Location: ' . BASE_URL . '/views/credit_cards/index.php');
            exit;
        }
    }

    public function payInvoice() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_id'])) {
            try {
                $card_id = $_POST['card_id'];
                $user_id = $_SESSION['user_id'];

                // Verificar se o cartão pertence ao usuário
                $card = $this->creditCard->getCardById($card_id);
                if (!$card || $card['user_id'] != $user_id) {
                    throw new Exception('Cartão não encontrado ou não pertence ao usuário');
                }

                // Buscar transações pendentes
                $transactions = $this->creditCard->getCardTransactions($card_id);
                $total = 0;

                // Iniciar transação do banco
                $this->db->begin_transaction();

                try {
                    while ($transaction = $transactions->fetch_assoc()) {
                        if ($transaction['status'] === 'pending') {
                            // Marcar transação como paga
                            $query = "UPDATE card_transactions SET status = 'paid' WHERE id = ?";
                            $stmt = $this->db->prepare($query);
                            if (!$stmt->execute([$transaction['id']])) {
                                throw new Exception('Erro ao atualizar status da transação');
                            }

                            $total += $transaction['amount'];
                        }
                    }

                    // Restaurar limite do cartão
                    $query = "UPDATE credit_cards 
                             SET available_limit = card_limit
                             WHERE id = ?";
                    $stmt = $this->db->prepare($query);
                    if (!$stmt->execute([$card_id])) {
                        throw new Exception('Erro ao restaurar limite do cartão');
                    }

                    $this->db->commit();
                    $_SESSION['success_message'] = sprintf(
                        'Fatura paga com sucesso! Total: R$ %s',
                        number_format($total, 2, ',', '.')
                    );
                } catch (Exception $e) {
                    $this->db->rollback();
                    throw $e;
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
            header('Location: ' . BASE_URL . '/views/credit_cards/index.php');
            exit;
        }
    }
}
?>
