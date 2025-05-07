<?php
// Arquivo de configuração do banco de dados para testes
// Usamos MySQL para testes
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=' . $_ENV['TEST_DB_HOST'] . ';port=' . $_ENV['TEST_DB_PORT'] . ';dbname=' . $_ENV['TEST_DB_DATABASE'],
    'username' => $_ENV['TEST_DB_USERNAME'],
    'password' => $_ENV['TEST_DB_PASSWORD'],
    'charset' => $_ENV['TEST_DB_CHARSET'],
    'tablePrefix' => '',
    'enableSchemaCache' => false,
];
