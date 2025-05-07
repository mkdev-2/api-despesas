<?php
// Script para executar migrações no banco de dados de teste

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

// Definir aliases
Yii::setAlias('@tests', __DIR__ . '/tests');

// Carrega apenas as configurações essenciais para o console
$db = require __DIR__ . '/config/test_db.php';
$config = [
    'id' => 'basic-tests-console',
    'basePath' => __DIR__,
    'components' => [
        'db' => $db,
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
    ],
];

$application = new yii\console\Application($config);
$exitCode = $application->runAction('migrate', ['interactive' => false]);

exit($exitCode); 