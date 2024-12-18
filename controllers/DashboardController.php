<?php
require_once __DIR__ . '/../config/database.php';

class DashboardController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getMonthlyStats($month, $year, $user_id) {
        try {
            // Receitas e despesas do mês
            $stmt = $this->conn->prepare("
                SELECT 
                    type,
                    SUM(amount) as total,
                    COUNT(*) as count
                FROM transactions 
                WHERE user_id = ? 
                AND MONTH(transaction_date) = ? 
                AND YEAR(transaction_date) = ?
                GROUP BY type
            ");
            $stmt->bind_param("iii", $user_id, $month, $year);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $stats = [
                'income' => 0,
                'expense' => 0,
                'balance' => 0
            ];
            
            while ($row = $result->fetch_assoc()) {
                if ($row['type'] === 'receita') {
                    $stats['income'] = floatval($row['total']);
                } else if ($row['type'] === 'despesa') {
                    $stats['expense'] = floatval($row['total']);
                }
            }
            
            $stats['balance'] = $stats['income'] - $stats['expense'];
            
            return [
                'success' => true,
                'data' => $stats
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getCategoryStats($month, $year, $user_id) {
        try {
            $sql = "
                SELECT 
                    c.name as category_name,
                    SUM(t.amount) as total_amount
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? 
                    AND MONTH(t.transaction_date) = ?
                    AND YEAR(t.transaction_date) = ?
                    AND t.type = 'despesa'
                GROUP BY c.id, c.name
                ORDER BY total_amount DESC
                LIMIT 10
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('iii', $user_id, $month, $year);
            $stmt->execute();
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

    public function getTrendStats($month, $year, $user_id) {
        try {
            $sql = "
                SELECT 
                    DATE_FORMAT(transaction_date, '%Y-%m') as month,
                    SUM(CASE WHEN type = 'receita' THEN amount ELSE 0 END) as income,
                    SUM(CASE WHEN type = 'despesa' THEN amount ELSE 0 END) as expense
                FROM transactions
                WHERE user_id = ?
                    AND transaction_date BETWEEN DATE_SUB(?, INTERVAL 5 MONTH) AND LAST_DAY(?)
                GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                ORDER BY month ASC
            ";

            $startDate = "$year-$month-01";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('iss', $user_id, $startDate, $startDate);
            $stmt->execute();
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

    public function getPaymentStats($month, $year, $user_id) {
        try {
            $sql = "
                SELECT 
                    payment_method,
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount
                FROM transactions
                WHERE user_id = ?
                    AND MONTH(transaction_date) = ?
                    AND YEAR(transaction_date) = ?
                GROUP BY payment_method
                ORDER BY total_amount DESC
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('iii', $user_id, $month, $year);
            $stmt->execute();
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

    public function getMonthlyTrend($year, $user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    MONTH(transaction_date) as month,
                    type,
                    SUM(amount) as total
                FROM transactions
                WHERE user_id = ? 
                AND YEAR(transaction_date) = ?
                GROUP BY MONTH(transaction_date), type
                ORDER BY month
            ");
            $stmt->bind_param("ii", $user_id, $year);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $months = array_fill(1, 12, ['receita' => 0, 'despesa' => 0]);
            
            while ($row = $result->fetch_assoc()) {
                $months[$row['month']][$row['type']] = $row['total'];
            }
            
            return [
                'success' => true,
                'trend' => $months
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getPaymentMethodStats($month, $year, $user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    payment_method,
                    COUNT(*) as count,
                    SUM(amount) as total
                FROM transactions
                WHERE user_id = ? 
                AND MONTH(transaction_date) = ? 
                AND YEAR(transaction_date) = ?
                AND type = 'despesa'
                GROUP BY payment_method
            ");
            $stmt->bind_param("iii", $user_id, $month, $year);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $methods = [];
            while ($row = $result->fetch_assoc()) {
                $methods[] = [
                    'method' => $row['payment_method'],
                    'count' => $row['count'],
                    'total' => $row['total']
                ];
            }
            
            return [
                'success' => true,
                'methods' => $methods
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
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

    $controller = new DashboardController();
    $month = $_POST['month'] ?? date('m');
    $year = $_POST['year'] ?? date('Y');
    $user_id = $_SESSION['user_id'];

    switch ($_POST['action']) {
        case 'monthly_stats':
            echo json_encode($controller->getMonthlyStats($month, $year, $user_id));
            break;
        case 'category_stats':
            echo json_encode($controller->getCategoryStats($month, $year, $user_id));
            break;
        case 'trend_stats':
            echo json_encode($controller->getTrendStats($month, $year, $user_id));
            break;
        case 'payment_stats':
            echo json_encode($controller->getPaymentStats($month, $year, $user_id));
            break;
        case 'monthly_trend':
            echo json_encode($controller->getMonthlyTrend($year, $user_id));
            break;
        case 'payment_method_stats':
            echo json_encode($controller->getPaymentMethodStats($month, $year, $user_id));
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
    exit;
}
?>
