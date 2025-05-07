<?php
define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

// Primeiro, carrega o Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Agora, carrega o Yii framework
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Agora inicializa a aplicação Yii2
$config = require __DIR__ . '/../config/test.php';
new yii\console\Application($config);

// Agora sim, pode abrir a sessão (se necessário)
if (!Yii::$app->session->isActive) {
    Yii::$app->session->open();
}
