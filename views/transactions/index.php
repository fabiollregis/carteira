<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/TransactionController.php';
require_once __DIR__ . '/../../controllers/CategoryController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /carteira/login.php');
    exit;
}

$transactionController = new TransactionController();
$categoryController = new CategoryController();

$transactions = $transactionController->getTransactions([], $_SESSION['user_id']);
$categories = $categoryController->getAllCategories($_SESSION['user_id']);
$paymentMethods = $transactionController->getPaymentMethods();
?>

<div class="container-fluid py-4 transactions-page">
    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-filter me-2"></i>Filtros
                    </h5>
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Mês</label>
                            <select class="form-select" name="month">
                                <option value="01" <?php echo date('m') == '01' ? 'selected' : ''; ?>>Janeiro</option>
                                <option value="02" <?php echo date('m') == '02' ? 'selected' : ''; ?>>Fevereiro</option>
                                <option value="03" <?php echo date('m') == '03' ? 'selected' : ''; ?>>Março</option>
                                <option value="04" <?php echo date('m') == '04' ? 'selected' : ''; ?>>Abril</option>
                                <option value="05" <?php echo date('m') == '05' ? 'selected' : ''; ?>>Maio</option>
                                <option value="06" <?php echo date('m') == '06' ? 'selected' : ''; ?>>Junho</option>
                                <option value="07" <?php echo date('m') == '07' ? 'selected' : ''; ?>>Julho</option>
                                <option value="08" <?php echo date('m') == '08' ? 'selected' : ''; ?>>Agosto</option>
                                <option value="09" <?php echo date('m') == '09' ? 'selected' : ''; ?>>Setembro</option>
                                <option value="10" <?php echo date('m') == '10' ? 'selected' : ''; ?>>Outubro</option>
                                <option value="11" <?php echo date('m') == '11' ? 'selected' : ''; ?>>Novembro</option>
                                <option value="12" <?php echo date('m') == '12' ? 'selected' : ''; ?>>Dezembro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Ano</label>
                            <select class="form-select" name="year">
                                <?php
                                $currentYear = date('Y');
                                for ($year = 2024; $year <= 2050; $year++) {
                                    $selected = $year == $currentYear ? 'selected' : '';
                                    echo "<option value=\"{$year}\" {$selected}>{$year}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" id="typeFilter" name="type">
                                <option value="">Todos</option>
                                <option value="receita">Receitas</option>
                                <option value="despesa">Despesas</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Categoria</label>
                            <select class="form-select" id="categoryFilter" name="category_id">
                                <option value="">Todas</option>
                                <?php if ($categories['success']): ?>
                                    <?php foreach ($categories['categories'] as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" data-type="<?php echo $category['type']; ?>">
                                            <?php echo $category['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Transações -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Transações
                        </h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">
                            <i class="fas fa-plus me-2"></i>Nova Transação
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                    <th>Método</th>
                                    <th>Tipo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="transactionsTable">
                                <?php if ($transactions['success'] && !empty($transactions['transactions'])): ?>
                                    <?php foreach ($transactions['transactions'] as $transaction): ?>
                                        <tr data-transaction='<?php echo json_encode($transaction); ?>'>
                                            <td><?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['description']); ?>
                                                <?php if ($transaction['installments']): ?>
                                                    <span class="badge bg-info">
                                                        <?php 
                                                            // Extrair o número da parcela atual da descrição
                                                            if (preg_match('/\((\d+)\/(\d+)\)/', $transaction['description'], $matches)) {
                                                                echo $matches[1] . '/' . $matches[2];
                                                            }
                                                        ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($transaction['is_recurring']): ?>
                                                    <span class="badge bg-warning">
                                                        <?php echo $transaction['recurring_type'] === 'mensal' ? 'Mensal' : 'Anual'; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
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
                                            <td><?php echo $paymentMethods[$transaction['payment_method']]; ?></td>
                                            <td>
                                                <span class="badge <?php echo $transaction['type'] === 'receita' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo ucfirst($transaction['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-transaction" data-id="<?php echo $transaction['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-transaction" data-id="<?php echo $transaction['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Nenhuma transação encontrada</td>
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

<!-- Modal de Transação -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionModalLabel">Nova Transação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="transactionForm">
                    <input type="hidden" id="transactionId" name="id">
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <input type="text" class="form-control" id="description" name="description" required>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Valor</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="type" id="typeReceita" value="receita" required>
                            <label class="btn btn-outline-success" for="typeReceita">Receita</label>
                            
                            <input type="radio" class="btn-check" name="type" id="typeDespesa" value="despesa">
                            <label class="btn btn-outline-danger" for="typeDespesa">Despesa</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Categoria</label>
                        <select class="form-select" id="category" name="category_id" required>
                            <option value="">Selecione uma categoria</option>
                            <?php if ($categories['success']): ?>
                                <?php foreach ($categories['categories'] as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" data-type="<?php echo $category['type']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="transactionDate" class="form-label">Data</label>
                        <input type="date" class="form-control" id="transactionDate" name="transaction_date" required>
                    </div>

                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label">Método de Pagamento</label>
                        <select class="form-select" id="paymentMethod" name="payment_method" required>
                            <option value="">Selecione um método</option>
                            <?php foreach ($paymentMethods as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="installmentsGroup" class="mb-3 d-none">
                        <label for="installments" class="form-label">Número de Parcelas</label>
                        <input type="number" class="form-control" id="installments" name="installments" min="1" value="1">
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="isRecurring" name="is_recurring">
                            <label class="form-check-label" for="isRecurring">Transação Recorrente</label>
                        </div>
                    </div>

                    <div id="recurringGroup" class="mb-3 d-none">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="recurringType" class="form-label">Tipo de Recorrência</label>
                                <select class="form-select" id="recurringType" name="recurring_type">
                                    <option value="mensal">Mensal</option>
                                    <option value="anual">Anual</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="recurringDay" class="form-label">Dia de Repetição</label>
                                <input type="number" class="form-control" id="recurringDay" name="recurring_day" min="1" max="31">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Anotações</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" form="transactionForm">Salvar</button>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
