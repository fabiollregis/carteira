<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/GoalController.php';
require_once __DIR__ . '/../../controllers/CategoryController.php';

$goalController = new GoalController();
$categoryController = new CategoryController();

$goals = $goalController->getGoals($_SESSION['user_id']);
$categories = $categoryController->getAllCategories($_SESSION['user_id']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bullseye me-2"></i>Minhas Metas
                        </h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#goalModal">
                            <i class="fas fa-plus me-2"></i>Nova Meta
                        </button>
                    </div>

                    <div class="row" id="goalsList">
                        <?php if ($goals['success'] && !empty($goals['goals'])): ?>
                            <?php foreach ($goals['goals'] as $goal): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 <?php echo $goal['status'] === 'concluida' ? 'bg-light' : ''; ?>" data-goal-id="<?php echo $goal['id']; ?>">
                                        <?php if ($goal['status'] === 'concluida'): ?>
                                            <div class="bg-success text-white text-center py-2">
                                                <i class="fas fa-check-circle me-2"></i>Meta Concluída
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h6 class="card-title d-flex justify-content-between">
                                                <?php echo htmlspecialchars($goal['title']); ?>
                                                <div class="dropdown">
                                                    <button class="btn btn-link text-dark p-0" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <button class="dropdown-item edit-goal" data-goal='<?php echo json_encode($goal); ?>'>
                                                                <i class="fas fa-edit me-2"></i>Editar
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item text-danger delete-goal" data-goal-id="<?php echo $goal['id']; ?>">
                                                                <i class="fas fa-trash me-2"></i>Excluir
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </h6>
                                            <p class="card-text text-muted small mb-2">
                                                <?php echo htmlspecialchars($goal['description'] ?? ''); ?>
                                            </p>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="small">Progresso</span>
                                                    <span class="small"><?php echo $goal['progress']; ?>%</span>
                                                </div>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar bg-success" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $goal['progress']; ?>%" 
                                                         aria-valuenow="<?php echo $goal['progress']; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center small text-muted mb-3">
                                                <span>
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($goal['end_date'])); ?>
                                                </span>
                                                <span>
                                                    <strong>R$ <?php echo number_format($goal['current_amount'], 2, ',', '.'); ?></strong>
                                                    /
                                                    R$ <?php echo number_format($goal['target_amount'], 2, ',', '.'); ?>
                                                </span>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" 
                                                        class="btn btn-primary btn-sm flex-grow-1"
                                                        onclick="showAddValueModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars($goal['title']); ?>')"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#addValueModal"
                                                        <?php echo $goal['status'] === 'concluida' ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-plus me-2"></i>Adicionar Valor
                                                </button>
                                                <?php if ($goal['progress'] < 100): ?>
                                                    <button type="button" 
                                                            class="btn btn-success btn-sm flex-grow-1"
                                                            onclick="markAsCompleted(<?php echo $goal['id']; ?>)"
                                                            <?php echo $goal['status'] === 'concluida' ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-check me-2"></i>Concluir
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" 
                                                        class="btn btn-danger btn-sm flex-grow-1"
                                                        onclick="deleteGoal(<?php echo $goal['id']; ?>)"
                                                        <?php echo $goal['status'] === 'concluida' ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-trash me-2"></i>Excluir
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Você ainda não tem metas cadastradas. Crie sua primeira meta!
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Meta -->
<div class="modal fade" id="goalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="goalModalLabel">Nova Meta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="goalForm">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="id" id="goalId">

                    <div class="mb-3">
                        <label for="title" class="form-label">Título</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="target_amount" class="form-label">Valor Alvo</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="target_amount" name="target_amount" step="0.01" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="current_amount" class="form-label">Valor Atual</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="current_amount" name="current_amount" step="0.01" value="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoria</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">Selecione uma categoria</option>
                            <?php if ($categories['success']): ?>
                                <?php foreach ($categories['categories'] as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Data Inicial</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">Data Final</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required
                                   value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="em_andamento">Em Andamento</option>
                            <option value="concluida">Concluída</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" form="goalForm">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Valor -->
<div class="modal fade" id="addValueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Valor à Meta: <span id="goalTitleForValue"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="goalIdForValue">
                
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <label class="form-label">Meta Total:</label>
                        <div class="fw-bold" id="targetAmount"></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Valor Atual:</label>
                        <div class="fw-bold" id="currentAmount"></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Valor Restante:</label>
                        <div class="fw-bold" id="remainingAmount"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="valueAmount" class="form-label">Valor a Adicionar:</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" 
                               class="form-control" 
                               id="valueAmount" 
                               step="0.01" 
                               min="0.01" 
                               required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="addValueToGoal()">Adicionar</button>
            </div>
        </div>
    </div>
</div>

<script src="/carteira/assets/js/goals.js"></script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
