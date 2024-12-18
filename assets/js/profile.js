document.addEventListener('DOMContentLoaded', function() {
    // Elementos do formulário
    const profileForm = document.getElementById('profileForm');
    const changePasswordCheckbox = document.getElementById('changePassword');
    const passwordFields = document.getElementById('passwordFields');
    const currentPassword = document.getElementById('currentPassword');
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');

    // Controle de exibição dos campos de senha
    changePasswordCheckbox?.addEventListener('change', function() {
        passwordFields.classList.toggle('d-none');
        
        // Resetar campos de senha
        if (!this.checked) {
            currentPassword.value = '';
            newPassword.value = '';
            confirmPassword.value = '';
            currentPassword.required = false;
            newPassword.required = false;
            confirmPassword.required = false;
        } else {
            currentPassword.required = true;
            newPassword.required = true;
            confirmPassword.required = true;
        }
    });

    // Validação e envio do formulário
    profileForm?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validar campos obrigatórios
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        // Validar senhas se estiver alterando
        if (changePasswordCheckbox.checked) {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('As senhas não conferem');
                this.classList.add('was-validated');
                return;
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        const formData = new FormData(this);
        
        try {
            const response = await fetch('/carteira/controllers/ProfileController.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            const toast = new bootstrap.Toast(document.querySelector('.toast'));
            if (data.success) {
                // Mostrar mensagem de sucesso
                document.querySelector('.toast').classList.remove('bg-danger');
                document.querySelector('.toast').classList.add('bg-success');
                document.querySelector('.toast-body').textContent = 'Perfil atualizado com sucesso!';
                
                // Resetar campos de senha
                if (changePasswordCheckbox.checked) {
                    changePasswordCheckbox.checked = false;
                    passwordFields.classList.add('d-none');
                    currentPassword.value = '';
                    newPassword.value = '';
                    confirmPassword.value = '';
                }
                
                // Remover validação visual
                this.classList.remove('was-validated');
            } else {
                // Mostrar mensagem de erro
                document.querySelector('.toast').classList.remove('bg-success');
                document.querySelector('.toast').classList.add('bg-danger');
                document.querySelector('.toast-body').textContent = data.error || 'Erro ao atualizar perfil';
            }
            
            toast.show();
        } catch (error) {
            console.error('Erro:', error);
            const toast = new bootstrap.Toast(document.querySelector('.toast'));
            document.querySelector('.toast').classList.remove('bg-success');
            document.querySelector('.toast').classList.add('bg-danger');
            document.querySelector('.toast-body').textContent = 'Erro ao processar requisição';
            toast.show();
        }
    });
});
