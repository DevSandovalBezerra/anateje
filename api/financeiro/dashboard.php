<?php
// ANATEJE - API de Dashboard Financeiro
// Sistema de Gestao Financeira Associativa ANATEJE
// Indicadores financeiros conforme PRD

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/unidade_helper.php';

require_once __DIR__ . '/_bootstrap.php';
class DashboardFinanceiroAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function obterIndicadores($filtros = [])
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

            $sql = "
                SELECT 
                    SUM(CASE 
                        WHEN (lf.tipo = 'receber' OR lf.tipo = 'receita') 
                        AND (lf.status = 'quitado' OR lf.status = 'pago') 
                        THEN lf.valor_total ELSE 0 
                    END) as total_entradas_realizado,
                    SUM(CASE 
                        WHEN (lf.tipo = 'pagar' OR lf.tipo = 'despesa') 
                        AND (lf.status = 'quitado' OR lf.status = 'pago') 
                        THEN lf.valor_total ELSE 0 
                    END) as total_saidas_realizado,
                    SUM(CASE 
                        WHEN (lf.tipo = 'receber' OR lf.tipo = 'receita') 
                        AND lf.status IN ('previsto', 'aberto', 'pendente', 'parcial') 
                        THEN lf.valor_total ELSE 0 
                    END) as total_entradas_previsto,
                    SUM(CASE 
                        WHEN (lf.tipo = 'pagar' OR lf.tipo = 'despesa') 
                        AND lf.status IN ('previsto', 'aberto', 'pendente', 'parcial') 
                        THEN lf.valor_total ELSE 0 
                    END) as total_saidas_previsto
                FROM lancamentos_financeiros lf
                $whereClause
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $total_entradas_realizado = (float)($result['total_entradas_realizado'] ?? 0);
            $total_saidas_realizado = (float)($result['total_saidas_realizado'] ?? 0);
            $total_entradas_previsto = (float)($result['total_entradas_previsto'] ?? 0);
            $total_saidas_previsto = (float)($result['total_saidas_previsto'] ?? 0);

            $saldo_atual = $total_entradas_realizado - $total_saidas_realizado;
            $saldo_previsto = ($total_entradas_realizado + $total_entradas_previsto) - ($total_saidas_realizado + $total_saidas_previsto);

            $sql = "
                SELECT 
                    cb.id,
                    cb.nome_conta,
                    cb.saldo_inicial,
                    SUM(CASE 
                        WHEN (lf.tipo = 'receber' OR lf.tipo = 'receita') 
                        AND (lf.status = 'quitado' OR lf.status = 'pago') 
                        THEN lf.valor_total ELSE 0 
                    END) as entradas,
                    SUM(CASE 
                        WHEN (lf.tipo = 'pagar' OR lf.tipo = 'despesa') 
                        AND (lf.status = 'quitado' OR lf.status = 'pago') 
                        THEN lf.valor_total ELSE 0 
                    END) as saidas
                FROM contas_bancarias cb
                LEFT JOIN lancamentos_financeiros lf ON lf.conta_bancaria_id = cb.id
                WHERE cb.ativo = 1
            ";

            if ($unidadeSessao !== null) {
                $sql .= " AND (lf.unidade_id = ? OR lf.unidade_id IS NULL)";
                $params_saldo = [$unidadeSessao];
            } else {
                $params_saldo = [];
            }

            if (!empty($filtros['conta_bancaria_id'])) {
                $sql .= " AND cb.id = ?";
                $params_saldo[] = (int)$filtros['conta_bancaria_id'];
            }

            $sql .= " GROUP BY cb.id, cb.nome_conta, cb.saldo_inicial";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params_saldo);
            $saldos_contas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($saldos_contas as &$conta) {
                $conta['saldo_real'] = (float)$conta['saldo_inicial'] + (float)$conta['entradas'] - (float)$conta['saidas'];
            }

            return [
                'success' => true,
                'data' => [
                    'total_entradas_realizado' => $total_entradas_realizado,
                    'total_saidas_realizado' => $total_saidas_realizado,
                    'total_entradas_previsto' => $total_entradas_previsto,
                    'total_saidas_previsto' => $total_saidas_previsto,
                    'saldo_atual' => $saldo_atual,
                    'saldo_previsto' => $saldo_previsto,
                    'resultado_periodo' => $saldo_atual,
                    'saldos_contas' => $saldos_contas
                ]
            ];
        } catch (Exception $e) {
            logError("Erro ao obter indicadores: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter indicadores'];
        }
    }

    public function obterPorCategoria($filtros = [])
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $whereClause = "WHERE lf.categoria_id IS NOT NULL";
            $params = [];

            if ($unidadeSessao !== null) {
                $whereClause .= " AND (lf.unidade_id = ? OR lf.unidade_id IS NULL)";
                $params[] = $unidadeSessao;
            }

            if (!empty($filtros['data_inicio'])) {
                $whereClause .= " AND lf.data_vencimento >= ?";
                $params[] = $filtros['data_inicio'];
            }

            if (!empty($filtros['data_fim'])) {
                $whereClause .= " AND lf.data_vencimento <= ?";
                $params[] = $filtros['data_fim'];
            }

            $sql = "
                SELECT 
                    cf.id,
                    cf.nome as categoria_nome,
                    cf.tipo as categoria_tipo,
                    SUM(CASE 
                        WHEN (lf.tipo = 'receber' OR lf.tipo = 'receita') 
                        AND (lf.status = 'quitado' OR lf.status = 'pago') 
                        THEN lf.valor_total ELSE 0 
                    END) as receitas_realizado,
                    SUM(CASE 
                        WHEN (lf.tipo = 'pagar' OR lf.tipo = 'despesa') 
                        AND (lf.status = 'quitado' OR lf.status = 'pago') 
                        THEN lf.valor_total ELSE 0 
                    END) as despesas_realizado,
                    SUM(CASE 
                        WHEN (lf.tipo = 'receber' OR lf.tipo = 'receita') 
                        THEN lf.valor_total ELSE 0 
                    END) as receitas_total,
                    SUM(CASE 
                        WHEN (lf.tipo = 'pagar' OR lf.tipo = 'despesa') 
                        THEN lf.valor_total ELSE 0 
                    END) as despesas_total
                FROM categorias_financeiras cf
                LEFT JOIN lancamentos_financeiros lf ON lf.categoria_id = cf.id
                $whereClause
                GROUP BY cf.id, cf.nome, cf.tipo
                HAVING receitas_realizado > 0 OR despesas_realizado > 0 OR receitas_total > 0 OR despesas_total > 0
                ORDER BY (receitas_realizado - despesas_realizado) DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $categorias];
        } catch (Exception $e) {
            logError("Erro ao obter dados por categoria: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter dados por categoria'];
        }
    }

    public function obterPorCentroCusto($filtros = [])
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $whereClause = "WHERE lf.centro_custo_id IS NOT NULL";
            $params = [];

            if ($unidadeSessao !== null) {
                $whereClause .= " AND (lf.unidade_id = ? OR lf.unidade_id IS NULL)";
                $params[] = $unidadeSessao;
            }

            if (!empty($filtros['data_inicio'])) {
                $whereClause .= " AND lf.data_vencimento >= ?";
                $params[] = $filtros['data_inicio'];
            }

            if (!empty($filtros['data_fim'])) {
                $whereClause .= " AND lf.data_vencimento <= ?";
                $params[] = $filtros['data_fim'];
            }

            $sql = "
                SELECT 
                    cc.id,
                    cc.nome as centro_custo_nome,
                    SUM(CASE 
                        WHEN (lf.tipo = 'receber' OR lf.tipo = 'receita') 
                        AND (lf.status = 'quitado' OR lf.status = 'pago') 
                        THEN lf.valor_total ELSE 0 
                    END) as receitas_realizado,
                    SUM(CASE 
                        WHEN (lf.tipo = 'pagar' OR lf.tipo = 'despesa') 
                        AND (lf.status = 'quitado' OR lf.status = 'pago') 
                        THEN lf.valor_total ELSE 0 
                    END) as despesas_realizado
                FROM centros_custos cc
                LEFT JOIN lancamentos_financeiros lf ON lf.centro_custo_id = cc.id
                $whereClause
                GROUP BY cc.id, cc.nome
                HAVING receitas_realizado > 0 OR despesas_realizado > 0
                ORDER BY (receitas_realizado - despesas_realizado) DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $centros];
        } catch (Exception $e) {
            logError("Erro ao obter dados por centro de custo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter dados por centro de custo'];
        }
    }
}

$auth = financeiro_require_auth('dashboard');

$api = new DashboardFinanceiroAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'indicadores';
    $filtros = [
        'unidade_id' => $_GET['unidade_id'] ?? null,
        'conta_bancaria_id' => $_GET['conta_bancaria_id'] ?? null,
        'data_inicio' => $_GET['data_inicio'] ?? null,
        'data_fim' => $_GET['data_fim'] ?? null,
    ];

    switch ($action) {
        case 'indicadores':
            financeiro_response($api->obterIndicadores($filtros));
            break;
        case 'por_categoria':
            financeiro_response($api->obterPorCategoria($filtros));
            break;
        case 'por_centro_custo':
            financeiro_response($api->obterPorCentroCusto($filtros));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Ação inválida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Método não permitido'], 405);



