<?php
session_start();
require_once '../config/config.php';
require_once 'CreditCardController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$controller = new CreditCardController();

if (isset($_GET['action']) || isset($_POST['action'])) {
    $action = $_GET['action'] ?? $_POST['action'];
    
    switch ($action) {
        case 'add_card':
            $controller->addCard();
            break;
            
        case 'add_transaction':
            $controller->addTransaction();
            break;
            
        case 'pay_card':
            $controller->payCard();
            break;
            
        case 'pay_transaction':
            $controller->payTransaction();
            break;
            
        case 'delete_card':
            $controller->deleteCard();
            break;
            
        case 'delete_transaction':
            $controller->deleteTransaction();
            break;
            
        case 'get_transactions':
            header('Content-Type: application/json');
            $transactions = $controller->listTransactions($_GET['card_id']);
            echo json_encode(iterator_to_array($transactions));
            exit;
            break;
            
        case 'get_card':
            header('Content-Type: application/json');
            $card = $controller->getCard($_GET['card_id']);
            echo json_encode($card);
            break;
            
        case 'edit_card':
            $controller->editCard();
            break;
            
        case 'pay_invoice':
            $controller->payInvoice();
            break;
            
        case 'delete':
            if (isset($_GET['id'])) {
                $controller->deleteCard($_GET['id']);
            }
            break;
            
        default:
            $_SESSION['error_message'] = 'Ação inválida';
            header('Location: ' . BASE_URL . '/views/credit_cards/index.php');
            exit;
    }
} else {
    $_SESSION['error_message'] = 'Ação não especificada';
    header('Location: ' . BASE_URL . '/views/credit_cards/index.php');
    exit;
}
