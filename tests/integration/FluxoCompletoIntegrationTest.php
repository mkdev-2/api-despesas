<?php

use Codeception\Test\Unit;
use Codeception\Util\HttpCode;

// Incluir a classe MockSession para manipulação da sessão durante testes
require_once __DIR__ . '/../_support/MockSession.php';

/**
 * Teste de integração do fluxo completo de autenticação e gerenciamento de despesas
 * 
 * Este teste simula o fluxo completo de um usuário, desde o registro até o gerenciamento
 * de suas despesas, verificando a integração entre os diferentes módulos do sistema.
 */
class FluxoCompletoIntegrationTest extends Unit
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;
    
    private $token;
    private $userId;
    private $despesaId;
    private $username;
    private $email;
    private $password;

    protected function _before()
    {
        // Substituir a sessão normal por nossa mock session para evitar problemas com headers
        \Yii::$app->set('session', new MockSession());
        
        // Configurar headers para JSON
        $this->tester->haveHttpHeader('Content-Type', 'application/json');
        $this->tester->haveHttpHeader('Accept', 'application/json');
        
        // Gerar dados aleatórios para o usuário de teste
        $this->username = 'test_user_' . time();
        $this->email = 'test_' . time() . '@example.com';
        $this->password = 'password123';
    }

    /**
     * Teste do fluxo completo: registro > login > criar despesa > ver despesa > editar > excluir
     */
    public function testFluxoCompleto()
    {
        $this->etapa1_RegistrarUsuario();
        $this->etapa2_EfetuarLogin();
        $this->etapa3_VerPerfilUsuario();
        $this->etapa4_CriarDespesa();
        $this->etapa5_ListarDespesas();
        $this->etapa6_VerDetalhesDespesa();
        $this->etapa7_EditarDespesa();
        $this->etapa8_VerResumo();
        $this->etapa9_ExcluirDespesa();
    }
    
    /**
     * Etapa 1: Registro de um novo usuário
     */
    private function etapa1_RegistrarUsuario()
    {
        $this->tester->comment('Etapa 1: Registrando novo usuário');
        
        $this->tester->sendPOST('/api/auth/register', [
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password
        ]);
        
        $this->tester->seeResponseCodeIs(HttpCode::CREATED);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseContainsJson(['message' => 'Usuário criado com sucesso']);
        $this->tester->seeResponseJsonMatchesJsonPath('$.access_token');
        $this->tester->seeResponseJsonMatchesJsonPath('$.user.id');
        
        // Extrair token e ID do usuário
        $response = json_decode($this->tester->grabResponse(), true);
        $this->token = $response['access_token'];
        $this->userId = $response['user']['id'];
        
        // Verificar que dados foram extraídos
        $this->assertNotEmpty($this->token, 'Token de acesso não deveria estar vazio');
        $this->assertNotEmpty($this->userId, 'ID do usuário não deveria estar vazio');
    }
    
    /**
     * Etapa 2: Login com o usuário criado
     */
    private function etapa2_EfetuarLogin()
    {
        $this->tester->comment('Etapa 2: Efetuando login');
        
        $this->tester->sendPOST('/api/auth/login', [
            'email' => $this->email,
            'password' => $this->password
        ]);
        
        $this->tester->seeResponseCodeIs(HttpCode::OK);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseJsonMatchesJsonPath('$.access_token');
        $this->tester->seeResponseContainsJson(['user' => ['email' => $this->email]]);
        
        // Atualizar token
        $response = json_decode($this->tester->grabResponse(), true);
        $this->token = $response['access_token'];
        
        // Adicionar token para requisições autenticadas
        $this->tester->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
    }
    
    /**
     * Etapa 3: Verificar perfil do usuário
     */
    private function etapa3_VerPerfilUsuario()
    {
        $this->tester->comment('Etapa 3: Verificando perfil do usuário');
        
        $this->tester->sendGET('/api/auth/profile');
        
        $this->tester->seeResponseCodeIs(HttpCode::OK);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseContainsJson([
            'username' => $this->username,
            'email' => $this->email,
            'id' => $this->userId
        ]);
    }
    
    /**
     * Etapa 4: Criar uma nova despesa
     */
    private function etapa4_CriarDespesa()
    {
        $this->tester->comment('Etapa 4: Criando nova despesa');
        
        $despesaData = [
            'descricao' => 'Despesa de teste de integração',
            'categoria' => 'alimentacao',
            'valor' => 75.50,
            'data' => date('Y-m-d')
        ];
        
        $this->tester->sendPOST('/api/despesas/create', $despesaData);
        
        $this->tester->seeResponseCodeIs(HttpCode::CREATED);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseContainsJson([
            'descricao' => $despesaData['descricao'],
            'categoria' => $despesaData['categoria'],
            'valor' => $despesaData['valor'],
            'data' => $despesaData['data'],
            'user_id' => $this->userId
        ]);
        
        // Extrair ID da despesa criada
        $response = json_decode($this->tester->grabResponse(), true);
        $this->despesaId = $response['id'];
        
        $this->assertNotEmpty($this->despesaId, 'ID da despesa não deveria estar vazio');
    }
    
    /**
     * Etapa 5: Listar despesas do usuário
     */
    private function etapa5_ListarDespesas()
    {
        $this->tester->comment('Etapa 5: Listando despesas do usuário');
        
        $this->tester->sendGET('/api/despesas');
        
        $this->tester->seeResponseCodeIs(HttpCode::OK);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseJsonMatchesJsonPath('$.items[*]');
        $this->tester->seeResponseJsonMatchesJsonPath('$._meta');
        
        // Verificar que a despesa recém-criada está na lista
        $this->tester->seeResponseContainsJson([
            'items' => [
                ['id' => $this->despesaId]
            ]
        ]);
    }
    
    /**
     * Etapa 6: Ver detalhes de uma despesa específica
     */
    private function etapa6_VerDetalhesDespesa()
    {
        $this->tester->comment('Etapa 6: Verificando detalhes da despesa');
        
        $this->tester->sendGET('/api/despesas/' . $this->despesaId);
        
        $this->tester->seeResponseCodeIs(HttpCode::OK);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseContainsJson(['id' => $this->despesaId]);
        $this->tester->seeResponseContainsJson(['user_id' => $this->userId]);
    }
    
    /**
     * Etapa 7: Editar uma despesa existente
     */
    private function etapa7_EditarDespesa()
    {
        $this->tester->comment('Etapa 7: Editando despesa');
        
        $dadosAtualizados = [
            'descricao' => 'Despesa atualizada no teste integrado',
            'categoria' => 'transporte',
            'valor' => 100.00,
            'data' => date('Y-m-d')
        ];
        
        $this->tester->sendPUT('/api/despesas/' . $this->despesaId . '/update', $dadosAtualizados);
        
        $this->tester->seeResponseCodeIs(HttpCode::OK);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseContainsJson([
            'id' => $this->despesaId,
            'descricao' => $dadosAtualizados['descricao'],
            'categoria' => $dadosAtualizados['categoria'],
            'valor' => $dadosAtualizados['valor'],
            'user_id' => $this->userId
        ]);
    }
    
    /**
     * Etapa 8: Ver resumo de despesas
     */
    private function etapa8_VerResumo()
    {
        $this->tester->comment('Etapa 8: Verificando resumo de despesas');
        
        // Obter o mês e ano atuais
        $mesAtual = date('m');
        $anoAtual = date('Y');
        
        $this->tester->sendGET("/api/despesas/resumo?mes={$mesAtual}&ano={$anoAtual}");
        
        $this->tester->seeResponseCodeIs(HttpCode::OK);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseJsonMatchesJsonPath('$.periodo');
        $this->tester->seeResponseJsonMatchesJsonPath('$.categorias');
        $this->tester->seeResponseJsonMatchesJsonPath('$.total');
        
        // Verificar que nosso período está correto
        $this->tester->seeResponseContainsJson([
            'periodo' => [
                'mes' => intval($mesAtual),
                'ano' => intval($anoAtual)
            ]
        ]);
        
        // Verificar que a categoria da nossa despesa está no resumo
        $response = json_decode($this->tester->grabResponse(), true);
        
        $categoriaEncontrada = false;
        foreach ($response['categorias'] as $categoria) {
            if ($categoria['categoria'] === 'transporte') {
                $categoriaEncontrada = true;
                // Verificar que o valor corresponde ao esperado
                $this->assertEquals(100.00, $categoria['total'], 'O valor total da categoria deveria corresponder à nossa despesa');
                break;
            }
        }
        
        $this->assertTrue($categoriaEncontrada, 'A categoria da nossa despesa deveria estar no resumo');
    }
    
    /**
     * Etapa 9: Excluir uma despesa
     */
    private function etapa9_ExcluirDespesa()
    {
        $this->tester->comment('Etapa 9: Excluindo despesa');
        
        $this->tester->sendDELETE('/api/despesas/' . $this->despesaId . '/delete');
        
        $this->tester->seeResponseCodeIs(HttpCode::NO_CONTENT);
        
        // Verificar que a despesa não está mais acessível
        $this->tester->sendGET('/api/despesas/' . $this->despesaId);
        
        $this->tester->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }
} 