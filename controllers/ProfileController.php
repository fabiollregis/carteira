<?php
require_once __DIR__ . '/../config/database.php';

class ProfileController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getUserProfile($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, email, created_at
                FROM users
                WHERE id = ?
            ");

            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta: " . $this->conn->error);
            }

            $stmt->bind_param("i", $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar consulta: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (!$user) {
                throw new Exception("Usuário não encontrado");
            }

            return [
                'success' => true,
                'user' => $user
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function updateProfile($data) {
        try {
            // Verificar se o email já está em uso por outro usuário
            if (!empty($data['email'])) {
                $stmt = $this->conn->prepare("
                    SELECT id FROM users 
                    WHERE email = ? AND id != ?
                ");
                $stmt->bind_param("si", $data['email'], $data['user_id']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Este e-mail já está em uso");
                }
            }

            // Preparar a atualização
            $updates = [];
            $params = [];
            $types = "";

            if (!empty($data['name'])) {
                $updates[] = "name = ?";
                $params[] = $data['name'];
                $types .= "s";
            }

            if (!empty($data['email'])) {
                $updates[] = "email = ?";
                $params[] = $data['email'];
                $types .= "s";
            }

            if (!empty($data['new_password'])) {
                // Verificar a senha atual
                $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->bind_param("i", $data['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if (!password_verify($data['current_password'], $user['password'])) {
                    throw new Exception("Senha atual incorreta");
                }

                $updates[] = "password = ?";
                $params[] = password_hash($data['new_password'], PASSWORD_DEFAULT);
                $types .= "s";
            }

            if (empty($updates)) {
                throw new Exception("Nenhum dado para atualizar");
            }

            // Adicionar o ID do usuário aos parâmetros
            $params[] = $data['user_id'];
            $types .= "i";

            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);

            if (!$stmt) {
                throw new Exception("Erro ao preparar atualização: " . $this->conn->error);
            }

            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar perfil: " . $stmt->error);
            }

            return [
                'success' => true,
                'message' => 'Perfil atualizado com sucesso'
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
        echo json_encode([
            'success' => false,
            'error' => 'Usuário não autenticado'
        ]);
        exit;
    }

    $controller = new ProfileController();
    
    try {
        switch ($_POST['action']) {
            case 'update':
                $_POST['user_id'] = $_SESSION['user_id'];
                $response = $controller->updateProfile($_POST);
                break;
            default:
                throw new Exception('Ação inválida');
        }

        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
