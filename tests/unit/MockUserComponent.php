<?php

namespace tests\unit;

use Closure;
use app\modules\usuarios\models\User;
use yii\base\Component;
use yii\web\IdentityInterface;

/**
 * Componente mock para o `user` no Yii
 * 
 * Este componente permite simular o componente de usuário do Yii
 * durante os testes, evitando a necessidade de usar cookies ou sessão.
 * 
 * @property-read bool $isGuest Se o usuário atual é um convidado (não autenticado)
 * @property-read IdentityInterface|User $identity A identidade do usuário atual
 * @property-read int|string $id ID do usuário atual
 * @property-read \app\modules\usuarios\models\User|null O usuário autenticado
 */
class MockUserComponent extends Component
{
    /**
     * @var IdentityInterface|null
     */
    private $_identity = null;
    
    /**
     * @var Closure|null Callback para eventos
     */
    private $_event = null;
    
    /**
     * Realiza login do usuário
     * 
     * @param \app\modules\usuarios\models\User $identity
     * @return bool se o login foi bem-sucedido
     */
    public function login($identity)
    {
        $this->_identity = $identity;
        
        if ($this->_event !== null) {
            $e = new \stdClass();
            $e->identity = $identity;
            call_user_func($this->_event, $e);
        }
        
        return true;
    }
    
    /**
     * Realiza logout do usuário
     * 
     * @return bool
     */
    public function logout()
    {
        $this->_identity = null;
        return true;
    }
    
    /**
     * Obtém a identidade do usuário atual
     * 
     * @return \app\modules\usuarios\models\User|null
     */
    public function getIdentity()
    {
        return $this->_identity;
    }
    
    /**
     * Define a identidade do usuário atual
     * 
     * @param \app\modules\usuarios\models\User|null $identity
     */
    public function setIdentity($identity)
    {
        $this->_identity = $identity;
    }
    
    /**
     * Verifica se o usuário atual é um convidado (não autenticado)
     * 
     * @return bool
     */
    public function getIsGuest()
    {
        return $this->_identity === null;
    }
    
    /**
     * Obtém o ID do usuário atual
     * 
     * @return int|string|null
     */
    public function getId()
    {
        return $this->_identity !== null ? $this->_identity->getId() : null;
    }
    
    /**
     * Define o callback a ser chamado nos eventos
     * 
     * @param Closure $callback
     */
    public function onAfterLogin(Closure $callback)
    {
        $this->_event = $callback;
    }
} 