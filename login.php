<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT id, name, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        }
    }
    
    $error = "Email ou senha inválidos";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Financeiro</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        
        .form-signin {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }
        
        .form-signin .card {
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <main class="form-signin text-center">
        <div class="card">
            <form id="loginForm" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="login">
                
                <i class="fas fa-wallet fa-3x mb-3 text-primary"></i>
                <h1 class="h3 mb-3 fw-normal">Carteira Digital</h1>
                
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="nome@exemplo.com" required>
                    <label for="email">Email</label>
                    <div class="invalid-feedback">
                        Por favor, informe um email válido.
                    </div>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Senha" required>
                    <label for="password">Senha</label>
                    <div class="invalid-feedback">
                        Por favor, informe sua senha.
                    </div>
                </div>

                <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">
                    <i class="fas fa-sign-in-alt me-2"></i>Entrar
                </button>
                
                <p class="mb-0">
                    Não tem uma conta? 
                    <a href="register.php" class="text-primary text-decoration-none">Registre-se</a>
                </p>
            </form>
        </div>

        <!-- Toast para notificações -->
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong class="me-auto">Notificação</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body"></div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('<?php echo BASE_URL; ?>/login.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.redirected) {
                window.location.href = response.url;
                return;
            }
            
            const data = await response.json();
            if (data.success) {
                window.location.href = '<?php echo BASE_URL; ?>/index.php';
            } else {
                showAlert(data.error, 'danger');
            }
        } catch (error) {
            showAlert('Erro ao fazer login. Tente novamente.', 'danger');
        }
    });

    function showAlert(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastBody = toast.querySelector('.toast-body');
        
        toast.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info');
        toast.classList.add(`bg-${type}`);
        toastBody.textContent = message;
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }
    </script>
</body>
</html>
