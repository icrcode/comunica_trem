<?php
require_once __DIR__ . '/../includes/header.php';

// Verificar e ler arquivo de log
$logs = [];
$log_file = LOG_FILE;

if (file_exists($log_file)) {
    $file_content = file_get_contents($log_file);
    $logs = explode("\n", $file_content);
    $logs = array_filter($logs); // Remover linhas vazias
    $logs = array_reverse($logs); // Mostrar mais recentes primeiro
}
?>

<div class="container">
    <div class="logs-header">
        <h2>Registros do Sistema</h2>
        <div class="controls">
            <div class="filter-controls">
                <label for="logFilter">Filtrar:</label>
                <input type="text" id="logFilter" placeholder="Digite para filtrar...">
            </div>
            <div class="level-filter">
                <label>Nível:</label>
                <div class="level-options">
                    <label class="level-option">
                        <input type="checkbox" value="INFO" checked> INFO
                    </label>
                    <label class="level-option">
                        <input type="checkbox" value="DEBUG" checked> DEBUG
                    </label>
                    <label class="level-option">
                        <input type="checkbox" value="WARNING" checked> WARNING
                    </label>
                    <label class="level-option">
                        <input type="checkbox" value="ERROR" checked> ERROR
                    </label>
                </div>
            </div>
            <button id="clearLogsBtn" class="secondary-button">Limpar Filtros</button>
            <?php if (count($logs) > 0): ?>
                <a href="logs.php?download=true" class="secondary-button">Baixar Logs</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="logs-container">
        <?php if (count($logs) > 0): ?>
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Nível</th>
                        <th>Mensagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        // Extrair informações do log
                        if (preg_match('/\[(.*?)\]\s+\[(.*?)\]\s+(.*)/', $log, $matches)) {
                            $timestamp = $matches[1];
                            $level = $matches[2];
                            $message = $matches[3];
                            
                            // Determinar classe de estilo baseado no nível
                            $levelClass = strtolower($level);
                        } else {
                            $timestamp = '';
                            $level = '';
                            $message = $log;
                            $levelClass = '';
                        }
                        ?>
                        <tr class="log-entry <?php echo $levelClass; ?>" data-level="<?php echo $level; ?>">
                            <td class="timestamp"><?php echo htmlspecialchars($timestamp); ?></td>
                            <td class="level"><?php echo htmlspecialchars($level); ?></td>
                            <td class="message"><?php echo htmlspecialchars($message); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-logs">
                Nenhum registro encontrado.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logFilter = document.getElementById('logFilter');
        const levelFilters = document.querySelectorAll('.level-option input');
        const clearBtn = document.getElementById('clearLogsBtn');
        
        // Função para filtrar logs
        function filterLogs() {
            const filterText = logFilter.value.toLowerCase();
            const enabledLevels = Array.from(levelFilters)
                .filter(input => input.checked)
                .map(input => input.value);
            
            document.querySelectorAll('.log-entry').forEach(entry => {
                const message = entry.querySelector('.message').textContent.toLowerCase();
                const level = entry.dataset.level;
                
                const matchesText = filterText === '' || message.includes(filterText);
                const matchesLevel = enabledLevels.includes(level) || level === '';
                
                entry.style.display = (matchesText && matchesLevel) ? '' : 'none';
            });
        }
        
        // Configurar event listeners
        logFilter.addEventListener('input', filterLogs);
        
        levelFilters.forEach(input => {
            input.addEventListener('change', filterLogs);
        });
        
        clearBtn.addEventListener('click', function() {
            logFilter.value = '';
            levelFilters.forEach(input => {
                input.checked = true;
            });
            filterLogs();
        });
    });
</script>

<?php
// Tratar download de logs
if (isset($_GET['download']) && $_GET['download'] === 'true') {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="train_system_logs_' . date('Y-m-d_H-i-s') . '.log"');
    header('Pragma: no-cache');
    
    if (file_exists($log_file)) {
        readfile($log_file);
    }
    exit;
}

require_once __DIR__ . '/../includes/footer.php';
?>