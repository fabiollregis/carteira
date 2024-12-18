// Manipulação do Modal de Categoria
document.addEventListener('DOMContentLoaded', function() {
    const categoryModal = document.getElementById('categoryModal');
    const categoryForm = document.getElementById('categoryForm');
    const saveButton = document.getElementById('saveCategory');
    
    if (!categoryModal || !categoryForm || !saveButton) {
        console.error('Elementos do formulário de categoria não encontrados');
        return;
    }

    // Abrir modal para nova categoria ou edição
    categoryModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        
        // Resetar formulário e validação
        categoryForm.reset();
        categoryForm.classList.remove('was-validated');
        
        // Configurar para nova categoria por padrão
        categoryForm.elements['action'].value = 'create';
        document.getElementById('categoryModalLabel').textContent = 'Nova Categoria';
        
        // Se for edição, preencher com dados da categoria
        if (button && button.classList.contains('edit-category')) {
            const categoryId = button.dataset.id;
            const row = button.closest('tr');
            if (row && row.dataset.category) {
                const categoryData = JSON.parse(row.dataset.category);
                fillCategoryForm(categoryData);
            }
        }
    });
    
    // Salvar categoria
    saveButton.addEventListener('click', async function(e) {
        e.preventDefault();
        
        if (!categoryForm.checkValidity()) {
            categoryForm.classList.add('was-validated');
            return;
        }
        
        try {
            const formData = new FormData(categoryForm);
            
            const response = await fetch('/carteira/controllers/CategoryController.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                const modal = bootstrap.Modal.getInstance(categoryModal);
                if (modal) {
                    modal.hide();
                }
                window.location.reload();
            } else {
                showToast(data.error || 'Erro ao salvar categoria', 'danger');
            }
        } catch (error) {
            console.error('Erro ao salvar categoria:', error);
            showToast('Erro ao salvar categoria: ' + error.message, 'danger');
        }
    });
    
    // Deletar categoria
    document.querySelectorAll('.delete-category').forEach(button => {
        button.addEventListener('click', async function() {
            if (!confirm('Tem certeza que deseja excluir esta categoria?')) {
                return;
            }
            
            const categoryId = this.dataset.id;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', categoryId);
            
            try {
                const response = await fetch('/carteira/controllers/CategoryController.php', {
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
                console.error('Erro ao excluir categoria:', error);
                showToast('Erro ao excluir categoria', 'danger');
            }
        });
    });
    
    // Adicionar evento de mudança ao select de ícones
    const iconSelect = document.getElementById('categoryIcon');
    if (iconSelect) {
        iconSelect.addEventListener('change', updateIconPreview);
    }
});

// Função para mostrar toast
function showToast(message, type = 'success') {
    const toastElement = document.querySelector('.toast');
    const toastBody = document.querySelector('.toast-body');
    
    if (!toastElement || !toastBody) {
        console.error('Elementos do toast não encontrados');
        alert(message);
        return;
    }
    
    toastElement.className = `toast align-items-center text-white bg-${type} border-0`;
    toastBody.textContent = message;
    
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
}

// Preencher formulário com dados da categoria
function fillCategoryForm(data) {
    const form = document.getElementById('categoryForm');
    if (!form) return;
    
    form.elements['action'].value = 'update';
    form.elements['id'].value = data.id;
    form.elements['name'].value = data.name;
    form.elements['icon'].value = data.icon;
    form.elements['color'].value = data.color;
    
    const typeRadio = form.querySelector(`input[name="type"][value="${data.type}"]`);
    if (typeRadio) {
        typeRadio.checked = true;
    }
    
    document.getElementById('categoryModalLabel').textContent = 'Editar Categoria';
    updateIconPreview();
}

// Atualizar preview do ícone
function updateIconPreview() {
    const iconSelect = document.getElementById('categoryIcon');
    const iconPreview = document.getElementById('iconPreview');
    
    if (!iconSelect || !iconPreview) return;
    
    // Remover todas as classes atuais do preview
    iconPreview.className = '';
    
    // Adicionar a nova classe do ícone selecionado
    if (iconSelect.value) {
        iconPreview.className = iconSelect.value;
    } else {
        iconPreview.className = 'fas fa-tag'; // ícone padrão
    }
}
