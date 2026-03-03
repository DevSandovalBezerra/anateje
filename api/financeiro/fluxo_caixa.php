<?php
// ANATEJE - API de Fluxo de Caixa
// Sistema de Gestao Financeira Associativa ANATEJE
// VisÃ£o diÃ¡ria, semanal e mensal conforme PRD

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/unidade_helper.php';

require_once __DIR__ . '/_bootstrap.php';
class FluxoCaixaAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function obterFluxo($tipo = 'mensal', $filtros = [])
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $whereClause = "WHERE 1=1";
            $params = [];

            if ($unidadeSessao !== null) {
                $whereClause .= " AND (lf.unidade_id = ? OR lf.unidade_id IS NULL)";
                $params[] = $unidadeSessao;
            } elseif (!empty($filtros['unidade_id'])) {
                $whereClause .= " AND lf.unidade_id = ?";
                $params[] = (int)$filtros['unidade_id'];
            }

            if (!empty($filtros['conta_bancaria_id'])) {
                $whereClause .= " AND lf.conta_bancaria_id = ?";
                $params[] = (int)$filtros['conta_bancaria_id'];
            }

            if (!empty($filtros['data_inicio'])) {
                $whereClause .= " AND lf.data_vencimento >= ?";
                $params[] = $filtros['data_inicio'];
            }

            if (!empty($filtros['data_fim'])) {
                $whereClause .= " AND lf.data_vencimento <= ?";
                $params[] = $filtros['data_fim'];
            }

            $groupBy = '';
            $dateFormat = '';

            switch ($tipo) {
                case 'diario':
                    $groupBy = "DATE(lf.data_vencimento)";
                    $dateFormat = "DATE_FORMAT(lf.data_vencimento, '%d/%m/%Y')";
                    break;
                case 'semanal':
                    $groupBy = "YEARWEEK(lf.data_vencimento, 1)";
                    $dateFormat = "CONCAT('Semana ', YEARWEEK(lf.data_vencimento, 1))";
                    break;
                case 'mensal':
                default:
                    $groupBy = "DATE_FORMAT(lf.data_vencimento, '%Y-%m')";
                    $dateFormat = "DATE_FORMAT(lf.data_vencimento, '%m/%Y')";
                    break;
            }

            $sql = "
                SELECT 
                    $dateFormat as periodo,
                    $groupBy as periodo_key,
                    SUM(CASE 
                        WHEN (lf.tipo = 'receber' OR lf.tipo = 'receita') 
                        AND (lf.status = 'quitado' OR lf.status = 'pago') 
                        THEN lf.valor_total ELSE 0 
                    END) as entradas_realizado,
                    SUM(CASE 
                        WHEN (lf.tipo = 'pagar' OR lf.tipo = 'despesa') 
                        AND (lf.status = 'quitado' OR lf.status = 'pago') 
                        THEN lf.valor_total ELSE 0 
                    END) as saidas_realizado,
                    SUM(CASE 
                        WHEN (lf.tipo = 'receber' OR lf.tipo = 'receita') 
                        AND lf.status IN ('previsto', 'aberto', 'pendente', 'parcial') 
                        THEN lf.valor_total ELSE 0 
                    END) as entradas_previsto,
                    SUM(CASE 
                        WHEN (lf.tipo = 'pagar' OR lf.tipo = 'despesa') 
                        AND lf.status IN ('previsto', 'aberto', 'pendente', 'parcial') 
                        THEN lf.valor_total ELSE 0 
                    END) as saidas_previsto
                FROM lancamentos_financeiros lf
                $whereClause
                AND lf.data_vencimento IS NOT NULL
                GROUP BY $groupBy
                ORDER BY periodo_key ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $fluxo = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $saldo_acumulado = 0;
            foreach ($fluxo as &$periodo) {
                $entradas_realizado = (float)$periodo['entradas_realizado'];
                $saidas_realizado = (float)$periodo['saidas_realizado'];
                $entradas_previsto = (float)$periodo['entradas_previsto'];
                $saidas_previsto = (float)$periodo['saidas_previsto'];

                $saldo_periodo_realizado = $entradas_realizado - $saidas_realizado;
                $saldo_periodo_previsto = ($entradas_realizado + $entradas_previsto) - ($saidas_realizado + $saidas_previsto);

                $saldo_acumulado += $saldo_periodo_realizado;

                $periodo['saldo_periodo_realizado'] = $saldo_periodo_realizado;
                $periodo['saldo_periodo_previsto'] = $saldo_periodo_previsto;
                $periodo['saldo_acumulado'] = $saldo_acumulado;
            }

            return ['success' => true, 'data' => $fluxo, 'tipo' => $tipo];
        } catch (Exception $e) {
            logError("Erro ao obter fluxo de caixa: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter fluxo de caixa'];
        }
    }

    public function simular($dados = [])
    {
        try {
            $data_inicio = $dados['data_inicio'] ?? date('Y-m-01');
            $data_fim = $dados['data_fim'] ?? date('Y-m-t');
            $conta_bancaria_id = !empty($dados['conta_bancaria_id']) ? (int)$dados['conta_bancaria_id'] : null;

            $unidadeSessao = getUserUnidadeId();
            $whereClause = "WHERE lf.data_vencimento BETWEEN ? AND ?";
            $params = [$data_inicio, $data_fim];

            if ($unidadeSessao !== null) {
                $whereClause .= " AND (lf.unidade_id = ? OR lf.unidade_id IS NULL)";
                $params[] = $unidadeSessao;
            }

            if ($conta_bancaria_id) {
                $whereClause .= " AND lf.conta_bancaria_id = ?";
                $params[] = $conta_bancaria_id;
            }

            $sql = "
                SELECT 
                    DATE(lf.data_vencimento) as data,
                    SUM(CASE 
                        WHEN (lf.tipo = 'receber' OR lf.tipo = 'receita') 
                        THEN lf.valor_total ELSE 0 
                    END) as entradas,
                    SUM(CASE 
                        WHEN (lf.tipo = 'pagar' OR lf.tipo = 'despesa') 
                        THEN lf.valor_total ELSE 0 
                    END) as saidas
                FROM lancamentos_financeiros lf
                $whereClause
                AND lf.status IN ('previsto', 'aberto', 'pendente', 'parcial')
                GROUP BY DATE(lf.data_vencimento)
                ORDER BY data ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $simulacao = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $saldo_inicial = 0;
            if ($conta_bancaria_id) {
                $stmt = $this->db->prepare("SELECT saldo_inicial FROM contas_bancarias WHERE id = ?");
                $stmt->execute([$conta_bancaria_id]);
                $conta = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($conta) {
                    $saldo_inicial = (float)$conta['saldo_inicial'];
                }
            }

            $saldo_atual = $saldo_inicial;
            foreach ($simulacao as &$dia) {
                $entradas = (float)$dia['entradas'];
                $saidas = (float)$dia['saidas'];
                $saldo_atual += ($entradas - $saidas);
                $dia['saldo_projetado'] = $saldo_atual;
            }

            return [
                'success' => true,
                'data' => [
                    'saldo_inicial' => $saldo_inicial,
                    'simulacao' => $simulacao,
                    'saldo_final_projetado' => $saldo_atual
                ]
            ];
        } catch (Exception $e) {
            logError("Erro ao simular fluxo de caixa: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao simular fluxo de caixa'];
        }
    }
}

$auth = financeiro_require_auth('fluxo_caixa');

$api = new FluxoCaixaAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'obter';
    $tipo = $_GET['tipo'] ?? 'mensal';
    $filtros = [
        'unidade_id' => $_GET['unidade_id'] ?? null,
        'conta_bancaria_id' => $_GET['conta_bancaria_id'] ?? null,
        'data_inicio' => $_GET['data_inicio'] ?? null,
        'data_fim' => $_GET['data_fim'] ?? null,
    ];

    switch ($action) {
        case 'obter':
            financeiro_response($api->obterFluxo($tipo, $filtros));
            break;
        case 'simular':
            financeiro_response($api->simular($filtros));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'AÃ§Ã£o invÃ¡lida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'MÃ©todo nÃ£o permitido'], 405);



