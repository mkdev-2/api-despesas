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
                
                
                // Rotas diretas para outros módulos
                'financeiro/despesas' => 'financeiro/despesa/index',
                'financeiro/despesas/create' => 'financeiro/despesa/create',
                'financeiro/despesas/<id:\d+>' => 'financeiro/despesa/view',
                'financeiro/despesas/<id:\d+>/update' => 'financeiro/despesa/update',
                'financeiro/despesas/<id:\d+>/delete' => 'financeiro/despesa/delete',
                'financeiro/despesas/categorias' => 'financeiro/despesa/categorias',
                'financeiro/despesas/resumo' => 'financeiro/despesa/resumo',
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


