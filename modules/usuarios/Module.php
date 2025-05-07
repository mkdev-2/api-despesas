<?php

namespace app\modules\usuarios;

/**
 * Módulo Usuários
 * 
 * Este módulo gerencia todas as funcionalidades relacionadas aos usuários,
 * incluindo autenticação, autorização e gerenciamento de perfis.
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\usuarios\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        // Inicialização personalizada do módulo pode ser adicionada aqui
    }
} 