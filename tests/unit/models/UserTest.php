<?php

namespace tests\unit\models;
require_once __DIR__ . '/../fixtures/UserFixture.php';

use app\modules\usuarios\models\User;
use Codeception\Test\Unit;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Yii;

class UserTest extends Unit
{
    protected function _before()
    {
        Yii::$app->jwt->key = 'test-key-for-jwt-validation';
    }

    public function _fixtures()
    {
        return [
            'users' => [
                'class' => \tests\unit\fixtures\UserFixture::class,
            ],
        ];
    }
    
    public function testFindUserById()
    {
        $user = User::findOne(1);
    
        $this->assertNotNull($user, 'O usuário não foi encontrado no banco de dados.');
        $this->assertEquals(1, $user->id, 'O ID do usuário não corresponde.');
    }
    
    public function testFindUserByAccessToken()
    {
        // Gerando um token JWT válido para teste
        $jwtSecret = 'test-key-for-jwt-validation';
        
        $token = (new Builder())
            ->setIssuer('https://api.gerenciamento-despesas.com')
            ->setAudience('https://app.gerenciamento-despesas.com')
            ->setId(uniqid('token-', true), true)
            ->setIssuedAt(time())
            ->setExpiration(time() + 3600) // Expira em 1 hora
            ->set('uid', 1) // ID do usuário do fixture
            ->set('email', 'admin@example.com')
            ->set('username', 'admin')
            ->sign(new Sha256(), $jwtSecret)
            ->getToken();
        
        $jwtString = (string) $token;
        
        // Testando a busca de identidade pelo token
        $user = User::findIdentityByAccessToken($jwtString);
        
        $this->assertNotNull($user, 'Nenhum usuário encontrado com esse token.');
        $this->assertEquals(1, $user->id, 'O ID do usuário não corresponde.');
    }
    
    public function testFindByUsername()
    {
        $user = User::findByUsername('admin');
    
        $this->assertNotNull($user, 'Nenhum usuário encontrado com este nome.');
        $this->assertEquals('admin', $user->username, 'O nome do usuário não corresponde.');
    }
    
    public function testFindByEmail()
    {
        $user = User::findByEmail('admin@example.com');
    
        $this->assertNotNull($user, 'Nenhum usuário encontrado com este email.');
        $this->assertEquals('admin@example.com', $user->email, 'O email do usuário não corresponde.');
    }
    
    public function testValidatePassword()
    {
        $user = User::findByUsername('admin');
        
        $this->assertTrue($user->validatePassword('admin'), 'Senha deveria validar corretamente');
        $this->assertFalse($user->validatePassword('senha_incorreta'), 'Senha incorreta não deveria validar');
    }

    public function testValidation()
    {
        // Teste com dados corretos
        $user = new User();
        $user->username = 'novousuario';  // Certifique-se de que este username ainda não existe
        $user->email = 'novousuario@example.com';  // Certifique-se de que este email ainda não existe
        $user->password = 'password123';
        $user->scenario = 'create';  // Definir cenário explicitamente
        
        $this->assertTrue($user->validate(), 'Validação deveria passar com dados corretos: ' . print_r($user->errors, true));
        
        // Teste com email inválido
        $user = new User();
        $user->username = 'novousuario2';
        $user->email = 'email-invalido';
        $user->password = 'password123';
        $user->scenario = 'create';
        
        $this->assertFalse($user->validate(), 'Validação deveria falhar com email inválido');
        $this->assertArrayHasKey('email', $user->errors, 'Erro deveria estar no campo email');
        
        // Teste com senha muito curta
        $user = new User();
        $user->username = 'novousuario3';
        $user->email = 'novousuario3@example.com';
        $user->password = '123';
        $user->scenario = 'create';
        
        $this->assertFalse($user->validate(), 'Validação deveria falhar com senha curta');
        $this->assertArrayHasKey('password', $user->errors, 'Erro deveria estar no campo password');
        
        // Teste com username duplicado
        $user = new User();
        $user->username = 'admin';  // username que já existe no fixture
        $user->email = 'outro@example.com';
        $user->password = 'password123';
        $user->scenario = 'create';
        
        $this->assertFalse($user->validate(), 'Validação deveria falhar com username duplicado');
        $this->assertArrayHasKey('username', $user->errors, 'Erro deveria estar no campo username');
        
        // Teste com email duplicado
        $user = new User();
        $user->username = 'novousuario4';
        $user->email = 'admin@example.com';  // email que já existe no fixture
        $user->password = 'password123';
        $user->scenario = 'create';
        
        $this->assertFalse($user->validate(), 'Validação deveria falhar com email duplicado');
        $this->assertArrayHasKey('email', $user->errors, 'Erro deveria estar no campo email');
    }
    
    public function testSetPassword()
    {
        $user = new User();
        $user->setPassword('password123');
        
        $this->assertNotEmpty($user->password_hash, 'Password hash não deveria estar vazio');
        $this->assertTrue(Yii::$app->security->validatePassword('password123', $user->password_hash), 'Senha deveria validar corretamente');
        $this->assertFalse(Yii::$app->security->validatePassword('wrong_password', $user->password_hash), 'Senha incorreta não deveria validar');
    }
    
    public function testRegister()
    {
        // Teste do método de registro implementando manualmente
        $timestamp = time();
        $username = 'reg' . substr($timestamp, -5); // Nome curto para ficar dentro do limite de 20 caracteres
        $email = 'register_test_' . $timestamp . '@example.com';
        $password = 'password123';
        
        // Criando usuário manualmente
        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->password = $password;  // Atributo virtual
        $user->setPassword($password); // Gera o password_hash
        $user->generateAuthKey();
        $user->scenario = 'create';
        
        $saved = $user->save();
        
        $this->assertTrue($saved, 'Usuário deveria ser salvo com sucesso: ' . print_r($user->errors, true));
        $this->assertInstanceOf(User::class, $user, 'Usuário deveria ser uma instância de User');
        $this->assertNotEmpty($user->id, 'Usuário deveria ter um ID');
        $this->assertEquals($username, $user->username, 'Username deveria corresponder ao fornecido');
        $this->assertEquals($email, $user->email, 'Email deveria corresponder ao fornecido');
        $this->assertNotEmpty($user->password_hash, 'Password hash não deveria estar vazio');
        $this->assertNotEmpty($user->auth_key, 'Auth key não deveria estar vazio');
        
        // Verificar se o usuário pode ser encontrado
        $foundUser = User::findOne($user->id);
        $this->assertNotNull($foundUser, 'Usuário deveria ser encontrado pelo ID');
        $this->assertEquals($user->id, $foundUser->id, 'IDs deveriam corresponder');
        $this->assertEquals($username, $foundUser->username, 'Username deveria corresponder');
        
        // Limpar dados de teste
        if ($user && $user->id) {
            $user->delete();
        }
    }
}
