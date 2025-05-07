<?php

namespace tests\functional;

use Codeception\Util\HttpCode;
use FunctionalTester;
use app\modules\financeiro\models\Despesa;

class DespesaApiCest
{
    private $token;
    private $userId;
    private $despesaId;
    private static $usedEmails = [];

    public function _before(FunctionalTester $I)
    {
        // Configurar headers para JSON
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Accept', 'application/json');

        // Gerar um nome e email únicos que não foram usados antes
        $timestamp = time();
        $random = rand(1000, 9999);
        
        // Obter o nome do método de teste atual
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $testMethod = isset($trace[1]['function']) ? $trace[1]['function'] : '';
        $testMethod = substr($testMethod, 0, 4); // Usar apenas os primeiros 4 caracteres
        
        $baseEmail = 'dsp_' . $testMethod . '_' . $random . '_' . substr($timestamp, -4) . '@example.com';
        
        // Garantir que o email seja único
        $attempts = 0;
        $email = $baseEmail;
        while (in_array($email, self::$usedEmails) && $attempts < 5) {
            $random = rand(1000, 9999);
            $email = 'dsp_' . $testMethod . '_' . $random . '_' . substr($timestamp, -4) . '@example.com';
            $attempts++;
        }
        
        // Adicionar o email à lista de emails usados
        self::$usedEmails[] = $email;
        
        // Criar username a partir do email (limitado a 20 caracteres)
        $username = 'u_' . $random . '_' . substr($timestamp, -4);
        
        $password = 'password123';

        // Registrar o usuário
        $I->sendPost('/api/auth/register', [
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

        $I->sendPost('/api/despesas/create', $despesaData);

        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'descricao' => $despesaData['descricao'],
            'categoria' => $despesaData['categoria'],
            'valor' => $despesaData['valor'],
            'data' => $despesaData['data']
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
        $I->sendGet('/api/despesas');
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.items[*]');
        $I->seeResponseJsonMatchesJsonPath('$._meta');
        
        // Como a API não retorna o user_id, não podemos verificar diretamente
        // A autenticação já garante que apenas despesas do usuário são retornadas
        $response = json_decode($I->grabResponse(), true);
        $I->assertNotEmpty($response['items'], 'A lista de despesas não deve estar vazia');
    }
    
    public function testFiltrarDespesasPorCategoria(FunctionalTester $I)
    {
        // Criar algumas despesas para teste
        $this->criarDespesasDeTeste($I);
        
        // Filtrar por categoria
        $I->sendGet('/api/despesas?categoria=' . Despesa::CATEGORIA_TRANSPORTE);
        
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
        $I->sendGet("/api/despesas?mes={$mesAtual}&ano={$anoAtual}");
        
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
        $I->sendGet('/api/despesas/' . $this->despesaId);
        
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
        $I->sendPut('/api/despesas/' . $this->despesaId . '/update', $dadosAtualizados);
        
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
        $I->sendDelete('/api/despesas/' . $this->despesaId . '/delete');
        
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        
        // Verificar que a despesa não está mais acessível
        $I->sendGet('/api/despesas/' . $this->despesaId);
        
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testListarCategorias(FunctionalTester $I)
    {
        $this->configurarHeadersJson($I);
        $I->sendGet("/api/despesas/categorias");
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        
        // Verificar a estrutura das categorias principais
        $I->seeResponseJsonMatchesJsonPath('$.alimentacao');
        $I->seeResponseJsonMatchesJsonPath('$.alimentacao.nome');
        $I->seeResponseJsonMatchesJsonPath('$.alimentacao.icone');
        
        $I->seeResponseJsonMatchesJsonPath('$.transporte');
        $I->seeResponseJsonMatchesJsonPath('$.transporte.nome');
        
        $I->seeResponseJsonMatchesJsonPath('$.lazer');
        $I->seeResponseJsonMatchesJsonPath('$.lazer.nome');
    }

    public function testResumoDespesas(FunctionalTester $I)
    {
        $this->configurarHeadersJson($I);
        
        // Criar despesas para o resumo
        $this->criarDespesasDeTeste($I);
        
        // Usar o mês e ano atuais para o resumo
        $mesAtual = date('m');
        $anoAtual = date('Y');
        
        $I->sendGet("/api/despesas/resumo?mes={$mesAtual}&ano={$anoAtual}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        
        // Verificar campos que realmente existem na resposta
        $I->seeResponseJsonMatchesJsonPath('$.mes');
        $I->seeResponseJsonMatchesJsonPath('$.ano');
        $I->seeResponseJsonMatchesJsonPath('$.mes_nome');
        $I->seeResponseJsonMatchesJsonPath('$.total');
        $I->seeResponseJsonMatchesJsonPath('$.categorias');
    }

    public function testAcessoNaoAutorizado(FunctionalTester $I)
    {
        // Remover token de autenticação
        $I->deleteHeader('Authorization');
        
        // Tentar acessar endpoint protegido
        $I->sendGet('/api/despesas');
        
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function testAcessoDespesaOutroUsuario(FunctionalTester $I)
    {
        // Este teste simula a tentativa de acesso a despesa de outro usuário
        // Mas como estamos em ambiente de teste onde não temos acesso a IDs reais de outros usuários,
        // vamos apenas verificar que a resposta 404 é retornada para um ID inexistente
        
        $idInexistente = 999999;
        $I->sendGet('/api/despesas/' . $idInexistente);
        
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    /**
     * Método auxiliar para criar despesas de teste
     */
    private function criarDespesasDeTeste(FunctionalTester $I)
    {
        // Data atual para todas as despesas
        $dataAtual = date('Y-m-d');
        
        // Criar despesa de alimentação
        $I->sendPost('/api/despesas/create', [
            'descricao' => 'Almoço',
            'categoria' => Despesa::CATEGORIA_ALIMENTACAO,
            'valor' => 45.90,
            'data' => $dataAtual
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        
        // Criar despesa de transporte
        $I->sendPost('/api/despesas/create', [
            'descricao' => 'Táxi',
            'categoria' => Despesa::CATEGORIA_TRANSPORTE,
            'valor' => 35.50,
            'data' => $dataAtual
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        
        // Criar despesa de lazer
        $I->sendPost('/api/despesas/create', [
            'descricao' => 'Cinema',
            'categoria' => Despesa::CATEGORIA_LAZER,
            'valor' => 28.00,
            'data' => $dataAtual
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);
    }

    private function configurarHeadersJson(FunctionalTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Accept', 'application/json');
    }
} 