<?php
// Define valores para conexão com o banco de dados
$dbHost = getenv("DB_HOST") ?: "despesas_db";
$dbPort = getenv("DB_PORT") ?: "3306";
$dbName = getenv("DB_DATABASE") ?: "gerenciamento_despesas";
$dbUser = getenv("DB_USERNAME") ?: "despesas";
$dbPass = getenv("DB_PASSWORD") ?: "root";
$dbCharset = getenv("DB_CHARSET") ?: "utf8mb4";

return [
    "class" => "yii\\db\\Connection",
    "dsn" => "mysql:host=$dbHost;port=$dbPort;dbname=$dbName",
    "username" => $dbUser,
    "password" => $dbPass,
    "charset" => $dbCharset,
]; 