<?php
require_once __DIR__ . '/../config/database.php';

class CategoryController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAllCategories($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, type, icon, color
                FROM categories 
                WHERE user_id = ? 
                ORDER BY name ASC
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            
            return ['success' => true, 'categories' => $categories];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createCategory($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO categories (name, type, icon, color, user_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssi", 
                $data['name'],
                $data['type'],
                $data['icon'],
                $data['color'],
                $data['user_id']
            );
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Categoria criada com sucesso',
                    'category_id' => $stmt->insert_id
                ];
            } else {
                throw new Exception("Erro ao criar categoria");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateCategory($data) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE categories 
                SET name = ?, type = ?, icon = ?, color = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ssssii",
                $data['name'],
                $data['type'],
                $data['icon'],
                $data['color'],
                $data['id'],
                $data['user_id']
            );
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Categoria atualizada com sucesso'];
            } else {
                throw new Exception("Erro ao atualizar categoria");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteCategory($id, $user_id) {
        try {
            // Verificar se existem transações usando esta categoria
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM transactions 
                WHERE category_id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                return [
                    'success' => false,
                    'error' => 'Não é possível excluir esta categoria pois existem transações associadas a ela'
                ];
            }
            
            // Deletar categoria
            $stmt = $this->conn->prepare("
                DELETE FROM categories 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $id, $user_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Categoria excluída com sucesso'];
            } else {
                throw new Exception("Erro ao excluir categoria");
            }
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
    
    $controller = new CategoryController();
    $user_id = $_SESSION['user_id'];
    
    switch ($_POST['action']) {
        case 'create':
            $_POST['user_id'] = $user_id;
            echo json_encode($controller->createCategory($_POST));
            break;
            
        case 'update':
            $_POST['user_id'] = $user_id;
            echo json_encode($controller->updateCategory($_POST));
            break;
            
        case 'delete':
            echo json_encode($controller->deleteCategory($_POST['id'], $user_id));
            break;
            
        case 'getAll':
            echo json_encode($controller->getAllCategories($user_id));
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
}
?>
