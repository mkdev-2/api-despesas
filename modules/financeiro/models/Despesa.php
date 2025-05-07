<?php
namespace app\modules\financeiro\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use app\modules\usuarios\models\User;

/**
 * Model de Despesas
 *
 * @property int $id
 * @property int $user_id
 * @property string $descricao
 * @property string $categoria
 * @property float $valor
 * @property string $data
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 *
 * @property User $user
 */
class Despesa extends ActiveRecord
{
    /**
     * Categorias disponíveis para despesas
     */
    const CATEGORIA_ALIMENTACAO = 'alimentacao';
    const CATEGORIA_TRANSPORTE = 'transporte';
    const CATEGORIA_LAZER = 'lazer';
    const CATEGORIA_MORADIA = 'moradia';
    const CATEGORIA_SAUDE = 'saude';
    const CATEGORIA_EDUCACAO = 'educacao';
    const CATEGORIA_OUTROS = 'outros';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%despesas}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Regras para ambos os cenários (create e update)
            [['descricao', 'categoria', 'valor', 'data'], 'required', 'message' => '{attribute} é obrigatório.'],
            
            // user_id é obrigatório apenas se não for possível obtê-lo automaticamente
            [['user_id'], 'required', 'when' => function($model) {
                return Yii::$app->user->isGuest; // Só obrigatório se o usuário não estiver autenticado
            }, 'message' => '{attribute} é obrigatório.'],
            
            [['user_id'], 'integer'],
            [['valor'], 'number', 'min' => 0, 'message' => 'Valor deve ser maior ou igual a zero.'],
            [['data'], 'date', 'format' => 'php:Y-m-d'],
            [['created_at', 'updated_at', 'deleted_at'], 'safe'],
            [['descricao'], 'string', 'max' => 255],
            [['categoria'], 'string', 'max' => 50],
            [['categoria'], 'in', 'range' => [
                self::CATEGORIA_ALIMENTACAO,
                self::CATEGORIA_TRANSPORTE,
                self::CATEGORIA_LAZER,
                self::CATEGORIA_MORADIA,
                self::CATEGORIA_SAUDE,
                self::CATEGORIA_EDUCACAO,
                self::CATEGORIA_OUTROS
            ]],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * Define cenários disponíveis para validação
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['user_id', 'descricao', 'categoria', 'valor', 'data'];
        $scenarios['update'] = ['descricao', 'categoria', 'valor', 'data']; // user_id não pode ser alterado na edição
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Usuário',
            'descricao' => 'Descrição',
            'categoria' => 'Categoria',
            'valor' => 'Valor',
            'data' => 'Data',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
            'deleted_at' => 'Excluído em',
        ];
    }

    /**
     * Executa antes da validação
     * Define automaticamente o user_id a partir do usuário autenticado se estiver em branco
     * @return bool se a validação deve continuar
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            // Se estamos criando um novo registro e user_id não está definido
            if ($this->isNewRecord && empty($this->user_id) && !Yii::$app->user->isGuest) {
                $this->user_id = Yii::$app->user->id;
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Retorna a relação com a tabela de usuários
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Sobrescreve o método find para retornar DespesaQuery
     * @return DespesaQuery a query ativa para o modelo
     */
    public static function find()
    {
        return new DespesaQuery(get_called_class());
    }

    /**
     * Escopo para filtrar despesas por categoria
     */
    public function porCategoria($categoria)
    {
        return $this->andWhere(['categoria' => $categoria]);
    }

    /**
     * Escopo para filtrar despesas por período (mês/ano)
     */
    public function porPeriodo($mes, $ano)
    {
        $dataInicio = sprintf('%d-%02d-01', $ano, $mes);
        $dataFim = date('Y-m-t', strtotime($dataInicio));
        return $this->andWhere(['between', 'data', $dataInicio, $dataFim]);
    }

    /**
     * Escopo para filtrar despesas entre datas
     */
    public function entreDatas($dataInicio, $dataFim)
    {
        return $this->andWhere(['between', 'data', $dataInicio, $dataFim]);
    }

    /**
     * Escopo para ordenar despesas por data
     */
    public function ordenarPorData($asc = false)
    {
        return $this->orderBy(['data' => $asc ? SORT_ASC : SORT_DESC]);
    }

    /**
     * Escopo para obter apenas as despesas do usuário
     */
    public function doUsuario($userId)
    {
        return $this->andWhere(['user_id' => $userId]);
    }
    
    /**
     * Retorna as categorias disponíveis para despesas
     */
    public static function getCategorias()
    {
        return [
            self::CATEGORIA_ALIMENTACAO,
            self::CATEGORIA_TRANSPORTE,
            self::CATEGORIA_LAZER,
            self::CATEGORIA_MORADIA,
            self::CATEGORIA_SAUDE,
            self::CATEGORIA_EDUCACAO,
            self::CATEGORIA_OUTROS,
        ];
    }
    
    /**
     * Retorna as categorias com descrições amigáveis
     */
    public static function getCategoriasDetalhadas()
    {
        return [
            self::CATEGORIA_ALIMENTACAO => [
                'id' => self::CATEGORIA_ALIMENTACAO,
                'nome' => 'Alimentação',
                'descricao' => 'Despesas com refeições, mercado, delivery, etc.',
                'icone' => 'restaurant'
            ],
            self::CATEGORIA_TRANSPORTE => [
                'id' => self::CATEGORIA_TRANSPORTE,
                'nome' => 'Transporte',
                'descricao' => 'Despesas com combustível, passagens, táxi, aplicativos de transporte, etc.',
                'icone' => 'directions_car'
            ],
            self::CATEGORIA_LAZER => [
                'id' => self::CATEGORIA_LAZER,
                'nome' => 'Lazer',
                'descricao' => 'Despesas com cinema, teatro, parques, viagens, etc.',
                'icone' => 'sports_esports'
            ],
            self::CATEGORIA_MORADIA => [
                'id' => self::CATEGORIA_MORADIA,
                'nome' => 'Moradia',
                'descricao' => 'Despesas com aluguel, condomínio, água, luz, internet, etc.',
                'icone' => 'home'
            ],
            self::CATEGORIA_SAUDE => [
                'id' => self::CATEGORIA_SAUDE,
                'nome' => 'Saúde',
                'descricao' => 'Despesas com plano de saúde, medicamentos, consultas, etc.',
                'icone' => 'local_hospital'
            ],
            self::CATEGORIA_EDUCACAO => [
                'id' => self::CATEGORIA_EDUCACAO,
                'nome' => 'Educação',
                'descricao' => 'Despesas com mensalidades, cursos, livros, etc.',
                'icone' => 'school'
            ],
            self::CATEGORIA_OUTROS => [
                'id' => self::CATEGORIA_OUTROS,
                'nome' => 'Outros',
                'descricao' => 'Outras despesas que não se encaixam nas categorias anteriores.',
                'icone' => 'more_horiz'
            ],
        ];
    }
    
    /**
     * Soft delete da despesa
     * Ao invés de excluir, apenas marca como deleted_at com a data atual
     */
    public function softDelete()
    {
        $this->deleted_at = date('Y-m-d H:i:s');
        return $this->save(false);
    }
    
    /**
     * {@inheritdoc}
     * Define quais campos serão retornados nas consultas via API
     */
    public function fields()
    {
        return [
            'id',
            'descricao',
            'categoria',
            'categoria_label' => function ($model) {
                $categorias = self::getCategoriasDetalhadas();
                return isset($categorias[$model->categoria]) ? $categorias[$model->categoria]['nome'] : $model->categoria;
            },
            'categoria_icone' => function ($model) {
                $categorias = self::getCategoriasDetalhadas();
                return isset($categorias[$model->categoria]) ? $categorias[$model->categoria]['icone'] : 'help';
            },
            'valor',
            'data',
            'created_at',
            'updated_at'
        ];
    }
}

/**
 * DespesaQuery representa a classe base para consultas na tabela de despesas.
 * Isso permite criar consultas mais expressivas e reutilizáveis.
 */
class DespesaQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     */
    public function __construct($modelClass, $config = [])
    {
        parent::__construct($modelClass, $config);
        // Inicializa query com condições/ordenações padrão se necessário
    }
    
    /**
     * Método para debug que retorna a consulta SQL
     */
    public function getSql()
    {
        $command = $this->createCommand();
        return $command->getRawSql();
    }
    
    /**
     * Escopo para filtrar despesas por categoria
     */
    public function porCategoria($categoria)
    {
        return $this->andWhere(['categoria' => $categoria]);
    }
    
    /**
     * Escopo para filtrar despesas por período (mês/ano)
     */
    public function porPeriodo($mes, $ano)
    {
        $dataInicio = sprintf('%d-%02d-01', $ano, $mes);
        $dataFim = date('Y-m-t', strtotime($dataInicio));
        return $this->andWhere(['between', 'data', $dataInicio, $dataFim]);
    }
    
    /**
     * Escopo para filtrar despesas entre datas
     */
    public function entreDatas($dataInicio, $dataFim)
    {
        return $this->andWhere(['between', 'data', $dataInicio, $dataFim]);
    }
    
    /**
     * Escopo para obter apenas as despesas do usuário
     */
    public function doUsuario($userId)
    {
        return $this->andWhere(['user_id' => $userId]);
    }
    
    /**
     * Escopo para ordenar despesas por data
     */
    public function ordenarPorData($asc = false)
    {
        return $this->orderBy(['data' => $asc ? SORT_ASC : SORT_DESC]);
    }
    
    /**
     * Escopo para retornar apenas despesas ativas (não excluídas)
     */
    public function active()
    {
        // Usamos a condição SQL crua para garantir que a consulta use IS NULL corretamente
        $this->andWhere(['IS', 'deleted_at', null]);
        return $this;
    }
} 