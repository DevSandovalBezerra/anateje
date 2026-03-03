<?php
// ANATEJE - API de Contas a Pagar/Receber com Parcelas

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/unidade_helper.php';

require_once __DIR__ . '/_bootstrap.php';
class ContasAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function listar($filtros = [])
    {
        try {
            $sql = "SELECT lf.*,
                           (SELECT COUNT(*) FROM lancamento_parcelas lp WHERE lp.lancamento_id = lf.id) AS total_parcelas,
                           (SELECT COUNT(*) FROM lancamento_parcelas lp WHERE lp.lancamento_id = lf.id AND lp.status = 'paga') AS parcelas_pagas
                    FROM lancamentos_financeiros lf
                    WHERE 1=1";
            $params = [];

            if (!empty($filtros['tipo']) && in_array($filtros['tipo'], ['pagar','receber'])) {
                $sql .= " AND lf.tipo = ?";
                $params[] = $filtros['tipo'];
            }
            if (!empty($filtros['status'])) {
                $sql .= " AND lf.status = ?";
                $params[] = $filtros['status'];
            }
            if (!empty($filtros['unidade_id'])) {
                $sql .= " AND lf.unidade_id = ?";
                $params[] = (int)$filtros['unidade_id'];
            } else {
                $unidadeSessao = getUserUnidadeId();
                if ($unidadeSessao !== null) {
                    $sql .= " AND lf.unidade_id = ?";
                    $params[] = $unidadeSessao;
                }
            }

            $sql .= " ORDER BY lf.data_vencimento IS NULL, lf.data_vencimento ASC, lf.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $rows];
        } catch (Exception $e) {
            logError('Erro ao listar contas', ['err' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro ao listar contas'];
        }
    }

    public function obter($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM lancamentos_financeiros WHERE id = ?");
            $stmt->execute([$id]);
            $conta = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$conta) {
                return ['success' => false, 'message' => 'Conta nÃ£o encontrada'];
            }
            $stmt = $this->db->prepare("SELECT * FROM lancamento_parcelas WHERE lancamento_id = ? ORDER BY numero_parcela ASC");
            $stmt->execute([$id]);
            $parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => ['conta' => $conta, 'parcelas' => $parcelas]];
        } catch (Exception $e) {
            logError('Erro ao obter conta', ['err' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro ao obter conta'];
        }
    }

    public function criar($dados)
    {
        try {
            $tipo = $dados['tipo'] ?? null;
            $descricao = sanitizeInput($dados['descricao'] ?? '');
            $valor_total = isset($dados['valor_total']) ? (float)$dados['valor_total'] : 0;
            $data_emissao = $dados['data_emissao'] ?? date('Y-m-d');
            $data_vencimento = $dados['data_vencimento'] ?? null;
            $unidadeSessao = getUserUnidadeId();

            if (!$tipo || !in_array($tipo, ['pagar','receber'])) {
                return ['success' => false, 'message' => 'Tipo invÃ¡lido'];
            }
            if (!$descricao || $valor_total <= 0) {
                return ['success' => false, 'message' => 'DescriÃ§Ã£o e valor sÃ£o obrigatÃ³rios'];
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO lancamentos_financeiros (unidade_id, tipo, descricao, valor_total, data_emissao, data_vencimento, status)
                                         VALUES (?, ?, ?, ?, ?, ?, 'pendente')");
            $stmt->execute([$unidadeSessao, $tipo, $descricao, $valor_total, $data_emissao, $data_vencimento]);
            $lancamento_id = (int)$this->db->lastInsertId();

            $qtd_parcelas = isset($dados['qtd_parcelas']) ? (int)$dados['qtd_parcelas'] : 1;
            $primeiro_vencimento = $dados['primeiro_vencimento'] ?? $data_vencimento ?? date('Y-m-d');

            if ($qtd_parcelas <= 0) { $qtd_parcelas = 1; }

            // Gerar parcelas
            $valor_parcela_base = $qtd_parcelas > 0 ? floor($valor_total * 100 / $qtd_parcelas) / 100 : $valor_total; // evitar centavos perdidos
            $restante = round($valor_total - ($valor_parcela_base * $qtd_parcelas), 2);

            $data = new DateTime($primeiro_vencimento);
            for ($i = 1; $i <= $qtd_parcelas; $i++) {
                $valor_parcela = $valor_parcela_base;
                if ($restante > 0) {
                    $valor_parcela = round($valor_parcela + 0.01, 2);
                    $restante = round($restante - 0.01, 2);
                }
                $stmtParcela = $this->db->prepare("INSERT INTO lancamento_parcelas (lancamento_id, numero_parcela, valor_parcela, data_vencimento, status)
                                                     VALUES (?, ?, ?, ?, 'pendente')");
                $stmtParcela->execute([$lancamento_id, $i, $valor_parcela, $data->format('Y-m-d')]);
                // PrÃ³ximo mÃªs
                $data->modify('+1 month');
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Conta criada com sucesso', 'id' => $lancamento_id];
        } catch (Exception $e) {
            $this->db->rollBack();
            logError('Erro ao criar conta', ['err' => $e->getMessage(), 'dados' => $dados]);
            return ['success' => false, 'message' => 'Erro ao criar conta'];
        }
    }

    public function registrarPagamento($parcela_id, $valor_pago, $data_pagamento)
    {
        try {
            // Atualizar parcela
            $stmt = $this->db->prepare("UPDATE lancamento_parcelas SET valor_pago = ?, data_pagamento = ?, status = 'paga' WHERE id = ?");
            $stmt->execute([(float)$valor_pago, $data_pagamento, (int)$parcela_id]);

            // Ajustar status do lanÃ§amento
            $stmt = $this->db->prepare("SELECT lancamento_id FROM lancamento_parcelas WHERE id = ?");
            $stmt->execute([(int)$parcela_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $lancamento_id = (int)$row['lancamento_id'];
                $stmt = $this->db->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status='paga' THEN 1 ELSE 0 END) AS pagas FROM lancamento_parcelas WHERE lancamento_id = ?");
                $stmt->execute([$lancamento_id]);
                $agg = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($agg) {
                    $status = ((int)$agg['pagas'] >= (int)$agg['total']) ? 'pago' : 'parcial';
                    $stmt = $this->db->prepare("UPDATE lancamentos_financeiros SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $lancamento_id]);
                }
            }

            return ['success' => true, 'message' => 'Pagamento registrado'];
        } catch (Exception $e) {
            logError('Erro ao registrar pagamento', ['err' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro ao registrar pagamento'];
        }
    }

    public function excluir($id)
    {
        try {
            // Cancelar lanÃ§amento e parcelas
            $stmt = $this->db->prepare("UPDATE lancamentos_financeiros SET status = 'cancelado' WHERE id = ?");
            $stmt->execute([(int)$id]);
            $stmt = $this->db->prepare("UPDATE lancamento_parcelas SET status = 'cancelada' WHERE lancamento_id = ?");
            $stmt->execute([(int)$id]);
            return ['success' => true, 'message' => 'Conta cancelada'];
        } catch (Exception $e) {
            logError('Erro ao excluir conta', ['err' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro ao excluir conta'];
        }
    }
}

// AutenticaÃ§Ã£o
$auth = financeiro_require_auth('contas');

$api = new ContasAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            $filtros = [
                'tipo' => $_GET['tipo'] ?? null,
                'status' => $_GET['status'] ?? null,
                'unidade_id' => $_GET['unidade_id'] ?? null,
            ];
            financeiro_response($api->listar($filtros));
            break;
        case 'obter':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) { financeiro_response(['success' => false, 'message' => 'ID obrigatÃ³rio'], 400); }
            financeiro_response($api->obter($id));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'AÃ§Ã£o invÃ¡lida'], 404);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = financeiro_input();
    if (!empty($input)) {
        $_POST = $input;
    }
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'criar':
            financeiro_response($api->criar($_POST));
            break;
        case 'registrar_pagamento':
            $parcela_id = (int)($_POST['parcela_id'] ?? 0);
            $valor_pago = $_POST['valor_pago'] ?? null;
            $data_pagamento = $_POST['data_pagamento'] ?? date('Y-m-d');
            if (!$parcela_id || !$valor_pago) { financeiro_response(['success' => false, 'message' => 'Parcela e valor sÃ£o obrigatÃ³rios'], 400); }
            financeiro_response($api->registrarPagamento($parcela_id, $valor_pago, $data_pagamento));
            break;
        case 'excluir':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { financeiro_response(['success' => false, 'message' => 'ID obrigatÃ³rio'], 400); }
            financeiro_response($api->excluir($id));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'AÃ§Ã£o invÃ¡lida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'MÃ©todo nÃ£o permitido'], 405);
