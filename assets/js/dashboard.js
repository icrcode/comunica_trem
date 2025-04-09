/**
 * Dashboard principal - Controle do Trem via MQTT
 * Inicializa e coordena os diversos componentes do dashboard
 */

// Estado atual do sistema
const trainState = {
    power: false,
    speed: 0,
    targetSpeed: 0,
    direction: 'frente',
    accelerationMode: 'slow',
    emergencyStop: false,
    stats: {
        operationTime: 0,
        distance: 0,
        maxSpeed: 0,
        avgSpeed: 0
    },
    lastUpdate: Date.now()
};

// Configurações do sistema
const config = {
    updateInterval: 500,     // Intervalo de atualização em ms
    statusCheckInterval: 3000,  // Intervalo de verificação de status em ms
    maxSpeed: 150,           // Velocidade máxima
    acceleration: {
        slow: 2,             // Alteração por intervalo no modo lento
        normal: 5,           // Alteração por intervalo no modo normal
        fast: 10             // Alteração por intervalo no modo rápido
    }
};

// Inicializar a aplicação quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    initializeControls();
    initializeCharts();
    setupEventListeners();
    startUpdateCycle();
    
    // Adicionar evento ao formulário para evitar submissão tradicional
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
        });
    });
    
    // Registrar evento no log
    addEventToLog('Dashboard inicializado');
});

// Configurar event listeners para controles da interface
function setupEventListeners() {
    // Botão de ligar/desligar
    const powerBtn = document.getElementById('powerBtn');
    powerBtn.addEventListener('click', function() {
        trainState.power = !trainState.power;
        updatePowerButtonUI();
        
        // Enviar comando para o servidor
        sendCommand('status', trainState.power ? 'on' : 'off')
            .then(response => {
                if (response.success) {
                    addEventToLog(trainState.power ? 'Sistema ligado' : 'Sistema desligado');
                    
                    // Atualizar estado dos botões de direção
                    document.getElementById('forwardBtn').disabled = !trainState.power;
                    document.getElementById('reverseBtn').disabled = !trainState.power;
                    document.getElementById('throttle').disabled = !trainState.power;
                    
                    // Se desligou, zerar velocidade
                    if (!trainState.power) {
                        trainState.targetSpeed = 0;
                        trainState.speed = 0;
                        updateSpeedUI();
                    }
                }
            })
            .catch(error => {
                addEventToLog(`Erro ao alterar estado de energia: ${error}`, 'error');
                // Reverter estado em caso de erro
                trainState.power = !trainState.power;
                updatePowerButtonUI();
            });
    });
    
    // Controle de velocidade
    const throttle = document.getElementById('throttle');
    throttle.addEventListener('input', function() {
        if (trainState.power) {
            trainState.targetSpeed = parseInt(this.value);
            updateSpeedUI(true); // Apenas atualizar exibição, não o valor real
        }
    });
    
    throttle.addEventListener('change', function() {
        if (trainState.power) {
            sendCommand('velocidade', this.value)
                .then(response => {
                    if (response.success) {
                        addEventToLog(`Velocidade ajustada para ${this.value}`);
                    }
                })
                .catch(error => {
                    addEventToLog(`Erro ao ajustar velocidade: ${error}`, 'error');
                    // Reverter em caso de erro
                    this.value = trainState.speed;
                    trainState.targetSpeed = trainState.speed;
                    updateSpeedUI();
                });
        }
    });
    
    // Botões de direção
    document.getElementById('forwardBtn').addEventListener('click', function() {
        if (trainState.power) {
            setDirection('frente');
        }
    });
    
    document.getElementById('reverseBtn').addEventListener('click', function() {
        if (trainState.power) {
            setDirection('re');
        }
    });
    
    // Botão de emergência
    document.getElementById('emergencyBtn').addEventListener('click', function() {
        activateEmergencyStop();
    });
    
    // Opções de aceleração
    const accOptions = document.querySelectorAll('input[name="acceleration"]');
    accOptions.forEach(option => {
        option.addEventListener('change', function() {
            if (this.checked) {
                trainState.accelerationMode = this.value;
                sendCommand('aceleracao', this.value)
                    .then(response => {
                        if (response.success) {
                            addEventToLog(`Modo de aceleração alterado para: ${getAccelerationLabel(this.value)}`);
                        }
                    })
                    .catch(error => {
                        addEventToLog(`Erro ao alterar modo de aceleração: ${error}`, 'error');
                    });
            }
        });
    });
}

// Configurar os controles iniciais
function initializeControls() {
    updatePowerButtonUI();
    updateSpeedUI();
    updateDirectionUI();
    
    // Desabilitar controles inicialmente (sistema começa desligado)
    document.getElementById('forwardBtn').disabled = true;
    document.getElementById('reverseBtn').disabled = true;
    document.getElementById('throttle').disabled = true;
}

// Ciclo de atualização para alterações graduais de velocidade
function startUpdateCycle() {
    // Atualizar velocidade gradualmente baseado no modo de aceleração
    setInterval(function() {
        if (trainState.power && !trainState.emergencyStop) {
            if (trainState.speed !== trainState.targetSpeed) {
                const accelerationRate = config.acceleration[trainState.accelerationMode];
                
                if (trainState.speed < trainState.targetSpeed) {
                    trainState.speed = Math.min(trainState.speed + accelerationRate, trainState.targetSpeed);
                } else {
                    trainState.speed = Math.max(trainState.speed - accelerationRate, trainState.targetSpeed);
                }
                
                updateSpeedUI();
                updateSpeedChart(trainState.speed);
            }
        }
    }, config.updateInterval);
    
    // Verificar status do trem periodicamente
    setInterval(checkTrainStatus, config.statusCheckInterval);
}

// Atualizar UI do botão de ligar/desligar
function updatePowerButtonUI() {
    const powerBtn = document.getElementById('powerBtn');
    const statusIndicator = document.getElementById('status-indicator');
    
    if (trainState.power) {
        powerBtn.classList.remove('power-off');
        powerBtn.classList.add('power-on');
        powerBtn.querySelector('.power-text').textContent = 'Ligado';
        
        statusIndicator.querySelector('.status-dot').classList.remove('off');
        statusIndicator.querySelector('.status-dot').classList.add('on');
        statusIndicator.querySelector('.status-text').textContent = 'Conectado';
    } else {
        powerBtn.classList.remove('power-on');
        powerBtn.classList.add('power-off');
        powerBtn.querySelector('.power-text').textContent = 'Desligado';
        
        statusIndicator.querySelector('.status-dot').classList.remove('on');
        statusIndicator.querySelector('.status-dot').classList.add('off');
        statusIndicator.querySelector('.status-text').textContent = 'Desconectado';
    }
}

// Atualizar UI de velocidade
function updateSpeedUI(onlyDisplay = false) {
    const speedValue = document.getElementById('speedValue');
    speedValue.textContent = Math.round(trainState.speed);
    
    if (!onlyDisplay) {
        document.getElementById('throttle').value = trainState.speed;
    }
}

// Atualizar UI de direção
function updateDirectionUI() {
    const forwardBtn = document.getElementById('forwardBtn');
    const reverseBtn = document.getElementById('reverseBtn');
    
    if (trainState.direction === 'frente') {
        forwardBtn.classList.add('active');
        reverseBtn.classList.remove('active');
    } else {
        forwardBtn.classList.remove('active');
        reverseBtn.classList.add('active');
    }
}

// Definir direção do trem
function setDirection(direction) {
    if (trainState.direction !== direction) {
        // Verificar se precisa parar antes de mudar direção
        if (trainState.speed > 0) {
            addEventToLog("Reduzindo velocidade para mudar direção", "warning");
            trainState.targetSpeed = 0;
            
            // Aguardar velocidade chegar a zero antes de mudar direção
            const directionCheckInterval = setInterval(() => {
                if (trainState.speed <= 1) {
                    clearInterval(directionCheckInterval);
                    completeDirectionChange(direction);
                }
            }, 100);
        } else {
            completeDirectionChange(direction);
        }
    }
}

// Completar a mudança de direção após redução de velocidade
function completeDirectionChange(direction) {
    trainState.direction = direction;
    updateDirectionUI();
    
    sendCommand('direcao', direction)
        .then(response => {
            if (response.success) {
                addEventToLog(`Direção alterada para: ${direction === 'frente' ? 'frente' : 'ré'}`);
            }
        })
        .catch(error => {
            addEventToLog(`Erro ao alterar direção: ${error}`, 'error');
            // Reverter em caso de erro
            trainState.direction = trainState.direction === 'frente' ? 're' : 'frente';
            updateDirectionUI();
        });
}

// Ativar parada de emergência
function activateEmergencyStop() {
    trainState.emergencyStop = true;
    trainState.targetSpeed = 0;
    trainState.speed = 0;
    updateSpeedUI();
    
    // Desabilitar controles
    document.getElementById('throttle').disabled = true;
    document.getElementById('forwardBtn').disabled = true;
    document.getElementById('reverseBtn').disabled = true;
    
    // Aplicar classe visual de emergência
    document.body.classList.add('emergency-active');
    
    sendCommand('emergencia', 'true')
        .then(response => {
            if (response.success) {
                addEventToLog("PARADA DE EMERGÊNCIA ATIVADA", "emergency");
            }
        })
        .catch(error => {
            addEventToLog(`Erro ao ativar parada de emergência: ${error}`, 'error');
        });
        
    // Para desativar a emergência, seria necessário reiniciar o sistema
    setTimeout(() => {
        trainState.power = false;
        updatePowerButtonUI();
        document.body.classList.remove('emergency-active');
        trainState.emergencyStop = false;
        addEventToLog("Sistema desligado após parada de emergência");
    }, 2000);
}

// Verificar status do trem no servidor
function checkTrainStatus() {
    fetch('status.php?check=train')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                updateStatistics(data.data);
            }
        })
        .catch(error => {
            console.error('Erro ao verificar status do trem:', error);
        });
}

// Atualizar estatísticas exibidas
function updateStatistics(stats) {
    // Formatar tempo de operação
    const hours = Math.floor(stats.operation_time / 3600);
    const minutes = Math.floor((stats.operation_time % 3600) / 60);
    const seconds = stats.operation_time % 60;
    const timeFormatted = 
        String(hours).padStart(2, '0') + ':' + 
        String(minutes).padStart(2, '0') + ':' + 
        String(seconds).padStart(2, '0');
    
    document.getElementById('operationTime').textContent = timeFormatted;
    document.getElementById('distance').textContent = stats.distance.toFixed(2) + ' km';
    document.getElementById('maxSpeed').textContent = stats.max_speed + ' km/h';
    document.getElementById('avgSpeed').textContent = Math.round(stats.avg_speed) + ' km/h';
    
    // Atualizar estado local
    trainState.stats = {
        operationTime: stats.operation_time,
        distance: stats.distance,
        maxSpeed: stats.max_speed,
        avgSpeed: stats.avg_speed
    };
}

// Adicionar evento ao log de eventos
function addEventToLog(message, type = 'info') {
    const eventsLog = document.getElementById('eventsLog');
    const eventItem = document.createElement('div');
    
    eventItem.classList.add('event-item');
    eventItem.classList.add(type);
    
    // Adicionar timestamp
    const now = new Date();
    const timestamp = now.toTimeString().split(' ')[0];
    
    eventItem.textContent = `[${timestamp}] ${message}`;
    
    // Adicionar ao topo da lista
    eventsLog.insertBefore(eventItem, eventsLog.firstChild);
    
    // Limitar número de itens no log
    if (eventsLog.children.length > 50) {
        eventsLog.removeChild(eventsLog.lastChild);
    }
}

// Enviar comando para o servidor
async function sendCommand(type, value) {
    const data = {};
    data[type] = value;
    
    try {
        const response = await fetch('enviar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        
        return await response.json();
    } catch (error) {
        console.error('Erro ao enviar comando:', error);
        throw error;
    }
}

// Obter label para modo de aceleração
function getAccelerationLabel(mode) {
    const labels = {
        'slow': 'Suave',
        'normal': 'Normal',
        'fast': 'Rápida'
    };
    
    return labels[mode] || mode;
}