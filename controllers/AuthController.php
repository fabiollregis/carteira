<?php
require_once '../config/database.php';
require_once '../models/User.php';

class AuthController {
    private $user;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->user = new User($db);
    }

    public function register() {
        header('Content-Type: application/json');
        
        try {
            // Validar campos
            $required_fields = ['name', 'email', 'password', 'confirm_password'];
            foreach ($required_fields as $field) {
                if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                    throw new Exception("O campo " . str_replace('_', ' ', $field) . " é obrigatório");
                }
            }

            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email inválido");
            }

            // Validar senha
            if (strlen($password) < 6) {
                throw new Exception("A senha deve ter no mínimo 6 caracteres");
            }

            if ($password !== $confirm_password) {
                throw new Exception("As senhas não conferem");
            }

            // Registrar usuário
            $result = $this->user->register($name, $email, $password);
            
            if ($result['success']) {
                // Iniciar sessão
                session_start();
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['user_name'] = $name;
            }

            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function login() {
        header('Content-Type: application/json');
        
        try {
            // Validar campos
            if (!isset($_POST['email']) || !isset($_POST['password'])) {
                throw new Exception("Email e senha são obrigatórios");
            }

            $email = trim($_POST['email']);
            $password = $_POST['password'];

            // Fazer login
            $result = $this->user->login($email, $password);
            
            if ($result['success']) {
                // Iniciar sessão
                session_start();
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['user_name'] = $result['user_name'];
            }

            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function logout() {
        session_start();
        session_destroy();
        header('Location: /carteira/login.php');
        exit;
    }
}

// Processar requisições
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register':
                $controller->register();
                break;
            case 'login':
                $controller->login();
                break;
        }
    }
}

// Processar logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $controller = new AuthController();
    $controller->logout();
}
?>
