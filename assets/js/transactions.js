// Manipulação do Modal de Transação
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do formulário
    const transactionForm = document.getElementById('transactionForm');
    const transactionModal = document.getElementById('transactionModal');
    const modal = new bootstrap.Modal(transactionModal);
    const paymentMethod = document.getElementById('paymentMethod');
    const installmentsGroup = document.getElementById('installmentsGroup');
    const isRecurring = document.getElementById('isRecurring');
    const recurringGroup = document.getElementById('recurringGroup');
    const filterForm = document.getElementById('filterForm');

    // Controle de exibição do campo de parcelas
    paymentMethod?.addEventListener('change', function() {
        console.log('Método de pagamento alterado:', this.value);
        if (this.value === 'parcelado') {
            installmentsGroup.classList.remove('d-none');
            document.getElementById('installments').required = true;
        } else {
            installmentsGroup.classList.add('d-none');
            document.getElementById('installments').required = false;
            document.getElementById('installments').value = '1';
        }
    });

    // Controle de exibição dos campos de recorrência
    isRecurring?.addEventListener('change', function() {
        console.log('Recorrência alterada:', this.checked);
        if (this.checked) {
            recurringGroup.classList.remove('d-none');
            document.getElementById('recurringType').required = true;
            document.getElementById('recurringDay').required = true;
        } else {
            recurringGroup.classList.add('d-none');
            document.getElementById('recurringType').required = false;
            document.getElementById('recurringDay').required = false;
            document.getElementById('recurringType').value = 'mensal';
            document.getElementById('recurringDay').value = '';
        }
    });

    // Controle do tipo de transação e categorias
    const typeReceita = document.getElementById('typeReceita');
    const typeDespesa = document.getElementById('typeDespesa');
    const categorySelect = document.getElementById('category');

    function filterCategories(type) {
        Array.from(categorySelect.options).forEach(option => {
            if (option.value === '') return; // Pula a opção "Selecione uma categoria"
            if (option.dataset.type === type) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
        categorySelect.value = ''; // Limpa a seleção atual
    }

    typeReceita?.addEventListener('change', () => filterCategories('receita'));
    typeDespesa?.addEventListener('change', () => filterCategories('despesa'));

    // Envio do formulário
    transactionForm?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const transactionId = this.dataset.transactionId;
        formData.append('action', transactionId ? 'update' : 'create');
        if (transactionId) {
            formData.append('id', transactionId);
        }
        
        try {
            const response = await fetch('/carteira/controllers/TransactionController.php', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text(); // Primeiro pegamos o texto da resposta
            let data;
            try {
                data = JSON.parse(text); // Tentamos fazer o parse do JSON
            } catch (e) {
                console.error('Resposta não é um JSON válido:', text);
                throw new Error('Erro no servidor: ' + text);
            }
            
            if (data.success) {
                // Mostra mensagem de sucesso
                const toast = new bootstrap.Toast(document.querySelector('.toast'));
                document.querySelector('.toast').classList.remove('bg-danger');
                document.querySelector('.toast').classList.add('bg-success');
                document.querySelector('.toast-body').textContent = 'Transação salva com sucesso!';
                toast.show();
                
                // Fecha o modal e reseta o formulário
                const modal = bootstrap.Modal.getInstance(document.getElementById('transactionModal'));
                modal.hide();
                this.reset();
                this.querySelector('input[name="action"]').value = 'create'; // Reseta a ação para create
                delete this.dataset.transactionId; // Remove o ID da transação
                
                // Redireciona para a página de transações após um breve delay
                setTimeout(() => {
                    window.location.href = '/carteira/views/transactions/index.php';
                }, 1000);
                
                // Recarrega as transações e atualiza a página de forma suave
                await loadTransactions();
                if (typeof loadDashboard === 'function') {
                    const date = new Date();
                    await loadDashboard(date.getMonth() + 1, date.getFullYear());
                }
            } else {
                throw new Error(data.error || 'Erro ao salvar transação');
            }
        } catch (error) {
            console.error('Erro ao salvar transação:', error);
            // Mostra mensagem de erro
            const toast = new bootstrap.Toast(document.querySelector('.toast'));
            document.querySelector('.toast').classList.remove('bg-success');
            document.querySelector('.toast').classList.add('bg-danger');
            document.querySelector('.toast-body').textContent = error.message;
            toast.show();
        }
    });

    // Carregamento de transações
    async function loadTransactions(filters = {}) {
        try {
            const formData = new FormData();
            formData.append('action', 'list');
            
            // Adiciona os filtros à formData
            Object.keys(filters).forEach(key => {
                formData.append(key, filters[key]);
            });

            const response = await fetch('/carteira/controllers/TransactionController.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Atualizar saldos se os elementos existirem
                const totalReceitas = document.getElementById('totalReceitas');
                const totalDespesas = document.getElementById('totalDespesas');
                const saldoTotal = document.getElementById('saldoTotal');

                if (totalReceitas) totalReceitas.textContent = formatCurrency(data.total_receitas);
                if (totalDespesas) totalDespesas.textContent = formatCurrency(data.total_despesas);
                if (saldoTotal) saldoTotal.textContent = formatCurrency(data.saldo);
                
                // Atualizar tabela
                updateTransactionsTable(data.transactions);
            } else {
                throw new Error(data.error || 'Erro ao carregar transações');
            }
        } catch (error) {
            console.error('Erro ao carregar transações:', error);
            const toast = new bootstrap.Toast(document.querySelector('.toast'));
            document.querySelector('.toast').classList.remove('bg-success');
            document.querySelector('.toast').classList.add('bg-danger');
            document.querySelector('.toast-body').textContent = error.message;
            toast.show();
        }
    }

    // Função para formatar moeda
    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    // Atualização da tabela de transações
    function updateTransactionsTable(transactions) {
        const tbody = document.getElementById('transactionsTable');
        if (!tbody) return;

        tbody.innerHTML = '';
        
        if (transactions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">Nenhuma transação encontrada</td>
                </tr>
            `;
            return;
        }

        transactions.forEach(transaction => {
            const tr = document.createElement('tr');
            tr.dataset.transaction = JSON.stringify(transaction);
            
            tr.innerHTML = `
                <td>${new Date(transaction.transaction_date).toLocaleDateString('pt-BR')}</td>
                <td>${transaction.description}</td>
                <td>
                    <span class="category-badge" style="background-color: ${transaction.category_color}">
                        <i class="${transaction.category_icon} me-1"></i>
                        ${transaction.category_name}
                    </span>
                </td>
                <td class="${transaction.type === 'receita' ? 'text-success' : 'text-danger'}">
                    R$ ${parseFloat(transaction.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                </td>
                <td>${getPaymentMethodLabel(transaction.payment_method)}${
                    transaction.payment_method === 'parcelado' ? 
                    ` (${transaction.installments}x)` : 
                    ''
                }</td>
                <td>
                    <span class="badge ${transaction.type === 'receita' ? 'bg-success' : 'bg-danger'}">
                        ${transaction.type === 'receita' ? 'Receita' : 'Despesa'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary edit-transaction" data-id="${transaction.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-transaction" data-id="${transaction.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(tr);
        });
    }

    // Função auxiliar para obter o label do método de pagamento
    function getPaymentMethodLabel(method) {
        const methods = {
            'dinheiro': 'Dinheiro',
            'pix': 'PIX',
            'debito': 'Cartão de Débito',
            'credito': 'Cartão de Crédito',
            'parcelado': 'Cartão Parcelado'
        };
        return methods[method] || method;
    }

    // Filtros
    filterForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const filters = {
            month: formData.get('month'),
            year: formData.get('year'),
            type: formData.get('type'),
            category: formData.get('category_id') // Alterado para category_id
        };

        // Remove filtros vazios
        Object.keys(filters).forEach(key => {
            if (!filters[key]) delete filters[key];
        });

        loadTransactions(filters);
    });

    // Edição de transação
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-transaction')) {
            const btn = e.target.closest('.edit-transaction');
            const tr = btn.closest('tr');
            const transaction = JSON.parse(tr.dataset.transaction);
            fillTransactionForm(transaction);
        }
    });

    // Função para preencher o formulário com dados da transação
    function fillTransactionForm(data) {
        const form = document.getElementById('transactionForm');
        form.dataset.transactionId = data.id; // Adiciona o ID da transação ao formulário
        
        // Preenche os campos
        form.querySelector('input[name="description"]').value = data.description;
        form.querySelector('input[name="amount"]').value = data.amount;
        form.querySelector('input[name="transaction_date"]').value = data.transaction_date;
        form.querySelector('select[name="payment_method"]').value = data.payment_method;
        form.querySelector('textarea[name="notes"]').value = data.notes || '';
        form.querySelector('input[name="action"]').value = 'update';

        // Define o tipo (receita/despesa) e dispara o evento change
        if (data.type === 'receita') {
            document.getElementById('typeReceita').checked = true;
            document.getElementById('typeReceita').dispatchEvent(new Event('change'));
        } else {
            document.getElementById('typeDespesa').checked = true;
            document.getElementById('typeDespesa').dispatchEvent(new Event('change'));
        }

        // Após filtrar as categorias, define o valor
        setTimeout(() => {
            form.querySelector('select[name="category_id"]').value = data.category_id;
        }, 100);

        // Define o campo de parcelas
        const installmentsGroup = document.getElementById('installmentsGroup');
        if (data.payment_method === 'parcelado') {
            installmentsGroup.classList.remove('d-none');
            form.querySelector('input[name="installments"]').value = data.installments || 1;
            form.querySelector('input[name="installments"]').required = true;
        } else {
            installmentsGroup.classList.add('d-none');
            form.querySelector('input[name="installments"]').value = '1';
            form.querySelector('input[name="installments"]').required = false;
        }

        // Define o campo de recorrência
        const isRecurringCheckbox = document.getElementById('isRecurring');
        const recurringGroup = document.getElementById('recurringGroup');
        isRecurringCheckbox.checked = data.is_recurring == 1;
        
        if (data.is_recurring == 1) {
            recurringGroup.classList.remove('d-none');
            form.querySelector('select[name="recurring_type"]').value = data.recurring_type || 'mensal';
            form.querySelector('input[name="recurring_day"]').value = data.recurring_day || '';
            form.querySelector('select[name="recurring_type"]').required = true;
            form.querySelector('input[name="recurring_day"]').required = true;
        } else {
            recurringGroup.classList.add('d-none');
            form.querySelector('select[name="recurring_type"]').value = 'mensal';
            form.querySelector('input[name="recurring_day"]').value = '';
            form.querySelector('select[name="recurring_type"]').required = false;
            form.querySelector('input[name="recurring_day"]').required = false;
        }

        // Atualiza o título do modal
        document.querySelector('#transactionModal .modal-title').textContent = 'Editar Transação';
        
        // Mostra o modal
        const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
        modal.show();
    }

    // Exclusão de transação
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.delete-transaction')) {
            if (!confirm('Tem certeza que deseja excluir esta transação?')) return;
            
            const btn = e.target.closest('.delete-transaction');
            const id = btn.dataset.id;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                const response = await fetch('/carteira/controllers/TransactionController.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const toast = new bootstrap.Toast(document.querySelector('.toast'));
                    document.querySelector('.toast').classList.add('bg-success');
                    document.querySelector('.toast-body').textContent = 'Transação excluída com sucesso!';
                    toast.show();
                    loadTransactions();
                } else {
                    throw new Error(data.error || 'Erro ao excluir transação');
                }
            } catch (error) {
                console.error('Erro ao excluir transação:', error);
                const toast = new bootstrap.Toast(document.querySelector('.toast'));
                document.querySelector('.toast').classList.add('bg-danger');
                document.querySelector('.toast-body').textContent = error.message;
                toast.show();
            }
        }
    });

    // Limpa o formulário quando o modal é fechado
    transactionModal?.addEventListener('hidden.bs.modal', function() {
        transactionForm.reset();
        delete transactionForm.dataset.transactionId;
        document.getElementById('transactionId').value = '';
        document.querySelector('[name="action"]').value = 'create';
        document.getElementById('transactionModalLabel').textContent = 'Nova Transação';
        installmentsGroup.classList.add('d-none');
        recurringGroup.classList.add('d-none');
        Array.from(categorySelect.options).forEach(option => {
            option.style.display = '';
        });
    });

    // Carregamento inicial e eventos dos filtros
    loadTransactions();

    // Atualizar ao mudar mês ou ano
    const monthSelect = document.querySelector('select[name="month"]');
    const yearSelect = document.querySelector('select[name="year"]');
    const typeFilter = document.querySelector('select[name="type"]');
    const categoryFilter = document.querySelector('select[name="category_id"]');

    monthSelect?.addEventListener('change', loadTransactions);
    yearSelect?.addEventListener('change', loadTransactions);
    typeFilter?.addEventListener('change', loadTransactions);
    categoryFilter?.addEventListener('change', loadTransactions);
});

// Preencher formulário com dados da transação
function fillTransactionForm(data) {
    const form = document.getElementById('transactionForm');
    form.dataset.transactionId = data.id; // Adiciona o ID da transação ao formulário
    
    // Preenche os campos
    form.querySelector('input[name="description"]').value = data.description;
    form.querySelector('input[name="amount"]').value = data.amount;
    form.querySelector('input[name="transaction_date"]').value = data.transaction_date;
    form.querySelector('select[name="payment_method"]').value = data.payment_method;
    form.querySelector('textarea[name="notes"]').value = data.notes || '';
    form.querySelector('input[name="action"]').value = 'update';

    // Define o tipo (receita/despesa) e dispara o evento change
    if (data.type === 'receita') {
        document.getElementById('typeReceita').checked = true;
        document.getElementById('typeReceita').dispatchEvent(new Event('change'));
    } else {
        document.getElementById('typeDespesa').checked = true;
        document.getElementById('typeDespesa').dispatchEvent(new Event('change'));
    }

    // Após filtrar as categorias, define o valor
    setTimeout(() => {
        form.querySelector('select[name="category_id"]').value = data.category_id;
    }, 100);

    // Atualiza o título do modal
    document.querySelector('#transactionModal .modal-title').textContent = 'Editar Transação';
    
    // Mostra o modal
    const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
    modal.show();
}

// Filtrar categorias por tipo
function filterCategories(type) {
    const categorySelect = document.getElementById('category');
    const options = categorySelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value === '') return;
        const categoryType = option.dataset.type;
        option.style.display = categoryType === type ? '' : 'none';
    });
    
    if (categorySelect.selectedOptions[0].style.display === 'none') {
        categorySelect.value = '';
    }
}

// Atualizar tabela de transações
function updateTransactionsTable(transactions) {
    const tbody = document.getElementById('transactionsTable');
    tbody.innerHTML = '';
    
    if (transactions.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center">Nenhuma transação encontrada</td>
            </tr>
        `;
        return;
    }
    
    transactions.forEach(transaction => {
        const row = document.createElement('tr');
        row.dataset.transaction = JSON.stringify(transaction);
        
        row.innerHTML = `
            <td>${formatDate(transaction.transaction_date)}</td>
            <td>${escapeHtml(transaction.description)}</td>
            <td>
                <span class="category-badge" style="background-color: ${transaction.category_color}">
                    <i class="${transaction.category_icon} me-1"></i>
                    ${escapeHtml(transaction.category_name)}
                </span>
            </td>
            <td class="${transaction.type === 'receita' ? 'text-success' : 'text-danger'}">
                R$ ${formatCurrency(transaction.amount)}
            </td>
            <td>${getPaymentMethodName(transaction.payment_method)}</td>
            <td>
                <span class="badge ${transaction.type === 'receita' ? 'bg-success' : 'bg-danger'}">
                    ${transaction.type.charAt(0).toUpperCase() + transaction.type.slice(1)}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary edit-transaction" data-id="${transaction.id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-transaction" data-id="${transaction.id}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Reattach event listeners
    attachEventListeners();
}

// Funções auxiliares
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getPaymentMethodName(key) {
    const methods = {
        'dinheiro': 'Dinheiro',
        'pix': 'PIX',
        'credito': 'Cartão de Crédito',
        'debito': 'Cartão de Débito',
        'transferencia': 'Transferência',
        'boleto': 'Boleto'
    };
    return methods[key] || key;
}

function attachEventListeners() {
    // Reattach edit buttons
    document.querySelectorAll('.edit-transaction').forEach(button => {
        button.addEventListener('click', function() {
            const transactionData = JSON.parse(this.closest('tr').dataset.transaction);
            const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
            fillTransactionForm(transactionData);
            modal.show();
        });
    });
    
    // Reattach delete buttons
    document.querySelectorAll('.delete-transaction').forEach(button => {
        button.addEventListener('click', async function() {
            if (!confirm('Tem certeza que deseja excluir esta transação?')) {
                return;
            }
            
            const transactionId = this.dataset.id;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', transactionId);
            
            try {
                const response = await fetch('/carteira/controllers/TransactionController.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    this.closest('tr').remove();
                } else {
                    showToast(data.error, 'danger');
                }
            } catch (error) {
                showToast('Erro ao excluir transação', 'danger');
                console.error('Erro:', error);
            }
        });
    });
}
