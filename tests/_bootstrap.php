<?php
define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);

// Desativa mensagens de depreciação e avisos para evitar problemas com "Headers already sent"
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Primeiro, carrega o Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Agora, carrega o Yii framework
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Agora inicializa a aplicação Yii2
$config = require __DIR__ . '/../config/test.php';
new yii\console\Application($config);

// Verifica se uma sessão já está em andamento antes de abrir
if (session_status() === PHP_SESSION_NONE) {
    // Não inicializa a sessão nos testes, usamos MockSession
    // Yii::$app->session->open();
}
