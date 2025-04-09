</main>
        <footer class="app-footer">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></p>
                <p class="mqtt-status">Status MQTT: <span id="mqtt-connection-status">Verificando...</span></p>
            </div>
        </footer>
    </div>
    
    <script>
        // Verifica status da conexão MQTT periodicamente
        function checkMQTTStatus() {
            fetch('status.php?check=mqtt')
                .then(response => response.json())
                .then(data => {
                    const statusElement = document.getElementById('mqtt-connection-status');
                    if (data.connected) {
                        statusElement.textContent = 'Conectado';
                        statusElement.className = 'connected';
                    } else {
                        statusElement.textContent = 'Desconectado';
                        statusElement.className = 'disconnected';
                    }
                })
                .catch(err => {
                    console.error('Erro ao verificar status MQTT:', err);
                    document.getElementById('mqtt-connection-status').textContent = 'Erro';
                });
        }
        
        // Verificar a cada 10 segundos
        checkMQTTStatus();
        setInterval(checkMQTTStatus, 10000);
    </script>
</body>
</html>