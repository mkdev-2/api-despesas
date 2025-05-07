<?php
// Script para testar a validação de senha do hash no fixture

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
new yii\web\Application($config);

$hash = '$2y$13$9GVMKQ7jp.s4wY.G/5s.IOuLM8Cl2mofmZbeW1MPCRs7hpdhVAeDa';

// Tentativas de senha
$passwords = [
    'admin',
    'password',
    'senha123',
    'admin123',
    '123456',
    'test',
    'demo',
];

echo "Verificando hash do fixture: $hash\n";
echo "Tentando senhas para encontrar a correspondência:\n";

foreach ($passwords as $password) {
    $result = Yii::$app->security->validatePassword($password, $hash);
    echo "Senha '$password' " . ($result ? 'CORRESPONDE AO HASH!' : 'não corresponde') . "\n";
}

// Gerar um novo hash para 'admin'
$newHash = Yii::$app->security->generatePasswordHash('admin');
echo "\nNovo hash para 'admin': $newHash\n";
echo "Validação do novo hash: " . (Yii::$app->security->validatePassword('admin', $newHash) ? "OK" : "Falha") . "\n"; 