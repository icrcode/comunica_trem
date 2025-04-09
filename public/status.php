<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/mqtt_handler.php';

define('MQTT_TOPIC_EMERGENCY', 'train/emergency');
define('MQTT_TOPIC_CONTROL', 'train/control');

// Validar solicitação AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!$is_ajax) {
    http_response_code(403);
    exit('Acesso negado');
}

// Preparar resposta
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'timestamp' => time()
];

// Verificar tipo de solicitação
if (isset($_GET['check'])) {
    $check = filter_var($_GET['check'], FILTER_SANITIZE_STRING);
    
    // Verificar status MQTT
    if ($check === 'mqtt') {
        try {
            $mqtt = new MQTTHandler();
            $connected = $mqtt->connect();
            
            $response['success'] = true;
            $response['data'] = [
                'connected' => $connected
            ];
            
            // Fechar conexão
            $mqtt->close();
            
        } catch (Exception $e) {
            $response['message'] = 'Erro ao verificar status do MQTT: ' . $e->getMessage();
            logActivity("Erro ao verificar status MQTT: " . $e->getMessage(), 'ERROR');
        }
    }
    // Verificar status do trem
    elseif ($check === 'train') {
        // Usar uma variável de sessão para simular status
        // Em uma implementação real, você obteria dados de sensores via MQTT
        
        if (!isset($_SESSION['train_status'])) {
            $_SESSION['train_status'] = [
                'power' => false,
                'speed' => 0,
                'direction' => 'frente',
                'operation_time' => 0,
                'distance' => 0,
                'max_speed' => 0,
                'avg_speed' => 0,
                'started_at' => null
            ];
        }
        
        // Atualizar tempo de operação
        if ($_SESSION['train_status']['power'] && $_SESSION['train_status']['started_at']) {
            $elapsed = time() - $_SESSION['train_status']['started_at'];
            $_SESSION['train_status']['operation_time'] = $elapsed;
            
            // Atualizar distância (simulação)
            $speed_km_h = $_SESSION['train_status']['speed'];
            $hours = $elapsed / 3600;
            $_SESSION['train_status']['distance'] = round($speed_km_h * $hours, 2);
        }
        
        $response['success'] = true;
        $response['data'] = $_SESSION['train_status'];
    }
    else {
        $response['message'] = 'Verificação não reconhecida';
    }
}
// Atualizar status do trem (simulação)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Dados inválidos';
    } else {
        if (!isset($_SESSION['train_status'])) {
            $_SESSION['train_status'] = [
                'power' => false,
                'speed' => 0,
                'direction' => 'frente',
                'operation_time' => 0,
                'distance' => 0,
                'max_speed' => 0,
                'avg_speed' => 0,
                'started_at' => null
            ];
        }
        
        // Atualizar estado de energia
        if (isset($input['power'])) {
            $power = filter_var($input['power'], FILTER_VALIDATE_BOOLEAN);
            $_SESSION['train_status']['power'] = $power;
            
            if ($power && !$_SESSION['train_status']['started_at']) {
                $_SESSION['train_status']['started_at'] = time();
            } elseif (!$power) {
                $_SESSION['train_status']['started_at'] = null;
                $_SESSION['train_status']['speed'] = 0;
            }
        }
        
        // Atualizar velocidade
        if (isset($input['speed'])) {
            $speed = filter_var($input['speed'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 0, 'max_range' => 150]
            ]);
            
            if ($speed !== false) {
                $_SESSION['train_status']['speed'] = $speed;
                
                // Atualizar velocidade máxima
                if ($speed > $_SESSION['train_status']['max_speed']) {
                    $_SESSION['train_status']['max_speed'] = $speed;
                }
                
                // Velocidade média (simulação simples)
                $_SESSION['train_status']['avg_speed'] = 
                    ($_SESSION['train_status']['avg_speed'] + $speed) / 2;
            }
        }
        
        // Atualizar direção
        if (isset($input['direction'])) {
            $direction = filter_var($input['direction'], FILTER_SANITIZE_STRING);
            
            if (in_array($direction, ['frente', 'reverso'])) {
                $_SESSION['train_status']['direction'] = $direction;
            }
        }
        
        // Enviar dados para o MQTT (em uma aplicação real)
        if (isset($input['power']) || isset($input['speed']) || isset($input['direction'])) {
            try {
                $mqtt = new MQTTHandler();
                if ($mqtt->connect()) {
                    // Preparar mensagem de controle
                    $control_msg = json_encode([
                        'power' => $_SESSION['train_status']['power'],
                        'speed' => $_SESSION['train_status']['speed'],
                        'direction' => $_SESSION['train_status']['direction'],
                        'timestamp' => time()
                    ]);
                    
                    // Publicar no tópico de controle do trem
                    $mqtt->publish(MQTT_TOPIC_CONTROL, $control_msg);
                    
                    // Registrar atividade
                    logActivity("Enviado comando de controle: " . $control_msg, 'INFO');
                    
                    // Fechar conexão
                    $mqtt->close();
                }
            } catch (Exception $e) {
                $response['message'] = 'Aviso: Operação realizada, mas falha ao enviar para MQTT: ' . $e->getMessage();
                logActivity("Erro ao enviar dados para MQTT: " . $e->getMessage(), 'WARNING');
            }
        }
        
        $response['success'] = true;
        $response['data'] = $_SESSION['train_status'];
        $response['message'] = 'Status atualizado com sucesso';
    }
}
// Tratamento de emergência
elseif (isset($_GET['emergency'])) {
    $action = filter_var($_GET['emergency'], FILTER_SANITIZE_STRING);
    
    if ($action === 'stop') {
        // Parar o trem imediatamente
        $_SESSION['train_status']['speed'] = 0;
        $_SESSION['train_status']['power'] = false;
        
        // Enviar sinal de emergência via MQTT
        try {
            $mqtt = new MQTTHandler();
            if ($mqtt->connect()) {
                $emergency_msg = json_encode([
                    'emergency' => true,
                    'action' => 'stop',
                    'timestamp' => time()
                ]);
                
                // Publicar no tópico de emergência (prioridade alta)
                $mqtt->publish(MQTT_TOPIC_EMERGENCY, $emergency_msg, 2);
                
                // Registrar atividade crítica
                logActivity("EMERGÊNCIA: Parada de emergência acionada!", 'CRITICAL');
                
                // Fechar conexão
                $mqtt->close();
            }
        } catch (Exception $e) {
            // Ainda consideramos bem-sucedido no sistema local
            logActivity("EMERGÊNCIA: Falha ao enviar sinal MQTT: " . $e->getMessage(), 'ERROR');
        }
        
        $response['success'] = true;
        $response['message'] = 'Parada de emergência acionada!';
        $response['data'] = $_SESSION['train_status'];
    } else {
        $response['message'] = 'Ação de emergência não reconhecida';
    }
}
// Obter dados históricos (simulação)
elseif (isset($_GET['history'])) {
    $type = filter_var($_GET['history'], FILTER_SANITIZE_STRING);
    $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, ['options' => ['default' => 24, 'min_range' => 1, 'max_range' => 100]]) : 24;
    
    // Simular dados históricos
    $history = [];
    
    if ($type === 'speed') {
        // Gerar dados de velocidade simulados para as últimas 'limit' horas
        $current_time = time();
        for ($i = $limit - 1; $i >= 0; $i--) {
            $time_point = $current_time - ($i * 3600);
            $history[] = [
                'timestamp' => $time_point,
                'time' => date('H:i', $time_point),
                'value' => rand(0, 120) // Valor aleatório entre 0 e 120 km/h
            ];
        }
        
        $response['success'] = true;
        $response['data'] = [
            'type' => 'speed',
            'unit' => 'km/h',
            'records' => $history
        ];
    }
    elseif ($type === 'power') {
        // Gerar dados de consumo de energia simulados
        $current_time = time();
        for ($i = $limit - 1; $i >= 0; $i--) {
            $time_point = $current_time - ($i * 3600);
            $history[] = [
                'timestamp' => $time_point,
                'time' => date('H:i', $time_point),
                'value' => rand(50, 200) // Consumo entre 50 e 200 kWh
            ];
        }
        
        $response['success'] = true;
        $response['data'] = [
            'type' => 'power',
            'unit' => 'kWh',
            'records' => $history
        ];
    }
    else {
        $response['message'] = 'Tipo de histórico não reconhecido';
    }
}
// Solicitação não reconhecida
else {
    $response['message'] = 'Solicitação não reconhecida';
}

// Função para registrar atividades
function logActivity($message, $level = 'INFO') {
    // Em uma implementação real, isto poderia gravar em um arquivo de log 
    // ou banco de dados. Por enquanto, apenas simulamos.
    
    // Registrar na sessão para simulação
    if (!isset($_SESSION['activity_log'])) {
        $_SESSION['activity_log'] = [];
    }
    
    // Limitar tamanho do log para evitar problemas com a sessão
    if (count($_SESSION['activity_log']) > 100) {
        array_shift($_SESSION['activity_log']);
    }
    
    $_SESSION['activity_log'][] = [
        'timestamp' => time(),
        'message' => $message,
        'level' => $level
    ];
    
    // Em ambiente de produção, seria melhor usar o sistema de log do PHP
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("[TRAIN CONTROL][$level] $message");
    }
}

// Devolver resposta como JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;