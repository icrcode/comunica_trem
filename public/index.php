<?php
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h2>Painel de Controle</h2>
        <div class="system-status">
            <div class="status-indicator" id="status-indicator">
                <span class="status-dot off"></span>
                <span class="status-text">Desconectado</span>
            </div>
            <button id="emergencyBtn" class="emergency-button">PARADA DE EMERGÊNCIA</button>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Painel de Controle Principal -->
        <div class="control-card">
            <h3>Controle Principal</h3>
            <div class="main-controls">
                <div class="power-control">
                    <button type="button" id="powerBtn" class="power-button power-off">
                        <span class="power-icon"></span>
                        <span class="power-text">Desligado</span>
                    </button>
                </div>
                
                <div class="direction-control">
                    <h4>Direção</h4>
                    <div class="direction-buttons">
                        <button type="button" id="forwardBtn" class="direction-btn forward" disabled>
                            <span class="direction-icon forward"></span>
                            <span>Frente</span>
                        </button>
                        <button type="button" id="reverseBtn" class="direction-btn reverse" disabled>
                            <span class="direction-icon reverse"></span>
                            <span>Ré</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Controle de Velocidade -->
        <div class="control-card">
            <h3>Velocidade</h3>
            <div class="speed-control">
                <div class="slider-container">
                    <div class="speed-display">
                        <div id="speedValue" class="speed-value">0</div>
                        <div class="speed-unit">km/h</div>
                    </div>
                    <input type="range" id="throttle" name="velocidade" min="0" max="150" value="0" step="1" disabled>
                    <div class="speed-limits">
                        <span>0</span>
                        <span>75</span>
                        <span>150</span>
                    </div>
                </div>
                
                <div class="acceleration-controls">
                    <div class="acceleration-option">
                        <input type="radio" id="accSlow" name="acceleration" value="slow" checked>
                        <label for="accSlow">Suave</label>
                    </div>
                    <div class="acceleration-option">
                        <input type="radio" id="accNormal" name="acceleration" value="normal">
                        <label for="accNormal">Normal</label>
                    </div>
                    <div class="acceleration-option">
                        <input type="radio" id="accFast" name="acceleration" value="fast">
                        <label for="accFast">Rápida</label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Velocidade -->
        <div class="control-card wide">
            <h3>Histórico de Velocidade</h3>
            <div class="chart-container">
                <canvas id="speedChart"></canvas>
            </div>
        </div>
        
        <!-- Estatísticas -->
        <div class="control-card">
            <h3>Estatísticas</h3>
            <div class="stats-container">
                <div class="stat-item">
                    <div class="stat-label">Tempo de operação</div>
                    <div class="stat-value" id="operationTime">00:00:00</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Distância</div>
                    <div class="stat-value" id="distance">0 km</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Velocidade máxima</div>
                    <div class="stat-value" id="maxSpeed">0 km/h</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Velocidade média</div>
                    <div class="stat-value" id="avgSpeed">0 km/h</div>
                </div>
            </div>
        </div>
        
        <!-- Registro de Eventos -->
        <div class="control-card">
            <h3>Eventos</h3>
            <div class="events-log" id="eventsLog">
                <div class="event-item">Sistema inicializado</div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script src="../assets/js/controls.js"></script>
<script src="../assets/js/charts.js"></script>
<script src="../assets/js/dashboard.js"></script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>