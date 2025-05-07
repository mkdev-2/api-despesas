<?php
/**
 * Script para marcar migrações existentes como aplicadas
 * 
 * Este script deve ser executado quando as tabelas já foram criadas manualmente
 * e as migrações estão falhando pois as tabelas já existem.
 */

// Configuração para ambiente fora do Docker
if (!getenv('DOCKER_ENV')) {
    $host = 'localhost';
    $port = '3307';
    $dbname = 'gerenciamento_despesas';
    $username = 'despesas';
    $password = 'root';
    echo "Conectando ao banco de dados em $host:$port\n";
} else {
    // Configuração para ambiente Docker
    $host = getenv('DB_HOST') ?: 'despesas_db';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_DATABASE') ?: 'gerenciamento_despesas';
    $username = getenv('DB_USERNAME') ?: 'despesas';
    $password = getenv('DB_PASSWORD') ?: 'root';
    echo "Conectando ao banco de dados em $host:$port\n";
}

try {
    // Primeiro, tenta conectar direto ao banco gerenciamento_despesas
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Conectado ao banco de dados $dbname.\n";
    } catch (PDOException $e) {
        // Se não conseguir conectar, tenta conectar ao MySQL sem especificar o banco de dados
        // para verificar se o banco existe
        echo "Não foi possível conectar ao banco $dbname. Verificando conexão principal...\n";
        $dsn = "mysql:host=$host;port=$port";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verificar se o banco de dados existe
        $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
        if ($stmt->rowCount() === 0) {
            // Criar o banco de dados se não existir
            echo "Banco de dados $dbname não existe. Criando...\n";
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "Banco de dados $dbname criado com sucesso.\n";
        }
        
        // Conectar ao banco de dados recém-criado
        $pdo->exec("USE `$dbname`");
        echo "Conectado ao banco de dados $dbname.\n";
    }
    
    // Verificar se a tabela de migração existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'migration'");
    if ($stmt->rowCount() == 0) {
        // Criar a tabela de migração se não existir
        echo "Criando tabela de migração...\n";
        $pdo->exec("CREATE TABLE `migration` (
            `version` varchar(180) NOT NULL,
            `apply_time` int(11) DEFAULT NULL,
            PRIMARY KEY (`version`)
        )");
    }
    
    // Verificar se a tabela de usuários existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        // Criar a tabela de usuários se não existir
        echo "Criando tabela de usuários...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `email` varchar(255) NOT NULL,
            `auth_key` varchar(32) DEFAULT NULL,
            `password_hash` varchar(255) NOT NULL,
            `password_reset_token` varchar(255) DEFAULT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 10,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`),
            UNIQUE KEY `password_reset_token` (`password_reset_token`)
        )");
    }
    
    // Verificar se a tabela de despesas existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'despesas'");
    if ($stmt->rowCount() == 0) {
        // Criar a tabela de despesas se não existir
        echo "Criando tabela de despesas...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `despesas` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `descricao` varchar(255) NOT NULL,
            `categoria` varchar(50) NOT NULL,
            `valor` decimal(10,2) NOT NULL,
            `data` date NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_despesas_user_id` (`user_id`),
            KEY `idx_despesas_categoria` (`categoria`),
            KEY `idx_despesas_data` (`data`),
            KEY `idx_despesas_deleted_at` (`deleted_at`),
            CONSTRAINT `fk_despesas_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
    }
    
    // Listar todas as migrações a serem marcadas como aplicadas
    $migrations = [
        'm000000_000000_base', // Migração base Yii
        'm230101_000001_create_despesas_table',
        'm250118_194946_create_users_table',
        'm250118_195453_create_despesas_table',
        'm250118_201422_optimize_database'
    ];
    
    // Obter a hora atual
    $now = time();
    
    // Marcar cada migração como aplicada
    foreach ($migrations as $migration) {
        $stmt = $pdo->prepare("SELECT * FROM `migration` WHERE version = :version");
        $stmt->execute([':version' => $migration]);
        
        if ($stmt->rowCount() == 0) {
            // Inserir se não existir
            $stmt = $pdo->prepare("INSERT INTO `migration` (version, apply_time) VALUES (:version, :apply_time)");
            $stmt->execute([':version' => $migration, ':apply_time' => $now]);
            echo "Migração $migration marcada como aplicada.\n";
        } else {
            echo "Migração $migration já está marcada como aplicada.\n";
        }
    }
    
    // Verificar se existem usuários
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        // Criar usuário demo se não existir nenhum usuário
        echo "Não há usuários. Criando usuário demo...\n";
        $passwordHash = password_hash("demo123", PASSWORD_BCRYPT, ["cost" => 13]);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, auth_key, password_hash, status) VALUES ('demo', 'demo@example.com', 'test-auth-key', :password_hash, 10)");
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->execute();
        echo "Usuário demo criado com sucesso!\n";
    } else {
        echo "Existem $userCount usuários no sistema. Não é necessário criar o usuário demo.\n";
    }
    
    echo "Todas as migrações foram marcadas como aplicadas com sucesso!\n";
    
} catch (PDOException $e) {
    echo "Erro ao conectar/processar o banco de dados: " . $e->getMessage() . "\n";
    exit(1);
} 