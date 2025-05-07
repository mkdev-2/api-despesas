<?php

use Dotenv\Dotenv;

$params = require __DIR__ . '/params.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad(); // ✅ Usa safeLoad() para evitar erros caso o .env não exista

// Garante que as variáveis sejam carregadas
foreach ($_ENV as $key => $value) {
    putenv("$key=$value");
}

// Definir a configuração do banco de dados dinamicamente
$db = [
    'class' => 'yii\db\Connection',
    'dsn' => "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']};charset={$_ENV['DB_CHARSET']}",
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => $_ENV['DB_CHARSET'],
    'tablePrefix' => '',
];

// Definir a configuração do banco de testes
$testDb = require __DIR__ . '/test_db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => (getenv('YII_ENV') === 'test') ? $testDb : $db,

    ],
    'params' => $params,

    // Adicionando comando personalizado para criar o banco de dados
    'controllerMap' => [
        'create-db' => 'app\commands\CreateDbController',
    ],
];

if (YII_ENV_DEV) {
    // Configuração para ambiente de desenvolvimento
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
    
    // Debug do Yii2
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];
}

return $config;
