<?php
namespace app\modules\usuarios\controllers;

use Yii;
use yii\rest\Controller;
use app\modules\usuarios\models\User;
use sizeg\jwt\JwtHttpBearerAuth;
use yii\filters\Cors;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class AuthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Adiciona configuração de CORS específica para este controller
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['http://localhost:5173', 'http://localhost:8080', 'http://localhost'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'accept', 'origin', 'X-CSRF-Token'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 3600,
                'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page'],
                'Access-Control-Allow-Origin' => ['http://localhost:5173', 'http://localhost:8080', 'http://localhost'],
                'Access-Control-Allow-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'accept', 'origin', 'X-CSRF-Token'],
            ],
        ];
        
        $behaviors['authenticator'] = [
            'class' => JwtHttpBearerAuth::class,
            'except' => ['login', 'register', 'options'],
        ];
        
        // Definindo o comportamento para OPTIONS para suportar preflight CORS
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'login' => ['post', 'options'],
                'register' => ['post', 'options'],
                'options' => ['options'],
                'profile' => ['get', 'options'],
            ],
        ];
        
        return $behaviors;
    }
    
    // Adiciona suporte a requisições OPTIONS (preflight)
    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        Yii::$app->response->headers->set('Allow', 'POST, OPTIONS');
        return 'ok';
    }

    /**
     * Login de usuário - recebe email e senha e retorna token JWT
     */
    public function actionLogin()
    {
        $request = Yii::$app->request->post();

        if (empty($request['email']) || empty($request['password'])) {
            Yii::$app->response->statusCode = 400;
            return ['error' => 'Email e senha são obrigatórios'];
        }

        $user = User::findByEmail($request['email']);

        if (!$user || !$user->validatePassword($request['password'])) {
        Yii::$app->response->statusCode = 401;
            return ['error' => 'Credenciais inválidas'];
        }

        // Criar o token JWT usando a versão 3.2.5 da biblioteca
        $jwtSecret = Yii::$app->jwt->key;
        
        $token = (new Builder())
            ->setIssuer('https://api.gerenciamento-despesas.com') // Emissor
            ->setAudience('https://app.gerenciamento-despesas.com') // Destinatário
            ->setId(uniqid('token-', true), true) // ID único do token
            ->setIssuedAt(time()) // Momento em que o token foi emitido
            ->setExpiration(time() + 86400) // Expira em 24 horas
            ->set('uid', $user->id) // Dados customizados 
            ->set('email', $user->email)
            ->set('username', $user->username)
            ->sign(new Sha256(), $jwtSecret) // Assinando o token com a chave secreta
            ->getToken(); // Obtendo a string do token
        
        $jwtString = (string) $token;
        
        // Não salvamos mais o token no banco de dados para evitar problemas de tamanho
        // $user->access_token = $jwtString;
        // $user->save(false);

        return [
            'access_token' => $jwtString,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ]
        ];
    }

    /**
     * Registro de novo usuário
     */
    public function actionRegister()
    {
        $request = Yii::$app->request->post();
        
        if (empty($request['username']) || empty($request['email']) || empty($request['password'])) {
            Yii::$app->response->statusCode = 400;
            return ['error' => 'Nome de usuário, email e senha são obrigatórios'];
        }
        
        // Verifica se o email já está em uso
        if (User::findByEmail($request['email'])) {
            Yii::$app->response->statusCode = 409; // Conflict
            return ['error' => 'O email informado já está cadastrado'];
        }
        
        // Verifica se o username já está em uso
        if (User::findByUsername($request['username'])) {
            Yii::$app->response->statusCode = 409; // Conflict
            return ['error' => 'O nome de usuário informado já está em uso'];
        }
        
        // Cria o novo usuário
        $user = new User();
        $user->scenario = 'create'; // Define explicitamente o cenário de criação
        $user->username = $request['username'];
        $user->email = $request['email'];
        $user->password = $request['password'];
        $user->setPassword($request['password']);
        $user->generateAuthKey();
        
        if (!$user->save()) {
            Yii::$app->response->statusCode = 422; // Unprocessable Entity
            return ['error' => 'Não foi possível criar o usuário', 'details' => $user->errors];
        }
        
        Yii::$app->response->statusCode = 201; // Created
        
        // Criar o token JWT usando a versão 3.2.5 da biblioteca
        $jwtSecret = Yii::$app->jwt->key;
        
        $token = (new Builder())
            ->setIssuer('https://api.gerenciamento-despesas.com') // Emissor
            ->setAudience('https://app.gerenciamento-despesas.com') // Destinatário
            ->setId(uniqid('token-', true), true) // ID único do token
            ->setIssuedAt(time()) // Momento em que o token foi emitido
            ->setExpiration(time() + 86400) // Expira em 24 horas
            ->set('uid', $user->id) // Dados customizados 
            ->set('email', $user->email)
            ->set('username', $user->username)
            ->sign(new Sha256(), $jwtSecret) // Assinando o token com a chave secreta
            ->getToken(); // Obtendo a string do token
        
        $jwtString = (string) $token;
            
        return [
            'message' => 'Usuário criado com sucesso',
            'access_token' => $jwtString,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ]
        ];
    }
    
    /**
     * Retorna o perfil do usuário autenticado
     */
    public function actionProfile()
    {
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            throw new ForbiddenHttpException('Usuário não autenticado');
        }
        
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'created_at' => $user->created_at,
        ];
    }

    /**
     * Atualiza o perfil do usuário autenticado
     */
    public function actionUpdateProfile()
    {
        $user = Yii::$app->user->identity;
        $request = Yii::$app->request->post();
        
        if (!$user) {
            throw new ForbiddenHttpException('Usuário não autenticado');
        }
        
        $user->scenario = 'update';
        
        // Atualiza apenas os campos permitidos
        if (isset($request['username'])) {
            $user->username = $request['username'];
        }
        
        if (isset($request['email'])) {
            $user->email = $request['email'];
        }
        
        // Atualiza a senha apenas se for fornecida
        if (!empty($request['password'])) {
            $user->setPassword($request['password']);
        }
        
        if ($user->save()) {
            return [
                'message' => 'Perfil atualizado com sucesso',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'updated_at' => $user->updated_at,
                ]
            ];
        }
        
        Yii::$app->response->statusCode = 422; // Unprocessable Entity
        return ['error' => 'Não foi possível atualizar o perfil', 'details' => $user->errors];
    }
} 