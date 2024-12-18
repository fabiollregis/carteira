<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/CreditCardController.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Inicializar o controller
$dashboardController = new DashboardController();
$month = date('m');
$year = date('Y');
$user_id = $_SESSION['user_id'];

// Buscar dados iniciais
$monthlyStats = $dashboardController->getMonthlyStats($month, $year, $user_id);
$stats = $monthlyStats['data'] ?? ['income' => 0, 'expense' => 0, 'balance' => 0];

// Adicionar novas rotas para cartões de crédito
if (isset($_GET['action'])) {
    $controller = new CreditCardController();
    
    switch ($_GET['action']) {
        case 'add_card':
            $controller->addCard();
            break;
            
        case 'add_transaction':
            $controller->addTransaction();
            break;
            
        case 'pay_transaction':
            $controller->payTransaction();
            break;
            
        case 'get_transactions':
            header('Content-Type: application/json');
            $transactions = $controller->listTransactions($_GET['card_id']);
            echo json_encode(iterator_to_array($transactions));
            exit;
            break;
    }
}
?>

<div class="container-fluid py-4" id="dashboard">
    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-filter me-2"></i>Filtros
                        </h5>
                        <div class="d-flex gap-3">
                            <select class="form-select" id="monthFilter">
                                <?php
                                $months = [
                                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',
                                    4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
                                    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro',
                                    10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                ];
                                foreach ($months as $num => $name) {
                                    $selected = $num == date('n') ? 'selected' : '';
                                    echo "<option value=\"$num\" $selected>$name</option>";
                                }
                                ?>
                            </select>
                            <select class="form-select" id="yearFilter">
                                <?php
                                $currentYear = date('Y');
                                for ($year = 2024; $year <= 2050; $year++) {
                                    $selected = $year == $currentYear ? 'selected' : '';
                                    echo "<option value=\"$year\" $selected>$year</option>";
                                }
                                ?>
                            </select>
                            <button class="btn btn-primary" onclick="window.location.reload()">
                                <i class="fas fa-sync-alt me-2"></i>Atualizar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row mb-4">
        <!-- Receitas -->
        <div class="col-md-4">
            <div class="dashboard-card bg-success bg-opacity-10">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-success mb-2">Receitas</h6>
                        <h3 class="text-success mb-0" id="total-income">
                            R$ <?php echo number_format($stats['income'], 2, ',', '.'); ?>
                        </h3>
                    </div>
                    <div class="icon text-success">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Despesas -->
        <div class="col-md-4">
            <div class="dashboard-card bg-danger bg-opacity-10">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-danger mb-2">Despesas</h6>
                        <h3 class="text-danger mb-0" id="total-expense">
                            R$ <?php echo number_format($stats['expense'], 2, ',', '.'); ?>
                        </h3>
                    </div>
                    <div class="icon text-danger">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Saldo -->
        <div class="col-md-4">
            <div class="dashboard-card <?php echo $stats['balance'] >= 0 ? 'bg-primary' : 'bg-warning'; ?> bg-opacity-10" id="balance-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="<?php echo $stats['balance'] >= 0 ? 'text-primary' : 'text-warning'; ?> mb-2">Saldo</h6>
                        <h3 class="<?php echo $stats['balance'] >= 0 ? 'text-primary' : 'text-warning'; ?> mb-0" id="total-balance">
                            R$ <?php echo number_format($stats['balance'], 2, ',', '.'); ?>
                        </h3>
                    </div>
                    <div class="icon <?php echo $stats['balance'] >= 0 ? 'text-primary' : 'text-warning'; ?>">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <!-- Gráfico de Categorias -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-chart-pie me-2"></i>Despesas por Categoria
                    </h5>
                    <canvas id="categoryChart" style="height: 250px !important;"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de Tendência -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-chart-line me-2"></i>Tendência Mensal
                    </h5>
                    <canvas id="trendChart" style="height: 250px !important;"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de Métodos de Pagamento -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-credit-card me-2"></i>Métodos de Pagamento
                    </h5>
                    <canvas id="paymentChart" style="height: 250px !important;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast de Notificação -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/dashboard.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
