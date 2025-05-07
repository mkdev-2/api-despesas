<?php
$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/test_db.php';

/**
 * Application configuration shared by all test types
 */
$config = [
    'id' => 'basic-tests',
    'basePath' => dirname(__DIR__),
    'aliases' => [
        '@npm'   => '@vendor/npm-asset',
        '@tests' => dirname(__DIR__) . '/tests',
    ],
    'language' => 'en-US',
    'components' => [
        'db' => $db,
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'jwt' => [
            'class' => \sizeg\jwt\Jwt::class,
            'key' => 'secret-test-key',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => true,
            'messageClass' => 'yii\symfonymailer\Message'
        ],
        'assetManager' => [
            'basePath' => __DIR__ . '/../web/assets',
            'bundles' => [
                'yii\bootstrap5\BootstrapAsset' => false,
            ],
        ],
        'urlManager' => [
            'showScriptName' => false,
            'enablePrettyUrl' => true,
            'rules' => [
                // Rota de índice para a API v1
                'api/v1' => 'api/v1/index/index',
                
                // API v1 - Rotas principais
                'api/v1/despesas' => 'api/v1/despesa/index',
                'api/v1/despesas/create' => 'api/v1/despesa/create',
                'api/v1/despesas/<id:\d+>' => 'api/v1/despesa/view',
                'api/v1/despesas/<id:\d+>/update' => 'api/v1/despesa/update',
                'api/v1/despesas/<id:\d+>/delete' => 'api/v1/despesa/delete',
                'api/v1/despesas/categorias' => 'api/v1/despesa/categorias',
                'api/v1/despesas/resumo' => 'api/v1/despesa/resumo',
                'api/v1/relatorio/anual' => 'api/v1/relatorio/anual',
                'api/v1/relatorio/proporcao' => 'api/v1/relatorio/proporcao',
                'api/v1/auth/login' => 'api/v1/auth/login',
                'api/v1/auth/register' => 'api/v1/auth/register',
                'api/v1/auth/profile' => 'api/v1/auth/profile',
                'api/v1/auth/update-profile' => 'api/v1/auth/update-profile',
                
                // Rotas de retrocompatibilidade (uso direto dos módulos internos)
                'api/despesas' => 'financeiro/despesa/index',
                'api/despesas/create' => 'financeiro/despesa/create',
                'api/despesas/<id:\d+>' => 'financeiro/despesa/view',
                'api/despesas/<id:\d+>/update' => 'financeiro/despesa/update',
                'api/despesas/<id:\d+>/delete' => 'financeiro/despesa/delete',
                'api/despesas/categorias' => 'financeiro/despesa/categorias',
                'api/despesas/resumo' => 'financeiro/despesa/resumo',
                
                // Rotas para o módulo de relatórios
                'api/relatorio/anual' => 'financeiro/relatorio/anual',
                'api/relatorio/proporcao' => 'financeiro/relatorio/proporcao',
                
                // Rotas para o módulo de usuários
                'api/auth/login' => 'usuarios/auth/login',
                'api/auth/register' => 'usuarios/auth/register',
                'api/auth/profile' => 'usuarios/auth/profile',
                'api/auth/update-profile' => 'usuarios/auth/update-profile',

                // Suporte a OPTIONS para CORS
                'OPTIONS api/<controller:\w+>' => '<controller>/options',
                'OPTIONS api/<controller:\w+>/<action:\w+>' => '<controller>/options',
                'OPTIONS api/<controller:\w+>/<id:\d+>/<action:\w+>' => '<controller>/options',
                'OPTIONS api/v1/<controller:\w+>' => 'api/v1/<controller>/options',
                'OPTIONS api/v1/<controller:\w+>/<action:\w+>' => 'api/v1/<controller>/options',
                'OPTIONS api/v1/<controller:\w+>/<id:\d+>/<action:\w+>' => 'api/v1/<controller>/options',
                
                // Suporte a OPTIONS para módulos
                'OPTIONS api/<module:\w+>/<controller:\w+>' => '<module>/<controller>/options',
                'OPTIONS api/<module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/options',
                'OPTIONS api/<module:\w+>/<controller:\w+>/<id:\d+>/<action:\w+>' => '<module>/<controller>/options',
            ],
        ],
        'user' => [
            'class' => 'yii\web\User',
            'identityClass' => 'app\modules\usuarios\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
        ],
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'response' => [
            'formatters' => [
                'json' => [
                    'class' => 'yii\web\JsonResponseFormatter',
                    'prettyPrint' => true,
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ],
            ],
            'format' => \yii\web\Response::FORMAT_JSON,
        ],
        'session' => [
            'class' => 'tests\unit\widgets\MockSession',
        ],
    ],
    'modules' => [
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
    ],
    'params' => $params,
];

return $config;
