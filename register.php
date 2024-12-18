<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema Financeiro</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/carteira/assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        
        .form-signup {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }
        
        .form-signup .card {
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <main class="form-signup text-center">
        <div class="card">
            <form id="registerForm" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="register">
                
                <i class="fas fa-wallet fa-3x mb-3 text-primary"></i>
                <h1 class="h3 mb-3 fw-normal">Criar Conta</h1>
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="name" name="name" placeholder="Seu nome" required>
                    <label for="name">Nome completo</label>
                    <div class="invalid-feedback">
                        Por favor, informe seu nome.
                    </div>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="nome@exemplo.com" required>
                    <label for="email">Email</label>
                    <div class="invalid-feedback">
                        Por favor, informe um email válido.
                    </div>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Senha" required minlength="6">
                    <label for="password">Senha</label>
                    <div class="invalid-feedback">
                        A senha deve ter no mínimo 6 caracteres.
                    </div>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Confirme a senha" required>
                    <label for="confirm_password">Confirme a senha</label>
                    <div class="invalid-feedback">
                        As senhas não conferem.
                    </div>
                </div>

                <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">
                    <i class="fas fa-user-plus me-2"></i>Criar conta
                </button>
                
                <p class="mb-0">
                    Já tem uma conta? 
                    <a href="login.php" class="text-primary text-decoration-none">Faça login</a>
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
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validar senhas iguais
        const password = document.getElementById('password');
        const confirm_password = document.getElementById('confirm_password');
        
        if (password.value !== confirm_password.value) {
            confirm_password.setCustomValidity('As senhas não conferem');
        } else {
            confirm_password.setCustomValidity('');
        }
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        try {
            const formData = new FormData(this);
            const response = await fetch('controllers/AuthController.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = 'index.php';
            } else {
                showAlert(data.error, 'danger');
            }
        } catch (error) {
            showAlert('Erro ao criar conta. Tente novamente.', 'danger');
        }
    });

    // Validar senhas em tempo real
    document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('password');
        if (this.value !== password.value) {
            this.setCustomValidity('As senhas não conferem');
        } else {
            this.setCustomValidity('');
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
