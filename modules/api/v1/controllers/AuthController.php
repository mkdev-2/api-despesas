<?php

namespace app\modules\api\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\Cors;
use sizeg\jwt\JwtHttpBearerAuth;

/**
 * Controlador de API para autenticação e gerenciamento de usuários (versão 1)
 * Este controlador serve como proxy para o módulo de usuários
 */
class AuthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Adiciona configuração de CORS
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
        
        // Configuração do autenticador JWT
        $behaviors['authenticator'] = [
            'class' => JwtHttpBearerAuth::class,
            'except' => ['login', 'register', 'options'],
        ];
        
        return $behaviors;
    }
    
    // Suporte a requisições OPTIONS (preflight)
    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return 'ok';
    }
    
    // Login de usuário
    public function actionLogin()
    {
        return $this->runAction('usuarios/auth/login');
    }
    
    // Registro de novo usuário
    public function actionRegister()
    {
        return $this->runAction('usuarios/auth/register');
    }
    
    // Obter perfil do usuário autenticado
    public function actionProfile()
    {
        return $this->runAction('usuarios/auth/profile');
    }
    
    // Atualizar perfil do usuário
    public function actionUpdateProfile()
    {
        return $this->runAction('usuarios/auth/update-profile');
    }
    
    /**
     * Encaminha a ação para o controlador real no módulo correspondente
     * 
     * @param string $route A rota para o controlador real (ex: 'usuarios/auth/login')
     * @param array $params Parâmetros adicionais para a ação
     * @return mixed O resultado da ação executada
     */
    protected function runAction($route, $params = [])
    {
        $request = Yii::$app->request;
        
        // Preserva os parâmetros GET e POST da requisição original
        $getParams = $request->get();
        $postParams = $request->post();
        
        // Mescla os parâmetros específicos com os originais
        $params = array_merge($getParams, $params);
        
        // Executa a ação no controlador real
        return Yii::$app->runAction($route, $params);
    }
} 