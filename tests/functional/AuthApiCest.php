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
        // Gerar um timestamp curto para evitar nomes muito longos
        $timestamp = time() % 10000; // Usar apenas os últimos 4 dígitos
        
        $I->sendPost('/api/auth/register', [
            'username' => 'test_user_' . $timestamp,
            'email' => 'test_' . $timestamp . '@example.com',
            'password' => 'password123'
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
        // Gerar um timestamp curto para evitar nomes muito longos
        $timestamp = time() % 10000; // Usar apenas os últimos 4 dígitos
        
        // Primeiro vamos registrar um usuário
        $username1 = 'test_u1_' . $timestamp;
        $email = 'test_dup_' . $timestamp . '@example.com';
        
        $I->sendPost('/api/auth/register', [
            'username' => $username1,
            'email' => $email,
            'password' => 'password123'
        ]);
        
        $I->seeResponseCodeIs(HttpCode::CREATED);
        
        // Agora tentamos registrar outro com o mesmo email
        $username2 = 'test_u2_' . $timestamp;
        
        $I->sendPost('/api/auth/register', [
            'username' => $username2,
            'email' => $email, // mesmo email
            'password' => 'password123'
        ]);
        
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['error' => 'O email informado já está cadastrado']);
    }

    public function testRegistroUsuarioDuplicado(FunctionalTester $I)
    {
        // Gerar um timestamp curto para evitar nomes muito longos
        $timestamp = time() % 10000; // Usar apenas os últimos 4 dígitos
        
        // Primeiro vamos registrar um usuário
        $username = 'test_udup_' . $timestamp;
        $email1 = 'test_dup1_' . $timestamp . '@example.com';
        
        $I->sendPost('/api/auth/register', [
            'username' => $username,
            'email' => $email1,
            'password' => 'password123'
        ]);
        
        $I->seeResponseCodeIs(HttpCode::CREATED);
        
        // Agora tentamos registrar outro com o mesmo username
        $email2 = 'test_dup2_' . $timestamp . '@example.com';
        
        $I->sendPost('/api/auth/register', [
            'username' => $username, // mesmo username
            'email' => $email2,
            'password' => 'password123'
        ]);
        
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['error' => 'O nome de usuário informado já está em uso']);
    }

    public function testLoginSucesso(FunctionalTester $I)
    {
        // Gerar um timestamp curto para evitar nomes muito longos
        $timestamp = time() % 10000; // Usar apenas os últimos 4 dígitos
        
        // Registrar um novo usuário
        $username = 'test_lg_' . $timestamp;
        $email = 'test_log_' . $timestamp . '@example.com';
        $password = 'password123';
        
        $I->sendPost('/api/auth/register', [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);
        
        $I->seeResponseCodeIs(HttpCode::CREATED);
        
        // Tentar fazer login
        $I->sendPost('/api/auth/login', [
            'email' => $email,
            'password' => $password
        ]);
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.access_token');
        $I->seeResponseJsonMatchesJsonPath('$.user');
    }

    public function testLoginFalha(FunctionalTester $I)
    {
        $I->sendPost('/api/auth/login', [
            'email' => 'email_inexistente@example.com',
            'password' => 'senha_incorreta'
        ]);
        
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['error' => 'Credenciais inválidas']);
    }

    public function testPerfilComTokenValido(FunctionalTester $I)
    {
        // Gerar um timestamp curto para evitar nomes muito longos
        $timestamp = time() % 10000; // Usar apenas os últimos 4 dígitos
        
        // Registrar um novo usuário
        $username = 'test_prf_' . $timestamp;
        $email = 'test_prf_' . $timestamp . '@example.com';
        $password = 'password123';
        
        $I->sendPost('/api/auth/register', [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);
        
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $response = json_decode($I->grabResponse(), true);
        $token = $response['access_token'];
        
        // Acessar o perfil com o token
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGet('/api/auth/profile');
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.id');
        $I->seeResponseJsonMatchesJsonPath('$.username');
        $I->seeResponseJsonMatchesJsonPath('$.email');
    }

    public function testPerfilSemToken(FunctionalTester $I)
    {
        // Remover cabeçalho de autorização, se houver
        $I->deleteHeader('Authorization');
        
        $I->sendGet('/api/auth/profile');
        
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseIsJson();
    }
} 