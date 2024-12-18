// Função para formatar valores monetários
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Função para mostrar toast de notificação
function showToast(message, type = 'success') {
    const toast = document.querySelector('.toast');
    const toastBody = toast.querySelector('.toast-body');
    
    toast.classList.remove('bg-success', 'bg-danger');
    toast.classList.add(type === 'success' ? 'bg-success' : 'bg-danger');
    toastBody.textContent = message;
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

// Função para mostrar o modal de adicionar valor
function showAddValueModal(goalId, goalTitle) {
    const modal = new bootstrap.Modal(document.getElementById('addValueModal'));
    document.getElementById('goalIdForValue').value = goalId;
    document.getElementById('goalTitleForValue').textContent = goalTitle;
    
    // Limpa qualquer barra de progresso existente
    const existingProgress = document.querySelector('#addValueModal .progress-container');
    if (existingProgress) {
        existingProgress.remove();
    }
    
    // Busca os dados atuais da meta
    fetch(`/carteira/controllers/GoalController.php?action=get_goal&goal_id=${goalId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const goal = data.goal;
                const currentAmount = parseFloat(goal.current_amount) || 0;
                const targetAmount = parseFloat(goal.target_amount);
                const remaining = targetAmount - currentAmount;
                const progress = (currentAmount / targetAmount) * 100;

                // Atualiza os valores no modal
                document.getElementById('targetAmount').textContent = formatCurrency(targetAmount);
                document.getElementById('currentAmount').textContent = formatCurrency(currentAmount);
                document.getElementById('remainingAmount').textContent = formatCurrency(remaining);
                document.getElementById('valueAmount').max = remaining;
                document.getElementById('valueAmount').value = '';

                // Adiciona a barra de progresso
                const progressContainer = document.createElement('div');
                progressContainer.className = 'progress-container mt-3';
                progressContainer.innerHTML = `
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: ${progress}%;" 
                             aria-valuenow="${progress}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            ${progress.toFixed(1)}%
                        </div>
                    </div>
                `;
                document.querySelector('#addValueModal .modal-body').appendChild(progressContainer);
                
                modal.show();
            } else {
                showToast(data.error, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro ao buscar dados da meta:', error);
            showToast('Erro ao carregar dados da meta', 'danger');
        });
}

// Função para adicionar valor à meta
function addValueToGoal() {
    const goalId = document.getElementById('goalIdForValue').value;
    const valueAmount = document.getElementById('valueAmount').value;
    
    if (!valueAmount || isNaN(valueAmount) || parseFloat(valueAmount) <= 0) {
        showToast('Por favor, insira um valor válido', 'danger');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add_value');
    formData.append('goal_id', goalId);
    formData.append('amount', valueAmount);

    fetch('/carteira/controllers/GoalController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('addValueModal')).hide();
            
            // Recarrega a página após um pequeno delay para o toast ser visível
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast(data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Erro ao adicionar valor:', error);
        showToast('Erro ao adicionar valor', 'danger');
    });
}

// Função para marcar meta como concluída
async function markAsCompleted(goalId) {
    if (!confirm('Tem certeza que deseja marcar esta meta como concluída?')) {
        return;
    }

    try {
        // Primeiro, busca os dados atuais da meta
        const response = await fetch(`/carteira/controllers/GoalController.php?action=get_goal&goal_id=${goalId}`);
        const goalData = await response.json();

        if (!goalData.success) {
            throw new Error(goalData.error || 'Erro ao buscar dados da meta');
        }

        // Prepara os dados para atualização
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('id', goalId);
        formData.append('status', 'concluida');

        // Envia a atualização
        const updateResponse = await fetch('/carteira/controllers/GoalController.php', {
            method: 'POST',
            body: formData
        });

        const updateData = await updateResponse.json();

        if (updateData.success) {
            showToast('Meta marcada como concluída!', 'success');
            
            // Atualiza a interface sem recarregar a página
            const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`);
            if (goalCard) {
                // Adiciona a faixa de sucesso
                const successBanner = document.createElement('div');
                successBanner.className = 'bg-success text-white text-center py-2';
                successBanner.innerHTML = '<i class="fas fa-check-circle me-2"></i>Meta Concluída';
                
                // Adiciona a faixa no início do card
                goalCard.insertBefore(successBanner, goalCard.firstChild);
                
                // Adiciona classe de fundo claro
                goalCard.classList.add('bg-light');
                
                // Desabilita os botões de ação
                const actionButtons = goalCard.querySelectorAll('button');
                actionButtons.forEach(button => {
                    if (!button.classList.contains('dropdown-toggle')) {
                        button.disabled = true;
                    }
                });
            }
        } else {
            throw new Error(updateData.error || 'Erro ao concluir meta');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast(error.message, 'danger');
    }
}

// Função para excluir meta
async function deleteGoal(goalId) {
    if (!confirm('Tem certeza que deseja excluir esta meta?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', goalId);

        const response = await fetch('/carteira/controllers/GoalController.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast('Meta excluída com sucesso!', 'success');
            location.reload();
        } else {
            throw new Error(data.error || 'Erro ao excluir meta');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast(error.message, 'danger');
    }
}

// Quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do formulário
    const goalForm = document.getElementById('goalForm');
    const goalModal = document.getElementById('goalModal');
    const modal = new bootstrap.Modal(goalModal);

    // Envio do formulário
    goalForm?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('/carteira/controllers/GoalController.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Meta salva com sucesso!', 'success');
                
                // Fecha o modal e reseta o formulário
                modal.hide();
                this.reset();
                this.querySelector('input[name="action"]').value = 'create';
                
                // Recarrega a página
                window.location.reload();
            } else {
                throw new Error(data.error || 'Erro ao salvar meta');
            }
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    // Edição de meta
    document.addEventListener('click', function(e) {
        const editButton = e.target.closest('.edit-goal');
        if (editButton) {
            const goalData = JSON.parse(editButton.dataset.goal);
            fillGoalForm(goalData);
        }
    });

    // Preenche o formulário com os dados da meta
    function fillGoalForm(data) {
        const form = document.getElementById('goalForm');
        form.querySelector('input[name="id"]').value = data.id;
        form.querySelector('input[name="action"]').value = 'update';
        form.querySelector('input[name="title"]').value = data.title;
        form.querySelector('textarea[name="description"]').value = data.description || '';
        form.querySelector('input[name="target_amount"]').value = data.target_amount;
        form.querySelector('input[name="current_amount"]').value = data.current_amount || 0;
        form.querySelector('select[name="category_id"]').value = data.category_id || '';
        form.querySelector('input[name="start_date"]').value = data.start_date;
        form.querySelector('input[name="end_date"]').value = data.end_date;
        form.querySelector('select[name="status"]').value = data.status || 'em_andamento';

        // Atualiza o título do modal
        document.querySelector('#goalModal .modal-title').textContent = 'Editar Meta';
        
        // Mostra o modal
        const modal = new bootstrap.Modal(document.getElementById('goalModal'));
        modal.show();
    }

    // Reset do modal quando fechado
    goalModal?.addEventListener('hidden.bs.modal', function() {
        const form = document.getElementById('goalForm');
        form.reset();
        form.querySelector('input[name="action"]').value = 'create';
        form.querySelector('input[name="id"]').value = '';
        document.querySelector('#goalModal .modal-title').textContent = 'Nova Meta';
    });
});
