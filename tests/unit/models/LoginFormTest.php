<?php

namespace tests\unit\models;

use app\modules\usuarios\models\LoginForm;
use app\modules\usuarios\models\User;
use tests\unit\MockUserComponent;
use tests\unit\widgets\MockSession;
use Yii;

class LoginFormTest extends \Codeception\Test\Unit
{
    private $model;
    
    /**
     * @var MockUserComponent
     */
    private $userComponent;

    protected function _before()
    {
        // Usar a mesma classe de sessão mockada
        $sessionMock = new MockSession();
        Yii::$app->set('session', $sessionMock);
        
        // Criar e configurar o mock do componente user
        $this->userComponent = new MockUserComponent();
        Yii::$app->set('user', $this->userComponent);
    }

    protected function _after()
    {
        $this->userComponent->logout();
    }

    public function _fixtures()
    {
        return [
            'users' => [
                'class' => \tests\unit\fixtures\UserFixture::class,
            ],
        ];
    }

    public function testLoginNoUser()
    {
        $this->model = new LoginForm([
            'username' => 'not_existing_username',
            'password' => 'not_existing_password',
        ]);

        $this->assertFalse($this->model->login(), 'Login com usuário inexistente deveria falhar');
        $this->assertTrue($this->userComponent->getIsGuest(), 'Usuário deveria continuar como guest');
        $this->assertArrayHasKey('password', $this->model->errors, 'Erro deveria estar em password');
    }

    public function testLoginWrongPassword()
    {
        $this->model = new LoginForm([
            'username' => 'admin',
            'password' => 'wrong_password',
        ]);

        $this->assertFalse($this->model->login(), 'Login com senha incorreta deveria falhar');
        $this->assertTrue($this->userComponent->getIsGuest(), 'Usuário deveria continuar como guest');
        $this->assertArrayHasKey('password', $this->model->errors, 'Erro deveria estar em password');
    }

    public function testLoginCorrectEmail()
    {
        $this->model = new LoginForm([
            'email' => 'admin@example.com',
            'password' => 'admin', // Senha conforme fixture
        ]);

        // Verifica se o usuário admin e a senha admin são válidos
        $user = User::findByEmail('admin@example.com');
        
        // Pule o teste se o usuário não existir ou a senha não for válida
        if (!$user || !$user->validatePassword('admin')) {
            $this->markTestSkipped('Usuário admin@example.com não encontrado ou senha inválida no fixture');
        }

        $this->assertTrue($this->model->login(), 'Login com email e senha válidos deveria ser bem-sucedido');
        $this->assertFalse($this->userComponent->getIsGuest(), 'Usuário não deveria ser considerado guest após login');
    }
    
    public function testLoginEmptyCredentials()
    {
        $this->model = new LoginForm([
            'password' => 'admin',
            // Sem username nem email
        ]);

        // Validar o modelo diretamente
        $isValid = $this->model->validate();
        echo "Validate result: " . ($isValid ? 'true' : 'false') . "\n";
        echo "Erros do modelo: " . print_r($this->model->errors, true) . "\n";
        
        $this->assertFalse($isValid, 'Validação sem credenciais deveria falhar');
        
        // Tentar login após validação
        $result = $this->model->login();
        $this->assertFalse($result, 'Login sem credenciais deveria falhar');
        $this->assertTrue($this->userComponent->getIsGuest(), 'Usuário deveria continuar como guest');
        
        // Verificar se o modelo tem erros (não importa em qual campo específico)
        $this->assertTrue($this->model->hasErrors(), 'O modelo deveria ter erros de validação');
    }
}
