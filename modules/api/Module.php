<?php

namespace app\modules\api;

/**
 * Módulo API
 * 
 * Este módulo gerencia todas as funcionalidades relacionadas à API RESTful,
 * incluindo versionamento e autenticação.
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\api\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        
        // Configura o formato de resposta para JSON
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        // Remove a validação CSRF para APIs
        \Yii::$app->request->enableCsrfValidation = false;
    }
} 