<?php
namespace app\modules\financeiro\controllers;

use Yii;
use yii\rest\ActiveController;
use app\modules\financeiro\models\Despesa;
use yii\filters\Cors;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use sizeg\jwt\JwtHttpBearerAuth;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;

/**
 * Controlador de Despesas.
 * 
 * Fornece endpoints para:
 * - Cadastro de Despesas
 * - Edição de Despesas
 * - Exclusão de Despesas
 * - Listagem de Despesas (com filtros)
 * - Detalhamento de Despesas
 */
class DespesaController extends ActiveController
{
    public $modelClass = 'app\modules\financeiro\models\Despesa';
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Remove o autenticador padrão para substituir pelo JWT
        unset($behaviors['authenticator']);
        
        // Adiciona configuração de CORS específica para este controller
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['http://localhost:5173', 'http://localhost:8080', 'http://localhost'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'accept', 'origin', 'X-CSRF-Token'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 3600,
                'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page', 'X-Pagination-Page-Count', 'X-Pagination-Per-Page', 'X-Pagination-Total-Count'],
                'Access-Control-Allow-Origin' => ['http://localhost:5173', 'http://localhost:8080', 'http://localhost'],
                'Access-Control-Allow-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'accept', 'origin', 'X-CSRF-Token'],
            ],
        ];
        
        // Adiciona autenticação JWT
        $behaviors['authenticator'] = [
            'class' => JwtHttpBearerAuth::class,
            'except' => ['options', 'diagnosticar'],
        ];
        
        // Adiciona controle de acesso baseado em regras
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'], // Apenas usuários autenticados
                ]
            ],
            'except' => ['options', 'diagnosticar'],
        ];
        
        return $behaviors;
    }
    
    /**
     * Sobrescreve as actions padrão para personalizar
     */
    public function actions()
    {
        $actions = parent::actions();
        
        // Customiza a action de index para permitir filtros
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        
        // Adiciona suporte a OPTIONS para CORS
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        
        return $actions;
    }
    
    /**
     * Prepara o DataProvider para listar despesas com filtros aplicados
     * Implementa filtros de categoria e período (mês/ano)
     * Implementa ordenação por data
     */
    public function prepareDataProvider()
    {
        $request = Yii::$app->request;
        $userId = Yii::$app->user->id;
        
        // Inicia a query limitando às despesas do usuário atual
        $query = Despesa::find()->active()->doUsuario($userId);
        
        // Filtra por categoria se especificado
        $categoria = $request->get('categoria');
        if (!empty($categoria)) {
            $query->porCategoria($categoria);
        }
        
        // Filtra por período (mês/ano) se especificado
        $mes = $request->get('mes');
        $ano = $request->get('ano');
        if (!empty($mes) && !empty($ano)) {
            $query->porPeriodo($mes, $ano);
        }
        
        // Filtra por intervalo de datas se especificado
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');
        if (!empty($dataInicio) && !empty($dataFim)) {
            $query->entreDatas($dataInicio, $dataFim);
        }
        
        // Filtra por descrição se especificado (busca parcial)
        $descricao = $request->get('descricao');
        if (!empty($descricao)) {
            $query->andWhere(['like', 'descricao', $descricao]);
        }
        
        // Ordenação por data (padrão é decrescente - mais recente primeiro)
        $ordenacaoAscendente = $request->get('ordem_asc', false);
        $query->ordenarPorData($ordenacaoAscendente);
        
        // Cria e retorna o data provider
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $request->get('per_page', 10),
            ],
            'sort' => [
                'defaultOrder' => [
                    'data' => $ordenacaoAscendente ? SORT_ASC : SORT_DESC,
                ]
            ],
        ]);
    }
    
    /**
     * Cria uma nova despesa
     * Campos obrigatórios: descrição, categoria, valor, data
     */
    public function actionCreate()
    {
        $model = new Despesa();
        $model->scenario = 'create'; // Define o cenário como create
        
        // Define o user_id como o usuário atual ANTES de carregar os dados do POST
        $model->user_id = Yii::$app->user->id;
        
        // Carrega os dados do POST depois de definir o user_id
        $model->load(Yii::$app->request->post(), '');
        
        if ($model->save()) {
            Yii::$app->response->statusCode = 201;
            return $model;
        }
        
        Yii::$app->response->statusCode = 422;
        return ['errors' => $model->errors];
    }
    
    /**
     * Atualiza uma despesa existente
     * Permite editar qualquer campo da despesa
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = 'update'; // Define o cenário como update
        
        // Verifica se a despesa pertence ao usuário atual
        if ($model->user_id != Yii::$app->user->id) {
            throw new ForbiddenHttpException('Você não tem permissão para editar esta despesa');
        }
        
        // Define o user_id como o usuário atual ANTES de carregar os dados do POST
        // Isso garante que o user_id não será alterado mesmo se for enviado no POST
        $model->user_id = Yii::$app->user->id;
        
        // Carrega os dados do POST depois de garantir o user_id
        $model->load(Yii::$app->request->post(), '');
        
        if ($model->save()) {
            return $model;
        }
        
        Yii::$app->response->statusCode = 422;
        return ['errors' => $model->errors];
    }
    
    /**
     * Visualiza uma despesa específica
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Verifica se a despesa pertence ao usuário atual
        if ($model->user_id != Yii::$app->user->id) {
            throw new ForbiddenHttpException('Você não tem permissão para visualizar esta despesa');
        }
        
        return $model;
    }
    
    /**
     * Exclui uma despesa (soft delete)
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Verifica se a despesa pertence ao usuário atual
        if ($model->user_id != Yii::$app->user->id) {
            throw new ForbiddenHttpException('Você não tem permissão para excluir esta despesa');
        }
        
        if ($model->softDelete()) {
            Yii::$app->response->statusCode = 204; // No Content
            return null;
        }
        
        throw new ServerErrorHttpException('Não foi possível excluir a despesa');
    }
    
    /**
     * Retorna todas as categorias disponíveis para despesas
     */
    public function actionCategorias()
    {
        return Despesa::getCategoriasDetalhadas();
    }
    
    /**
     * Retorna um resumo das despesas do usuário
     * - Total de despesas por categoria no mês/ano
     * - Total geral no mês/ano
     * - Comparativo com o mês anterior
     */
    public function actionResumo()
    {
        $request = Yii::$app->request;
        $userId = Yii::$app->user->id;
        
        // Obtém mês e ano da requisição, ou usa o mês/ano atual
        $mes = $request->get('mes', date('m'));
        $ano = $request->get('ano', date('Y'));
        
        // Valida mês e ano
        if (!is_numeric($mes) || $mes < 1 || $mes > 12) {
            throw new BadRequestHttpException('Mês inválido');
        }
        
        if (!is_numeric($ano) || $ano < 2000 || $ano > 2100) {
            throw new BadRequestHttpException('Ano inválido');
        }
        
        // Calcula o mês e ano anterior para comparação
        $dataAnterior = strtotime("{$ano}-{$mes}-01 -1 month");
        $mesAnterior = date('m', $dataAnterior);
        $anoAnterior = date('Y', $dataAnterior);
        
        // Busca todas as despesas do mês atual
        $despesasMesAtual = Despesa::find()
            ->active()
            ->doUsuario($userId)
            ->porPeriodo($mes, $ano)
            ->asArray()
            ->all();
        
        // Busca todas as despesas do mês anterior para comparação
        $despesasMesAnterior = Despesa::find()
            ->active()
            ->doUsuario($userId)
            ->porPeriodo($mesAnterior, $anoAnterior)
            ->asArray()
            ->all();
        
        // Inicializa arrays para armazenar totais por categoria
        $totalPorCategoria = [];
        $totalPorCategoriaAnterior = [];
        $totalGeral = 0;
        $totalGeralAnterior = 0;
        
        // Inicializa com todas as categorias e valor zero
        $categorias = Despesa::getCategoriasDetalhadas();
        foreach ($categorias as $id => $categoria) {
            $totalPorCategoria[$id] = 0;
            $totalPorCategoriaAnterior[$id] = 0;
        }
        
        // Calcula totais do mês atual
        foreach ($despesasMesAtual as $despesa) {
            $totalPorCategoria[$despesa['categoria']] += $despesa['valor'];
            $totalGeral += $despesa['valor'];
        }
        
        // Calcula totais do mês anterior
        foreach ($despesasMesAnterior as $despesa) {
            $totalPorCategoriaAnterior[$despesa['categoria']] += $despesa['valor'];
            $totalGeralAnterior += $despesa['valor'];
        }
        
        // Prepara o resultado formatado
        $resumoPorCategoria = [];
        foreach ($categorias as $id => $categoria) {
            $valorAtual = $totalPorCategoria[$id];
            $valorAnterior = $totalPorCategoriaAnterior[$id];
            $variacao = $valorAnterior > 0 ? (($valorAtual - $valorAnterior) / $valorAnterior) * 100 : 0;
            
            $resumoPorCategoria[] = [
                'id' => $id,
                'nome' => $categoria['nome'],
                'icone' => $categoria['icone'],
                'valor' => $valorAtual,
                'valor_anterior' => $valorAnterior,
                'variacao' => $variacao,
            ];
        }
        
        // Calcula variação percentual do total
        $variacaoTotal = $totalGeralAnterior > 0 ? (($totalGeral - $totalGeralAnterior) / $totalGeralAnterior) * 100 : 0;
        
        return [
            'mes' => $mes,
            'mes_nome' => $this->nomeMes($mes),
            'ano' => $ano,
            'mes_anterior' => $mesAnterior,
            'mes_anterior_nome' => $this->nomeMes($mesAnterior),
            'ano_anterior' => $anoAnterior,
            'total' => $totalGeral,
            'total_anterior' => $totalGeralAnterior,
            'variacao_total' => $variacaoTotal,
            'categorias' => $resumoPorCategoria,
        ];
    }
    
    /**
     * Retorna o nome do mês por extenso
     */
    private function nomeMes($mes)
    {
        $meses = [
            '01' => 'Janeiro',
            '02' => 'Fevereiro',
            '03' => 'Março',
            '04' => 'Abril',
            '05' => 'Maio',
            '06' => 'Junho',
            '07' => 'Julho',
            '08' => 'Agosto',
            '09' => 'Setembro',
            '10' => 'Outubro',
            '11' => 'Novembro',
            '12' => 'Dezembro',
        ];
        
        return $meses[str_pad($mes, 2, '0', STR_PAD_LEFT)] ?? '';
    }
    
    /**
     * Método auxiliar para encontrar um modelo por ID
     */
    protected function findModel($id)
    {
        if (($model = Despesa::findOne($id)) !== null) {
            return $model;
        }
        
        throw new NotFoundHttpException('A despesa solicitada não existe');
    }
    
    /**
     * Endpoint para diagnóstico de problemas
     * - Retorna informações úteis para depuração
     * - NÃO DEVE SER USADO EM PRODUÇÃO
     */
    public function actionDiagnosticar()
    {
        return [
            'baseUrl' => Yii::$app->request->baseUrl,
            'homeUrl' => Yii::$app->homeUrl,
            'php_version' => PHP_VERSION,
            'yii_version' => Yii::getVersion(),
            'app_name' => Yii::$app->name,
            'db_dsn' => Yii::$app->db->dsn,
            'is_jwt_configured' => isset(Yii::$app->jwt),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
} 