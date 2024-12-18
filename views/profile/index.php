<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/ProfileController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /carteira/login.php');
    exit;
}

$profileController = new ProfileController();
$profile = $profileController->getUserProfile($_SESSION['user_id']);
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-user-circle me-2"></i>Meu Perfil
                    </h5>

                    <?php if ($profile['success']): ?>
                        <form id="profileForm" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="update">

                            <!-- Nome -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                    value="<?php echo htmlspecialchars($profile['user']['name']); ?>" required>
                                <div class="invalid-feedback">
                                    Por favor, informe seu nome.
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                    value="<?php echo htmlspecialchars($profile['user']['email']); ?>" required>
                                <div class="invalid-feedback">
                                    Por favor, informe um e-mail válido.
                                </div>
                            </div>

                            <!-- Alterar Senha -->
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="changePassword">
                                    <label class="form-check-label" for="changePassword">
                                        Alterar senha
                                    </label>
                                </div>
                            </div>

                            <!-- Campos de Senha (inicialmente ocultos) -->
                            <div id="passwordFields" class="d-none">
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Senha Atual</label>
                                    <input type="password" class="form-control" id="currentPassword" name="current_password">
                                    <div class="invalid-feedback">
                                        Por favor, informe sua senha atual.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control" id="newPassword" name="new_password">
                                    <div class="invalid-feedback">
                                        Por favor, informe uma nova senha.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" id="confirmPassword">
                                    <div class="invalid-feedback">
                                        As senhas não conferem.
                                    </div>
                                </div>
                            </div>

                            <!-- Informações da Conta -->
                            <div class="mb-4">
                                <small class="text-muted">
                                    Conta criada em: <?php echo date('d/m/Y', strtotime($profile['user']['created_at'])); ?>
                                </small>
                            </div>

                            <!-- Botões -->
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Salvar Alterações
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($profile['error']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast de Notificação -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- JavaScript do Perfil -->
<script src="/carteira/assets/js/profile.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
