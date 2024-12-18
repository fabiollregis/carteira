<?php
class User {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($name, $email, $password) {
        try {
            // Verificar se o email já existe
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return ['success' => false, 'error' => 'Este email já está cadastrado'];
            }

            // Hash da senha
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Inserir novo usuário
            $stmt = $this->conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $userId = $this->conn->insert_id;
                
                // Criar categorias padrão para o novo usuário
                $this->createDefaultCategories($userId);
                
                return ['success' => true, 'user_id' => $userId];
            }

            return ['success' => false, 'error' => 'Erro ao cadastrar usuário'];
        } catch (Exception $e) {
            error_log("Erro no registro: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao processar registro'];
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    return [
                        'success' => true,
                        'user_id' => $user['id'],
                        'user_name' => $user['name']
                    ];
                }
            }
            
            return ['success' => false, 'error' => 'Email ou senha incorretos'];
        } catch (Exception $e) {
            error_log("Erro no login: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao processar login'];
        }
    }

    private function createDefaultCategories($userId) {
        $categories = [
            ['name' => 'Salário', 'type' => 'receita'],
            ['name' => 'Freelance', 'type' => 'receita'],
            ['name' => 'Investimentos', 'type' => 'receita'],
            ['name' => 'Outros', 'type' => 'receita'],
            ['name' => 'Alimentação', 'type' => 'despesa'],
            ['name' => 'Moradia', 'type' => 'despesa'],
            ['name' => 'Transporte', 'type' => 'despesa'],
            ['name' => 'Saúde', 'type' => 'despesa'],
            ['name' => 'Educação', 'type' => 'despesa'],
            ['name' => 'Lazer', 'type' => 'despesa'],
            ['name' => 'Outros', 'type' => 'despesa']
        ];

        $stmt = $this->conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
        
        foreach ($categories as $category) {
            $stmt->bind_param("iss", $userId, $category['name'], $category['type']);
            $stmt->execute();
        }
    }
}
?>
