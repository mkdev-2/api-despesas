<?php

use app\modules\financeiro\models\Despesa;
use app\modules\usuarios\models\User;
use Codeception\Test\Unit;

// Incluir a classe MockSession para manipulação da sessão durante testes
require_once __DIR__ . '/../_support/MockSession.php';

/**
 * Teste de integração entre usuários e despesas
 * 
 * Este teste verifica a interação entre usuários e suas despesas,
 * garantindo que os relacionamentos e regras de negócio funcionem
 * corretamente entre esses dois módulos.
 */
class UsuarioDespesasIntegrationTest extends Unit
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;
    
    /**
     * @var User
     */
    private $user;
    
    /**
     * @var Despesa[]
     */
    private $despesas;

    protected function _before()
    {
        // Substituir a sessão normal por nossa mock session para evitar problemas com headers
        \Yii::$app->set('session', new MockSession());
        
        // Carregar fixtures
        $this->tester->haveFixtures([
            'users' => [
                'class' => \tests\unit\fixtures\UserFixture::class,
            ],
            'despesas' => [
                'class' => \tests\unit\fixtures\DespesaFixture::class,
            ]
        ]);
        
        // Obter usuário do fixture
        $this->user = User::findOne(1); // Usuário 'admin'
    }

    /**
     * Testa a relação entre usuário e suas despesas
     */
    public function testUsuarioPodeAcessarSuasDespesas()
    {
        // Obter despesas do usuário através do relacionamento
        $despesasDoUsuario = $this->user->getDespesas()->all();
        
        // Verificar que o usuário tem despesas
        $this->assertNotEmpty($despesasDoUsuario, 'O usuário deveria ter despesas');
        
        // Verificar que todas as despesas pertencem ao usuário correto
        foreach ($despesasDoUsuario as $despesa) {
            $this->assertEquals($this->user->id, $despesa->user_id, 'A despesa deve pertencer ao usuário correto');
        }
    }
    
    /**
     * Testa a filtragem de despesas por categorias
     */
    public function testFiltragemDespesasPorCategoria()
    {
        // Obter despesas de alimentação do usuário
        $despesasAlimentacao = Despesa::find()
            ->active()
            ->doUsuario($this->user->id)
            ->porCategoria(Despesa::CATEGORIA_ALIMENTACAO)
            ->all();
            
        // Verificar que foram encontradas despesas
        $this->assertNotEmpty($despesasAlimentacao, 'Deveriam existir despesas de alimentação');
        
        // Verificar que todas as despesas são da categoria correta
        foreach ($despesasAlimentacao as $despesa) {
            $this->assertEquals(
                Despesa::CATEGORIA_ALIMENTACAO,
                $despesa->categoria,
                'Todas as despesas devem ser da categoria alimentação'
            );
        }
    }
    
    /**
     * Testa a criação de uma nova despesa e associação com usuário
     */
    public function testCriacaoDespesaParaUsuario()
    {
        // Criar uma nova despesa
        $despesa = new Despesa();
        $despesa->descricao = 'Despesa de teste de integração';
        $despesa->categoria = Despesa::CATEGORIA_LAZER;
        $despesa->valor = 99.90;
        $despesa->data = date('Y-m-d');
        $despesa->user_id = $this->user->id;
        
        // Salvar a despesa
        $resultado = $despesa->save();
        
        // Verificações
        $this->assertTrue($resultado, 'A despesa deveria ser salva com sucesso');
        $this->assertNotNull($despesa->id, 'A despesa deveria ter um ID após salvar');
        
        // Verificar a relação com o usuário
        $this->assertEquals($this->user->id, $despesa->user_id, 'A despesa deve estar associada ao usuário correto');
        
        // Obter despesas atualizadas do usuário
        $despesasDoUsuario = $this->user->getDespesas()->all();
        
        // Verificar que a nova despesa está incluída no conjunto de despesas do usuário
        $encontrou = false;
        foreach ($despesasDoUsuario as $d) {
            if ($d->id == $despesa->id) {
                $encontrou = true;
                break;
            }
        }
        
        $this->assertTrue($encontrou, 'A nova despesa deve estar incluída nas despesas do usuário');
    }
    
    /**
     * Testa o cálculo do total de despesas por categoria
     */
    public function testCalculoTotalDespesasPorCategoria()
    {
        // Obter o mês e ano atuais
        $mesAtual = date('m');
        $anoAtual = date('Y');
        
        // Calcular datas de início e fim do período
        $inicio = "$anoAtual-$mesAtual-01";
        $fim = date('Y-m-t', strtotime($inicio));
        
        // Buscar as despesas do período agrupadas por categoria
        $despesasPorCategoria = Despesa::find()
            ->select(['categoria', 'SUM(valor) as total'])
            ->where(['user_id' => $this->user->id])
            ->andWhere(['between', 'data', $inicio, $fim])
            ->andWhere(['deleted_at' => null])
            ->groupBy('categoria')
            ->asArray()
            ->all();
            
        // Verificar que há resultados
        $this->assertNotEmpty($despesasPorCategoria, 'Deveria haver despesas para o período');
        
        // Calcular o total esperado para cada categoria
        $totaisEsperados = [];
        $despesasDoMes = Despesa::find()
            ->where(['user_id' => $this->user->id])
            ->andWhere(['between', 'data', $inicio, $fim])
            ->andWhere(['deleted_at' => null])
            ->all();
            
        foreach ($despesasDoMes as $despesa) {
            if (!isset($totaisEsperados[$despesa->categoria])) {
                $totaisEsperados[$despesa->categoria] = 0;
            }
            $totaisEsperados[$despesa->categoria] += $despesa->valor;
        }
        
        // Verificar se os totais calculados correspondem aos agrupados
        foreach ($despesasPorCategoria as $item) {
            $this->assertArrayHasKey(
                $item['categoria'],
                $totaisEsperados,
                "A categoria {$item['categoria']} deveria estar nos totais esperados"
            );
            
            $this->assertEquals(
                $totaisEsperados[$item['categoria']],
                $item['total'],
                "O total para a categoria {$item['categoria']} deveria ser {$totaisEsperados[$item['categoria']]}"
            );
        }
    }
    
    /**
     * Testa a exclusão lógica (soft delete) de uma despesa
     */
    public function testSoftDeleteDespesa()
    {
        // Obter a primeira despesa do usuário
        $despesa = Despesa::find()
            ->where(['user_id' => $this->user->id])
            ->andWhere(['deleted_at' => null])
            ->one();
            
        $this->assertNotNull($despesa, 'Deveria existir pelo menos uma despesa não deletada');
        $id = $despesa->id;
        
        // Realizar soft delete usando o método softDelete()
        $resultado = $despesa->softDelete();
        $this->assertTrue($resultado, 'O método softDelete() deveria retornar true');
        
        // Consulta manual para verificar se despesas excluídas não aparecem
        $despesaAtiva = Despesa::find()
            ->where(['id' => $id])
            ->andWhere(['deleted_at' => null])
            ->one();
            
        $this->assertNull($despesaAtiva, 'A despesa não deveria ser encontrada quando buscamos por deleted_at = null');
        
        // Verificar que a despesa ainda existe no banco
        $despesaDeletada = Despesa::find()
            ->where(['id' => $id])
            ->one();
            
        $this->assertNotNull($despesaDeletada, 'A despesa ainda deveria existir no banco');
        $this->assertNotNull($despesaDeletada->deleted_at, 'A despesa deveria ter deleted_at preenchido');
    }
} 