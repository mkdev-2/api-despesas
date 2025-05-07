<?php

namespace app\modules\usuarios\models;

use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $rememberMe = true;

    private $_user = false;

    public function rules()
    {
        return [
            [['password'], 'required'],
            [['email', 'username'], 'default'],
            [['email'], 'email'],
            [['rememberMe'], 'boolean'],
            [['password'], 'validatePassword'],
            [['email', 'username'], 'validateLoginCredential'],
        ];
    }

    /**
     * Valida se ao menos um dos campos de login (email ou username) foi fornecido
     * @param string $attribute
     * @param array $params
     */
    public function validateLoginCredential($attribute, $params)
    {
        if (empty($this->email) && empty($this->username)) {
            $this->addError('email', 'É necessário fornecer um email ou nome de usuário.');
            $this->addError('username', 'É necessário fornecer um email ou nome de usuário.');
        }
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Email, nome de usuário ou senha inválidos.');
            }
        }
    }

    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
        }
        return false;
    }

    public function getUser()
    {
        if ($this->_user === false) {
            // Tenta encontrar o usuário por email primeiro
            if (!empty($this->email)) {
                $this->_user = User::findByEmail($this->email);
            }
            
            // Se não encontrar por email e tiver um username, tenta encontrar por username
            if ($this->_user === null && !empty($this->username)) {
                $this->_user = User::findByUsername($this->username);
            }
        }
        
        return $this->_user;
    }
} 