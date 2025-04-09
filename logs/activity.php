<?php
/**
 * Activity Manager - Sistema de gerenciamento de atividades
 * Sistema de Controle de Trem MQTT
 * 
 * Este arquivo gerencia o registro e recuperação de atividades do sistema,
 * fornecendo uma interface para registrar ações dos usuários, eventos do sistema,
 * e atividades MQTT relacionadas aos trens.
 */

// Incluir as configurações globais
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/logger.php';

class ActivityManager {
    private $logger;
    private $allowedTypes = [
        'login',           // Atividades de login/logout
        'user_action',     // Ações realizadas por usuários
        'system',          // Eventos do sistema
        'mqtt',            // Comunicações MQTT
        'train',           // Eventos relacionados aos trens
        'error',           // Erros e falhas
        'security'         // Eventos de segurança
    ];
    
    /**
     * Construtor da classe ActivityManager
     */
    public function __construct() {
        $this->logger = new Logger();
    }
    
    /**
     * Registra uma nova atividade do sistema
     * 
     * @param string $type Tipo da atividade (login, user_action, system, mqtt, train, error, security)
     * @param string $message Descrição da atividade
     * @param array $data Dados adicionais relacionados à atividade (opcional)
     * @param string $level Nível de importância da atividade (INFO, DEBUG, WARNING, ERROR)
     * @return bool Sucesso ou falha da operação
     */
    public function record($type, $message, array $data = [], $level = 'INFO') {
        // Verificar se o tipo é válido
        if (!in_array($type, $this->allowedTypes)) {
            return false;
        }
        
        // Formatar a mensagem com o tipo
        $formattedMessage = "[$type] $message";
        
        // Registrar a atividade com o logger
        switch ($level) {
            case 'DEBUG':
                return $this->logger->debug($formattedMessage, $data);
            case 'WARNING':
                return $this->logger->warning($formattedMessage, $data);
            case 'ERROR':
                return $this->logger->error($formattedMessage, $data);
            case 'CRITICAL':
                return $this->logger->critical($formattedMessage, $data);
            case 'INFO':
            default:
                return $this->logger->info($formattedMessage, $data);
        }
    }
    
    /**
     * Registra uma atividade de login
     * 
     * @param string $username Nome do usuário
     * @param bool $success Se o login foi bem-sucedido
     * @param string $ipAddress Endereço IP do usuário (opcional)
     * @return bool Sucesso ou falha da operação
     */
    public function recordLogin($username, $success = true, $ipAddress = null) {
        $ipAddress = $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $status = $success ? 'bem-sucedido' : 'falhou';
        $message = "Login $status para o usuário '$username'";
        $level = $success ? 'INFO' : 'WARNING';
        
        return $this->record('login', $message, [
            'username' => $username,
            'ip' => $ipAddress,
            'success' => $success
        ], $level);
    }
    
    /**
     * Registra uma atividade de logout
     * 
     * @param string $username Nome do usuário
     * @return bool Sucesso ou falha da operação
     */
    public function recordLogout($username) {
        $message = "Logout realizado para o usuário '$username'";
        
        return $this->record('login', $message, [
            'username' => $username
        ]);
    }
    
    /**
     * Registra uma ação realizada por um usuário
     * 
     * @param string $username Nome do usuário
     * @param string $action Descrição da ação realizada
     * @param array $details Detalhes adicionais da ação (opcional)
     * @return bool Sucesso ou falha da operação
     */
    public function recordUserAction($username, $action, array $details = []) {
        $message = "Usuário '$username' $action";
        
        return $this->record('user_action', $message, $details);
    }
    
    /**
     * Registra um evento do sistema
     * 
     * @param string $component Componente do sistema
     * @param string $event Descrição do evento
     * @param string $level Nível de importância (INFO, DEBUG, WARNING, ERROR)
     * @param array $data Dados adicionais (opcional)
     * @return bool Sucesso ou falha da operação
     */
    public function recordSystem($component, $event, $level = 'INFO', array $data = []) {
        $message = "[$component] $event";
        
        return $this->record('system', $message, $data, $level);
    }
    
    /**
     * Registra uma comunicação MQTT
     * 
     * @param string $topic Tópico MQTT
     * @param string $message Mensagem transmitida/recebida
     * @param bool $isIncoming Se a mensagem é recebida (true) ou enviada (false)
     * @param string $level Nível de importância (INFO, DEBUG, WARNING, ERROR)
     * @return bool Sucesso ou falha da operação
     */
    public function recordMqtt($topic, $message, $isIncoming = true, $level = 'DEBUG') {
        $direction = $isIncoming ? 'recebida' : 'enviada';
        $logMessage = "Mensagem MQTT $direction no tópico '$topic'";
        
        return $this->record('mqtt', $logMessage, [
            'topic' => $topic,
            'message' => $message,
            'direction' => $direction
        ], $level);
    }
    
    /**
     * Registra um evento relacionado a um trem
     * 
     * @param string $trainId Identificador do trem
     * @param string $event Descrição do evento
     * @param array $details Detalhes adicionais (opcional)
     * @param string $level Nível de importância (INFO, DEBUG, WARNING, ERROR)
     * @return bool Sucesso ou falha da operação
     */
    public function recordTrainEvent($trainId, $event, array $details = [], $level = 'INFO') {
        $message = "Trem #$trainId: $event";
        
        return $this->record('train', $message, $details, $level);
    }
    
    /**
     * Registra um erro ou falha
     * 
     * @param string $component Componente que gerou o erro
     * @param string $errorMessage Mensagem de erro
     * @param array $context Contexto do erro (opcional)
     * @param string $level Nível de importância (WARNING, ERROR, CRITICAL)
     * @return bool Sucesso ou falha da operação
     */
    public function recordError($component, $errorMessage, array $context = [], $level = 'ERROR') {
        $message = "Erro em '$component': $errorMessage";
        
        return $this->record('error', $message, $context, $level);
    }
    
    /**
     * Registra um evento de segurança
     * 
     * @param string $event Descrição do evento de segurança
     * @param array $details Detalhes do evento (opcional)
     * @param string $level Nível de importância (WARNING, ERROR)
     * @return bool Sucesso ou falha da operação
     */
    public function recordSecurityEvent($event, array $details = [], $level = 'WARNING') {
        return $this->record('security', $event, $details, $level);
    }
    
    /**
     * Recupera atividades do sistema
     * 
     * @param string $type Tipo de atividade a filtrar (opcional)
     * @param string $search Termo para pesquisar nas atividades (opcional)
     * @param int $limit Número máximo de registros (opcional)
     * @param bool $includeBackups Incluir arquivos de backup na busca (opcional)
     * @return array Lista de atividades
     */
    public function getActivities($type = null, $search = null, $limit = 100, $includeBackups = false) {
        $activities = [];
        $logs = $this->logger->getLogEntries(null, $search, $limit, $includeBackups);
        
        foreach ($logs as $log) {
            // Extrair o tipo da atividade da mensagem
            if (preg_match('/\[(.*?)\]\s+(.*)/', $log['message'], $matches)) {
                $extractedType = $matches[1];
                $message = $matches[2];
                
                // Filtrar por tipo se especificado
                if ($type !== null && $extractedType !== $type) {
                    continue;
                }
                
                // Adicionar a atividade à lista
                $activities[] = [
                    'timestamp' => $log['timestamp'],
                    'level' => $log['level'],
                    'type' => $extractedType,
                    'message' => $message
                ];
            }
        }
        
        return $activities;
    }
    
    /**
     * Exporta atividades para um arquivo CSV
     * 
     * @param string $outputFile Caminho para o arquivo CSV
     * @param string $type Tipo de atividade a filtrar (opcional)
     * @param string $search Termo para pesquisar nas atividades (opcional)
     * @param bool $includeBackups Incluir arquivos de backup na busca (opcional)
     * @return bool Sucesso ou falha da operação
     */
    public function exportActivitiesToCSV($outputFile, $type = null, $search = null, $includeBackups = false) {
        $activities = $this->getActivities($type, $search, PHP_INT_MAX, $includeBackups);
        
        if (empty($activities)) {
            return false;
        }
        
        $fp = fopen($outputFile, 'w');
        
        if ($fp === false) {
            return false;
        }
        
        // Escrever cabeçalho do CSV
        fputcsv($fp, ['Data/Hora', 'Nível', 'Tipo', 'Mensagem']);
        
        // Escrever dados
        foreach ($activities as $activity) {
            fputcsv($fp, [
                $activity['timestamp'],
                $activity['level'],
                $activity['type'],
                $activity['message']
            ]);
        }
        
        fclose($fp);
        return true;
    }
}

// Exemplo de uso:
/*
$activity = new ActivityManager();

// Registrar login
$activity->recordLogin('joao_silva', true);

// Registrar ação do usuário
$activity->recordUserAction('joao_silva', 'alterou a velocidade do trem #1245', [
    'antiga_velocidade' => 60,
    'nova_velocidade' => 80
]);

// Registrar evento do sistema
$activity->recordSystem('inicialização', 'Sistema inicializado com sucesso');

// Registrar comunicação MQTT
$activity->recordMqtt('trens/1245/velocidade', '{"velocidade": 80, "timestamp": "2025-04-09T10:15:30"}', true);

// Registrar evento de trem
$activity->recordTrainEvent('1245', 'chegou à estação Central', [
    'horario_previsto' => '10:15',
    'horario_real' => '10:17',
    'atraso' => '2min'
]);

// Registrar erro
$activity->recordError('MQTTConnector', 'Falha na conexão com o broker', [
    'broker' => 'mqtt.example.com',
    'porta' => 1883,
    'erro' => 'Connection refused'
]);

// Registrar evento de segurança
$activity->recordSecurityEvent('Tentativa de acesso não autorizado', [
    'ip' => '192.168.1.25',
    'tentativas' => 5
]);

// Recuperar atividades
$trainActivities = $activity->getActivities('train');
*/