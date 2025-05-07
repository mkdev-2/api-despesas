<?php
$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$jwtSecret = $_ENV['JWT_SECRET'] ?? 'gerenciamento-despesas-api-secret-key';

return [
    'id' => 'app-web',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => 'pt-BR',
    'sourceLanguage' => 'en-US',
    'as corsFilter' => [
        'class' => 'yii\filters\Cors',
        'cors' => [
            'Origin' => ['http://localhost:5173', 'http://localhost:8080', 'http://localhost'],
            'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
            'Access-Control-Request-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'accept', 'origin', 'X-CSRF-Token'],
            'Access-Control-Allow-Credentials' => true,
            'Access-Control-Max-Age' => 3600,
            'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page', 'X-Pagination-Page-Count', 'X-Pagination-Per-Page', 'X-Pagination-Total-Count'],
            'Access-Control-Allow-Origin' => ['http://localhost:5173', 'http://localhost:8080', 'http://localhost'],
            'Access-Control-Allow-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'accept', 'origin', 'X-CSRF-Token'],
        ],
    ],
    'components' => [
        'session' => [
            'class' => 'yii\web\Session',
            'timeout' => 86400,
            'cookieParams' => ['httponly' => true, 'secure' => false],
            'useCookies' => true,
            'saveHandler' => null,
        ],
        'jwt' => [
            'class' => 'sizeg\jwt\Jwt',
            'key' => $jwtSecret,
        ],
        'request' => [
            'cookieValidationKey' => 'F8y67Tt09e4kYJLmW-nKIZZ9mCnbf6lH',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'enableCsrfValidation' => false,
        ],
        'response' => [
            'formatters' => [
                'json' => [
                    'class' => 'yii\web\JsonResponseFormatter',
                    'prettyPrint' => YII_DEBUG,
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ],
            ],
            'format' => \yii\web\Response::FORMAT_JSON,
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5173');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, accept, origin, X-CSRF-Token');
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Expose-Headers', 'X-Pagination-Current-Page, X-Pagination-Page-Count, X-Pagination-Per-Page, X-Pagination-Total-Count');
                
                $request = Yii::$app->request;
                if ($request->isOptions) {
                    $response->statusCode = 200;
                    $response->data = 'ok';
                }
            },
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\modules\usuarios\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'db_test' => require(__DIR__ . '/test_db.php'),
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
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
                // Estas rotas serão mantidas temporariamente para compatibilidade
                // Rotas para o módulo financeiro
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


