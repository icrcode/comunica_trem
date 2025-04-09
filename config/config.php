<?php
// Configurações globais do sistema
define('APP_NAME', 'Controle de Trem MQTT');
define('APP_VERSION', '2.0');
define('DEBUG_MODE', true);  // Alterar para false em produção
define('LOG_DIR', __DIR__ . '/../logs/');
define('LOG_FILE', LOG_DIR . 'activity.log');

// Verificar e criar diretório de logs se não existir
if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// Definir manipulador de erros
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_message = date('[Y-m-d H:i:s]') . " Error ($errno): $errstr in $errfile on line $errline";
    error_log($error_message . PHP_EOL, 3, LOG_FILE);
    
    if (DEBUG_MODE) {
        echo "<div class='error-message'>$error_message</div>";
    } else {
        echo "<div class='error-message'>Ocorreu um erro no sistema. Verifique os logs para mais detalhes.</div>";
    }
    
    // Não execute o manipulador de erros interno do PHP
    return true;
}

// Definir manipulador de exceções
function customExceptionHandler($exception) {
    $error_message = date('[Y-m-d H:i:s]') . " Exceção: " . $exception->getMessage() . 
                    " em " . $exception->getFile() . " na linha " . $exception->getLine();
    error_log($error_message . PHP_EOL, 3, LOG_FILE);
    
    if (DEBUG_MODE) {
        echo "<div class='error-message'>$error_message</div>";
    } else {
        echo "<div class='error-message'>Ocorreu um erro no sistema. Verifique os logs para mais detalhes.</div>";
    }
    
    exit(1);
}

// Registrar manipuladores
set_error_handler("customErrorHandler");
set_exception_handler("customExceptionHandler");

// Configurações de sessão segura
ini_set('session.cookie_httponly', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
session_start();

// Função para registro de atividades
function logActivity($message, $level = 'INFO') {
    $log_message = date('[Y-m-d H:i:s]') . " [$level] " . $message;
    error_log($log_message . PHP_EOL, 3, LOG_FILE);
    return true;
}