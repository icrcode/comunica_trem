<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../mqtt/phpMQTT.php';

class MQTTHandler {
    private $mqtt;
    private $config;
    private $connected = false;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/mqtt_config.php';
        $client_id = $this->config['client_id_prefix'] . uniqid();
        
        try {
            $this->mqtt = new Bluerhinos\phpMQTT(
                $this->config['server'], 
                $this->config['port'], 
                $client_id
            );
            
            // Configurar keepalive
            $this->mqtt->keepalive = $this->config['keepalive'];
        } catch (Exception $e) {
            logActivity("Erro ao criar instância MQTT: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    public function connect() {
        try {
            $result = $this->mqtt->connect(
                true, 
                null, 
                $this->config['username'], 
                $this->config['password']
            );
            
            if ($result) {
                $this->connected = true;
                logActivity("Conectado ao broker MQTT: {$this->config['server']}:{$this->config['port']}", 'INFO');
                return true;
            } else {
                logActivity("Falha ao conectar ao broker MQTT", 'ERROR');
                return false;
            }
        } catch (Exception $e) {
            logActivity("Exceção ao conectar ao MQTT: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function publish($topic, $message, $retain = false) {
        if (!$this->connected && !$this->connect()) {
            return false;
        }
        
        $fullTopic = isset($this->config['topics'][$topic]) 
            ? $this->config['topics'][$topic] 
            : $topic;
            
        try {
            $this->mqtt->publish($fullTopic, $message, $this->config['qos'], $retain);
            logActivity("Mensagem publicada: $fullTopic = $message", 'DEBUG');
            return true;
        } catch (Exception $e) {
            logActivity("Erro ao publicar mensagem: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function subscribe($topics) {
        if (!$this->connected && !$this->connect()) {
            return false;
        }
        
        $topicList = [];
        foreach ($topics as $topic => $callback) {
            $fullTopic = isset($this->config['topics'][$topic]) 
                ? $this->config['topics'][$topic] 
                : $topic;
                
            $topicList[$fullTopic] = [
                'qos' => $this->config['qos'],
                'function' => $callback
            ];
        }
        
        try {
            $this->mqtt->subscribe($topicList, $this->config['qos']);
            logActivity("Inscrito em tópicos: " . implode(", ", array_keys($topicList)), 'DEBUG');
            return true;
        } catch (Exception $e) {
            logActivity("Erro ao se inscrever em tópicos: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function proc() {
        if (!$this->connected) {
            return false;
        }
        
        try {
            return $this->mqtt->proc();
        } catch (Exception $e) {
            logActivity("Erro ao processar mensagens MQTT: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function close() {
        if ($this->connected) {
            try {
                $this->mqtt->close();
                $this->connected = false;
                logActivity("Conexão MQTT fechada", 'DEBUG');
                return true;
            } catch (Exception $e) {
                logActivity("Erro ao fechar conexão MQTT: " . $e->getMessage(), 'ERROR');
                return false;
            }
        }
        return true;
    }
    
    public function __destruct() {
        $this->close();
    }
}