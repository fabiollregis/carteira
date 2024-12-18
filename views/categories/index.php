<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/CategoryController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /carteira/login.php');
    exit;
}

$controller = new CategoryController();
$categories = $controller->getAllCategories($_SESSION['user_id']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tags me-2"></i>Gerenciar Categorias
                        </h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="fas fa-plus me-2"></i>Nova Categoria
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ícone</th>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Cor</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTable">
                                <?php if ($categories['success'] && !empty($categories['categories'])): ?>
                                    <?php foreach ($categories['categories'] as $category): ?>
                                        <tr data-category='<?php echo json_encode($category); ?>'>
                                            <td>
                                                <i class="<?php echo htmlspecialchars($category['icon']); ?>" style="color: <?php echo htmlspecialchars($category['color']); ?>"></i>
                                            </td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $category['type'] === 'receita' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo ucfirst($category['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="color-preview" style="background-color: <?php echo htmlspecialchars($category['color']); ?>"></div>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-category" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#categoryModal"
                                                        data-id="<?php echo $category['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-category" 
                                                        data-id="<?php echo $category['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhuma categoria encontrada</td>
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

<!-- Modal de Categoria -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">Nova Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" class="needs-validation" novalidate>
                    <input type="hidden" id="categoryId" name="id">
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="categoryName" name="name" required>
                        <div class="invalid-feedback">
                            Por favor, insira um nome para a categoria.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Tipo</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="type" id="typeReceita" value="receita" required>
                            <label class="btn btn-outline-success" for="typeReceita">Receita</label>
                            
                            <input type="radio" class="btn-check" name="type" id="typeDespesa" value="despesa">
                            <label class="btn btn-outline-danger" for="typeDespesa">Despesa</label>
                        </div>
                        <div class="invalid-feedback">
                            Por favor, selecione um tipo.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="categoryIcon" class="form-label">Ícone</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i id="iconPreview" class="fas fa-tag"></i>
                            </span>
                            <select class="form-select" id="categoryIcon" name="icon" required>
                                <option value="">Selecione um ícone</option>
                                <optgroup label="Finanças">
                                    <option value="fas fa-money-bill-wave">💵 Dinheiro</option>
                                    <option value="fas fa-credit-card">💳 Cartão</option>
                                    <option value="fas fa-piggy-bank">🐷 Cofrinho</option>
                                    <option value="fas fa-wallet">👛 Carteira</option>
                                    <option value="fas fa-coins">🪙 Moedas</option>
                                    <option value="fas fa-dollar-sign">💲 Cifrão</option>
                                    <option value="fas fa-hand-holding-usd">🤲 Pagamento</option>
                                </optgroup>
                                <optgroup label="Casa">
                                    <option value="fas fa-home">🏠 Casa</option>
                                    <option value="fas fa-bed">🛏️ Quarto</option>
                                    <option value="fas fa-couch">🛋️ Móveis</option>
                                    <option value="fas fa-tv">📺 TV</option>
                                    <option value="fas fa-plug">🔌 Energia</option>
                                    <option value="fas fa-faucet">🚰 Água</option>
                                </optgroup>
                                <optgroup label="Alimentação">
                                    <option value="fas fa-utensils">🍽️ Restaurante</option>
                                    <option value="fas fa-shopping-basket">🧺 Mercado</option>
                                    <option value="fas fa-coffee">☕ Café</option>
                                    <option value="fas fa-pizza-slice">🍕 Lanches</option>
                                    <option value="fas fa-beer">🍺 Bebidas</option>
                                </optgroup>
                                <optgroup label="Transporte">
                                    <option value="fas fa-car">🚗 Carro</option>
                                    <option value="fas fa-bus">🚌 Ônibus</option>
                                    <option value="fas fa-taxi">🚕 Táxi</option>
                                    <option value="fas fa-gas-pump">⛽ Combustível</option>
                                    <option value="fas fa-bicycle">🚲 Bicicleta</option>
                                </optgroup>
                                <optgroup label="Saúde">
                                    <option value="fas fa-hospital">🏥 Hospital</option>
                                    <option value="fas fa-prescription-bottle-med">💊 Remédios</option>
                                    <option value="fas fa-tooth">🦷 Dentista</option>
                                    <option value="fas fa-heart">❤️ Saúde</option>
                                    <option value="fas fa-dumbbell">💪 Academia</option>
                                </optgroup>
                                <optgroup label="Educação">
                                    <option value="fas fa-graduation-cap">🎓 Educação</option>
                                    <option value="fas fa-book">📚 Livros</option>
                                    <option value="fas fa-laptop">💻 Cursos</option>
                                    <option value="fas fa-school">🏫 Escola</option>
                                </optgroup>
                                <optgroup label="Lazer">
                                    <option value="fas fa-gamepad">🎮 Games</option>
                                    <option value="fas fa-film">🎬 Cinema</option>
                                    <option value="fas fa-plane">✈️ Viagens</option>
                                    <option value="fas fa-music">🎵 Música</option>
                                    <option value="fas fa-palette">🎨 Arte</option>
                                </optgroup>
                                <optgroup label="Trabalho">
                                    <option value="fas fa-briefcase">💼 Trabalho</option>
                                    <option value="fas fa-building">🏢 Escritório</option>
                                    <option value="fas fa-tools">🛠️ Ferramentas</option>
                                </optgroup>
                                <optgroup label="Outros">
                                    <option value="fas fa-gift">🎁 Presentes</option>
                                    <option value="fas fa-paw">🐾 Pets</option>
                                    <option value="fas fa-tshirt">👕 Roupas</option>
                                    <option value="fas fa-cut">✂️ Beleza</option>
                                    <option value="fas fa-phone">📱 Telefone</option>
                                    <option value="fas fa-wifi">📶 Internet</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="categoryColor" class="form-label">Cor</label>
                        <input type="color" class="form-control form-control-color w-100" 
                               id="categoryColor" name="color" value="#563d7c" required>
                        <div class="invalid-feedback">
                            Por favor, selecione uma cor.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="saveCategory">Salvar</button>
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
