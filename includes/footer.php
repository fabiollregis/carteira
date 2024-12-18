</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php if (strpos($_SERVER['REQUEST_URI'], '/categories') !== false): ?>
    <script src="/carteira/assets/js/categories.js"></script>
<?php endif; ?>
    
<?php if (strpos($_SERVER['REQUEST_URI'], '/transactions') !== false): ?>
    <script src="/carteira/assets/js/transactions.js"></script>
<?php endif; ?>

<script>
// Função para mostrar notificações
function showAlert(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastBody = toast.querySelector('.toast-body');
    
    toast.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info');
    toast.classList.add(`bg-${type}`);
    toastBody.textContent = message;
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}
</script>

<footer class="fixed-bottom bg-light border-top py-2 text-center">
    <small class="text-muted">2024 - Feito por Tecno Solution By Fábio Regis</small>
</footer>

</body>
</html>
