<?php
namespace app\modules\financeiro\controllers;

use Yii;
use yii\rest\Controller;
use app\modules\financeiro\models\Despesa;
use sizeg\jwt\JwtHttpBearerAuth;
use yii\filters\Cors;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;

/**
 * Controlador de Relatórios Financeiros
 * 
 * Fornece endpoints para:
 * - Resumo de gastos por período
 * - Comparativo de gastos entre períodos
 * - Análise de gastos por categoria
 */
class RelatorioController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Remove o autenticador padrão para substituir pelo JWT
        unset($behaviors['authenticator']);
        
        // Adiciona configuração de CORS
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
            'except' => ['options'],
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
            'except' => ['options'],
        ];
        
        return $behaviors;
    }
    
    // Adiciona suporte a requisições OPTIONS (preflight)
    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        Yii::$app->response->headers->set('Allow', 'GET, OPTIONS');
        return 'ok';
    }
    
    /**
     * Relatório de gastos por categoria no ano
     * Mostra a evolução dos gastos por categoria ao longo dos meses do ano
     */
    public function actionAnual()
    {
        $request = Yii::$app->request;
        $userId = Yii::$app->user->id;
        
        // Obtém o ano da requisição, ou usa o ano atual
        $ano = $request->get('ano', date('Y'));
        
        // Valida o ano
        if (!is_numeric($ano) || $ano < 2000 || $ano > 2100) {
            throw new BadRequestHttpException('Ano inválido');
        }
        
        // Inicializa a estrutura de dados
        $resumo = [
            'ano' => $ano,
            'total_anual' => 0,
            'categorias' => [],
            'meses' => []
        ];
        
        // Prepara os nomes dos meses
        $nomesMeses = $this->getNomesMeses();
        
        // Inicializa dados para todos os meses
        for ($mes = 1; $mes <= 12; $mes++) {
            $mesPadding = str_pad($mes, 2, '0', STR_PAD_LEFT);
            $resumo['meses'][] = [
                'numero' => $mesPadding,
                'nome' => $nomesMeses[$mesPadding],
                'total' => 0
            ];
        }
        
        // Inicializa dados para todas as categorias
        $categorias = Despesa::getCategoriasDetalhadas();
        foreach ($categorias as $id => $categoria) {
            $resumo['categorias'][$id] = [
                'id' => $id,
                'nome' => $categoria['nome'],
                'icone' => $categoria['icone'],
                'total_anual' => 0,
                'meses' => []
            ];
            
            // Inicializa dados mensais para cada categoria
            for ($mes = 1; $mes <= 12; $mes++) {
                $mesPadding = str_pad($mes, 2, '0', STR_PAD_LEFT);
                $resumo['categorias'][$id]['meses'][$mesPadding] = 0;
            }
        }
        
        // Busca todas as despesas do ano
        $despesasAno = Despesa::find()
            ->active()
            ->doUsuario($userId)
            ->andWhere(['between', 'data', "{$ano}-01-01", "{$ano}-12-31"])
            ->asArray()
            ->all();
        
        // Processa as despesas encontradas
        foreach ($despesasAno as $despesa) {
            $data = new \DateTime($despesa['data']);
            $mes = $data->format('m');
            $categoria = $despesa['categoria'];
            $valor = $despesa['valor'];
            
            // Incrementa o total do mês
            $indice = (int)$mes - 1; // Índice do array começa em 0
            $resumo['meses'][$indice]['total'] += $valor;
            
            // Incrementa o total da categoria no mês
            $resumo['categorias'][$categoria]['meses'][$mes] += $valor;
            
            // Incrementa o total anual da categoria
            $resumo['categorias'][$categoria]['total_anual'] += $valor;
            
            // Incrementa o total anual geral
            $resumo['total_anual'] += $valor;
        }
        
        // Converte o array associativo de categorias para array indexado para melhor formatação JSON
        $categorias_lista = [];
        foreach ($resumo['categorias'] as $categoria) {
            // Converte o array associativo de meses para array indexado
            $meses_lista = [];
            foreach ($categoria['meses'] as $mes => $valor) {
                $meses_lista[] = [
                    'mes' => $mes,
                    'valor' => $valor
                ];
            }
            $categoria['meses'] = $meses_lista;
            $categorias_lista[] = $categoria;
        }
        $resumo['categorias'] = $categorias_lista;
        
        return $resumo;
    }
    
    /**
     * Relatório de comparação de gastos entre categorias
     * Mostra a proporção de gastos entre as diferentes categorias
     */
    public function actionProporcao()
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
        
        // Busca todas as despesas do período
        $despesas = Despesa::find()
            ->active()
            ->doUsuario($userId)
            ->porPeriodo($mes, $ano)
            ->asArray()
            ->all();
        
        // Inicializa estrutura para armazenar totais por categoria
        $totalPorCategoria = [];
        $totalGeral = 0;
        
        // Inicializa com todas as categorias e valor zero
        $categorias = Despesa::getCategoriasDetalhadas();
        foreach ($categorias as $id => $categoria) {
            $totalPorCategoria[$id] = 0;
        }
        
        // Calcula totais
        foreach ($despesas as $despesa) {
            $totalPorCategoria[$despesa['categoria']] += $despesa['valor'];
            $totalGeral += $despesa['valor'];
        }
        
        // Prepara o resultado formatado e calcula percentuais
        $resultado = [
            'mes' => $mes,
            'mes_nome' => $this->getNomeMes($mes),
            'ano' => $ano,
            'total' => $totalGeral,
            'categorias' => []
        ];
        
        foreach ($categorias as $id => $categoria) {
            $valor = $totalPorCategoria[$id];
            $percentual = $totalGeral > 0 ? ($valor / $totalGeral) * 100 : 0;
            
            // Só inclui categorias com valores
            if ($valor > 0) {
                $resultado['categorias'][] = [
                    'id' => $id,
                    'nome' => $categoria['nome'],
                    'icone' => $categoria['icone'],
                    'valor' => $valor,
                    'percentual' => $percentual
                ];
            }
        }
        
        // Ordena as categorias pelo valor (do maior para o menor)
        usort($resultado['categorias'], function($a, $b) {
            return $b['valor'] <=> $a['valor'];
        });
        
        return $resultado;
    }
    
    /**
     * Utilitário para obter o nome de um mês específico
     */
    private function getNomeMes($mes)
    {
        $meses = $this->getNomesMeses();
        return $meses[str_pad($mes, 2, '0', STR_PAD_LEFT)] ?? '';
    }
    
    /**
     * Utilitário para obter os nomes de todos os meses
     */
    private function getNomesMeses()
    {
        return [
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
            '12' => 'Dezembro'
        ];
    }
} 