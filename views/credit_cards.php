<?php
require_once 'controllers/CreditCardController.php';
$controller = new CreditCardController();
$cards = $controller->listCards();
?>

<div class="container mt-4">
    <h2>Gerenciamento de Cartões de Crédito</h2>

    <!-- Botão para adicionar novo cartão -->
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addCardModal">
        Adicionar Novo Cartão
    </button>

    <!-- Lista de cartões -->
    <div class="row">
        <?php while ($card = $cards->fetch_assoc()): ?>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($card['card_name']); ?></h5>
                        <div class="card-text">
                            <p>Limite Total: R$ <?php echo number_format($card['card_limit'], 2, ',', '.'); ?></p>
                            <p>Limite Disponível: R$ <?php echo number_format($card['available_limit'], 2, ',', '.'); ?></p>
                            <p>Vencimento: dia <?php echo $card['due_day']; ?></p>
                            <p>Fechamento: dia <?php echo $card['closing_day']; ?></p>
                        </div>
                        <button type="button" class="btn btn-success" 
                                onclick="showTransactions(<?php echo $card['id']; ?>)"
                                data-bs-toggle="modal" 
                                data-bs-target="#transactionsModal">
                            Ver Transações
                        </button>
                        <button type="button" class="btn btn-primary"
                                onclick="setCardId(<?php echo $card['id']; ?>)"
                                data-bs-toggle="modal" 
                                data-bs-target="#addTransactionModal">
                            Nova Compra
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Modal Adicionar Cartão -->
<div class="modal fade" id="addCardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Novo Cartão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php?action=add_card" method="POST">
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
                <h5 class="modal-title">Nova Compra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php?action=add_transaction" method="POST">
                <input type="hidden" name="card_id" id="transaction_card_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor</label>
                        <input type="number" step="0.01" class="form-control" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data da Compra</label>
                        <input type="date" class="form-control" name="transaction_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" class="form-control" name="due_date" required>
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

<script>
function setCardId(cardId) {
    document.getElementById('transaction_card_id').value = cardId;
}

function showTransactions(cardId) {
    fetch(`index.php?action=get_transactions&card_id=${cardId}`)
        .then(response => response.json())
        .then(data => {
            const transactionsList = document.getElementById('transactions-list');
            let html = `<table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Vencimento</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            data.forEach(transaction => {
                html += `<tr>
                    <td>${new Date(transaction.transaction_date).toLocaleDateString()}</td>
                    <td>${transaction.description}</td>
                    <td>R$ ${parseFloat(transaction.amount).toFixed(2)}</td>
                    <td>${new Date(transaction.due_date).toLocaleDateString()}</td>
                    <td>${transaction.status === 'pending' ? 'Pendente' : 'Pago'}</td>
                    <td>
                        ${transaction.status === 'pending' ? 
                            `<form action="index.php?action=pay_transaction" method="POST" style="display: inline;">
                                <input type="hidden" name="transaction_id" value="${transaction.id}">
                                <button type="submit" class="btn btn-success btn-sm">Pagar</button>
                            </form>` : 
                            ''}
                    </td>
                </tr>`;
            });
            
            html += '</tbody></table>';
            transactionsList.innerHTML = html;
        });
}
</script>
