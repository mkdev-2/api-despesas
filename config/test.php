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
                // Rotas do módulo API
                'api' => 'api/index/index',
                'api/despesas' => 'api/despesa/index',
                'api/despesas/create' => 'api/despesa/create',
                'api/despesas/<id:\d+>' => 'api/despesa/view',
                'api/despesas/<id:\d+>/update' => 'api/despesa/update',
                'api/despesas/<id:\d+>/delete' => 'api/despesa/delete',
                'api/despesas/categorias' => 'api/despesa/categorias',
                'api/despesas/resumo' => 'api/despesa/resumo',
                'api/relatorio/anual' => 'api/relatorio/anual',
                'api/relatorio/proporcao' => 'api/relatorio/proporcao',
                'api/auth/login' => 'api/auth/login',
                'api/auth/register' => 'api/auth/register',
                'api/auth/profile' => 'api/auth/profile',
                'api/auth/update-profile' => 'api/auth/update-profile',
                
                // Rotas diretas para outros módulos
                'financeiro/despesas' => 'financeiro/despesa/index',
                'financeiro/despesas/create' => 'financeiro/despesa/create',
                'financeiro/despesas/<id:\d+>' => 'financeiro/despesa/view',
                'financeiro/despesas/<id:\d+>/update' => 'financeiro/despesa/update',
                'financeiro/despesas/<id:\d+>/delete' => 'financeiro/despesa/delete',
                'financeiro/despesas/categorias' => 'financeiro/despesa/categorias',
                'financeiro/despesas/resumo' => 'financeiro/despesa/resumo',
                'financeiro/relatorio/anual' => 'financeiro/relatorio/anual',
                'financeiro/relatorio/proporcao' => 'financeiro/relatorio/proporcao',
                'usuarios/auth/login' => 'usuarios/auth/login',
                'usuarios/auth/register' => 'usuarios/auth/register',
                'usuarios/auth/profile' => 'usuarios/auth/profile',
                'usuarios/auth/update-profile' => 'usuarios/auth/update-profile',

                // Suporte a OPTIONS para CORS
                'OPTIONS api/<controller:\w+>' => 'api/<controller>/options',
                'OPTIONS api/<controller:\w+>/<action:\w+>' => 'api/<controller>/options',
                'OPTIONS api/<controller:\w+>/<id:\d+>/<action:\w+>' => 'api/<controller>/options',
                
                // Suporte a OPTIONS para módulos
                'OPTIONS <module:\w+>/<controller:\w+>' => '<module>/<controller>/options',
                'OPTIONS <module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/options',
                'OPTIONS <module:\w+>/<controller:\w+>/<id:\d+>/<action:\w+>' => '<module>/<controller>/options',
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
        ],
    ],
    'params' => $params,
];

return $config;
