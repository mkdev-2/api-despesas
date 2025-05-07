<?php

use app\modules\usuarios\models\User;

class LoginFormCest
{
    public function _before(\FunctionalTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Accept', 'application/json');
    }

    public function openLoginPage(\FunctionalTester $I)
    {
        // GET geralmente não é permitido em endpoints de login
        // O teste espera 405 Method Not Allowed
        $I->sendGet('/api/auth/login');
        $I->seeResponseCodeIs(405);
        $I->seeResponseIsJson();
    }

    public function loginWithEmptyCredentials(\FunctionalTester $I)
    {
        $I->sendPost('/api/auth/login', []);
        $I->seeResponseCodeIs(400); // A API retorna 400 Bad Request
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['error' => 'Email e senha são obrigatórios']);
    }

    public function loginWithWrongCredentials(\FunctionalTester $I)
    {
        $I->sendPost('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'senha_incorreta',
        ]);
        $I->seeResponseCodeIs(401); // A API retorna 401 Unauthorized
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['error' => 'Credenciais inválidas']);
    }

    public function loginSuccessfully(\FunctionalTester $I)
    {
        // Primeiro, registre um usuário para teste
        // Gerar um nome curto (máximo 20 caracteres)
        $timestamp = time() % 10000; // Usar apenas os últimos 4 dígitos
        $username = 'tst_' . $timestamp;
        $email = 'test' . $timestamp . '@example.com';
        $password = 'password123';
        
        $I->sendPost('/api/auth/register', [
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ]);
        
        $I->seeResponseCodeIs(201);
        
        // Agora, tente fazer login com esse usuário
        $I->sendPost('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
        
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.access_token');
        $I->seeResponseJsonMatchesJsonPath('$.user');
    }
}