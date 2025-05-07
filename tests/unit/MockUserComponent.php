<?php

namespace tests\unit;

use app\models\User;
use Yii;

/**
 * Mock do componente User para testes de autenticação
 */
class MockUserComponent
{
    /**
     * @var bool Se o usuário está autenticado
     */
    public $isGuest = true;
    
    /**
     * @var \app\models\User|null O usuário autenticado
     */
    private $identity = null;
    
    /**
     * @var int ID do usuário autenticado
     */
    public $id = null;
    
    /**
     * @var int Duração do cookie de autenticação em segundos
     */
    public $enableAutoLogin = false;
    
    /**
     * @var int Tempo de vida da sessão em segundos
     */
    public $authTimeout = 3600;
    
    /**
     * Verifica se o usuário é um visitante (não autenticado)
     * @return bool
     */
    public function getIsGuest()
    {
        return $this->isGuest;
    }
    
    /**
     * Faz login de um usuário
     * @param \app\models\User $identity
     * @param int $duration Duração em segundos
     * @return bool
     */
    public function login($identity, $duration = 0)
    {
        $this->identity = $identity;
        $this->isGuest = false;
        $this->id = $identity ? $identity->getId() : null;
        return true;
    }
    
    /**
     * Faz logout do usuário
     * @return bool
     */
    public function logout()
    {
        $this->identity = null;
        $this->isGuest = true;
        $this->id = null;
        return true;
    }
    
    /**
     * Retorna a identidade do usuário atual
     * @return \app\models\User|null
     */
    public function getIdentity()
    {
        return $this->identity;
    }
    
    /**
     * Define a identidade do usuário atual
     * @param \app\models\User|null $identity
     */
    public function setIdentity($identity)
    {
        $this->identity = $identity;
        $this->isGuest = $identity === null;
        $this->id = $identity ? $identity->getId() : null;
    }
    
    /**
     * Retorna o ID do usuário atual
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }
} 