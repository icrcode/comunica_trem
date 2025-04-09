<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/mqtt_handler.php';

// Validar solicitação AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Preparar resposta
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Validar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método não permitido';
    sendResponse($response, 405);
    exit;
}

// Processar dados de entrada
$input = json_decode(file_get_contents('php://input'), true);
if (!$input && empty($_POST)) {
    $response['message'] = 'Dados inválidos';
    sendResponse($response, 400);
    exit;
}

// Usar $_POST se $input estiver vazio
if (!$input) {
    $input = $_POST;
}

// Instanciar manipulador MQTT
try {
    $mqtt = new MQTTHandler();
    
    // Processar comando de status (ligar/desligar)
    if (isset($input['status'])) {
        $status = filter_var($input['status'], FILTER_SANITIZE_STRING);
        
        if ($status === 'on' || $status === 'off') {
            $msg = $status === 'on' ? 'ligar' : 'desligar';
            if ($mqtt->publish('command', $msg)) {
                logActivity("Comando enviado: $msg", 'INFO');
                $response['success'] = true;
                $response['message'] = 'Comando enviado com sucesso';
                $response['data'] = ['status' => $status];
            } else {
                $response['message'] = 'Falha ao enviar comando';
            }
        } else {
            $response['message'] = 'Status inválido';
        }
    }
    
    // Processar comando de velocidade
    elseif (isset($input['velocidade'])) {
        $vel = filter_var($input['velocidade'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 150]
        ]);
        
        if ($vel !== false) {
            if ($mqtt->publish('speed', strval($vel))) {
                logActivity("Velocidade definida: $vel", 'INFO');
                $response['success'] = true;
                $response['message'] = 'Velocidade ajustada com sucesso';
                $response['data'] = ['velocidade' => $vel];
            } else {
                $response['message'] = 'Falha ao ajustar velocidade';
            }
        } else {
            $response['message'] = 'Velocidade inválida';
        }
    }
    
    // Processar comando de direção
    elseif (isset($input['direcao'])) {
        $dir = filter_var($input['direcao'], FILTER_SANITIZE_STRING);
        
        if ($dir === 'frente' || $dir === 're') {
            if ($mqtt->publish('direction', $dir)) {
                logActivity("Direção definida: $dir", 'INFO');
                $response['success'] = true;
                $response['message'] = 'Direção ajustada com sucesso';
                $response['data'] = ['direcao' => $dir];
            } else {
                $response['message'] = 'Falha ao ajustar direção';
            }
        } else {
            $response['message'] = 'Direção inválida';
        }
    }
    
    // Processar emergência
    elseif (isset($input['emergencia']) && $input['emergencia'] === 'true') {
        if ($mqtt->publish('emergency', 'stop', true)) {
            logActivity("EMERGÊNCIA acionada", 'WARNING');
            $response['success'] = true;
            $response['message'] = 'Parada de emergência acionada';
        } else {
            $response['message'] = 'Falha ao acionar parada de emergência';
        }
    }
    
    // Processar aceleração
    elseif (isset($input['aceleracao'])) {
        $acc = filter_var($input['aceleracao'], FILTER_SANITIZE_STRING);
        
        if (in_array($acc, ['slow', 'normal', 'fast'])) {
            if ($mqtt->publish('acceleration', $acc)) {
                logActivity("Aceleração definida: $acc", 'INFO');
                $response['success'] = true;
                $response['message'] = 'Modo de aceleração ajustado com sucesso';
                $response['data'] = ['aceleracao' => $acc];
            } else {
                $response['message'] = 'Falha ao ajustar modo de aceleração';
            }
        } else {
            $response['message'] = 'Modo de aceleração inválido';
        }
    }
    
    else {
        $response['message'] = 'Comando não reconhecido';
    }
    
    // Fechar conexão
    $mqtt->close();
    
} catch (Exception $e) {
    $response['message'] = 'Erro interno: ' . $e->getMessage();
    logActivity("Erro ao processar comando: " . $e->getMessage(), 'ERROR');
}

// Enviar resposta adequada
if ($is_ajax) {
    sendResponse($response);
} else {
    // Redirecionamento para casos de submissão de formulário tradicional
    header('Location: index.php' . ($response['success'] ? '' : '?error=' . urlencode($response['message'])));
    exit;
}

// Função para enviar resposta
function sendResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}