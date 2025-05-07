<?php
// Define valores para conexão com o banco de dados de teste
$dbHost = getenv("DB_HOST") ?: "despesas_db";
$dbPort = getenv("DB_PORT") ?: "3306";
$dbName = getenv("DB_DATABASE_TEST") ?: "gerenciamento_despesas_test";

// Verifica se estamos em ambiente de CI para usar as credenciais de root
$isInCI = getenv('CI') === 'true' || getenv('GITHUB_ACTIONS') === 'true';

if ($isInCI) {
    // No CI, usamos o usuário root para garantir permissões suficientes
    $dbUser = "root";
    $dbPass = "root";
} else {
    $dbUser = getenv("DB_USERNAME") ?: "despesas";
    $dbPass = getenv("DB_PASSWORD") ?: "root";
}

$dbCharset = getenv("DB_CHARSET") ?: "utf8mb4";

return [
    "class" => "yii\\db\\Connection",
    "dsn" => "mysql:host=$dbHost;port=$dbPort;dbname=$dbName",
    "username" => $dbUser,
    "password" => $dbPass,
    "charset" => $dbCharset,
]; 