<?php
require_once __DIR__ . '/../config/database.php';

class TransactionController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getTransactions($filters, $user_id) {
        try {
            $conditions = ['t.user_id = ?'];
            $params = [$user_id];
            $types = 'i';

            // Filtro de data
            if (!empty($filters['month']) && !empty($filters['year'])) {
                $month = $filters['month'];
                $year = $filters['year'];
                
                // Para transações recorrentes, pegamos apenas uma por mês
                $conditions[] = "(
                    (DATE_FORMAT(t.transaction_date, '%Y-%m') = ?)
                    OR (
                        t.is_recurring = 1 
                        AND DAY(t.transaction_date) = t.recurring_day 
                        AND DATE_FORMAT(t.transaction_date, '%Y-%m') <= ?
                        AND NOT EXISTS (
                            SELECT 1 FROM transactions t2 
                            WHERE t2.description = t.description 
                            AND t2.is_recurring = 1 
                            AND DATE_FORMAT(t2.transaction_date, '%Y-%m') = ?
                            AND t2.id > t.id
                        )
                    )
                )";
                $date = sprintf('%04d-%02d', $year, $month);
                array_push($params, $date, $date, $date);
                $types .= 'sss';
            }

            // Filtro de tipo (receita/despesa)
            if (!empty($filters['type'])) {
                $conditions[] = 't.type = ?';
                $params[] = $filters['type'];
                $types .= 's';
            }

            // Filtro de categoria
            if (!empty($filters['category'])) {
                $conditions[] = 't.category_id = ?';
                $params[] = intval($filters['category']);
                $types .= 'i';
            }

            $sql = "
                SELECT t.*, c.name as category_name, c.color as category_color, c.icon as category_icon
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY t.transaction_date DESC, t.id DESC
            ";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta: " . $this->conn->error);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar consulta: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $transactions = [];
            $total_receitas = 0;
            $total_despesas = 0;

            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
                if ($row['type'] === 'receita') {
                    $total_receitas += $row['amount'];
                } else {
                    $total_despesas += $row['amount'];
                }
            }

            return [
                'success' => true,
                'transactions' => $transactions,
                'total_receitas' => $total_receitas,
                'total_despesas' => $total_despesas,
                'saldo' => $total_receitas - $total_despesas
            ];

        } catch (Exception $e) {
            error_log("Erro ao buscar transações: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createTransaction($data) {
        try {
            if (!isset($data['description']) || !isset($data['amount']) || !isset($data['type']) || 
                !isset($data['category_id']) || !isset($data['transaction_date'])) {
                throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
            }

            $this->conn->begin_transaction();

            $baseData = [
                'description' => $data['description'],
                'amount' => floatval($data['amount']),
                'type' => $data['type'],
                'category_id' => intval($data['category_id']),
                'payment_method' => $data['payment_method'] ?? 'dinheiro',
                'notes' => $data['notes'] ?? '',
                'user_id' => intval($data['user_id'])
            ];

            $transaction_ids = [];

            if (isset($data['payment_method']) && $data['payment_method'] === 'parcelado' && !empty($data['installments'])) {
                $installments = max(1, intval($data['installments']));
                $amount_per_installment = $baseData['amount'] / $installments;
                $baseData['amount'] = $amount_per_installment;

                for ($i = 1; $i <= $installments; $i++) {
                    $installment_date = date('Y-m-d', strtotime($data['transaction_date'] . " +" . ($i-1) . " month"));
                    $description = $baseData['description'] . " ({$i}/{$installments})";
                    
                    $transaction_ids[] = $this->insertSingleTransaction(array_merge($baseData, [
                        'description' => $description,
                        'transaction_date' => $installment_date,
                        'installments' => $installments,
                        'installment_number' => $i
                    ]));
                }
            }
            else if (!empty($data['is_recurring'])) {
                $recurring_type = $data['recurring_type'] ?? 'mensal';
                $recurring_day = !empty($data['recurring_day']) ? intval($data['recurring_day']) : intval(date('d'));
                
                $num_occurrences = ($recurring_type === 'mensal') ? 12 : 1;
                
                for ($i = 0; $i < $num_occurrences; $i++) {
                    $interval = ($recurring_type === 'mensal') ? "+{$i} month" : "+{$i} year";
                    
                    $next_date = new DateTime($data['transaction_date']);
                    $next_date->modify($interval);
                    $next_date->setDate(
                        $next_date->format('Y'),
                        $next_date->format('m'),
                        min($recurring_day, $next_date->format('t'))
                    );
                    
                    $transaction_ids[] = $this->insertSingleTransaction(array_merge($baseData, [
                        'transaction_date' => $next_date->format('Y-m-d'),
                        'is_recurring' => 1,
                        'recurring_type' => $recurring_type,
                        'recurring_day' => $recurring_day,
                        'installments' => 1,
                        'installment_number' => 1
                    ]));
                }
            }
            else {
                $transaction_ids[] = $this->insertSingleTransaction(array_merge($baseData, [
                    'transaction_date' => $data['transaction_date'],
                    'installments' => 1,
                    'installment_number' => 1
                ]));
            }

            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Transação criada com sucesso',
                'transaction_ids' => $transaction_ids
            ];

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollback();
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function updateTransaction($data) {
        try {
            if (!isset($data['id']) || !isset($data['description']) || !isset($data['amount']) || 
                !isset($data['type']) || !isset($data['category_id']) || !isset($data['transaction_date'])) {
                throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
            }

            $this->conn->begin_transaction();

            $sql = "UPDATE transactions SET 
                    description = ?,
                    amount = ?,
                    type = ?,
                    category_id = ?,
                    payment_method = ?,
                    transaction_date = ?,
                    notes = ?
                    WHERE id = ? AND user_id = ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta: " . $this->conn->error);
            }

            $stmt->bind_param(
                "sdsisssii",
                $data['description'],
                $data['amount'],
                $data['type'],
                $data['category_id'],
                $data['payment_method'],
                $data['transaction_date'],
                $data['notes'],
                $data['id'],
                $data['user_id']
            );

            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar transação: " . $stmt->error);
            }

            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Transação atualizada com sucesso'
            ];

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollback();
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function insertSingleTransaction($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO transactions (
                description, amount, type, category_id,
                transaction_date, payment_method, installments,
                installment_number, is_recurring, recurring_type,
                recurring_day, notes, user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Erro ao preparar a query: " . $this->conn->error);
        }

        $is_recurring = isset($data['is_recurring']) ? 1 : 0;
        $recurring_type = $data['recurring_type'] ?? null;
        $recurring_day = $data['recurring_day'] ?? null;
        
        if (!$stmt->bind_param(
            "sdsissiiissis",
            $data['description'],
            $data['amount'],
            $data['type'],
            $data['category_id'],
            $data['transaction_date'],
            $data['payment_method'],
            $data['installments'],
            $data['installment_number'],
            $is_recurring,
            $recurring_type,
            $recurring_day,
            $data['notes'],
            $data['user_id']
        )) {
            throw new Exception("Erro ao vincular parâmetros: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar query: " . $stmt->error);
        }
        
        $insert_id = $stmt->insert_id;
        $stmt->close();
        
        return $insert_id;
    }

    public function deleteTransaction($id, $user_id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Transação excluída com sucesso'
                ];
            }
            
            throw new Exception('Erro ao excluir transação');
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getPaymentMethods() {
        return [
            'dinheiro' => 'Dinheiro',
            'pix' => 'PIX',
            'debito' => 'Cartão de Débito',
            'credito' => 'Cartão de Crédito',
            'parcelado' => 'Cartão Parcelado'
        ];
    }

    // Obter resumo financeiro do mês
    public function getFinancialSummary($month, $year) {
        try {
            // Buscar total de receitas
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM transactions 
                WHERE user_id = ? 
                AND MONTH(transaction_date) = ? 
                AND YEAR(transaction_date) = ?
                AND type = 'receita'
            ");
            $stmt->bind_param("iii", $_SESSION['user_id'], $month, $year);
            $stmt->execute();
            $result = $stmt->get_result();
            $totalIncome = $result->fetch_assoc()['total'];

            // Buscar total de despesas
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM transactions 
                WHERE user_id = ? 
                AND MONTH(transaction_date) = ? 
                AND YEAR(transaction_date) = ?
                AND type = 'despesa'
            ");
            $stmt->bind_param("iii", $_SESSION['user_id'], $month, $year);
            $stmt->execute();
            $result = $stmt->get_result();
            $totalExpenses = $result->fetch_assoc()['total'];

            // Calcular saldo
            $balance = $totalIncome - $totalExpenses;

            return [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'balance' => $balance
            ];

        } catch (Exception $e) {
            return [
                'total_income' => 0,
                'total_expenses' => 0,
                'balance' => 0
            ];
        }
    }

    public function getRecentTransactions($limit = 5) {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Usuário não autenticado");
            }

            $sql = "
                SELECT 
                    t.id,
                    t.description,
                    t.amount,
                    t.type,
                    t.transaction_date,
                    t.installment_number,
                    t.installments as total_installments,
                    t.is_recurring,
                    t.recurring_type,
                    t.recurring_day,
                    c.name as category_name,
                    c.icon as category_icon,
                    c.color as category_color
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ?
                ORDER BY t.transaction_date DESC, t.id DESC
                LIMIT ?
            ";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta: " . $this->conn->error);
            }

            $user_id = $_SESSION['user_id'];
            if (!$stmt->bind_param("ii", $user_id, $limit)) {
                throw new Exception("Erro ao vincular parâmetros: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar consulta: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $transactions = [];
            
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }

            error_log("Transações encontradas: " . count($transactions));
            
            return [
                'success' => true,
                'transactions' => $transactions
            ];
            
        } catch (Exception $e) {
            error_log("Erro em getRecentTransactions: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'transactions' => []
            ];
        }
    }

    public function getFutureTransactions() {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Usuário não autenticado");
            }

            $today = date('Y-m-d');
            
            $sql = "
                SELECT 
                    t.id,
                    t.description,
                    t.amount,
                    t.type,
                    t.transaction_date,
                    t.installment_number,
                    t.installments as total_installments,
                    t.is_recurring,
                    t.recurring_type,
                    t.recurring_day,
                    c.name as category_name,
                    c.icon as category_icon,
                    c.color as category_color
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? 
                AND (
                    t.transaction_date > ? 
                    OR t.is_recurring = 1
                    OR (t.installments > 1 AND t.installment_number < t.installments)
                )
                ORDER BY t.transaction_date ASC
                LIMIT 10
            ";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta: " . $this->conn->error);
            }

            $user_id = $_SESSION['user_id'];
            if (!$stmt->bind_param("is", $user_id, $today)) {
                throw new Exception("Erro ao vincular parâmetros: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar consulta: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $transactions = [];
            
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }

            error_log("Transações futuras encontradas: " . count($transactions));
            
            return [
                'success' => true,
                'transactions' => $transactions
            ];
            
        } catch (Exception $e) {
            error_log("Erro em getFutureTransactions: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'transactions' => []
            ];
        }
    }
}

// Processamento das requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    session_start();

    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Usuário não autenticado'
        ]);
        exit;
    }

    $controller = new TransactionController();
    $_POST['user_id'] = $_SESSION['user_id'];

    switch ($_POST['action']) {
        case 'create':
            echo json_encode($controller->createTransaction($_POST));
            break;
            
        case 'update':
            echo json_encode($controller->updateTransaction($_POST));
            break;

        case 'delete':
            if (!isset($_POST['id'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'ID da transação não fornecido'
                ]);
                break;
            }
            echo json_encode($controller->deleteTransaction($_POST['id'], $_SESSION['user_id']));
            break;

        case 'list':
            $filters = [
                'month' => $_POST['month'] ?? date('m'),
                'year' => $_POST['year'] ?? date('Y'),
                'type' => $_POST['type'] ?? '',
                'category' => $_POST['category'] ?? ''
            ];
            echo json_encode($controller->getTransactions($filters, $_SESSION['user_id']));
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'Ação inválida'
            ]);
    }
    exit;
}

// Processamento das requisições GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    header('Content-Type: application/json');
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }
    
    $controller = new TransactionController();
    $filters = $_GET;
    unset($filters['action']);
    
    echo json_encode($controller->getTransactions($filters, $_SESSION['user_id']));
    exit;
}
