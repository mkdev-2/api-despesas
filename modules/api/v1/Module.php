<?php

namespace app\modules\api\v1;

/**
 * Módulo API v1
 * 
 * Este módulo gerencia a versão 1 da API.
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\api\v1\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        // Configurações específicas da v1 da API
    }
} 