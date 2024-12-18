document.addEventListener('DOMContentLoaded', function() {
    // Referências aos elementos
    const monthSelect = document.querySelector('select[name="month"]');
    const yearSelect = document.querySelector('select[name="year"]');
    
    // Instâncias dos gráficos
    let paymentMethodsChart = null;
    let transactionTypesChart = null;
    let categoriesChart = null;

    // Cores para os gráficos
    const chartColors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
        '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'
    ];

    // Função para formatar valores monetários
    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    // Função para carregar dados dos métodos de pagamento
    async function loadPaymentMethodsChart() {
        try {
            const formData = new FormData();
            formData.append('action', 'payment_methods');
            formData.append('month', monthSelect.value);
            formData.append('year', yearSelect.value);

            const response = await fetch('/carteira/controllers/ReportController.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const ctx = document.getElementById('paymentMethodsChart').getContext('2d');
                
                const paymentMethodLabels = {
                    'dinheiro': 'Dinheiro',
                    'pix': 'PIX',
                    'debito': 'Cartão de Débito',
                    'credito': 'Cartão de Crédito',
                    'parcelado': 'Cartão Parcelado'
                };

                const chartData = {
                    labels: data.data.map(item => paymentMethodLabels[item.payment_method] || item.payment_method),
                    datasets: [{
                        data: data.data.map(item => item.total_amount),
                        backgroundColor: chartColors
                    }]
                };

                if (paymentMethodsChart) {
                    paymentMethodsChart.destroy();
                }

                paymentMethodsChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = formatCurrency(context.raw);
                                        return `${label}: ${value}`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Erro ao carregar gráfico de métodos de pagamento:', error);
            showError('Erro ao carregar gráfico de métodos de pagamento');
        }
    }

    // Função para carregar dados dos tipos de transação
    async function loadTransactionTypesChart() {
        try {
            const formData = new FormData();
            formData.append('action', 'transaction_types');
            formData.append('month', monthSelect.value);
            formData.append('year', yearSelect.value);

            const response = await fetch('/carteira/controllers/ReportController.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const ctx = document.getElementById('transactionTypesChart').getContext('2d');

                const chartData = {
                    labels: ['Receitas', 'Despesas'],
                    datasets: [{
                        data: [
                            parseFloat(data.data.receitas) || 0,
                            parseFloat(data.data.despesas) || 0
                        ],
                        backgroundColor: ['#1cc88a', '#e74a3b']
                    }]
                };

                if (transactionTypesChart) {
                    transactionTypesChart.destroy();
                }

                transactionTypesChart = new Chart(ctx, {
                    type: 'pie',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = formatCurrency(context.raw);
                                        return `${label}: ${value}`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Erro ao carregar gráfico de tipos de transação:', error);
            showError('Erro ao carregar gráfico de tipos de transação');
        }
    }

    // Função para carregar dados das categorias
    async function loadCategoriesChart() {
        try {
            const formData = new FormData();
            formData.append('action', 'categories');
            formData.append('month', monthSelect.value);
            formData.append('year', yearSelect.value);

            const response = await fetch('/carteira/controllers/ReportController.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const ctx = document.getElementById('categoriesChart').getContext('2d');

                const chartData = {
                    labels: data.data.map(item => item.category_name),
                    datasets: [{
                        data: data.data.map(item => parseFloat(item.total_amount) || 0),
                        backgroundColor: chartColors.slice(0, data.data.length)
                    }]
                };

                if (categoriesChart) {
                    categoriesChart.destroy();
                }

                categoriesChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = formatCurrency(context.raw);
                                        return `${label}: ${value}`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Erro ao carregar gráfico de categorias:', error);
            showError('Erro ao carregar gráfico de categorias');
        }
    }

    // Função para mostrar erro
    function showError(message) {
        const toast = new bootstrap.Toast(document.querySelector('.toast'));
        document.querySelector('.toast').classList.remove('bg-success');
        document.querySelector('.toast').classList.add('bg-danger');
        document.querySelector('.toast-body').textContent = message;
        toast.show();
    }

    // Carregar todos os gráficos
    async function loadAllCharts() {
        await Promise.all([
            loadPaymentMethodsChart(),
            loadTransactionTypesChart(),
            loadCategoriesChart()
        ]);
    }

    // Event listeners
    monthSelect.addEventListener('change', loadAllCharts);
    yearSelect.addEventListener('change', loadAllCharts);

    // Carregar gráficos inicialmente
    loadAllCharts();
});
