<?php
require('phpMQTT.php');

$server = 'localhost';
$port = 1883;
$username = '';
$password = '';
$client_id = 'phpMQTT-sub';

$mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);

if(!$mqtt->connect(true, NULL, $username, $password)) {
    exit("Falha na conexão");
}

$topics['meu/topico'] = [
    'qos' => 0,
    'function' => function($topic, $msg){
        echo "Recebido do tópico: $topic\nMensagem: $msg\n";
    }
];

$mqtt->subscribe($topics, 0);

while($mqtt->proc()){
    // Loop para processar as mensagens
}
$mqtt->close();
