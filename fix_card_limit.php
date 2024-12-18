<?php
require_once 'config/config.php';
require_once 'models/CreditCard.php';

// Conectar ao banco de dados
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$card = new CreditCard($conn);

// ID do cartão que precisa ser corrigido
$card_id = 1; // Ajuste este valor para o ID do seu cartão

// Debug antes da correção
echo "Valores antes da correção:\n";
print_r($card->debugCardValues($card_id));

// Corrigir o limite
if ($card->fixAvailableLimit($card_id)) {
    echo "\nLimite corrigido com sucesso!\n";
} else {
    echo "\nErro ao corrigir o limite.\n";
}

// Debug depois da correção
echo "\nValores depois da correção:\n";
print_r($card->debugCardValues($card_id));
