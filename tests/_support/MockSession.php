<?php

use yii\web\SessionInterface;

/**
 * Classe MockSession para lidar com problemas de sessão durante testes
 * 
 * Esta classe implementa yii\web\SessionInterface e substitui a sessão padrão 
 * do Yii2 durante os testes, evitando problemas relacionados a sessões já iniciadas 
 * e headers já enviados, que são comuns em testes com Codeception.
 */
class MockSession implements SessionInterface
{
    private $data = [];
    
    public function get($key, $defaultValue = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $defaultValue;
    }
    
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
    
    public function remove($key)
    {
        unset($this->data[$key]);
    }
    
    public function has($key)
    {
        return isset($this->data[$key]);
    }
    
    public function open()
    {
        return true;
    }
    
    public function close()
    {
        return true;
    }
    
    public function getFlash($key, $defaultValue = null, $delete = true)
    {
        $result = $this->get($key, $defaultValue);
        if ($delete) {
            $this->remove($key);
        }
        return $result;
    }
    
    public function setFlash($key, $value = true, $removeAfterAccess = true)
    {
        $this->set($key, $value);
    }
    
    public function addFlash($key, $value = true, $removeAfterAccess = true)
    {
        $values = $this->get($key, []);
        if (!is_array($values)) {
            $values = [$values];
        }
        $values[] = $value;
        $this->set($key, $values);
    }
    
    public function hasFlash($key)
    {
        return $this->has($key);
    }
    
    public function getAllFlashes()
    {
        return $this->data;
    }
    
    public function removeFlash($key)
    {
        $this->remove($key);
    }
    
    public function removeAllFlashes()
    {
        $this->data = [];
    }

    public function destroy()
    {
        $this->data = [];
    }
    
    public function getId()
    {
        return 'test-session-id';
    }
    
    public function setId($value)
    {
        // Nada a fazer, usamos um ID fixo
    }
    
    public function regenerateID($deleteOldSession = false)
    {
        return true;
    }
    
    public function getName()
    {
        return 'PHPSESSID';
    }
    
    public function setName($value)
    {
        // Nada a fazer, usamos um nome fixo
    }
    
    public function getIsActive()
    {
        return true;
    }
    
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
    
    public function getCount()
    {
        return count($this->data);
    }
    
    public function count()
    {
        return $this->getCount();
    }
    
    public function setCookieParams($value)
    {
        // Nada a fazer, pois não usamos cookies nos testes
    }
    
    public function getCookieParams()
    {
        return [];
    }
    
    public function getSavePath()
    {
        return sys_get_temp_dir();
    }
    
    public function setSavePath($value)
    {
        // Nada a fazer
    }
    
    public function getUseCookies()
    {
        return false;
    }
    
    public function setUseCookies($value)
    {
        // Nada a fazer
    }
    
    public function getUseCustomStorage()
    {
        return true;
    }
    
    public function getUseTransparentSessionID()
    {
        return false;
    }
    
    public function setUseTransparentSessionID($value)
    {
        // Nada a fazer
    }
    
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }
    
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
    
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }
    
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
} 