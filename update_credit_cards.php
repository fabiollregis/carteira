<?php
require_once 'config/config.php';
require_once 'models/CreditCard.php';

// Conectar ao banco de dados
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

try {
    // Ler e executar o script SQL
    $sql = file_get_contents('bd/update_credit_cards.sql');
    
    if ($conn->multi_query($sql)) {
        do {
            // Consumir todos os resultados
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "Atualização concluída com sucesso!\n";
    }
    
    // Atualizar limites de todos os cartões
    $query = "SELECT id FROM credit_cards";
    $result = $conn->query($query);
    
    $card = new CreditCard($conn);
    while ($row = $result->fetch_assoc()) {
        if ($card->updateCardLimits($row['id'])) {
            echo "Limites do cartão ID {$row['id']} atualizados com sucesso.\n";
        } else {
            echo "Erro ao atualizar limites do cartão ID {$row['id']}.\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

$conn->close();
