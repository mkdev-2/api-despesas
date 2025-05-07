<?php

namespace tests\unit\models;

use app\modules\financeiro\models\Despesa;
use app\modules\financeiro\models\DespesaQuery;
use app\modules\usuarios\models\User;
use Codeception\Test\Unit;
use tests\unit\MockUserComponent;
use Yii;

class DespesaTest extends Unit
{
    private $testUser;
    private $userComponent;
    
    protected function _before()
    {
        // Criar um usuário para testes
        $username = 'despesa_test_user_' . time();
        $email = 'despesa_test_' . time() . '@example.com';
        $password = 'password123';
        
        // Apenas para os testes, criamos um usuário diretamente sem tentar salvá-lo no banco
        $this->testUser = new User();
        $this->testUser->id = 1; // ID fixo para testes
        $this->testUser->username = $username;
        $this->testUser->email = $email;
        $this->testUser->setPassword($password);
        $this->testUser->generateAuthKey();
        
        // Configurar o mock do componente user para autenticar o usuário de teste
        $this->userComponent = new MockUserComponent();
        Yii::$app->set('user', $this->userComponent);
        $this->userComponent->login($this->testUser);

        // Mock da sessão para testes
        $sessionMock = new \tests\unit\widgets\MockSession();
        Yii::$app->set('session', $sessionMock);
    }
    
    protected function _after()
    {
        // Fazer logout
        $this->userComponent->logout();
    }
    
    public function _fixtures()
    {
        return []; // Não usamos fixtures para evitar problemas com chaves estrangeiras
    }
    
    public function testGetCategorias()
    {
        $categorias = Despesa::getCategorias();
        
        $this->assertIsArray($categorias, 'O método getCategorias deveria retornar um array');
        $this->assertContains(Despesa::CATEGORIA_ALIMENTACAO, $categorias, 'Deveria conter a categoria alimentação');
        $this->assertContains(Despesa::CATEGORIA_TRANSPORTE, $categorias, 'Deveria conter a categoria transporte');
        $this->assertContains(Despesa::CATEGORIA_LAZER, $categorias, 'Deveria conter a categoria lazer');
    }
    
    public function testValidation()
    {
        // Teste de valor negativo
        $despesa = new Despesa();
        $despesa->descricao = 'Teste de despesa';
        $despesa->categoria = 'alimentacao';
        $despesa->valor = -10;
        $despesa->data = date('Y-m-d');
        $despesa->user_id = $this->testUser->id;
        
        $this->assertFalse($despesa->validate(['valor']), 'Validação deveria falhar com valor negativo');
        $this->assertArrayHasKey('valor', $despesa->errors, 'Erro deveria estar no campo valor');
        
        // Teste de categoria inválida
        $despesa = new Despesa();
        $despesa->descricao = 'Teste de despesa';
        $despesa->categoria = 'categoria_invalida';
        $despesa->valor = 50.50;
        $despesa->data = date('Y-m-d');
        $despesa->user_id = $this->testUser->id;
        
        $this->assertFalse($despesa->validate(['categoria']), 'Validação deveria falhar com categoria inválida');
        $this->assertArrayHasKey('categoria', $despesa->errors, 'Erro deveria estar no campo categoria');
        
        // Teste de data inválida
        $despesa = new Despesa();
        $despesa->descricao = 'Teste de despesa';
        $despesa->categoria = 'alimentacao';
        $despesa->valor = 50.50;
        $despesa->data = 'data-invalida';
        $despesa->user_id = $this->testUser->id;
        
        $this->assertFalse($despesa->validate(['data']), 'Validação deveria falhar com data inválida');
        $this->assertArrayHasKey('data', $despesa->errors, 'Erro deveria estar no campo data');
        
        // Teste de campos obrigatórios
        $despesa = new Despesa();
        $this->assertFalse($despesa->validate(['descricao']), 'Validação deveria falhar sem descrição');
        $this->assertArrayHasKey('descricao', $despesa->errors, 'Erro deveria estar no campo descricao');
    }
    
    public function testBeforeValidate()
    {
        // Teste do método beforeValidate para verificar se ele atribui o user_id corretamente
        $despesa = new Despesa();
        $despesa->descricao = 'Teste de criação';
        $despesa->categoria = 'transporte';
        $despesa->valor = 25.75;
        $despesa->data = date('Y-m-d');
        
        // Inicialmente, não definimos user_id
        $this->assertNull($despesa->user_id, 'User ID deveria ser null inicialmente');
        
        // Chamar beforeValidate diretamente
        $despesa->beforeValidate();
        
        // Verificar se o user_id foi preenchido
        $this->assertEquals($this->testUser->id, $despesa->user_id, 'User ID deveria ser preenchido pelo beforeValidate');
    }
    
    public function testModelRelations()
    {
        // Teste de relação com usuário
        $despesa = new Despesa();
        $despesa->user_id = $this->testUser->id;
        
        // Testar o método getUser
        $relation = $despesa->getUser();
        $this->assertInstanceOf('yii\db\ActiveQuery', $relation, 'getUser deveria retornar uma ActiveQuery');
        
        // Testar DespesaQuery
        $query = Despesa::find();
        $this->assertInstanceOf('app\modules\financeiro\models\DespesaQuery', $query, 'O método find deveria retornar uma instância de DespesaQuery');
        
        // Testar métodos de escopo
        $query = new DespesaQuery(Despesa::class);
        $this->assertInstanceOf('app\modules\financeiro\models\DespesaQuery', $query->porCategoria('alimentacao'), 'O método porCategoria deveria retornar uma instância de DespesaQuery');
        $this->assertInstanceOf('app\modules\financeiro\models\DespesaQuery', $query->entreDatas('2023-01-01', '2023-12-31'), 'O método entreDatas deveria retornar uma instância de DespesaQuery');
        $this->assertInstanceOf('app\modules\financeiro\models\DespesaQuery', $query->ordenarPorData(), 'O método ordenarPorData deveria retornar uma instância de DespesaQuery');
        $this->assertInstanceOf('app\modules\financeiro\models\DespesaQuery', $query->doUsuario(1), 'O método doUsuario deveria retornar uma instância de DespesaQuery');
    }
} 