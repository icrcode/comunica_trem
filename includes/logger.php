<?php
/**
 * Logger - Classe para gerenciamento avançado de logs
 * Sistema de Controle de Trem MQTT
 */

// Incluir as configurações globais
require_once __DIR__ . '/../config/config.php';

class Logger {
    // Níveis de log disponíveis
    const LEVEL_INFO = 'INFO';
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    
    private $logFile;
    private $maxLogSize; // em bytes
    private $keepBackups; // número de backups a manter
    
    /**
     * Construtor da classe Logger
     * 
     * @param string $logFile Caminho para o arquivo de log (opcional)
     * @param int $maxLogSize Tamanho máximo do arquivo de log em MB (opcional)
     * @param int $keepBackups Quantidade de arquivos de backup a manter (opcional)
     */
    public function __construct($logFile = null, $maxLogSize = 5, $keepBackups = 3) {
        $this->logFile = $logFile ?? LOG_FILE;
        $this->maxLogSize = $maxLogSize * 1024 * 1024; // Converter MB para bytes
        $this->keepBackups = $keepBackups;
        
        // Criar diretório de logs se não existir
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Verificar se o arquivo de log excede o tamanho máximo
        $this->rotateLogIfNeeded();
    }
    
    /**
     * Registra uma mensagem com nível INFO
     * 
     * @param string $message Mensagem a ser registrada
     * @param array $context Dados adicionais para o log (opcional)
     * @return bool Sucesso ou falha da operação
     */
    public function info($message, array $context = []) {
        return $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Registra uma mensagem com nível DEBUG
     * 
     * @param string $message Mensagem a ser registrada
     * @param array $context Dados adicionais para o log (opcional)
     * @return bool Sucesso ou falha da operação
     */
    public function debug($message, array $context = []) {
        return $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Registra uma mensagem com nível WARNING
     * 
     * @param string $message Mensagem a ser registrada
     * @param array $context Dados adicionais para o log (opcional)
     * @return bool Sucesso ou falha da operação
     */
    public function warning($message, array $context = []) {
        return $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Registra uma mensagem com nível ERROR
     * 
     * @param string $message Mensagem a ser registrada
     * @param array $context Dados adicionais para o log (opcional)
     * @return bool Sucesso ou falha da operação
     */
    public function error($message, array $context = []) {
        return $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Registra uma mensagem com nível CRITICAL
     * 
     * @param string $message Mensagem a ser registrada
     * @param array $context Dados adicionais para o log (opcional)
     * @return bool Sucesso ou falha da operação
     */
    public function critical($message, array $context = []) {
        return $this->log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Registra uma mensagem no arquivo de log
     * 
     * @param string $level Nível do log
     * @param string $message Mensagem a ser registrada
     * @param array $context Dados adicionais para o log (opcional)
     * @return bool Sucesso ou falha da operação
     */
    public function log($level, $message, array $context = []) {
        // Substituir placeholders na mensagem com os dados do contexto
        if (!empty($context)) {
            foreach ($context as $key => $value) {
                if (is_scalar($value) || is_null($value)) {
                    $message = str_replace("{{$key}}", $value, $message);
                } elseif (is_array($value) || is_object($value)) {
                    $message = str_replace("{{$key}}", json_encode($value), $message);
                }
            }
        }
        
        // Formatar mensagem de log
        $log_message = date('[Y-m-d H:i:s]') . " [$level] " . $message;
        
        // Adicionar informações do usuário se disponível
        if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
            $log_message .= " [Usuário: {$_SESSION['username']}]";
        }
        
        // Adicionar IP do cliente
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $log_message .= " [IP: {$_SERVER['REMOTE_ADDR']}]";
        }
        
        // Gravar no arquivo de log
        $result = error_log($log_message . PHP_EOL, 3, $this->logFile);
        
        // Verificar se o arquivo de log excede o tamanho máximo
        $this->rotateLogIfNeeded();
        
        return $result;
    }
    
    /**
     * Limpa o arquivo de log atual
     * 
     * @return bool Sucesso ou falha da operação
     */
    public function clearLog() {
        return file_put_contents($this->logFile, '') !== false;
    }
    
    /**
     * Rotaciona o arquivo de log se exceder o tamanho máximo
     */
    private function rotateLogIfNeeded() {
        if (!file_exists($this->logFile)) {
            return;
        }
        
        if (filesize($this->logFile) > $this->maxLogSize) {
            $this->rotateLog();
        }
    }
    
    /**
     * Rotaciona os arquivos de log
     */
    private function rotateLog() {
        $baseFilename = $this->logFile;
        $logDir = dirname($baseFilename);
        $baseFilenameOnly = basename($baseFilename);
        
        // Remover o backup mais antigo se exceder o limite
        $oldestBackup = $logDir . '/' . $baseFilenameOnly . '.' . $this->keepBackups;
        if (file_exists($oldestBackup)) {
            unlink($oldestBackup);
        }
        
        // Rotacionar backups existentes
        for ($i = $this->keepBackups - 1; $i >= 1; $i--) {
            $oldFile = $logDir . '/' . $baseFilenameOnly . '.' . $i;
            $newFile = $logDir . '/' . $baseFilenameOnly . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }
        
        // Mover o arquivo atual para .1
        $backupFile = $logDir . '/' . $baseFilenameOnly . '.1';
        if (file_exists($this->logFile)) {
            copy($this->logFile, $backupFile);
            $this->clearLog();
        }
        
        // Registrar a rotação do log
        $this->log(self::LEVEL_INFO, "Arquivo de log foi rotacionado devido ao tamanho excedido.");
    }
    
    /**
     * Extrai logs do arquivo baseado em critérios específicos
     * 
     * @param string $level Nível de log para filtrar (opcional)
     * @param string $search Termo para buscar nas mensagens (opcional)
     * @param int $limit Número máximo de registros (opcional)
     * @param bool $includeBackups Incluir arquivos de backup na busca (opcional)
     * @return array Registros de log encontrados
     */
    public function getLogEntries($level = null, $search = null, $limit = 1000, $includeBackups = false) {
        $logs = [];
        $filesCount = 0;
        $filesTotal = $includeBackups ? $this->keepBackups + 1 : 1;
        
        // Função para processar um arquivo de log
        $processLogFile = function($file) use (&$logs, &$filesCount, $filesTotal, $level, $search, $limit) {
            if (!file_exists($file)) {
                return;
            }
            
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            if ($lines === false) {
                return;
            }
            
            // Processar de baixo para cima (mais recente primeiro)
            $lines = array_reverse($lines);
            
            foreach ($lines as $line) {
                // Parar se atingir o limite
                if (count($logs) >= $limit) {
                    break;
                }
                
                // Extrair dados do log
                if (preg_match('/\[(.*?)\]\s+\[(.*?)\]\s+(.*)/', $line, $matches)) {
                    $timestamp = $matches[1];
                    $entryLevel = $matches[2];
                    $message = $matches[3];
                    
                    // Filtrar por nível se especificado
                    if ($level !== null && $entryLevel !== $level) {
                        continue;
                    }
                    
                    // Filtrar por termo de busca se especificado
                    if ($search !== null && stripos($message, $search) === false) {
                        continue;
                    }
                    
                    // Adicionar entrada ao resultado
                    $logs[] = [
                        'timestamp' => $timestamp,
                        'level' => $entryLevel,
                        'message' => $message
                    ];
                }
            }
            
            $filesCount++;
            
            // Se já temos registros suficientes, não processar mais arquivos
            return count($logs) >= $limit || $filesCount >= $filesTotal;
        };
        
        // Processar o arquivo principal primeiro
        $shouldStop = $processLogFile($this->logFile);
        
        // Processar backups se necessário
        if (!$shouldStop && $includeBackups) {
            $baseFilename = $this->logFile;
            $logDir = dirname($baseFilename);
            $baseFilenameOnly = basename($baseFilename);
            
            for ($i = 1; $i <= $this->keepBackups; $i++) {
                $backupFile = $logDir . '/' . $baseFilenameOnly . '.' . $i;
                $shouldStop = $processLogFile($backupFile);
                
                if ($shouldStop) {
                    break;
                }
            }
        }
        
        return $logs;
    }
    
    /**
     * Gera um arquivo CSV com os logs filtrados
     * 
     * @param string $outputFile Caminho para o arquivo CSV
     * @param string $level Nível de log para filtrar (opcional)
     * @param string $search Termo para buscar nas mensagens (opcional)
     * @param bool $includeBackups Incluir arquivos de backup na busca (opcional)
     * @return bool Sucesso ou falha da operação
     */
    public function exportToCSV($outputFile, $level = null, $search = null, $includeBackups = false) {
        $logs = $this->getLogEntries($level, $search, PHP_INT_MAX, $includeBackups);
        
        if (empty($logs)) {
            return false;
        }
        
        $fp = fopen($outputFile, 'w');
        
        if ($fp === false) {
            return false;
        }
        
        // Escrever cabeçalho do CSV
        fputcsv($fp, ['Data/Hora', 'Nível', 'Mensagem']);
        
        // Escrever dados
        foreach ($logs as $log) {
            fputcsv($fp, [$log['timestamp'], $log['level'], $log['message']]);
        }
        
        fclose($fp);
        return true;
    }
}

// Exemplo de uso:
/*
$logger = new Logger();
$logger->info("Conexão MQTT estabelecida com sucesso");
$logger->warning("Baixa velocidade detectada no trem #1245", ['velocidade' => '15km/h', 'min_esperado' => '30km/h']);
$logger->error("Falha na comunicação com o controlador");
$logger->debug("Dados recebidos: {dados}", ['dados' => json_encode(['velocidade' => 75, 'status' => 'em operação'])]);
*/