<?php
/**
 * Arquivo de Bootstrap para a aplicação
 * 
 * Este arquivo é carregado antes da aplicação ser inicializada.
 * Aqui configuramos aliases, componentes globais, etc.
 */

Yii::setAlias('@app', dirname(__DIR__));
Yii::setAlias('@webroot', dirname(__DIR__) . '/web');
Yii::setAlias('@web', '/');

// Carregamento de variáveis de ambiente, se disponível
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
} 