<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/ReportController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /carteira/login.php');
    exit;
}
?>

<div class="container-fluid py-4 reports-page">
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
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <!-- Métodos de Pagamento -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Métodos de Pagamento</h5>
                    <canvas id="paymentMethodsChart" style="height: 250px !important;"></canvas>
                </div>
            </div>
        </div>

        <!-- Tipos de Transação -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Tipos de Transação</h5>
                    <canvas id="transactionTypesChart" style="height: 250px !important;"></canvas>
                </div>
            </div>
        </div>

        <!-- Categorias -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Gastos por Categoria</h5>
                    <canvas id="categoriesChart" style="height: 250px !important;"></canvas>
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- JavaScript dos Relatórios -->
<script src="/carteira/assets/js/reports.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
