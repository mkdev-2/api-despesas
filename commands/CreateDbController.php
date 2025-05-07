<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\db\Exception;
use Dotenv\Dotenv;

class CreateDbController extends Controller
{
    public function actionIndex()
    {
        // Carregar as configurações do .env
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();

        // Criar bancos de produção e teste
        $this->createDatabase($_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_DATABASE']);
        $this->createDatabase($_ENV['TEST_DB_HOST'], $_ENV['TEST_DB_PORT'], $_ENV['TEST_DB_USERNAME'], $_ENV['TEST_DB_PASSWORD'], $_ENV['TEST_DB_DATABASE']);
    }

    private function createDatabase($host, $port, $user, $password, $database)
    {
        try {
            echo "🔍 Conectando ao MySQL em $host:$port com usuário $user...\n";

            $pdo = new \PDO("mysql:host=$host;port=$port", $user, $password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            echo "✅ Conexão bem-sucedida! Criando o banco `$database`...\n";

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            echo "🎉 Banco de dados `$database` criado com sucesso!\n";
        } catch (\PDOException $e) {
            echo "❌ Erro ao criar o banco `$database`: " . $e->getMessage() . "\n";
        }
    }

}
