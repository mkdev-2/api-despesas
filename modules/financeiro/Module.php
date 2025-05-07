<?php

namespace app\modules\financeiro;

/**
 * Módulo Financeiro
 * 
 * Este módulo gerencia todas as funcionalidades relacionadas às finanças,
 * incluindo despesas, receitas e relatórios financeiros.
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\financeiro\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        // Inicialização personalizada do módulo pode ser adicionada aqui
    }
} 