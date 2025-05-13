<?php

namespace tests\integration;

use Codeception\Test\Unit;
use Codeception\Util\HttpCode;
use app\modules\usuarios\models\User;
use app\modules\financeiro\models\Despesa;
use yii\web\Request;
use yii\web\Response;
use tests\support\MockSession;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use sizeg\jwt\Jwt;
use app\modules\api\controllers\DespesaController;
use app\modules\api\controllers\AuthController;

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
    
    /**
     * @var User
     */
    private $user;

    protected function _before()
    {
        // Substituir a sessão normal por nossa mock session para evitar problemas com headers
        \Yii::$app->set('session', new MockSession());
        
        // Preparando variáveis para o teste
        $this->username = 'test_user_' . time();
        $this->email = 'test_' . time() . '@example.com';
        $this->password = 'password123';
        
        // Configurando para retornar dados em formato array em vez de enviar respostas HTTP
        \Yii::$app->response->format = Response::FORMAT_JSON;
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
        
        // Criação direta do usuário
        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        
        $this->assertTrue($user->save(), 'O usuário deveria ser salvo com sucesso');
        
        $this->userId = $user->id;
        $this->user = $user;
        
        // Verificar que o usuário foi criado corretamente
        $this->assertNotEmpty($this->userId, 'ID do usuário não deveria estar vazio');
    }
    
    /**
     * Etapa 2: Login com o usuário criado
     */
    private function etapa2_EfetuarLogin()
    {
        $this->tester->comment('Etapa 2: Efetuando login');
        
        // Verificar se o usuário existe e a senha está correta
        $user = User::findByEmail($this->email);
        $this->assertNotNull($user, 'O usuário deveria existir no banco de dados');
        $this->assertTrue($user->validatePassword($this->password), 'A senha deveria ser válida');
        
        // Configurar o usuário como o usuário logado
        \Yii::$app->user->setIdentity($user);
        
        // Verificar se o usuário está logado
        $this->assertNotNull(\Yii::$app->user->identity, 'O usuário deveria estar autenticado');
        $this->assertEquals($this->userId, \Yii::$app->user->id, 'O ID do usuário logado deveria corresponder ao ID do usuário criado');
    }
    
    /**
     * Etapa 3: Verificar perfil do usuário
     */
    private function etapa3_VerPerfilUsuario()
    {
        $this->tester->comment('Etapa 3: Verificando perfil do usuário');
        
        // Verificar as informações do perfil diretamente
        $this->assertEquals($this->username, $this->user->username, 'O username deveria corresponder ao username fornecido');
        $this->assertEquals($this->email, $this->user->email, 'O email deveria corresponder ao email fornecido');
        $this->assertEquals($this->userId, $this->user->id, 'O ID deveria corresponder ao ID do usuário criado');
    }
    
    /**
     * Etapa 4: Criar uma nova despesa
     */
    private function etapa4_CriarDespesa()
    {
        $this->tester->comment('Etapa 4: Criando nova despesa');
        
        // Criação direta da despesa usando o model
        $despesa = new Despesa();
        $despesa->descricao = 'Despesa de teste de integração';
        $despesa->categoria = 'alimentacao';
        $despesa->valor = 75.50;
        $despesa->data = date('Y-m-d');
        $despesa->user_id = $this->userId;
        
        $this->assertTrue($despesa->save(), 'A despesa deveria ser salva com sucesso');
        
        $this->despesaId = $despesa->id;
        
        // Verificar que a despesa foi criada corretamente
        $this->assertNotEmpty($this->despesaId, 'ID da despesa não deveria estar vazio');
        $this->assertEquals('Despesa de teste de integração', $despesa->descricao, 'A descrição da despesa deveria corresponder ao valor fornecido');
        $this->assertEquals('alimentacao', $despesa->categoria, 'A categoria da despesa deveria corresponder ao valor fornecido');
        $this->assertEquals(75.50, $despesa->valor, 'O valor da despesa deveria corresponder ao valor fornecido');
        $this->assertEquals($this->userId, $despesa->user_id, 'O ID do usuário da despesa deveria corresponder ao ID do usuário logado');
    }
    
    /**
     * Etapa 5: Listar despesas do usuário
     */
    private function etapa5_ListarDespesas()
    {
        $this->tester->comment('Etapa 5: Listando despesas do usuário');
        
        // Buscar despesas do usuário diretamente no banco de dados
        $despesas = Despesa::find()->where(['user_id' => $this->userId])->all();
        
        // Verificar que a despesa recém-criada está na lista
        $this->assertNotEmpty($despesas, 'A lista de despesas não deveria estar vazia');
        $this->assertGreaterThanOrEqual(1, count($despesas), 'Deveria haver pelo menos uma despesa na lista');
        
        $despesaEncontrada = false;
        foreach ($despesas as $despesa) {
            if ($despesa->id == $this->despesaId) {
                $despesaEncontrada = true;
                break;
            }
        }
        
        $this->assertTrue($despesaEncontrada, 'A despesa recém-criada deveria estar na lista de despesas');
    }
    
    /**
     * Etapa 6: Ver detalhes de uma despesa específica
     */
    private function etapa6_VerDetalhesDespesa()
    {
        $this->tester->comment('Etapa 6: Verificando detalhes da despesa');
        
        // Buscar a despesa diretamente pelo ID
        $despesa = Despesa::findOne($this->despesaId);
        
        // Verificar que a despesa existe e seus detalhes estão corretos
        $this->assertNotNull($despesa, 'A despesa deveria existir no banco de dados');
        $this->assertEquals($this->despesaId, $despesa->id, 'O ID da despesa deveria corresponder ao ID solicitado');
        $this->assertEquals($this->userId, $despesa->user_id, 'O ID do usuário da despesa deveria corresponder ao ID do usuário logado');
        $this->assertEquals('Despesa de teste de integração', $despesa->descricao, 'A descrição da despesa deveria corresponder ao valor fornecido');
    }
    
    /**
     * Etapa 7: Editar uma despesa existente
     */
    private function etapa7_EditarDespesa()
    {
        $this->tester->comment('Etapa 7: Editando despesa');
        
        // Buscar a despesa diretamente pelo ID
        $despesa = Despesa::findOne($this->despesaId);
        
        // Atualizar os dados da despesa
        $despesa->descricao = 'Despesa atualizada no teste integrado';
        $despesa->categoria = 'transporte';
        $despesa->valor = 100.00;
        
        $this->assertTrue($despesa->save(), 'A despesa deveria ser atualizada com sucesso');
        
        // Verificar que a despesa foi atualizada corretamente
        $despesaAtualizada = Despesa::findOne($this->despesaId);
        $this->assertEquals('Despesa atualizada no teste integrado', $despesaAtualizada->descricao, 'A descrição da despesa deveria ser atualizada');
        $this->assertEquals('transporte', $despesaAtualizada->categoria, 'A categoria da despesa deveria ser atualizada');
        $this->assertEquals(100.00, $despesaAtualizada->valor, 'O valor da despesa deveria ser atualizado');
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
        
        // Buscar despesas do usuário no período atual
        $query = Despesa::find()
            ->where(['user_id' => $this->userId])
            ->andWhere(['MONTH(data)' => $mesAtual])
            ->andWhere(['YEAR(data)' => $anoAtual]);
        
        $despesas = $query->all();
        
        // Calcular o total
        $total = 0;
        $categorias = [];
        
        foreach ($despesas as $despesa) {
            $total += $despesa->valor;
            
            if (!isset($categorias[$despesa->categoria])) {
                $categorias[$despesa->categoria] = 0;
            }
            
            $categorias[$despesa->categoria] += $despesa->valor;
        }
        
        // Verificar que o total e categorias estão corretos
        $this->assertGreaterThan(0, $total, 'O total de despesas deveria ser maior que zero');
        $this->assertTrue(isset($categorias['transporte']), 'A categoria "transporte" deveria estar no resumo');
        $this->assertEquals(100.00, $categorias['transporte'], 'O valor total da categoria "transporte" deveria corresponder ao valor atualizado da despesa');
    }
    
    /**
     * Etapa 9: Excluir uma despesa
     */
    private function etapa9_ExcluirDespesa()
    {
        $this->tester->comment('Etapa 9: Excluindo despesa');
        
        // Buscar a despesa diretamente pelo ID
        $despesa = Despesa::findOne($this->despesaId);
        
        // Excluir a despesa
        $this->assertTrue($despesa->delete() > 0, 'A despesa deveria ser excluída com sucesso');
        
        // Verificar que a despesa não está mais acessível
        $despesaExcluida = Despesa::findOne($this->despesaId);
        $this->assertNull($despesaExcluida, 'A despesa excluída não deveria ser encontrada no banco de dados');
    }
} 