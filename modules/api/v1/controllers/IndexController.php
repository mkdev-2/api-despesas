<?php

namespace app\modules\api\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\Url;
use yii\web\Response;

/**
 * Controlador de índice para API v1
 * 
 * Este controlador serve como ponto de entrada principal para a API,
 * fornecendo informações sobre as rotas disponíveis e facilidades para navegação
 */
class IndexController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Permitir acesso anônimo ao índice da API
        $behaviors['authenticator'] = [
            'class' => \sizeg\jwt\JwtHttpBearerAuth::class,
            'except' => ['index', 'options'],
        ];
        
        return $behaviors;
    }
    
    /**
     * Ação padrão que exibe informações sobre a API
     * 
     * @return array Informações sobre a API
     */
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $baseUrl = Url::base(true);
        
        return [
            'name' => 'API de Gerenciamento de Despesas Pessoais',
            'version' => 'v1',
            'description' => 'API RESTful para gerenciamento de despesas pessoais',
            'endpoints' => [
                'auth' => [
                    'login' => [
                        'url' => "$baseUrl/api/v1/auth/login",
                        'method' => 'POST',
                        'description' => 'Autenticação de usuário'
                    ],
                    'register' => [
                        'url' => "$baseUrl/api/v1/auth/register",
                        'method' => 'POST',
                        'description' => 'Registro de novo usuário'
                    ],
                    'profile' => [
                        'url' => "$baseUrl/api/v1/auth/profile",
                        'method' => 'GET',
                        'description' => 'Perfil do usuário autenticado',
                        'requires_auth' => true
                    ],
                    'update-profile' => [
                        'url' => "$baseUrl/api/v1/auth/update-profile",
                        'method' => 'POST',
                        'description' => 'Atualização do perfil do usuário',
                        'requires_auth' => true
                    ],
                ],
                'despesas' => [
                    'list' => [
                        'url' => "$baseUrl/api/v1/despesas",
                        'method' => 'GET',
                        'description' => 'Listar despesas do usuário',
                        'requires_auth' => true
                    ],
                    'create' => [
                        'url' => "$baseUrl/api/v1/despesas/create",
                        'method' => 'POST',
                        'description' => 'Criar nova despesa',
                        'requires_auth' => true
                    ],
                    'view' => [
                        'url' => "$baseUrl/api/v1/despesas/{id}",
                        'method' => 'GET',
                        'description' => 'Visualizar detalhes de uma despesa',
                        'requires_auth' => true
                    ],
                    'update' => [
                        'url' => "$baseUrl/api/v1/despesas/{id}/update",
                        'method' => 'PUT',
                        'description' => 'Atualizar uma despesa existente',
                        'requires_auth' => true
                    ],
                    'delete' => [
                        'url' => "$baseUrl/api/v1/despesas/{id}/delete",
                        'method' => 'DELETE',
                        'description' => 'Excluir uma despesa',
                        'requires_auth' => true
                    ],
                    'categorias' => [
                        'url' => "$baseUrl/api/v1/despesas/categorias",
                        'method' => 'GET',
                        'description' => 'Listar categorias disponíveis',
                        'requires_auth' => true
                    ],
                    'resumo' => [
                        'url' => "$baseUrl/api/v1/despesas/resumo",
                        'method' => 'GET',
                        'description' => 'Resumo de despesas por categoria',
                        'requires_auth' => true
                    ],
                ],
                'relatorios' => [
                    'anual' => [
                        'url' => "$baseUrl/api/v1/relatorio/anual",
                        'method' => 'GET',
                        'description' => 'Relatório anual de despesas',
                        'requires_auth' => true
                    ],
                    'proporcao' => [
                        'url' => "$baseUrl/api/v1/relatorio/proporcao",
                        'method' => 'GET',
                        'description' => 'Proporção de gastos por categoria',
                        'requires_auth' => true
                    ],
                ],
            ],
            'documentation' => "$baseUrl/API.md",
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * Suporte a requisições OPTIONS (preflight CORS)
     */
    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return 'ok';
    }
} 