<?php

namespace app\modules\api\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\Cors;
use sizeg\jwt\JwtHttpBearerAuth;

/**
 * Controlador de API para relatórios financeiros (versão 1)
 * Este controlador serve como proxy para o módulo financeiro
 */
class RelatorioController extends Controller
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
                'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page', 'X-Pagination-Page-Count', 'X-Pagination-Per-Page', 'X-Pagination-Total-Count'],
                'Access-Control-Allow-Origin' => ['http://localhost:5173', 'http://localhost:8080', 'http://localhost'],
                'Access-Control-Allow-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'accept', 'origin', 'X-CSRF-Token'],
            ],
        ];
        
        // Configuração do autenticador JWT
        $behaviors['authenticator'] = [
            'class' => JwtHttpBearerAuth::class,
            'except' => ['options'],
        ];
        
        return $behaviors;
    }
    
    // Suporte a requisições OPTIONS (preflight)
    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return 'ok';
    }
    
    // Relatório anual
    public function actionAnual()
    {
        return $this->runAction('financeiro/relatorio/anual');
    }
    
    // Relatório de proporção de gastos por categoria
    public function actionProporcao()
    {
        return $this->runAction('financeiro/relatorio/proporcao');
    }
    
    /**
     * Encaminha a ação para o controlador real no módulo correspondente
     * 
     * @param string $route A rota para o controlador real (ex: 'financeiro/relatorio/anual')
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