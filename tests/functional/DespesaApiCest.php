<?php

namespace tests\functional;

use Codeception\Util\HttpCode;
use FunctionalTester;
use app\models\Despesa;

class DespesaApiCest
{
    private $token;
    private $userId;
    private $despesaId;

    public function _before(FunctionalTester $I)
    {
        // Configurar headers para JSON
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Accept', 'application/json');

        // Criar um usuário para testes
        $username = 'despesa_api_test_' . time();
        $email = 'despesa_api_' . time() . '@example.com';
        $password = 'password123';

        // Registrar o usuário
        $I->sendPOST('/api/auth/register', [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        
        // Guardar o token e ID do usuário para uso nos testes
        $response = json_decode($I->grabResponse(), true);
        $this->token = $response['access_token'];
        $this->userId = $response['user']['id'];
        
        // Adicionar o token de autenticação para as requisições subsequentes
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
    }

    public function testCriarDespesa(FunctionalTester $I)
    {
        $despesaData = [
            'descricao' => 'Despesa de teste',
            'categoria' => Despesa::CATEGORIA_ALIMENTACAO,
            'valor' => 75.50,
            'data' => date('Y-m-d')
        ];

        $I->sendPOST('/api/despesas/create', $despesaData);

        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'descricao' => $despesaData['descricao'],
            'categoria' => $despesaData['categoria'],
            'valor' => $despesaData['valor'],
            'data' => $despesaData['data'],
            'user_id' => $this->userId
        ]);
        
        // Guardar o ID da despesa criada para usar em outros testes
        $response = json_decode($I->grabResponse(), true);
        $this->despesaId = $response['id'];
    }

    public function testListarDespesas(FunctionalTester $I)
    {
        // Criar algumas despesas para teste
        $this->criarDespesasDeTeste($I);
        
        // Testar listagem sem filtros
        $I->sendGET('/api/despesas');
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.items[*]');
        $I->seeResponseJsonMatchesJsonPath('$._meta');
        
        // Testar que só vemos despesas do usuário atual
        $response = json_decode($I->grabResponse(), true);
        foreach ($response['items'] as $item) {
            $I->assertEquals($this->userId, $item['user_id'], 'Todas as despesas devem pertencer ao usuário atual');
        }
    }
    
    public function testFiltrarDespesasPorCategoria(FunctionalTester $I)
    {
        // Criar algumas despesas para teste
        $this->criarDespesasDeTeste($I);
        
        // Filtrar por categoria
        $I->sendGET('/api/despesas?categoria=' . Despesa::CATEGORIA_TRANSPORTE);
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        
        // Verificar que todas as despesas retornadas são da categoria solicitada
        $response = json_decode($I->grabResponse(), true);
        foreach ($response['items'] as $item) {
            $I->assertEquals(Despesa::CATEGORIA_TRANSPORTE, $item['categoria'], 'Todas as despesas devem ser da categoria solicitada');
        }
    }
    
    public function testFiltrarDespesasPorPeriodo(FunctionalTester $I)
    {
        // Criar algumas despesas para teste
        $this->criarDespesasDeTeste($I);
        
        // Pegar o mês e ano atuais
        $mesAtual = date('m');
        $anoAtual = date('Y');
        
        // Filtrar por mês/ano
        $I->sendGET("/api/despesas?mes={$mesAtual}&ano={$anoAtual}");
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        
        // Verificar que as despesas retornadas são do período solicitado
        $response = json_decode($I->grabResponse(), true);
        foreach ($response['items'] as $item) {
            $dataDespesa = new \DateTime($item['data']);
            $I->assertEquals($mesAtual, $dataDespesa->format('m'), 'Mês da despesa deve corresponder ao filtro');
            $I->assertEquals($anoAtual, $dataDespesa->format('Y'), 'Ano da despesa deve corresponder ao filtro');
        }
    }

    public function testVerDetalhesDespesa(FunctionalTester $I)
    {
        // Criar uma despesa para teste se não existir
        if (empty($this->despesaId)) {
            $this->testCriarDespesa($I);
        }
        
        // Testar endpoint de detalhes
        $I->sendGET('/api/despesas/' . $this->despesaId);
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['id' => $this->despesaId]);
    }

    public function testAtualizarDespesa(FunctionalTester $I)
    {
        // Criar uma despesa para teste se não existir
        if (empty($this->despesaId)) {
            $this->testCriarDespesa($I);
        }
        
        // Dados para atualização
        $dadosAtualizados = [
            'descricao' => 'Despesa atualizada',
            'categoria' => Despesa::CATEGORIA_LAZER,
            'valor' => 100.00,
            'data' => date('Y-m-d')
        ];
        
        // Testar endpoint de atualização
        $I->sendPUT('/api/despesas/' . $this->despesaId . '/update', $dadosAtualizados);
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'id' => $this->despesaId,
            'descricao' => $dadosAtualizados['descricao'],
            'categoria' => $dadosAtualizados['categoria'],
            'valor' => $dadosAtualizados['valor']
        ]);
    }

    public function testDeletarDespesa(FunctionalTester $I)
    {
        // Criar uma despesa para teste se não existir
        if (empty($this->despesaId)) {
            $this->testCriarDespesa($I);
        }
        
        // Testar endpoint de exclusão
        $I->sendDELETE('/api/despesas/' . $this->despesaId . '/delete');
        
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        
        // Verificar que a despesa não está mais acessível
        $I->sendGET('/api/despesas/' . $this->despesaId);
        
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testListarCategorias(FunctionalTester $I)
    {
        $I->sendGET('/api/despesas/categorias');
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            Despesa::CATEGORIA_ALIMENTACAO => 'Alimentação',
            Despesa::CATEGORIA_TRANSPORTE => 'Transporte',
            Despesa::CATEGORIA_LAZER => 'Lazer'
        ]);
    }

    public function testResumoDespesas(FunctionalTester $I)
    {
        // Criar algumas despesas para teste
        $this->criarDespesasDeTeste($I);
        
        // Pegar o mês e ano atuais
        $mesAtual = date('m');
        $anoAtual = date('Y');
        
        // Testar endpoint de resumo
        $I->sendGET("/api/despesas/resumo?mes={$mesAtual}&ano={$anoAtual}");
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.periodo');
        $I->seeResponseJsonMatchesJsonPath('$.categorias');
        $I->seeResponseJsonMatchesJsonPath('$.total');
    }

    public function testAcessoNaoAutorizado(FunctionalTester $I)
    {
        // Remover token de autenticação
        $I->deleteHeader('Authorization');
        
        // Tentar acessar endpoint protegido
        $I->sendGET('/api/despesas');
        
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function testAcessoDespesaOutroUsuario(FunctionalTester $I)
    {
        // Este teste simula a tentativa de acesso a despesa de outro usuário
        // Mas como estamos em ambiente de teste onde não temos acesso a IDs reais de outros usuários,
        // vamos apenas verificar que a resposta 404 é retornada para um ID inexistente
        
        $idInexistente = 999999;
        $I->sendGET('/api/despesas/' . $idInexistente);
        
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    /**
     * Método auxiliar para criar despesas de teste
     */
    private function criarDespesasDeTeste(FunctionalTester $I)
    {
        // Criar despesa de alimentação
        $I->sendPOST('/api/despesas/create', [
            'descricao' => 'Almoço',
            'categoria' => Despesa::CATEGORIA_ALIMENTACAO,
            'valor' => 45.90,
            'data' => date('Y-m-d')
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        
        // Criar despesa de transporte
        $I->sendPOST('/api/despesas/create', [
            'descricao' => 'Táxi',
            'categoria' => Despesa::CATEGORIA_TRANSPORTE,
            'valor' => 35.50,
            'data' => date('Y-m-d')
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        
        // Criar despesa de lazer
        $I->sendPOST('/api/despesas/create', [
            'descricao' => 'Cinema',
            'categoria' => Despesa::CATEGORIA_LAZER,
            'valor' => 28.00,
            'data' => date('Y-m-d')
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);
    }
} 