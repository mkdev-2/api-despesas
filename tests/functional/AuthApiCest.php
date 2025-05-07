<?php

namespace tests\functional;

use Codeception\Util\HttpCode;
use FunctionalTester;

class AuthApiCest
{
    public function _before(FunctionalTester $I)
    {
        // Limpar cabeçalhos e preparar para requisição JSON
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Accept', 'application/json');
    }

    public function testRegistroSucesso(FunctionalTester $I)
    {
        $username = 'test_user_' . time();
        $email = 'test_' . time() . '@example.com';
        $password = 'password123';

        $I->sendPOST('/api/auth/register', [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);

        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['message' => 'Usuário criado com sucesso']);
        $I->seeResponseJsonMatchesJsonPath('$.access_token');
        $I->seeResponseJsonMatchesJsonPath('$.user.id');
        $I->seeResponseJsonMatchesJsonPath('$.user.email');
    }

    public function testRegistroEmailDuplicado(FunctionalTester $I)
    {
        // Primeiro registro
        $username1 = 'test_user_duplicate_1_' . time();
        $email = 'test_duplicate_' . time() . '@example.com';
        $password = 'password123';

        $I->sendPOST('/api/auth/register', [
            'username' => $username1,
            'email' => $email,
            'password' => $password
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);

        // Tentativa com email duplicado
        $username2 = 'test_user_duplicate_2_' . time();
        $I->sendPOST('/api/auth/register', [
            'username' => $username2,
            'email' => $email,
            'password' => $password
        ]);

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['error' => 'O email informado já está cadastrado']);
    }

    public function testRegistroUsuarioDuplicado(FunctionalTester $I)
    {
        // Primeiro registro
        $username = 'test_username_duplicate_' . time();
        $email1 = 'test_username_1_' . time() . '@example.com';
        $password = 'password123';

        $I->sendPOST('/api/auth/register', [
            'username' => $username,
            'email' => $email1,
            'password' => $password
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);

        // Tentativa com username duplicado
        $email2 = 'test_username_2_' . time() . '@example.com';
        $I->sendPOST('/api/auth/register', [
            'username' => $username,
            'email' => $email2,
            'password' => $password
        ]);

        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['error' => 'O nome de usuário informado já está em uso']);
    }

    public function testLoginSucesso(FunctionalTester $I)
    {
        // Criar usuário para teste
        $username = 'test_login_' . time();
        $email = 'test_login_' . time() . '@example.com';
        $password = 'password123';

        $I->sendPOST('/api/auth/register', [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);

        // Testar login
        $I->sendPOST('/api/auth/login', [
            'email' => $email,
            'password' => $password
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.access_token');
        $I->seeResponseJsonMatchesJsonPath('$.user.id');
        $I->seeResponseJsonMatchesJsonPath('$.user.email');
        $I->seeResponseContainsJson(['user' => ['email' => $email]]);
    }

    public function testLoginFalha(FunctionalTester $I)
    {
        // Credenciais inválidas
        $I->sendPOST('/api/auth/login', [
            'email' => 'email_inexistente@example.com',
            'password' => 'senha_incorreta'
        ]);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['error' => 'Credenciais inválidas']);
    }

    public function testPerfilComTokenValido(FunctionalTester $I)
    {
        // Registro e login para obter token
        $username = 'test_profile_' . time();
        $email = 'test_profile_' . time() . '@example.com';
        $password = 'password123';

        $I->sendPOST('/api/auth/register', [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $registroResponse = json_decode($I->grabResponse(), true);
        $token = $registroResponse['access_token'];

        // Testar acesso ao perfil com token válido
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('/api/auth/profile');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['email' => $email, 'username' => $username]);
    }

    public function testPerfilSemToken(FunctionalTester $I)
    {
        // Tentativa de acesso sem token
        $I->sendGET('/api/auth/profile');

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }
} 