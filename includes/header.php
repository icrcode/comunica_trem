<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <!-- Adicionar favicon -->
    <link rel="icon" href="../assets/img/favicon.png" type="image/png">
    <!-- Meta tags para melhor compatibilidade mobile -->
    <meta name="theme-color" content="#121212">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body>
    <div class="app-container">
        <header class="app-header">
            <div class="logo">
                <h1><?php echo APP_NAME; ?> <span class="version">v<?php echo APP_VERSION; ?></span></h1>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>">Logs</a></li>
                </ul>
            </nav>
        </header>
        <main class="content">