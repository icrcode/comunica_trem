<?php
// Configurações do MQTT
return [
    'server' => 'localhost',  // Altere para o IP do seu broker MQTT
    'port' => 1883,
    'client_id_prefix' => 'trem_dashboard_',
    'username' => '',  // Adicione username se seu broker exigir autenticação
    'password' => '',  // Adicione password se seu broker exigir autenticação
    'use_ssl' => false,  // Altere para true para usar TLS/SSL
    'topics' => [
        'command' => 'trem/controle',
        'speed' => 'trem/velocidade',
        'direction' => 'trem/direcao',
        'status' => 'trem/status',
        'emergency' => 'trem/emergencia',
        'stats' => 'trem/estatisticas'
    ],
    'qos' => 1,  // Qualidade de serviço: 0, 1 ou 2
    'keepalive' => 60
];