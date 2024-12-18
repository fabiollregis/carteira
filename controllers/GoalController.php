<?php
require_once __DIR__ . '/../config/database.php';

class GoalController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getGoals($user_id) {
        try {
            $sql = "SELECT g.*, c.name as category_name, c.color as category_color 
                    FROM goals g 
                    LEFT JOIN categories c ON g.category_id = c.id 
                    WHERE g.user_id = ? 
                    ORDER BY g.end_date ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $goals = [];
            
            while ($row = $result->fetch_assoc()) {
                // Calcular o progresso
                $progress = 0;
                if ($row['target_amount'] > 0) {
                    $progress = ($row['current_amount'] / $row['target_amount']) * 100;
                }
                $row['progress'] = round($progress, 2);
                
                $goals[] = $row;
            }
            
            return ['success' => true, 'goals' => $goals];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createGoal($data) {
        try {
            if (!isset($data['title']) || !isset($data['target_amount']) || 
                !isset($data['start_date']) || !isset($data['end_date'])) {
                throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
            }

            $sql = "INSERT INTO goals (user_id, title, description, target_amount, category_id, 
                    start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $description = isset($data['description']) ? $data['description'] : '';
            $category_id = !empty($data['category_id']) ? $data['category_id'] : null;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("issdiss", 
                $data['user_id'],
                $data['title'],
                $description,
                $data['target_amount'],
                $category_id,
                $data['start_date'],
                $data['end_date']
            );
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Meta criada com sucesso'];
            } else {
                throw new Exception('Erro ao criar meta: ' . $stmt->error);
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateGoal($data) {
        try {
            if (!isset($data['id'])) {
                throw new Exception('ID da meta não fornecido');
            }

            // Se for apenas atualização de status
            if (isset($data['status'])) {
                // Primeiro, verifica se a meta existe
                $sql = "SELECT * FROM goals WHERE id = ? AND user_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ii", $data['id'], $data['user_id']);
                $stmt->execute();
                
                $result = $stmt->get_result();
                if (!$result->fetch_assoc()) {
                    throw new Exception('Meta não encontrada');
                }

                // Atualiza o status
                $sql = "UPDATE goals SET status = ? WHERE id = ? AND user_id = ?";
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Erro na preparação da query: " . $this->conn->error);
                }
                
                $stmt->bind_param("sii", $data['status'], $data['id'], $data['user_id']);
                
                if (!$stmt->execute()) {
                    throw new Exception('Erro ao atualizar status da meta: ' . $stmt->error);
                }
                
                return ['success' => true, 'message' => 'Status da meta atualizado com sucesso'];
            }

            // Atualização completa da meta
            if (!isset($data['title']) || !isset($data['target_amount'])) {
                throw new Exception('Dados inválidos para atualização');
            }

            $sql = "UPDATE goals SET 
                    title = ?, 
                    description = ?, 
                    target_amount = ?, 
                    current_amount = ?, 
                    category_id = ?, 
                    start_date = ?, 
                    end_date = ?, 
                    status = ? 
                    WHERE id = ? AND user_id = ?";
            
            // Trata campos opcionais
            $description = isset($data['description']) ? $data['description'] : '';
            $current_amount = isset($data['current_amount']) ? floatval($data['current_amount']) : 0;
            $category_id = !empty($data['category_id']) ? intval($data['category_id']) : null;
            $status = isset($data['status']) ? $data['status'] : 'em_andamento';
            $target_amount = floatval($data['target_amount']);
            $id = intval($data['id']);
            $user_id = intval($data['user_id']);
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro na preparação da query: " . $this->conn->error);
            }

            // s = string, d = double, i = integer
            $stmt->bind_param("ssddsssiii", 
                $data['title'],
                $description,
                $target_amount,
                $current_amount,
                $category_id,
                $data['start_date'],
                $data['end_date'],
                $status,
                $id,
                $user_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar meta: ' . $stmt->error);
            }
            
            return ['success' => true, 'message' => 'Meta atualizada com sucesso'];
        } catch (Exception $e) {
            error_log("Erro ao atualizar meta: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteGoal($id, $user_id) {
        try {
            $sql = "DELETE FROM goals WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $id, $user_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Meta excluída com sucesso'];
            } else {
                throw new Exception('Erro ao excluir meta');
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getGoal($goal_id, $user_id) {
        try {
            $sql = "SELECT * FROM goals WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $goal_id, $user_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            
            if ($goal = $result->fetch_assoc()) {
                return ['success' => true, 'goal' => $goal];
            } else {
                throw new Exception('Meta não encontrada');
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function addValue($goal_id, $user_id, $amount) {
        try {
            // Busca a meta atual
            $sql = "SELECT * FROM goals WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $goal_id, $user_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            if (!$goal = $result->fetch_assoc()) {
                throw new Exception('Meta não encontrada');
            }

            // Valida o valor
            $amount = floatval($amount);
            if ($amount <= 0) {
                throw new Exception('O valor deve ser maior que zero');
            }

            // Calcula o novo valor atual
            $new_amount = floatval($goal['current_amount']) + $amount;
            
            // Verifica se o novo valor não ultrapassa o valor total
            if ($new_amount > floatval($goal['target_amount'])) {
                throw new Exception('O valor excede o valor total da meta');
            }

            // Atualiza o valor atual da meta
            $sql = "UPDATE goals SET current_amount = ?";
            
            // Se atingiu o valor total, marca como concluída
            if ($new_amount >= floatval($goal['target_amount'])) {
                $sql .= ", status = 'concluida'";
            }
            
            $sql .= " WHERE id = ? AND user_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("dii", $new_amount, $goal_id, $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar valor da meta');
            }

            $progress = ($new_amount / floatval($goal['target_amount'])) * 100;
            $is_completed = $new_amount >= floatval($goal['target_amount']);

            return [
                'success' => true, 
                'message' => $is_completed ? 'Meta concluída com sucesso!' : 'Valor adicionado com sucesso',
                'current_amount' => $new_amount,
                'remaining_amount' => floatval($goal['target_amount']) - $new_amount,
                'is_completed' => $is_completed,
                'progress' => round($progress, 2)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Processamento das requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    session_start();

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }

    $controller = new GoalController();
    $_POST['user_id'] = $_SESSION['user_id'];

    try {
        switch ($_POST['action']) {
            case 'create':
                echo json_encode($controller->createGoal($_POST));
                break;
                
            case 'update':
                echo json_encode($controller->updateGoal($_POST));
                break;
                
            case 'delete':
                if (!isset($_POST['id'])) {
                    throw new Exception('ID da meta não fornecido');
                }
                echo json_encode($controller->deleteGoal($_POST['id'], $_SESSION['user_id']));
                break;
                
            case 'list':
                echo json_encode($controller->getGoals($_SESSION['user_id']));
                break;
                
            case 'add_value':
                if (!isset($_POST['goal_id']) || !isset($_POST['amount'])) {
                    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
                    exit;
                }
                echo json_encode($controller->addValue($_POST['goal_id'], $_SESSION['user_id'], $_POST['amount']));
                break;
                
            default:
                throw new Exception('Ação inválida');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Processamento das requisições GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    session_start();

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }

    $controller = new GoalController();

    switch ($_GET['action']) {
        case 'get_goal':
            if (!isset($_GET['goal_id'])) {
                echo json_encode(['success' => false, 'error' => 'ID da meta não fornecido']);
                exit;
            }
            echo json_encode($controller->getGoal($_GET['goal_id'], $_SESSION['user_id']));
            break;
        
        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
            break;
    }
    exit;
}
