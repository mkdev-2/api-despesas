<?php
// Script para executar migrações no banco de dados de teste em ordem específica

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Definir aliases
Yii::setAlias('@tests', __DIR__ . '/../tests');

// Detectar o ambiente: Docker, CI ou Local
$isInDocker = getenv('DOCKER_ENV') === '1' || file_exists('/.dockerenv');
$isInCI = getenv('CI') === 'true' || getenv('GITHUB_ACTIONS') === 'true';

// Definir configurações de conexão com base no ambiente
if ($isInCI) {
    // Ambiente de CI (GitHub Actions ou outro CI)
    $host = '127.0.0.1'; // Usar IP em vez de 'localhost' para evitar sockets
    $port = getenv('DB_PORT') ?: '3306';
    $dbName = getenv('DB_DATABASE_TEST') ?: 'gerenciamento_despesas_test';
    // No CI, usamos o usuário root para garantir que temos permissões suficientes
    $username = 'root';
    $password = 'root';
    echo "Ambiente: CI\n";
} elseif ($isInDocker) {
    // Ambiente Docker
    $host = 'despesas_db';
    $port = 3306;
    $dbName = 'gerenciamento_despesas_test';
    $username = 'despesas';
    $password = 'root';
    echo "Ambiente: Dentro do contêiner Docker\n";
} else {
    // Ambiente local fora do Docker
    $host = '127.0.0.1'; // Usar IP em vez de 'localhost' para forçar TCP/IP
    $port = 3307;
    $dbName = 'gerenciamento_despesas_test';
    $username = 'despesas';
    $password = 'root';
    echo "Ambiente: Desenvolvimento local\n";
}

echo "Host: $host\n";
echo "Porta: $port\n";

try {
    // Tentar conectar diretamente via TCP/IP
    $dsn = "mysql:host=$host;port=$port";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5, // Timeout de conexão em segundos
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        // Forçar TCP/IP para evitar problemas com sockets
        PDO::MYSQL_ATTR_DIRECT_QUERY => false,
    ];
    
    echo "Tentando conectar ao servidor de banco de dados em $host:$port...\n";
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Verificar se o usuário tem privilégios para criar/modificar o banco de dados
    try {
        // Testar se o usuário tem permissão para criar/modificar o banco de dados
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Permissões verificadas, usuário tem privilégios adequados.\n";
    } catch (PDOException $e) {
        // Se não tiver permissão, tentar conectar como root
        echo "Usuário $username não tem permissão para criar o banco de dados. Tentando como root...\n";
        try {
            $rootUsername = 'root';
            $rootPassword = $isInCI ? 'password' : 'password'; // Ajustar conforme o ambiente
            $rootPdo = new PDO("mysql:host=$host;port=$port", $rootUsername, $rootPassword, $options);
            
            // Dropar o banco de dados se existir
            $rootPdo->exec("DROP DATABASE IF EXISTS `$dbName`");
            echo "Banco de dados $dbName removido.\n";
            
            // Criar banco de dados
            $rootPdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "Banco de dados $dbName criado.\n";
            
            // Garantir que o usuário tenha permissões adequadas
            $rootPdo->exec("GRANT ALL PRIVILEGES ON `$dbName`.* TO '$username'@'%'");
            $rootPdo->exec("FLUSH PRIVILEGES");
            echo "Permissões concedidas ao usuário $username.\n";
            
            // Fechar conexão root
            $rootPdo = null;
        } catch (PDOException $rootError) {
            echo "Erro ao tentar como root: " . $rootError->getMessage() . "\n";
            echo "Por favor, certifique-se de que o banco de dados existe e que o usuário $username tem privilégios adequados.\n";
            exit(1);
        }
    }
    
    // Selecionar banco de dados recém-criado
    $pdo->exec("USE `$dbName`");
    echo "Usando banco de dados $dbName.\n";
    
    // Criar tabela de usuários manualmente para evitar problemas de ordem
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
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
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `deleted_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_despesas_user_id` (`user_id`),
          KEY `idx_despesas_categoria` (`categoria`),
          KEY `idx_despesas_data` (`data`),
          KEY `idx_despesas_deleted_at` (`deleted_at`),
          CONSTRAINT `fk_despesas_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    echo "Tabela de despesas criada com sucesso.\n";
    
} catch (PDOException $e) {
    echo "Erro ao conectar ou manipular o banco de dados: " . $e->getMessage() . "\n";
    echo "Certifique-se que o serviço MySQL está rodando e acessível em $host:$port.\n";
    exit(1);
}

// Preencher a tabela de usuário com dados iniciais para os testes
try {
    // Gerar um hash de senha seguro para usuários de teste
    $passwordHash = password_hash("test123", PASSWORD_BCRYPT, ["cost" => 13]);
    
    // Adicionar usuários de teste
    $timestamp = date('Y-m-d H:i:s');
    $pdo->exec("
        INSERT INTO `users` (`id`, `username`, `email`, `auth_key`, `password_hash`, `status`, `created_at`, `updated_at`)
        VALUES
        (1, 'admin', 'admin@example.com', 'test100key', '$passwordHash', 10, '$timestamp', '$timestamp'),
        (2, 'demo', 'demo@example.com', 'test101key', '$passwordHash', 10, '$timestamp', '$timestamp'),
        (3, 'test', 'test@example.com', 'test102key', '$passwordHash', 10, '$timestamp', '$timestamp'),
        (4, 'user', 'user@example.com', 'test103key', '$passwordHash', 10, '$timestamp', '$timestamp')
        ON DUPLICATE KEY UPDATE `updated_at` = '$timestamp';
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
        ('m250118_195453_create_despesas_table', UNIX_TIMESTAMP()),
        ('m250118_201422_optimize_database', UNIX_TIMESTAMP())
        ON DUPLICATE KEY UPDATE `apply_time` = UNIX_TIMESTAMP();
    ");
    
    echo "Tabela de migração configurada com sucesso.\n";
    
} catch (PDOException $e) {
    echo "Erro ao configurar tabela de migração: " . $e->getMessage() . "\n";
    exit(1);
}

// Após a criação e configuração do banco de dados de teste
try {
    // Configurar o banco de dados para o Yii
    $db = [
        'class' => 'yii\db\Connection',
        'dsn' => "mysql:host=$host;port=$port;dbname=$dbName",
        'username' => $username,
        'password' => $password,
        'charset' => 'utf8mb4',
    ];

    // Se estamos no CI, garantir que o usuário 'despesas' tenha todas as permissões necessárias
    if ($isInCI) {
        echo "Configurando permissões adicionais para o ambiente de CI...\n";
        try {
            // Garantir que o usuário 'despesas' tenha todas as permissões no banco de dados de teste
            $pdo->exec("GRANT ALL PRIVILEGES ON `$dbName`.* TO 'despesas'@'%';");
            $pdo->exec("FLUSH PRIVILEGES;");
            echo "Permissões concedidas ao usuário 'despesas' para o banco de teste.\n";
        } catch (PDOException $e) {
            echo "Aviso: Erro ao conceder permissões: " . $e->getMessage() . "\n";
            // Não vamos falhar completamente devido a isso
        }
    }

    // Carrega apenas as configurações essenciais para o console
    $config = [
        'id' => 'basic-tests-console',
        'basePath' => dirname(__DIR__),
        'components' => [
            'db' => $db,
            'cache' => [
                'class' => 'yii\caching\FileCache',
            ],
        ],
        'modules' => [
            'api' => [
                'class' => 'app\modules\api\Module',
                'modules' => [
                    'v1' => [
                        'class' => 'app\modules\api\v1\Module',
                    ],
                ],
            ],
        ],
    ];

    // Executar apenas migrações que ainda não foram aplicadas
    $application = new yii\console\Application($config);
    $exitCode = $application->runAction('migrate', ['interactive' => false]);
    
    echo "Banco de dados de testes preparado com sucesso.\n";
    exit($exitCode);
} catch (Exception $e) {
    echo "Erro ao executar migrações Yii: " . $e->getMessage() . "\n";
    echo "Banco de dados configurado, mas algumas migrações podem não ter sido aplicadas.\n";
    exit(1);
} 