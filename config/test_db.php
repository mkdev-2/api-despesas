<?php
// Arquivo de configuração do banco de dados para testes
// Usamos MySQL para testes
use Dotenv\Dotenv;

// Carregar variáveis de ambiente
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Exception $e) {
    // Se não conseguir carregar o .env, continuamos com as variáveis de ambiente
}

// Verificar se estamos em ambiente de CI
$isInCI = getenv('CI') === 'true' || getenv('GITHUB_ACTIONS') === 'true';

// Obter os valores das variáveis de ambiente
$dbHost = $_ENV['TEST_DB_HOST'] ?? getenv('TEST_DB_HOST') ?? 'localhost';
$dbPort = $_ENV['TEST_DB_PORT'] ?? getenv('TEST_DB_PORT') ?? '3306';
$dbName = $_ENV['TEST_DB_DATABASE'] ?? getenv('TEST_DB_DATABASE') ?? 'gerenciamento_despesas_test';
$dbCharset = $_ENV['TEST_DB_CHARSET'] ?? getenv('TEST_DB_CHARSET') ?? 'utf8mb4';

// Definir usuário e senha com base no ambiente
if ($isInCI) {
    // No CI, usamos o usuário root para garantir permissões
    $dbUser = 'root';
    $dbPass = 'root';
} else {
    $dbUser = $_ENV['TEST_DB_USERNAME'] ?? getenv('TEST_DB_USERNAME') ?? 'despesas';
    $dbPass = $_ENV['TEST_DB_PASSWORD'] ?? getenv('TEST_DB_PASSWORD') ?? 'root';
}

// Exibir configuração para debugging em CI
if ($isInCI) {
    error_log("CI: Usando configuração de banco de teste: $dbHost:$dbPort/$dbName (usuário: $dbUser)");
}

return [
    'class' => 'yii\db\Connection',
    'dsn' => "mysql:host=$dbHost;port=$dbPort;dbname=$dbName",
    'username' => $dbUser,
    'password' => $dbPass,
    'charset' => $dbCharset,
    'tablePrefix' => '',
    'enableSchemaCache' => false,
];
