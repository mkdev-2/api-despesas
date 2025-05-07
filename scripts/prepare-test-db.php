<?php
// Script para executar migrações no banco de dados de teste em ordem específica

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Definir aliases
Yii::setAlias('@tests', __DIR__ . '/../tests');

// Carrega apenas as configurações essenciais para o console
$db = require __DIR__ . '/../config/test_db.php';
$config = [
    'id' => 'basic-tests-console',
    'basePath' => dirname(__DIR__),
    'components' => [
        'db' => $db,
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
    ],
];

// Conectar ao banco de dados e verificar se existe
try {
    // Extrair o nome do banco de dados da string DSN
    preg_match('/dbname=([^;]*)/', $db['dsn'], $matches);
    $dbName = $matches[1];
    
    // Conectar sem especificar o banco de dados
    $dsn = str_replace("dbname=$dbName", '', $db['dsn']);
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    
    // Dropar o banco de dados se existir
    $pdo->exec("DROP DATABASE IF EXISTS $dbName");
    echo "Banco de dados $dbName removido.\n";
    
    // Criar banco de dados
    $pdo->exec("CREATE DATABASE $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Banco de dados $dbName criado.\n";
    
    // Selecionar banco de dados
    $pdo->exec("USE $dbName");
    
    // Criar tabela de usuários manualmente para evitar problemas de ordem
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(255) NOT NULL,
          `email` varchar(255) NOT NULL,
          `auth_key` varchar(32) NOT NULL,
          `password_hash` varchar(255) NOT NULL,
          `password_reset_token` varchar(255) DEFAULT NULL,
          `status` tinyint(1) NOT NULL DEFAULT 10,
          `created_at` int(11) NOT NULL,
          `updated_at` int(11) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`),
          UNIQUE KEY `email` (`email`),
          UNIQUE KEY `password_reset_token` (`password_reset_token`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    echo "Tabela de usuários criada com sucesso.\n";
    
} catch (PDOException $e) {
    echo "Erro ao conectar ou manipular o banco de dados: " . $e->getMessage() . "\n";
    exit(1);
}

// Executar as migrações
$application = new yii\console\Application($config);
$exitCode = $application->runAction('migrate', ['interactive' => false]);

echo "Banco de dados de testes preparado com sucesso.\n";
exit($exitCode); 