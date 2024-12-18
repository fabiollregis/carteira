<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/TransactionController.php';

if (!isset($_SESSION['user_id'])) {
    error_log('Dashboard: Usuário não autenticado');
    header('Location: /carteira/login.php');
    exit;
}

error_log('Dashboard: User ID = ' . $_SESSION['user_id']);

$controller = new TransactionController();

// Obter o mês e ano atual
$currentMonth = date('m');
$currentYear = date('Y');

// Buscar resumo financeiro do mês atual
$summary = $controller->getFinancialSummary($currentMonth, $currentYear);
error_log('Dashboard: Summary = ' . print_r($summary, true));

$recentTransactions = $controller->getRecentTransactions(5);
error_log('Dashboard: Recent Transactions = ' . print_r($recentTransactions, true));

$futureTransactions = $controller->getFutureTransactions();
error_log('Dashboard: Future Transactions = ' . print_r($futureTransactions, true));
?>

<div class="container-fluid py-4">
    <!-- Cards de Resumo -->
    <div class="row mb-4">
        <!-- Receitas -->
        <div class="col-md-4">
            <div class="card dashboard-card bg-success bg-opacity-10 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-subtitle text-success mb-0">Receitas</h6>
                        <i class="fas fa-arrow-up text-success icon"></i>
                    </div>
                    <h3 class="card-title text-success mb-0">
                        R$ <?php echo number_format($summary['total_income'], 2, ',', '.'); ?>
                    </h3>
                    <small class="text-muted">Este mês</small>
                </div>
            </div>
        </div>

        <!-- Despesas -->
        <div class="col-md-4">
            <div class="card dashboard-card bg-danger bg-opacity-10 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-subtitle text-danger mb-0">Despesas</h6>
                        <i class="fas fa-arrow-down text-danger icon"></i>
                    </div>
                    <h3 class="card-title text-danger mb-0">
                        R$ <?php echo number_format($summary['total_expenses'], 2, ',', '.'); ?>
                    </h3>
                    <small class="text-muted">Este mês</small>
                </div>
            </div>
        </div>

        <!-- Saldo -->
        <div class="col-md-4">
            <div class="card dashboard-card <?php echo $summary['balance'] >= 0 ? 'bg-info bg-opacity-10' : 'bg-warning bg-opacity-10'; ?> h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-subtitle <?php echo $summary['balance'] >= 0 ? 'text-info' : 'text-warning'; ?> mb-0">Saldo</h6>
                        <i class="fas fa-wallet <?php echo $summary['balance'] >= 0 ? 'text-info' : 'text-warning'; ?> icon"></i>
                    </div>
                    <h3 class="card-title <?php echo $summary['balance'] >= 0 ? 'text-info' : 'text-warning'; ?> mb-0">
                        R$ <?php echo number_format($summary['balance'], 2, ',', '.'); ?>
                    </h3>
                    <small class="text-muted">Este mês</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Últimas Transações -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Últimas Transações</h5>
                        <a href="/carteira/views/transactions" class="btn btn-sm btn-outline-primary">Ver todas</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentTransactions['success'] && !empty($recentTransactions['transactions'])): ?>
                                    <?php foreach ($recentTransactions['transactions'] as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                            <td>
                                                <span class="category-badge">
                                                    <i class="<?php echo htmlspecialchars($transaction['category_icon']); ?> category-icon" 
                                                       style="color: <?php echo htmlspecialchars($transaction['category_color']); ?>"></i>
                                                    <span><?php echo htmlspecialchars($transaction['category_name']); ?></span>
                                                </span>
                                            </td>
                                            <td class="<?php echo $transaction['type'] === 'receita' ? 'text-success' : 'text-danger'; ?>">
                                                R$ <?php echo number_format($transaction['amount'], 2, ',', '.'); ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($transaction['total_installments']) && $transaction['total_installments'] > 1): ?>
                                                    <span class="badge bg-info">
                                                        <?php echo $transaction['installment_number'] . '/' . $transaction['total_installments']; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($transaction['is_recurring']) && $transaction['is_recurring'] == 1): ?>
                                                    <span class="badge bg-warning">Recorrente</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhuma transação encontrada</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transações Futuras -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Transações Futuras</h5>
                        <span class="badge bg-primary">Próximos Lançamentos</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($futureTransactions['success'] && !empty($futureTransactions['transactions'])): ?>
                                    <?php foreach ($futureTransactions['transactions'] as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                            <td>
                                                <span class="category-badge">
                                                    <i class="<?php echo htmlspecialchars($transaction['category_icon']); ?> category-icon" 
                                                       style="color: <?php echo htmlspecialchars($transaction['category_color']); ?>"></i>
                                                    <span><?php echo htmlspecialchars($transaction['category_name']); ?></span>
                                                </span>
                                            </td>
                                            <td class="<?php echo $transaction['type'] === 'receita' ? 'text-success' : 'text-danger'; ?>">
                                                R$ <?php echo number_format($transaction['amount'], 2, ',', '.'); ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($transaction['total_installments']) && $transaction['total_installments'] > 1): ?>
                                                    <span class="badge bg-info">
                                                        <?php echo $transaction['installment_number'] . '/' . $transaction['total_installments']; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($transaction['is_recurring']) && $transaction['is_recurring'] == 1): ?>
                                                    <span class="badge bg-warning">Recorrente</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhuma transação futura encontrada</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
