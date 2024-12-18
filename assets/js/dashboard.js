// Variáveis globais para os gráficos
let categoryChart = null;
let trendChart = null;
let paymentChart = null;

// Cores para os gráficos
const chartColors = {
    primary: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
        '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'],
    receita: '#28a745',
    despesa: '#dc3545',
    background: {
        receita: 'rgba(40, 167, 69, 0.1)',
        despesa: 'rgba(220, 53, 69, 0.1)'
    }
};

// Função para formatar valores monetários
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Função para carregar o dashboard
async function loadDashboard(month, year) {
    try {
        // Carregar resumo mensal
        const formData = new FormData();
        formData.append('action', 'monthly_stats');
        formData.append('month', month);
        formData.append('year', year);

        const response = await fetch('/carteira/controllers/DashboardController.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Atualizar cards
            const incomeElement = document.getElementById('total-income');
            const expenseElement = document.getElementById('total-expense');
            const balanceElement = document.getElementById('total-balance');
            const balanceCard = document.getElementById('balance-card');

            if (incomeElement && expenseElement && balanceElement && balanceCard) {
                const stats = data.data;
                
                // Atualizar valores
                incomeElement.textContent = formatCurrency(stats.income || 0);
                expenseElement.textContent = formatCurrency(stats.expense || 0);
                balanceElement.textContent = formatCurrency(stats.balance || 0);

                // Atualizar classes
                const isPositive = (stats.balance || 0) >= 0;
                const colorClass = isPositive ? 'primary' : 'warning';

                balanceCard.className = `dashboard-card bg-${colorClass} bg-opacity-10`;
                balanceElement.className = `text-${colorClass} mb-0`;
            }
        }

        // Carregar gráficos
        await Promise.all([
            loadCategoryChart(month, year),
            loadTrendChart(month, year),
            loadPaymentChart(month, year)
        ]);

    } catch (error) {
        console.error('Erro ao carregar dashboard:', error);
        showToast('Erro ao carregar dados do dashboard', 'error');
    }
}

// Função para carregar o gráfico de categorias
async function loadCategoryChart(month, year) {
    try {
        const formData = new FormData();
        formData.append('action', 'category_stats');
        formData.append('month', month);
        formData.append('year', year);

        const response = await fetch('/carteira/controllers/DashboardController.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success && data.data.length > 0) {
            const ctx = document.getElementById('categoryChart');
            if (!ctx) return;

            const chartData = {
                labels: data.data.map(item => item.category_name),
                datasets: [{
                    data: data.data.map(item => parseFloat(item.total_amount) || 0),
                    backgroundColor: chartColors.primary.slice(0, data.data.length)
                }]
            };

            if (categoryChart) {
                categoryChart.destroy();
            }

            categoryChart = new Chart(ctx, {
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
    }
}

// Função para carregar o gráfico de tendência
async function loadTrendChart(month, year) {
    try {
        const formData = new FormData();
        formData.append('action', 'trend_stats');
        formData.append('month', month);
        formData.append('year', year);

        const response = await fetch('/carteira/controllers/DashboardController.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success && data.data.length > 0) {
            const ctx = document.getElementById('trendChart');
            if (!ctx) return;

            const chartData = {
                labels: data.data.map(item => {
                    const [year, month] = item.month.split('-');
                    const date = new Date(year, month - 1);
                    return date.toLocaleString('pt-BR', { month: 'short', year: 'numeric' });
                }),
                datasets: [
                    {
                        label: 'Receitas',
                        data: data.data.map(item => parseFloat(item.income) || 0),
                        borderColor: chartColors.receita,
                        backgroundColor: chartColors.background.receita,
                        fill: true
                    },
                    {
                        label: 'Despesas',
                        data: data.data.map(item => parseFloat(item.expense) || 0),
                        borderColor: chartColors.despesa,
                        backgroundColor: chartColors.background.despesa,
                        fill: true
                    }
                ]
            };

            if (trendChart) {
                trendChart.destroy();
            }

            trendChart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)',
                                drawBorder: false
                            },
                            title: {
                                display: true,
                                text: 'Valor (R$)',
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Período',
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            display: true,
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#000',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyColor: '#666',
                            bodyFont: {
                                size: 13
                            },
                            borderColor: 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1,
                            padding: 12,
                            callbacks: {
                                title: function(context) {
                                    return 'Período: ' + context[0].label;
                                },
                                label: function(context) {
                                    const label = context.dataset.label || '';
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
        console.error('Erro ao carregar gráfico de tendência:', error);
    }
}

// Função para carregar o gráfico de métodos de pagamento
async function loadPaymentChart(month, year) {
    try {
        const formData = new FormData();
        formData.append('action', 'payment_stats');
        formData.append('month', month);
        formData.append('year', year);

        const response = await fetch('/carteira/controllers/DashboardController.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success && data.data.length > 0) {
            const ctx = document.getElementById('paymentChart');
            if (!ctx) return;

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
                    data: data.data.map(item => parseFloat(item.total_amount) || 0),
                    backgroundColor: chartColors.primary.slice(0, data.data.length)
                }]
            };

            if (paymentChart) {
                paymentChart.destroy();
            }

            paymentChart = new Chart(ctx, {
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
    }
}

// Função para mostrar toast de notificação
function showToast(message, type = 'success') {
    const toast = document.querySelector('.toast');
    const toastBody = toast.querySelector('.toast-body');
    
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toastBody.textContent = message;
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

// Carregar dashboard quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    // Sempre definir o mês atual ao carregar a página
    const currentDate = new Date();
    const currentMonth = (currentDate.getMonth() + 1).toString();
    const currentYear = currentDate.getFullYear().toString();
    
    const monthFilter = document.getElementById('monthFilter');
    const yearFilter = document.getElementById('yearFilter');
    
    // Definir os valores atuais nos filtros
    if (monthFilter) monthFilter.value = currentMonth;
    if (yearFilter) yearFilter.value = currentYear;

    // Carregar o dashboard com o mês e ano atuais
    loadDashboard(currentMonth, currentYear);

    // Adicionar event listeners para os filtros
    monthFilter?.addEventListener('change', updateDashboard);
    yearFilter?.addEventListener('change', updateDashboard);
});

// Função para atualizar o dashboard quando os filtros mudarem
function updateDashboard() {
    const month = document.getElementById('monthFilter').value;
    const year = document.getElementById('yearFilter').value;
    loadDashboard(month, year);
}
