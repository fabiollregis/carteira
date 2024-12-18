<?php
require_once 'config/config.php';
require_once 'models/CreditCard.php';

// Conectar ao banco de dados
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Função para formatar valor em reais
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

// Estilo para a página
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .error { color: red; }
    .success { color: green; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>';

// ID do cartão (ajuste conforme necessário)
$card_id = 1; // Substitua pelo ID do seu cartão

$card = new CreditCard($conn);

echo "<h2>Verificação e Correção de Transação</h2>";

// 1. Verificar a transação de R$ 14,00
echo "<div class='section'>";
echo "<h3>1. Verificando transação de R$ 14,00</h3>";

$query = "SELECT * FROM card_transactions 
          WHERE card_id = ? AND amount = 14.00 AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->execute([$card_id]);
$transaction = $stmt->get_result()->fetch_assoc();

if ($transaction) {
    echo "<p class='success'>✓ Transação encontrada:</p>";
    echo "<pre>";
    print_r($transaction);
    echo "</pre>";
} else {
    echo "<p class='error'>✗ Transação de R$ 14,00 não encontrada!</p>";
}

// 2. Verificar o limite atual do cartão
echo "</div><div class='section'>";
echo "<h3>2. Verificando limite do cartão</h3>";

$query = "SELECT * FROM credit_cards WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$card_id]);
$card_data = $stmt->get_result()->fetch_assoc();

echo "Limite Total: " . formatMoney($card_data['card_limit']) . "<br>";
echo "Limite Disponível: " . formatMoney($card_data['available_limit']) . "<br>";
echo "Valor Usado: " . formatMoney($card_data['card_limit'] - $card_data['available_limit']) . "<br>";

// 3. Recalcular e corrigir os limites
echo "</div><div class='section'>";
echo "<h3>3. Recalculando limites</h3>";

$result = $card->updateCardLimits($card_id);

if ($result) {
    echo "<p class='success'>✓ Limites atualizados com sucesso!</p>";
    echo "Novo limite disponível: " . formatMoney($result['available_limit']) . "<br>";
    echo "Total pendente: " . formatMoney($result['total_pending']) . "<br>";
} else {
    echo "<p class='error'>✗ Erro ao atualizar limites!</p>";
}

// 4. Verificar todas as transações pendentes
echo "</div><div class='section'>";
echo "<h3>4. Listando todas as transações pendentes</h3>";

$query = "SELECT * FROM card_transactions WHERE card_id = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->execute([$card_id]);
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($transactions) {
    foreach ($transactions as $t) {
        echo "- " . $t['description'] . ": " . formatMoney($t['amount']) . 
             " (Data: " . date('d/m/Y', strtotime($t['transaction_date'])) . ")<br>";
    }
} else {
    echo "<p>Nenhuma transação pendente encontrada.</p>";
}
