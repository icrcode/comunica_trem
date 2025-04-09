<?php
require_once __DIR__ . '/../mqtt/phpMQTT.php';

$server = 'localhost';     // Endereço do servidor MQTT
$port = 1883;              // Porta padrão MQTT
$username = '';            // Usuário
$password = '';            // Senha
$client_id = 'phpMQTT-pub'; // ID único do cliente

$mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);

if ($mqtt->connect(true, NULL, $username, $password)) {
    $topic = 'meu/topico';
    $message = 'Olá do PHP via MQTT!';
    $mqtt->publish($topic, $message, 0);
    $mqtt->close();
} else {
    echo "Falha ao conectar...\n";
}
