<?php

namespace tests\unit\widgets;

/**
 * Classe mock para simular a funcionalidade de sessão durante os testes
 */
class MockSession
{
    /**
     * @var array Armazena as mensagens flash para testes
     */
    private $flashes = [];
    
    /**
     * @var bool Status da sessão
     */
    public $isActive = true;
    
    /**
     * @var array Dados da sessão
     */
    private $data = [];
    
    /**
     * @var bool Se tem um ID de sessão
     */
    private $hasSessionId = true;

    /**
     * @var string ID da sessão
     */
    private $id = 'test-session-id';
    
    /**
     * Verifica se existe uma mensagem flash com o nome especificado
     * @param string $key Nome da mensagem flash
     * @return bool
     */
    public function hasFlash($key)
    {
        return isset($this->flashes[$key]);
    }
    
    /**
     * Retorna uma mensagem flash
     * @param string $key Nome da mensagem flash
     * @return mixed|null
     */
    public function getFlash($key)
    {
        return $this->flashes[$key] ?? null;
    }
    
    /**
     * Remove uma mensagem flash
     * @param string $key Nome da mensagem flash
     */
    public function removeFlash($key)
    {
        if (isset($this->flashes[$key])) {
            unset($this->flashes[$key]);
        }
    }
    
    /**
     * Define uma mensagem flash
     * @param string $key Nome da mensagem flash
     * @param mixed $value Valor da mensagem flash
     */
    public function setFlash($key, $value)
    {
        $this->flashes[$key] = $value;
    }
    
    /**
     * Fecha a sessão (método necessário para compatibilidade com Yii2)
     * @return bool Sempre retorna true
     */
    public function close()
    {
        $this->isActive = false;
        return true;
    }
    
    /**
     * Abre a sessão (método necessário para compatibilidade com Yii2)
     * @return bool Sempre retorna true
     */
    public function open()
    {
        $this->isActive = true;
        return true;
    }
    
    /**
     * Retorna todas as mensagens flash
     * @return array
     */
    public function getAllFlashes()
    {
        return $this->flashes;
    }
    
    /**
     * Verifica se a sessão tem um ID válido
     * @return bool
     */
    public function getHasSessionId()
    {
        return $this->hasSessionId;
    }
    
    /**
     * Define se a sessão tem um ID válido
     * @param bool $value
     */
    public function setHasSessionId($value)
    {
        $this->hasSessionId = (bool) $value;
    }
    
    /**
     * Define um valor na sessão
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
    
    /**
     * Obtém um valor da sessão
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        return $this->data[$key] ?? $defaultValue;
    }
    
    /**
     * Verifica se a sessão contém uma chave
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->data[$key]);
    }
    
    /**
     * Remove um valor da sessão
     * @param string $key
     */
    public function remove($key)
    {
        unset($this->data[$key]);
    }
    
    /**
     * Retorna o ID da sessão
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Define o ID da sessão
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
} 