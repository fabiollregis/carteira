<?php
require_once 'config/config.php';
require_once 'models/CreditCard.php';

// Conectar ao banco de dados
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Função para debug
function debugCard($conn, $card_id) {
    echo "<h2>Debug do Cartão ID: $card_id</h2>";
    
    // 1. Verificar dados do cartão
    $query = "SELECT * FROM credit_cards WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$card_id]);
    $card = $stmt->get_result()->fetch_assoc();
    
    echo "<h3>Dados do Cartão:</h3>";
    echo "<pre>";
    print_r($card);
    echo "</pre>";
    
    // 2. Verificar todas as transações
    $query = "SELECT 
                *,
                DATE_FORMAT(transaction_date, '%d/%m/%Y') as formatted_date,
                DATE_FORMAT(due_date, '%d/%m/%Y') as formatted_due_date
              FROM card_transactions 
              WHERE card_id = ?
              ORDER BY transaction_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$card_id]);
    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<h3>Transações:</h3>";
    echo "<pre>";
    print_r($transactions);
    echo "</pre>";
    
    // 3. Calcular soma das transações pendentes
    $query = "SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(amount), 0) as total_amount
              FROM card_transactions 
              WHERE card_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$card_id]);
    $totals = $stmt->get_result()->fetch_assoc();
    
    echo "<h3>Totais:</h3>";
    echo "Número de transações pendentes: " . $totals['total_transactions'] . "<br>";
    echo "Valor total pendente: R$ " . number_format($totals['total_amount'], 2, ',', '.') . "<br>";
    echo "Limite disponível no cartão: R$ " . number_format($card['available_limit'], 2, ',', '.') . "<br>";
    echo "Diferença (disponível - usado): R$ " . number_format($card['card_limit'] - $card['available_limit'], 2, ',', '.') . "<br>";
    
    // 4. Verificar inconsistências
    $used_amount = $card['card_limit'] - $card['available_limit'];
    $diff = abs($used_amount - $totals['total_amount']);
    if ($diff > 0.01) {
        echo "<h3 style='color: red;'>INCONSISTÊNCIA DETECTADA!</h3>";
        echo "Valor usado segundo o cartão: R$ " . number_format($used_amount, 2, ',', '.') . "<br>";
        echo "Soma das transações pendentes: R$ " . number_format($totals['total_amount'], 2, ',', '.') . "<br>";
        echo "Diferença: R$ " . number_format($diff, 2, ',', '.') . "<br>";
    }
}

// Adicionar estilo para melhor visualização
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    h2, h3 { color: #333; }
</style>';

// Debug do cartão (ajuste o ID conforme necessário)
debugCard($conn, 1); // Substitua 1 pelo ID do seu cartão
