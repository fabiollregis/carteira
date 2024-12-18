<?php
// Definir ambiente (development ou production)
define('ENVIRONMENT', 'development');

// Configurações específicas para cada ambiente
if (ENVIRONMENT === 'production') {
    // Configurações de Produção
    // Database configuration
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASSWORD') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'carteira_db');
    define('BASE_URL', '');              // URL base do site em produção
    
    // Desabilitar exibição de erros em produção
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    // Configurações de Desenvolvimento
    // Database configuration
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASSWORD') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'carteira_db');
    define('BASE_URL', getenv('BASE_URL') ?: '/carteira');
    
    // Habilitar exibição de erros em desenvolvimento
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Configurações globais
define('CHARSET', 'UTF-8');
define('TIMEZONE', 'America/Sao_Paulo');

// Definir timezone
date_default_timezone_set(TIMEZONE);

// Definir charset
ini_set('default_charset', CHARSET);
?>
