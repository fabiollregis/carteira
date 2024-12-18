<?php
require_once __DIR__ . '/../config/database.php';

class ReportController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getPaymentMethodStats($filters) {
        try {
            $conditions = ['user_id = ?'];
            $params = [$filters['user_id']];
            $types = 'i';

            if (!empty($filters['year'])) {
                $conditions[] = "YEAR(transaction_date) = ?";
                $params[] = $filters['year'];
                $types .= 'i';
            }

            if (!empty($filters['month'])) {
                $conditions[] = "MONTH(transaction_date) = ?";
                $params[] = $filters['month'];
                $types .= 'i';
            }

            $sql = "
                SELECT 
                    payment_method,
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount
                FROM transactions
                WHERE " . implode(' AND ', $conditions) . "
                GROUP BY payment_method
                ORDER BY total_amount DESC
            ";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta: " . $this->conn->error);
            }

            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar consulta: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            return [
                'success' => true,
                'data' => $data
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getRecurringStats($filters) {
        try {
            $conditions = ['user_id = ?'];
            $params = [$filters['user_id']];
            $types = 'i';

            if (!empty($filters['year'])) {
                $conditions[] = "YEAR(transaction_date) = ?";
                $params[] = $filters['year'];
                $types .= 'i';
            }

            if (!empty($filters['month'])) {
                $conditions[] = "MONTH(transaction_date) = ?";
                $params[] = $filters['month'];
                $types .= 'i';
            }

            $sql = "
                SELECT 
                    CASE 
                        WHEN is_recurring = 1 THEN 'Recorrente'
                        WHEN installments > 1 THEN 'Parcelado'
                        ELSE 'Único'
                    END as transaction_type,
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount
                FROM transactions
                WHERE " . implode(' AND ', $conditions) . "
                GROUP BY 
                    CASE 
                        WHEN is_recurring = 1 THEN 'Recorrente'
                        WHEN installments > 1 THEN 'Parcelado'
                        ELSE 'Único'
                    END
                ORDER BY total_amount DESC
            ";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta: " . $this->conn->error);
            }

            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar consulta: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            return [
                'success' => true,
                'data' => $data
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getTransactionTypeStats($filters) {
        try {
            $conditions = ['user_id = ?'];
            $params = [$filters['user_id']];
            $types = 'i';

            if (!empty($filters['year'])) {
                $conditions[] = "YEAR(transaction_date) = ?";
                $params[] = $filters['year'];
                $types .= 'i';
            }

            if (!empty($filters['month'])) {
                $conditions[] = "MONTH(transaction_date) = ?";
                $params[] = $filters['month'];
                $types .= 'i';
            }

            $sql = "
                SELECT 
                    SUM(CASE WHEN type = 'receita' THEN amount ELSE 0 END) as receitas,
                    SUM(CASE WHEN type = 'despesa' THEN amount ELSE 0 END) as despesas
                FROM transactions
                WHERE " . implode(' AND ', $conditions);

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta: " . $this->conn->error);
            }

            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar consulta: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $data = $result->fetch_assoc();

            return [
                'success' => true,
                'data' => $data
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getCategoryStats($filters) {
        try {
            $conditions = ['t.user_id = ?'];
            $params = [$filters['user_id']];
            $types = 'i';

            if (!empty($filters['year'])) {
                $conditions[] = "YEAR(t.transaction_date) = ?";
                $params[] = $filters['year'];
                $types .= 'i';
            }

            if (!empty($filters['month'])) {
                $conditions[] = "MONTH(t.transaction_date) = ?";
                $params[] = $filters['month'];
                $types .= 'i';
            }

            $sql = "
                SELECT 
                    c.name as category_name,
                    SUM(t.amount) as total_amount
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE " . implode(' AND ', $conditions) . "
                GROUP BY c.id, c.name
                ORDER BY total_amount DESC
                LIMIT 10";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta: " . $this->conn->error);
            }

            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar consulta: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            return [
                'success' => true,
                'data' => $data
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    session_start();

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }

    $reportController = new ReportController();
    $filters = [
        'user_id' => $_SESSION['user_id'],
        'year' => $_POST['year'] ?? null,
        'month' => $_POST['month'] ?? null
    ];

    switch ($_POST['action']) {
        case 'payment_methods':
            echo json_encode($reportController->getPaymentMethodStats($filters));
            break;
        case 'transaction_types':
            echo json_encode($reportController->getTransactionTypeStats($filters));
            break;
        case 'categories':
            echo json_encode($reportController->getCategoryStats($filters));
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
    exit;
}
