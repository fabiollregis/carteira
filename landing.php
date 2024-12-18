<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Carteira - Controle Financeiro Inteligente</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .hero {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .cta-section {
            background-color: #f8f9fa;
            padding: 80px 0;
        }
        .btn-hero {
            padding: 12px 30px;
            font-size: 1.1rem;
            margin: 10px;
        }
        .feature-card {
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: 100%;
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container text-center">
            <h1 class="display-4 mb-4"><i class="fas fa-wallet me-3"></i>Minha Carteira</h1>
            <p class="lead mb-5">Sua solução completa para controle financeiro pessoal</p>
            <div>
                <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-light btn-lg btn-hero">
                    <i class="fas fa-sign-in-alt me-2"></i>Entrar
                </a>
                <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-outline-light btn-lg btn-hero">
                    <i class="fas fa-user-plus me-2"></i>Criar Conta
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Por que escolher Minha Carteira?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card bg-white">
                        <div class="text-center">
                            <i class="fas fa-chart-line feature-icon"></i>
                            <h3>Dashboard Intuitivo</h3>
                            <p>Visualize seus gastos e ganhos de forma clara e objetiva com gráficos interativos.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card bg-white">
                        <div class="text-center">
                            <i class="fas fa-credit-card feature-icon"></i>
                            <h3>Gestão de Cartões</h3>
                            <p>Controle seus cartões de crédito, limites e faturas em um só lugar.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card bg-white">
                        <div class="text-center">
                            <i class="fas fa-bullseye feature-icon"></i>
                            <h3>Metas Financeiras</h3>
                            <p>Estabeleça e acompanhe suas metas financeiras de forma simples e eficiente.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <h2 class="mb-4">Comece a controlar suas finanças hoje mesmo!</h2>
            <p class="lead mb-4">Junte-se a milhares de usuários que já estão no controle de suas finanças</p>
            <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary btn-lg">
                <i class="fas fa-rocket me-2"></i>Começar Agora
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Minha Carteira - Todos os direitos reservados - Tecno Solution</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
