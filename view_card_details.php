<?php
require_once 'config/config.php';
require_once 'models/CreditCard.php';

// Conectar ao banco de dados
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Simular sessão de usuário se necessário
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Ajuste para o ID do seu usuário
}

$card = new CreditCard($conn);
$cards = $card->listCards();

// Estilo para melhor visualização
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .card { border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .transaction { margin: 5px 0; padding: 5px; background: #f9f9f9; }
    .total { font-weight: bold; margin-top: 10px; }
    .error { color: red; }
    .details { margin-left: 20px; }
</style>';

if ($cards) {
    foreach ($cards as $cardData) {
        echo "<div class='card'>";
        echo "<h2>{$cardData['card_name']}</h2>";
        echo "<p>Limite Total: R$ " . number_format($cardData['card_limit'], 2, ',', '.') . "</p>";
        echo "<p>Limite Usado: R$ " . number_format($cardData['used_limit'], 2, ',', '.') . "</p>";
        echo "<p>Limite Disponível: R$ " . number_format($cardData['available_limit'], 2, ',', '.') . "</p>";
        echo "<p>Fechamento: Dia {$cardData['closing_day']}</p>";
        echo "<p>Vencimento: Dia {$cardData['due_day']}</p>";
        
        if ($cardData['transactions']) {
            echo "<h3>Transações Pendentes:</h3>";
            echo "<div class='details'>";
            foreach ($cardData['transactions']['items'] as $transaction) {
                echo "<div class='transaction'>";
                echo "{$transaction['description']} - {$transaction['formatted_amount']} ";
                echo "({$transaction['formatted_date']})";
                echo "</div>";
            }
            echo "<p class='total'>Total das Transações: {$cardData['transactions']['formatted_total']}</p>";
            echo "</div>";
            
            // Verificar se há diferença entre o valor usado e o total das transações
            $diff = abs($cardData['used_limit'] - $cardData['transactions']['total']);
            if ($diff > 0.01) { // Usar 0.01 para evitar problemas com arredondamento
                echo "<p class='error'>Atenção: Há uma diferença de R$ " . 
                     number_format($diff, 2, ',', '.') . 
                     " entre o valor usado e o total das transações!</p>";
            }
        } else {
            echo "<p>Nenhuma transação pendente</p>";
        }
        echo "</div>";
    }
} else {
    echo "<p class='error'>Erro ao buscar cartões</p>";
}
