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
        CREATE TABLE IF NOT EXISTS `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(255) NOT NULL,
          `email` varchar(255) NOT NULL,
          `auth_key` varchar(32) NOT NULL,
          `password_hash` varchar(255) NOT NULL,
          `password_reset_token` varchar(255) DEFAULT NULL,
          `status` tinyint(1) NOT NULL DEFAULT 10,
          `created_at` datetime NOT NULL,
          `updated_at` datetime NOT NULL,
          `deleted_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`),
          UNIQUE KEY `email` (`email`),
          UNIQUE KEY `password_reset_token` (`password_reset_token`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    echo "Tabela de usuários criada com sucesso.\n";
    
    // Criar a tabela de despesas manualmente para garantir integridade referencial
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `despesas` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `descricao` varchar(255) NOT NULL,
          `categoria` varchar(50) NOT NULL,
          `valor` decimal(10,2) NOT NULL,
          `data` date NOT NULL,
          `created_at` datetime NOT NULL,
          `updated_at` datetime NOT NULL,
          `deleted_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_despesas_user_id` (`user_id`),
          KEY `idx_despesas_categoria` (`categoria`),
          KEY `idx_despesas_data` (`data`),
          KEY `idx_despesas_deleted_at` (`deleted_at`),
          CONSTRAINT `fk-despesas-user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    echo "Tabela de despesas criada com sucesso.\n";
    
} catch (PDOException $e) {
    echo "Erro ao conectar ou manipular o banco de dados: " . $e->getMessage() . "\n";
    exit(1);
}

// Preencher a tabela de usuário com dados iniciais para os testes
try {
    // Adicionar usuário de teste para os fixtures
    $timestamp = date('Y-m-d H:i:s');
    $pdo->exec("
        INSERT INTO `users` (`id`, `username`, `email`, `auth_key`, `password_hash`, `status`, `created_at`, `updated_at`)
        VALUES
        (1, 'admin', 'admin@example.com', 'test100key', '$2y$13$F8oA1DnpOKY0zWB4W.RZXevrZr4Cvw4jc0t9/lg5fvK8R9aNbJ5rm', 10, '$timestamp', '$timestamp'),
        (2, 'demo', 'demo@example.com', 'test101key', '$2y$13$F8oA1DnpOKY0zWB4W.RZXevrZr4Cvw4jc0t9/lg5fvK8R9aNbJ5rm', 10, '$timestamp', '$timestamp'),
        (3, 'test', 'test@example.com', 'test102key', '$2y$13$F8oA1DnpOKY0zWB4W.RZXevrZr4Cvw4jc0t9/lg5fvK8R9aNbJ5rm', 10, '$timestamp', '$timestamp'),
        (4, 'user', 'user@example.com', 'test103key', '$2y$13$F8oA1DnpOKY0zWB4W.RZXevrZr4Cvw4jc0t9/lg5fvK8R9aNbJ5rm', 10, '$timestamp', '$timestamp')
    ");
    
    echo "Dados iniciais de usuários inseridos com sucesso.\n";
    
} catch (PDOException $e) {
    echo "Erro ao inserir dados iniciais: " . $e->getMessage() . "\n";
    exit(1);
}

// Criar tabela de migração para controle do Yii
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `migration` (
            `version` varchar(180) NOT NULL,
            `apply_time` int(11) NULL,
            PRIMARY KEY (`version`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Inserir as migrações que já foram aplicadas manualmente
    $pdo->exec("
        INSERT INTO `migration` (`version`, `apply_time`) VALUES
        ('m000000_000000_base', UNIX_TIMESTAMP()),
        ('m230101_000001_create_despesas_table', UNIX_TIMESTAMP()),
        ('m250118_194946_create_users_table', UNIX_TIMESTAMP()),
        ('m250118_195453_create_despesas_table', UNIX_TIMESTAMP())
    ");
    
    echo "Tabela de migração configurada com sucesso.\n";
    
} catch (PDOException $e) {
    echo "Erro ao configurar tabela de migração: " . $e->getMessage() . "\n";
    exit(1);
}

// Executar apenas migrações que ainda não foram aplicadas
$application = new yii\console\Application($config);
$exitCode = $application->runAction('migrate', ['interactive' => false]);

echo "Banco de dados de testes preparado com sucesso.\n";
exit($exitCode); 