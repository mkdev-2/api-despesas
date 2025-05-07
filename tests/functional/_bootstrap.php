<?php

// Código de inicialização específico para testes funcionais

// Garante que os módulos necessários estão registrados
Yii::$app->setModules([
    'financeiro' => [
        'class' => 'app\modules\financeiro\Module',
    ],
    'usuarios' => [
        'class' => 'app\modules\usuarios\Module',
    ],
    'api' => [
        'class' => 'app\modules\api\Module',
        'modules' => [
            'v1' => [
                'class' => 'app\modules\api\v1\Module',
            ],
        ],
    ],
]);

// Define o ambiente para a API
defined('YII_ENV_TEST') or define('YII_ENV_TEST', true);
