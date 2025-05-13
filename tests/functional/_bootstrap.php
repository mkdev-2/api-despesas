<?php

// Código de inicialização específico para testes funcionais

// Garante que os módulos necessários estão registrados
$modules = [
    'financeiro' => [
        'class' => 'app\modules\financeiro\Module',
    ],
    'usuarios' => [
        'class' => 'app\modules\usuarios\Module',
    ],
    'api' => [
        'class' => 'app\modules\api\Module',
    ],
];

Yii::$app->setModules($modules);

// Define o ambiente para a API
defined('YII_ENV_TEST') or define('YII_ENV_TEST', true);
