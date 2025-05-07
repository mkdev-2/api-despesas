<?php
// Script para inserir dados de teste no banco de dados de testes

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

// Definir aliases
Yii::setAlias('@tests', __DIR__ . '/tests');

// Carrega configurações para o console
$db = require __DIR__ . '/config/test_db.php';
$config = [
    'id' => 'basic-tests-console',
    'basePath' => __DIR__,
    'components' => [
        'db' => $db,
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
    ],
];

$application = new yii\console\Application($config);

// Conectar ao banco de dados
try {
    $connection = $application->db;
    
    // Verificar se já existem usuários
    $userCount = $connection->createCommand('SELECT COUNT(*) FROM user')->queryScalar();
    
    if ($userCount > 0) {
        echo "Já existem {$userCount} usuários no banco de dados de testes.\n";
    } else {
        // Inserir usuários de teste
        $time = time();
        $users = [
            [1, 'admin', 'admin@example.com', 'admin123', Yii::$app->security->generatePasswordHash('admin123'), $time, $time, 10],
            [2, 'demo', 'demo@example.com', 'demo123', Yii::$app->security->generatePasswordHash('demo123'), $time, $time, 10],
            [3, 'test', 'test@example.com', 'test123', Yii::$app->security->generatePasswordHash('test123'), $time, $time, 10],
            [4, 'user', 'user@example.com', 'user123', Yii::$app->security->generatePasswordHash('user123'), $time, $time, 10],
        ];
        
        foreach ($users as $user) {
            $connection->createCommand()->insert('user', [
                'id' => $user[0],
                'username' => $user[1],
                'email' => $user[2],
                'auth_key' => $user[3],
                'password_hash' => $user[4],
                'created_at' => $user[5],
                'updated_at' => $user[6],
                'status' => $user[7],
            ])->execute();
        }
        
        echo "Usuários de teste inseridos com sucesso.\n";
    }
    
    // Verificar inserção de usuários
    $userCount = $connection->createCommand('SELECT COUNT(*) FROM user')->queryScalar();
    echo "Total de usuários no banco de dados de testes: {$userCount}\n";
    
    // Inserir algumas despesas para os testes
    $despesasCount = $connection->createCommand('SELECT COUNT(*) FROM despesas')->queryScalar();
    
    if ($despesasCount > 0) {
        echo "Já existem {$despesasCount} despesas no banco de dados de testes.\n";
    } else {
        // Inserir despesas de teste
        $despesas = [
            ['Mercado mensal', 'alimentacao', 450.00, '2023-01-10', 1],
            ['Gasolina', 'transporte', 200.00, '2023-01-15', 1],
            ['Cinema', 'lazer', 80.00, '2023-01-20', 1],
            ['Conta de luz', 'moradia', 120.00, '2023-01-25', 1],
            ['Restaurante', 'alimentacao', 150.00, '2023-02-05', 2],
            ['Uber', 'transporte', 50.00, '2023-02-10', 2],
            ['Show', 'lazer', 200.00, '2023-02-15', 2],
            ['Internet', 'moradia', 100.00, '2023-02-20', 2],
            ['Lanche', 'alimentacao', 35.00, '2023-03-05', 3],
            ['Ônibus', 'transporte', 20.00, '2023-03-10', 3],
            ['Livro', 'educacao', 75.00, '2023-03-15', 3],
            ['Água', 'moradia', 50.00, '2023-03-20', 3],
        ];
        
        $now = date('Y-m-d H:i:s');
        
        foreach ($despesas as $despesa) {
            $connection->createCommand()->insert('despesas', [
                'descricao' => $despesa[0],
                'categoria' => $despesa[1],
                'valor' => $despesa[2],
                'data' => $despesa[3],
                'user_id' => $despesa[4],
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
        }
        
        echo "Despesas de teste inseridas com sucesso.\n";
    }
    
    // Verificar inserção de despesas
    $despesasCount = $connection->createCommand('SELECT COUNT(*) FROM despesas')->queryScalar();
    echo "Total de despesas no banco de dados de testes: {$despesasCount}\n";
    
    echo "Dados de teste preparados com sucesso.\n";
    
} catch (Exception $e) {
    echo "Erro ao inserir dados de teste: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0); 