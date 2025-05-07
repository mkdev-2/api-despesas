<?php

namespace app\services;

use app\modules\financeiro\models\Despesa;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * Serviço para geração de relatórios de despesas
 * 
 * Fornece métodos para:
 * - Resumo de despesas por período
 * - Despesas por categoria
 * - Tendências e comparações
 * - Estatísticas gerais
 */
class RelatorioService
{
    /**
     * Obtém resumo geral das despesas do usuário
     * 
     * @param int $userId ID do usuário
     * @param string|null $dataInicio Data inicial para filtro (formato Y-m-d)
     * @param string|null $dataFim Data final para filtro (formato Y-m-d)
     * @return array Dados do resumo
     */
    public function getResumoGeral($userId, $dataInicio = null, $dataFim = null)
    {
        // Define período padrão (último ano) se não especificado
        if (empty($dataInicio)) {
            $dataInicio = date('Y-m-d', strtotime('-1 year'));
        }
        
        if (empty($dataFim)) {
            $dataFim = date('Y-m-d');
        }
        
        // Query base para despesas do usuário no período
        $query = Despesa::find()
            ->active()
            ->doUsuario($userId)
            ->entreDatas($dataInicio, $dataFim);
        
        // Total de despesas no período
        $totalDespesas = $query->sum('valor') ?: 0;
        
        // Maior despesa no período
        $maiorDespesa = $query->max('valor') ?: 0;
        
        // Média de despesas por mês
        $mesesNoPeriodo = $this->calcularMesesEntreDatas($dataInicio, $dataFim);
        $mediaMensal = $mesesNoPeriodo > 0 ? $totalDespesas / $mesesNoPeriodo : 0;
        
        // Categorias mais utilizadas (top 3)
        $categoriasQuery = clone $query;
        $categoriasMaisUsadas = $categoriasQuery
            ->select(['categoria', 'total' => new Expression('SUM(valor)')])
            ->groupBy('categoria')
            ->orderBy(['total' => SORT_DESC])
            ->limit(3)
            ->asArray()
            ->all();
        
        // Mês com mais despesas
        $mesMaisGastosQuery = clone $query;
        $mesesMaisGastos = $mesMaisGastosQuery
            ->select([
                'mes' => new Expression('MONTH(data)'),
                'ano' => new Expression('YEAR(data)'),
                'total' => new Expression('SUM(valor)')
            ])
            ->groupBy(['mes', 'ano'])
            ->orderBy(['total' => SORT_DESC])
            ->limit(1)
            ->asArray()
            ->one();
        
        $mesMaisGastos = $mesesMaisGastos 
            ? [
                'mes' => $mesesMaisGastos['mes'],
                'ano' => $mesesMaisGastos['ano'],
                'nome' => $this->getNomeMes($mesesMaisGastos['mes']),
                'total' => $mesesMaisGastos['total']
            ] 
            : null;
        
        return [
            'periodo' => [
                'inicio' => $dataInicio,
                'fim' => $dataFim,
                'meses' => $mesesNoPeriodo
            ],
            'total' => $totalDespesas,
            'media_mensal' => $mediaMensal,
            'maior_despesa' => $maiorDespesa,
            'categorias_principais' => $categoriasMaisUsadas,
            'mes_mais_gastos' => $mesMaisGastos
        ];
    }
    
    /**
     * Obtém dados para o gráfico de despesas por categoria
     * 
     * @param int $userId ID do usuário
     * @param string|null $dataInicio Data inicial para filtro
     * @param string|null $dataFim Data final para filtro
     * @return array Dados para o gráfico
     */
    public function getDespesasPorCategoria($userId, $dataInicio = null, $dataFim = null)
    {
        // Define período padrão (último ano) se não especificado
        if (empty($dataInicio)) {
            $dataInicio = date('Y-m-d', strtotime('-1 year'));
        }
        
        if (empty($dataFim)) {
            $dataFim = date('Y-m-d');
        }
        
        // Consulta despesas agrupadas por categoria
        $despesasPorCategoria = Despesa::find()
            ->active()
            ->doUsuario($userId)
            ->entreDatas($dataInicio, $dataFim)
            ->select(['categoria', 'total' => new Expression('SUM(valor)'), 'quantidade' => new Expression('COUNT(*)')])
            ->groupBy('categoria')
            ->orderBy(['total' => SORT_DESC])
            ->asArray()
            ->all();
        
        // Adiciona informações de cores e ícones para cada categoria
        $categorias = Despesa::CATEGORIAS;
        foreach ($despesasPorCategoria as &$cat) {
            if (isset($categorias[$cat['categoria']])) {
                $cat['cor'] = $categorias[$cat['categoria']]['cor'];
                $cat['icone'] = $categorias[$cat['categoria']]['icone'];
                $cat['percentual'] = 0; // Será calculado abaixo
            }
        }
        
        // Calcula percentual de cada categoria
        $total = array_sum(ArrayHelper::getColumn($despesasPorCategoria, 'total'));
        if ($total > 0) {
            foreach ($despesasPorCategoria as &$cat) {
                $cat['percentual'] = round(($cat['total'] / $total) * 100, 1);
            }
        }
        
        return [
            'categorias' => $despesasPorCategoria,
            'total' => $total
        ];
    }
    
    /**
     * Obtém dados para o gráfico de evolução mensal das despesas
     * 
     * @param int $userId ID do usuário
     * @param int $meses Número de meses para incluir no gráfico
     * @return array Dados para o gráfico
     */
    public function getEvolucaoMensal($userId, $meses = 12)
    {
        // Define período
        $dataFim = date('Y-m-d');
        $dataInicio = date('Y-m-d', strtotime("-{$meses} months"));
        
        // Consulta despesas agrupadas por mês
        $evolucaoMensal = Despesa::find()
            ->active()
            ->doUsuario($userId)
            ->entreDatas($dataInicio, $dataFim)
            ->select([
                'mes' => new Expression('MONTH(data)'),
                'ano' => new Expression('YEAR(data)'),
                'total' => new Expression('SUM(valor)'),
            ])
            ->groupBy(['mes', 'ano'])
            ->orderBy(['ano' => SORT_ASC, 'mes' => SORT_ASC])
            ->asArray()
            ->all();
        
        // Formata resultados para o gráfico
        $resultado = [];
        foreach ($evolucaoMensal as $item) {
            $nomeMes = $this->getNomeMes($item['mes']);
            $resultado[] = [
                'mes' => $item['mes'],
                'ano' => $item['ano'],
                'nome_mes' => $nomeMes,
                'rotulo' => $nomeMes . '/' . $item['ano'],
                'total' => $item['total']
            ];
        }
        
        return $resultado;
    }
    
    /**
     * Identifica tendências e insights com base nas despesas
     * 
     * @param int $userId ID do usuário
     * @return array Lista de insights
     */
    public function getInsights($userId)
    {
        $insights = [];
        
        // Dados dos últimos 6 meses
        $dataFim = date('Y-m-d');
        $dataInicio6Meses = date('Y-m-d', strtotime('-6 months'));
        
        // Dados dos últimos 3 meses
        $dataInicio3Meses = date('Y-m-d', strtotime('-3 months'));
        
        // Gastos totais nos últimos 6 meses
        $total6Meses = Despesa::find()
            ->active()
            ->doUsuario($userId)
            ->entreDatas($dataInicio6Meses, $dataFim)
            ->sum('valor') ?: 0;
        
        // Gastos totais nos últimos 3 meses
        $total3Meses = Despesa::find()
            ->active()
            ->doUsuario($userId)
            ->entreDatas($dataInicio3Meses, $dataFim)
            ->sum('valor') ?: 0;
        
        // Tendência de gastos (aumento ou diminuição)
        $media3Meses = $total3Meses / 3;
        $media3MesesAnteriores = ($total6Meses - $total3Meses) / 3;
        
        if ($media3MesesAnteriores > 0) {
            $variacao = (($media3Meses / $media3MesesAnteriores) - 1) * 100;
            
            if ($variacao < -5) {
                $insights[] = [
                    'tipo' => 'positivo',
                    'mensagem' => 'Seus gastos diminuíram aproximadamente ' . abs(round($variacao)) . '% nos últimos 3 meses em comparação com o período anterior.',
                    'icone' => 'trending_down'
                ];
            } elseif ($variacao > 5) {
                $insights[] = [
                    'tipo' => 'negativo',
                    'mensagem' => 'Seus gastos aumentaram aproximadamente ' . round($variacao) . '% nos últimos 3 meses em comparação com o período anterior.',
                    'icone' => 'trending_up'
                ];
            }
        }
        
        // Categoria com maior crescimento
        $categoriasMes1 = $this->getGastosPorCategoriaMes($userId, 1);
        $categoriasMes3 = $this->getGastosPorCategoriaMes($userId, 3);
        
        foreach ($categoriasMes1 as $categoria => $valor1) {
            $valor3 = isset($categoriasMes3[$categoria]) ? $categoriasMes3[$categoria] : 0;
            
            if ($valor3 > 0) {
                $variacao = (($valor1 / $valor3) - 1) * 100;
                
                if ($variacao > 30 && $valor1 > 100) {
                    $insights[] = [
                        'tipo' => 'negativo',
                        'mensagem' => "Seus gastos com '{$categoria}' aumentaram " . round($variacao) . "% no último mês.",
                        'icone' => 'warning'
                    ];
                } elseif ($variacao < -20 && $valor3 > 100) {
                    $insights[] = [
                        'tipo' => 'positivo',
                        'mensagem' => "Seus gastos com '{$categoria}' diminuíram " . abs(round($variacao)) . "% no último mês.",
                        'icone' => 'thumb_up'
                    ];
                }
            }
        }
        
        // Categorias sem gastos nos últimos meses (economia)
        $todasCategorias = array_keys(Despesa::CATEGORIAS);
        $categoriasSemGastos = array_diff($todasCategorias, array_keys($categoriasMes3));
        
        if (!empty($categoriasSemGastos)) {
            $categoria = reset($categoriasSemGastos);
            $insights[] = [
                'tipo' => 'neutro',
                'mensagem' => "Não houve gastos na categoria '{$categoria}' nos últimos 3 meses.",
                'icone' => 'info'
            ];
        }
        
        // Verifica se há meses com gastos muito acima da média
        $mesesAcimaDaMedia = $this->getMesesAcimaDaMedia($userId);
        if (!empty($mesesAcimaDaMedia)) {
            $mes = reset($mesesAcimaDaMedia);
            $insights[] = [
                'tipo' => 'neutro',
                'mensagem' => "Em {$mes['nome']} de {$mes['ano']}, seus gastos foram {$mes['percentual']}% acima da sua média mensal.",
                'icone' => 'calendar_today'
            ];
        }
        
        return $insights;
    }
    
    /**
     * Obtém os dados completos para a página de relatórios
     * 
     * @param int $userId ID do usuário
     * @param array $params Parâmetros de filtro (dataInicio, dataFim, categoria)
     * @return array Dados completos para relatórios
     */
    public function getRelatorioCompleto($userId, $params = [])
    {
        $dataInicio = $params['dataInicio'] ?? null;
        $dataFim = $params['dataFim'] ?? null;
        $categoria = $params['categoria'] ?? null;
        
        // Resumo geral
        $resumo = $this->getResumoGeral($userId, $dataInicio, $dataFim);
        
        // Despesas por categoria
        $despesasPorCategoria = $this->getDespesasPorCategoria($userId, $dataInicio, $dataFim);
        
        // Evolução mensal
        $evolucaoMensal = $this->getEvolucaoMensal($userId);
        
        // Insights e tendências
        $insights = $this->getInsights($userId);
        
        return [
            'resumo' => $resumo,
            'categorias' => $despesasPorCategoria,
            'evolucao_mensal' => $evolucaoMensal,
            'insights' => $insights,
            'filtros_aplicados' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'categoria' => $categoria
            ]
        ];
    }
    
    /**
     * Calcula o número de meses entre duas datas
     * 
     * @param string $dataInicio Data inicial (formato Y-m-d)
     * @param string $dataFim Data final (formato Y-m-d)
     * @return int Número de meses
     */
    private function calcularMesesEntreDatas($dataInicio, $dataFim)
    {
        $date1 = new \DateTime($dataInicio);
        $date2 = new \DateTime($dataFim);
        
        $diff = $date1->diff($date2);
        return ($diff->y * 12) + $diff->m + 1; // +1 para incluir o mês atual
    }
    
    /**
     * Retorna o nome do mês com base no número
     * 
     * @param int $numeroMes Número do mês (1-12)
     * @return string Nome do mês
     */
    private function getNomeMes($numeroMes)
    {
        $meses = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro'
        ];
        
        return $meses[$numeroMes] ?? '';
    }
    
    /**
     * Obtém gastos por categoria para um determinado número de meses anteriores
     * 
     * @param int $userId ID do usuário
     * @param int $meses Número de meses anteriores
     * @return array Gastos por categoria
     */
    private function getGastosPorCategoriaMes($userId, $meses = 1)
    {
        $dataFim = date('Y-m-d');
        $dataInicio = date('Y-m-d', strtotime("-{$meses} months"));
        
        $gastos = Despesa::find()
            ->active()
            ->doUsuario($userId)
            ->entreDatas($dataInicio, $dataFim)
            ->select(['categoria', 'total' => new Expression('SUM(valor)')])
            ->groupBy('categoria')
            ->asArray()
            ->all();
        
        $resultado = [];
        foreach ($gastos as $item) {
            $resultado[$item['categoria']] = $item['total'];
        }
        
        return $resultado;
    }
    
    /**
     * Identifica meses com gastos muito acima da média
     * 
     * @param int $userId ID do usuário
     * @param int $meses Número de meses a analisar
     * @param float $percentualCorte Percentual acima da média para considerar relevante
     * @return array Meses com gastos acima da média
     */
    private function getMesesAcimaDaMedia($userId, $meses = 12, $percentualCorte = 30)
    {
        $dataFim = date('Y-m-d');
        $dataInicio = date('Y-m-d', strtotime("-{$meses} months"));
        
        $gastosMensais = Despesa::find()
            ->active()
            ->doUsuario($userId)
            ->entreDatas($dataInicio, $dataFim)
            ->select([
                'mes' => new Expression('MONTH(data)'),
                'ano' => new Expression('YEAR(data)'),
                'total' => new Expression('SUM(valor)')
            ])
            ->groupBy(['mes', 'ano'])
            ->orderBy(['ano' => SORT_DESC, 'mes' => SORT_DESC])
            ->asArray()
            ->all();
        
        if (empty($gastosMensais)) {
            return [];
        }
        
        // Calcula média mensal
        $totalGeral = array_sum(ArrayHelper::getColumn($gastosMensais, 'total'));
        $mediaMensal = $totalGeral / count($gastosMensais);
        
        // Identifica meses acima do percentual de corte
        $mesesAcimaDaMedia = [];
        foreach ($gastosMensais as $mes) {
            $percentualAcima = (($mes['total'] / $mediaMensal) - 1) * 100;
            
            if ($percentualAcima >= $percentualCorte) {
                $mesesAcimaDaMedia[] = [
                    'mes' => $mes['mes'],
                    'ano' => $mes['ano'],
                    'nome' => $this->getNomeMes($mes['mes']),
                    'total' => $mes['total'],
                    'percentual' => round($percentualAcima)
                ];
            }
        }
        
        return $mesesAcimaDaMedia;
    }
} 