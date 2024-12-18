<?php
require_once '../../config/config.php';
require_once '../../controllers/CreditCardController.php';
require_once '../../includes/header.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$controller = new CreditCardController();
$cards = $controller->listCards();
?>

<div class="container mt-4">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-credit-card me-2"></i>Cartões de Crédito</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCardModal">
            <i class="fas fa-plus me-2"></i>Novo Cartão
        </button>
    </div>

    <!-- Lista de cartões -->
    <div class="row">
        <?php if ($cards && $cards->num_rows > 0): ?>
            <?php while ($card = $cards->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 credit-card-container">
                        <div class="card-header d-flex justify-content-between align-items-center bg-dark">
                            <h5 class="card-title mb-0 text-white"><?php echo htmlspecialchars($card['card_name']); ?></h5>
                            <div class="dropdown">
                                <button class="btn btn-link text-white" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="showTransactions(<?php echo $card['id']; ?>, '<?php echo htmlspecialchars($card['card_name']); ?>')" data-bs-toggle="modal" data-bs-target="#viewTransactionsModal">
                                            <i class="fas fa-list me-2"></i>Ver Transações
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="editCard(<?php echo $card['id']; ?>)" data-bs-toggle="modal" data-bs-target="#editCardModal">
                                            <i class="fas fa-edit me-2"></i>Editar Cartão
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="showPayInvoiceModal(<?php echo $card['id']; ?>, '<?php echo htmlspecialchars($card['card_name']); ?>')" data-bs-toggle="modal">
                                            <i class="fas fa-money-bill me-2"></i>Pagar Fatura
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="../../controllers/process_card.php?action=delete&id=<?php echo $card['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este cartão? Esta ação não pode ser desfeita e todas as transações associadas serão excluídas.');">
                                            <i class="fas fa-trash me-2"></i>Excluir Cartão
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Barra de Progresso -->
                            <div class="limit-info mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <small>Limite Utilizado</small>
                                    <?php 
                                    $usedLimit = floatval($card['used_limit'] ?? 0);
                                    $totalLimit = floatval($card['card_limit']);
                                    $availableLimit = floatval($card['available_limit']);
                                    $usedPercentage = $totalLimit > 0 ? ($usedLimit / $totalLimit) * 100 : 0;
                                    
                                    // Define as classes de cor baseado no percentual usado
                                    $progressClass = 'bg-success';
                                    $textClass = 'text-success';
                                    if ($usedPercentage > 80) {
                                        $progressClass = 'bg-danger';
                                        $textClass = 'text-danger';
                                    } elseif ($usedPercentage > 60) {
                                        $progressClass = 'bg-warning';
                                        $textClass = 'text-warning';
                                    }
                                    ?>
                                    <small class="<?php echo $textClass; ?>"><?php echo number_format($usedPercentage, 1); ?>%</small>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar <?php echo $progressClass; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $usedPercentage; ?>%"
                                         aria-valuenow="<?php echo $usedPercentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>

                            <!-- Informações do Cartão -->
                            <div class="card-info">
                                <div class="row mb-3">
                                    <div class="col">
                                        <small class="text-muted d-block">Limite Total</small>
                                        <strong>R$ <?php echo number_format($totalLimit, 2, ',', '.'); ?></strong>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted d-block">Usado</small>
                                        <strong class="<?php echo $textClass; ?>">
                                            R$ <?php echo number_format($usedLimit, 2, ',', '.'); ?>
                                        </strong>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted d-block">Disponível</small>
                                        <strong class="text-success">
                                            R$ <?php echo number_format($availableLimit, 2, ',', '.'); ?>
                                        </strong>
                                    </div>
                                </div>

                                <div class="dates-info mt-3">
                                    <div class="row">
                                        <div class="col">
                                            <small class="text-muted d-block">Fechamento</small>
                                            <strong>Dia <?php echo str_pad($card['closing_day'], 2, '0', STR_PAD_LEFT); ?></strong>
                                        </div>
                                        <div class="col">
                                            <small class="text-muted d-block">Vencimento</small>
                                            <strong>Dia <?php echo str_pad($card['due_day'], 2, '0', STR_PAD_LEFT); ?></strong>
                                        </div>
                                        <?php
                                        // Calcular próxima data de fechamento e vencimento
                                        $today = new DateTime();
                                        $closingDay = $card['closing_day'];
                                        $dueDay = $card['due_day'];
                                        
                                        $nextClosing = new DateTime();
                                        $nextClosing->setDate($today->format('Y'), $today->format('m'), $closingDay);
                                        
                                        if ($today->format('d') > $closingDay) {
                                            $nextClosing->modify('+1 month');
                                        }
                                        
                                        $nextDue = clone $nextClosing;
                                        if ($dueDay < $closingDay) {
                                            $nextDue->modify('+1 month');
                                        }
                                        $nextDue->setDate($nextDue->format('Y'), $nextDue->format('m'), $dueDay);
                                        ?>
                                        <div class="col-12 mt-2">
                                            <small class="text-muted d-block">Próximo Fechamento</small>
                                            <strong><?php echo $nextClosing->format('d/m/Y'); ?></strong>
                                            
                                            <small class="text-muted d-block mt-2">Próximo Vencimento</small>
                                            <strong><?php echo $nextDue->format('d/m/Y'); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-sm btn-success w-100" 
                                        onclick="setCardId(<?php echo $card['id']; ?>, '<?php echo htmlspecialchars($card['card_name']); ?>')" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#addTransactionModal">
                                    <i class="fas fa-plus me-2"></i>Nova Transação
                                </button>
                                <div class="btn-group w-100">
                                    <button type="button" class="btn btn-sm btn-outline-primary small py-1" 
                                            onclick="showTransactions(<?php echo $card['id']; ?>, '<?php echo htmlspecialchars($card['card_name']); ?>')" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewTransactionsModal">
                                        <i class="fas fa-list me-1"></i>Ver
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success small py-1" 
                                            onclick="showPayInvoiceModal(<?php echo $card['id']; ?>, '<?php echo htmlspecialchars($card['card_name']); ?>')" 
                                            data-bs-toggle="modal">
                                        <i class="fas fa-money-bill me-1"></i>Pagar
                                    </button>
                                    <a class="btn btn-sm btn-outline-danger small py-1" 
                                       href="../../controllers/process_card.php?action=delete&id=<?php echo $card['id']; ?>" 
                                       onclick="return confirm('Tem certeza que deseja excluir este cartão? Esta ação não pode ser desfeita e todas as transações associadas serão excluídas.');">
                                        <i class="fas fa-trash me-1"></i>Excluir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Você ainda não possui cartões cadastrados. Clique em "Novo Cartão" para começar!
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.credit-card-container {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.credit-card-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.card-header {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    color: white;
    border-bottom: none;
}

.card-title {
    font-size: 1.2rem;
    font-weight: 600;
}

.dropdown-toggle::after {
    display: none;
}

.progress {
    background-color: rgba(0,0,0,0.1);
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.3s ease;
}

.card-info {
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.dates-info {
    padding-top: 10px;
    border-top: 1px solid #dee2e6;
}

.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}

.next-due {
    padding-top: 10px;
    border-top: 1px solid #dee2e6;
}

.dropdown-item {
    padding: 0.5rem 1rem;
}

.dropdown-item i {
    width: 20px;
}

.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.text-danger { color: #dc3545 !important; }

.bg-success { background-color: #28a745 !important; }
.bg-warning { background-color: #ffc107 !important; }
.bg-danger { background-color: #dc3545 !important; }
</style>

<!-- Modal Adicionar Cartão -->
<div class="modal fade" id="addCardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Novo Cartão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo BASE_URL; ?>/controllers/process_card.php?action=add_card" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Cartão</label>
                        <input type="text" class="form-control" name="card_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Limite</label>
                        <input type="number" step="0.01" class="form-control" name="card_limit" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dia do Fechamento</label>
                        <input type="number" min="1" max="31" class="form-control" name="closing_day" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dia do Vencimento</label>
                        <input type="number" min="1" max="31" class="form-control" name="due_day" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Adicionar Transação -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Transação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo BASE_URL; ?>/controllers/process_card.php?action=add_transaction" method="POST">
                    <input type="hidden" name="card_id" id="transaction_card_id">
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição da Compra</label>
                        <input type="text" class="form-control" id="description" name="description" required 
                               placeholder="Ex: Mercado, Farmácia, etc">
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Valor Total (R$)</label>
                        <input type="text" class="form-control" id="amount" name="amount" required
                               placeholder="0,00">
                        <small id="limit_warning" class="form-text text-danger" style="display: none;">
                            Atenção: O valor excede o limite disponível do cartão
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="transaction_date" class="form-label">Data da Compra</label>
                        <input type="date" class="form-control" id="transaction_date" name="transaction_date" required
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="installments" class="form-label">Número de Parcelas</label>
                        <input type="number" class="form-control" id="installments" name="installments" min="1" value="1" required>
                        <small class="form-text text-muted">Deixe 1 para pagamento à vista</small>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="submit_transaction">Adicionar Transação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Transações -->
<div class="modal fade" id="viewTransactionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transações do Cartão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Vencimento</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="transactions-list">
                            <!-- As transações serão carregadas aqui via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Transações -->
<div class="modal fade" id="transactionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transações do Cartão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="transactions-list"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pagamento -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pagar Fatura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="payment-list"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pagar Fatura -->
<div class="modal fade" id="payInvoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pagar Fatura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive mb-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Vencimento</th>
                            </tr>
                        </thead>
                        <tbody id="invoice-transactions-list">
                            <!-- As transações serão carregadas aqui -->
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="2"><strong>Total da Fatura</strong></td>
                                <td colspan="2"><strong id="invoice-total">R$ 0,00</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <form id="payInvoiceForm" action="<?php echo BASE_URL; ?>/controllers/process_card.php" method="POST">
                    <input type="hidden" name="action" value="pay_invoice">
                    <input type="hidden" name="card_id" id="pay_invoice_card_id">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Ao pagar a fatura, todas as transações pendentes serão marcadas como pagas 
                        e o limite do cartão será restaurado.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Confirmar Pagamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Excluir Cartão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Excluir Cartão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este cartão?</p>
                <p class="text-danger">Esta ação não pode ser desfeita e todas as transações associadas serão excluídas.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="GET" action="controllers/process_card.php" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteCardId">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Excluir Transação -->
<div class="modal fade" id="deleteTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir esta transação?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="deleteTransactionId">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteTransaction()">Excluir</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Cartão -->
<div class="modal fade" id="editCardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Cartão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo BASE_URL; ?>/controllers/process_card.php?action=edit_card" method="POST">
                    <input type="hidden" name="card_id" id="edit_card_id">
                    
                    <div class="mb-3">
                        <label for="edit_card_name" class="form-label">Nome do Cartão</label>
                        <input type="text" class="form-control" id="edit_card_name" name="card_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_card_limit" class="form-label">Limite Total (R$)</label>
                        <input type="number" class="form-control" id="edit_card_limit" name="card_limit" step="0.01" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_closing_day" class="form-label">Dia do Fechamento</label>
                                <input type="number" class="form-control" id="edit_closing_day" name="closing_day" min="1" max="31" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_due_day" class="form-label">Dia do Vencimento</label>
                                <input type="number" class="form-control" id="edit_due_day" name="due_day" min="1" max="31" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let cardIdToDelete = null;

function setCardId(cardId, cardName) {
    document.getElementById('transaction_card_id').value = cardId;
    document.querySelector('#addTransactionModal .modal-title').textContent = 
        `Nova Transação - ${cardName}`;
}

// Outras funções...

document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount');
    const submitButton = document.getElementById('submit_transaction');

    // Formatar o input de valor como moeda brasileira
    amountInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = (parseFloat(value) / 100).toFixed(2);
        e.target.value = value.replace('.', ',');
    });

    // Validar o valor antes de enviar
    document.querySelector('#addTransactionModal form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Converter o valor para o formato correto
        let amount = amountInput.value.replace('.', '').replace(',', '.');
        amount = parseFloat(amount);
        
        if (isNaN(amount) || amount <= 0) {
            alert('Por favor, insira um valor válido');
            return;
        }
        
        this.submit();
    });
});

function showTransactions(cardId, cardName) {
    // Atualizar título do modal
    document.querySelector('#viewTransactionsModal .modal-title').textContent = 
        `Transações - ${cardName}`;
    
    // Mostrar loading
    document.getElementById('transactions-list').innerHTML = 
        '<tr><td colspan="6" class="text-center">Carregando transações...</td></tr>';
    
    // Abrir modal
    new bootstrap.Modal(document.getElementById('viewTransactionsModal')).show();
    
    // Buscar transações
    fetch(`<?php echo BASE_URL; ?>/controllers/process_card.php?action=get_transactions&card_id=${cardId}`)
        .then(response => response.json())
        .then(data => {
            const transactionsList = document.getElementById('transactions-list');
            
            if (data.length === 0) {
                transactionsList.innerHTML = 
                    '<tr><td colspan="6" class="text-center">Nenhuma transação encontrada</td></tr>';
                return;
            }
            
            let html = '';
            data.forEach(transaction => {
                const transactionDate = new Date(transaction.transaction_date).toLocaleDateString('pt-BR');
                const dueDate = new Date(transaction.due_date).toLocaleDateString('pt-BR');
                const amount = parseFloat(transaction.amount).toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                });
                
                html += `
                    <tr>
                        <td>${transactionDate}</td>
                        <td>${transaction.description}</td>
                        <td>${amount}</td>
                        <td>${dueDate}</td>
                        <td>
                            ${transaction.status === 'pending' 
                                ? '<span class="badge bg-warning">Pendente</span>' 
                                : '<span class="badge bg-success">Pago</span>'}
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm" 
                                    onclick="setTransactionToDelete(${transaction.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>`;
            });
            
            transactionsList.innerHTML = html;
        })
        .catch(error => {
            console.error('Erro ao carregar transações:', error);
            document.getElementById('transactions-list').innerHTML = 
                '<tr><td colspan="6" class="text-center text-danger">Erro ao carregar transações</td></tr>';
        });
}

function confirmDeleteTransaction() {
    const transactionId = document.getElementById('deleteTransactionId').value;
    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteTransactionModal'));
    
    const formData = new FormData();
    formData.append('action', 'delete_transaction');
    formData.append('transaction_id', transactionId);

    fetch('<?php echo BASE_URL; ?>/controllers/process_card.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            modal.hide();
            window.location.reload();
        } else {
            alert('Erro ao excluir transação: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao excluir transação. Por favor, tente novamente.');
    });
}

function setTransactionToDelete(transactionId) {
    document.getElementById('deleteTransactionId').value = transactionId;
    new bootstrap.Modal(document.getElementById('deleteTransactionModal')).show();
}

function showPayInvoiceModal(cardId, cardName) {
    // Atualizar título e ID do cartão
    document.querySelector('#payInvoiceModal .modal-title').textContent = 
        `Pagar Fatura - ${cardName}`;
    document.getElementById('pay_invoice_card_id').value = cardId;
    
    // Mostrar loading
    document.getElementById('invoice-transactions-list').innerHTML = 
        '<tr><td colspan="4" class="text-center">Carregando transações...</td></tr>';
    
    // Abrir modal
    new bootstrap.Modal(document.getElementById('payInvoiceModal')).show();
    
    // Buscar transações
    fetch(`<?php echo BASE_URL; ?>/controllers/process_card.php?action=get_transactions&card_id=${cardId}`)
        .then(response => response.json())
        .then(data => {
            const transactionsList = document.getElementById('invoice-transactions-list');
            const pendingTransactions = data.filter(t => t.status === 'pending');
            
            if (pendingTransactions.length === 0) {
                transactionsList.innerHTML = 
                    '<tr><td colspan="4" class="text-center">Não há transações pendentes</td></tr>';
                document.getElementById('invoice-total').textContent = 'R$ 0,00';
                return;
            }
            
            let total = 0;
            let html = '';
            
            pendingTransactions.forEach(transaction => {
                const transactionDate = new Date(transaction.transaction_date).toLocaleDateString('pt-BR');
                const dueDate = new Date(transaction.due_date).toLocaleDateString('pt-BR');
                const amount = parseFloat(transaction.amount);
                total += amount;
                
                html += `
                    <tr>
                        <td>${transactionDate}</td>
                        <td>${transaction.description}</td>
                        <td>${amount.toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        })}</td>
                        <td>${dueDate}</td>
                    </tr>`;
            });
            
            transactionsList.innerHTML = html;
            document.getElementById('invoice-total').textContent = 
                total.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                });
        })
        .catch(error => {
            console.error('Erro ao carregar transações:', error);
            document.getElementById('invoice-transactions-list').innerHTML = 
                '<tr><td colspan="4" class="text-center text-danger">Erro ao carregar transações</td></tr>';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            // Remove qualquer backdrop que possa ter ficado
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            
            // Remove a classe modal-open do body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            // Recarrega a página
            window.location.reload();
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
