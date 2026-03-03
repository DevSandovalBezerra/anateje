<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/unidade_helper.php';

require_once __DIR__ . '/_bootstrap.php';
class ReceitasDespesasAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function listar($filtros = [])
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $sql = "
                SELECT rd.*, 
                       u.nome AS unidade_nome,
                       cc.nome AS centro_nome,
                       cf.nome AS categoria_nome,
                       cf.tipo AS categoria_tipo,
                       conta.nome AS conta_nome
                FROM receitas_despesas_fixas rd
                LEFT JOIN unidades u ON rd.unidade_id = u.id
                LEFT JOIN centros_custos cc ON rd.centro_custo_id = cc.id
                LEFT JOIN categorias_financeiras cf ON rd.categoria_id = cf.id
                LEFT JOIN contas_financeiras conta ON rd.conta_financeira_id = conta.id
                WHERE 1=1
            ";

            $params = [];

            if ($unidadeSessao !== null) {
                $sql .= " AND rd.unidade_id = ?";
                $params[] = $unidadeSessao;
            } elseif (!empty($filtros['unidade_id'])) {
                $sql .= " AND rd.unidade_id = ?";
                $params[] = (int)$filtros['unidade_id'];
            }

            if (!empty($filtros['tipo'])) {
                $sql .= " AND rd.tipo = ?";
                $params[] = $filtros['tipo'];
            }

            if (isset($filtros['ativo'])) {
                $sql .= " AND rd.ativo = ?";
                $params[] = (int)$filtros['ativo'];
            }

            if (!empty($filtros['centro_custo_id'])) {
                $sql .= " AND rd.centro_custo_id = ?";
                $params[] = (int)$filtros['centro_custo_id'];
            }

            if (!empty($filtros['categoria_id'])) {
                $sql .= " AND rd.categoria_id = ?";
                $params[] = (int)$filtros['categoria_id'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (rd.nome LIKE ? OR rd.descricao LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY rd.tipo, rd.nome ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $registros];
        } catch (Exception $e) {
            logError("Erro ao listar receitas/despesas fixas: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar registros'];
        }
    }

    public function obter($id)
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $sql = "SELECT * FROM receitas_despesas_fixas WHERE id = ?";
            $params = [$id];

            if ($unidadeSessao !== null) {
                $sql .= " AND unidade_id = ?";
                $params[] = $unidadeSessao;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registro) {
                return ['success' => false, 'message' => 'Registro não encontrado'];
            }

            return ['success' => true, 'data' => $registro];
        } catch (Exception $e) {
            logError("Erro ao obter receita/despesa fixa: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter registro'];
        }
    }

    public function criar($dados)
    {
        try {
            if (empty($dados['nome']) || empty($dados['tipo']) || empty($dados['valor']) || 
                empty($dados['centro_custo_id']) || empty($dados['categoria_id'])) {
                return ['success' => false, 'message' => 'Campos obrigatórios: nome, tipo, valor, centro_custo_id, categoria_id'];
            }

            if (!in_array($dados['tipo'], ['receita', 'despesa'])) {
                return ['success' => false, 'message' => 'Tipo inválido'];
            }

            if ((float)$dados['valor'] <= 0) {
                return ['success' => false, 'message' => 'Valor deve ser maior que zero'];
            }

            $unidadeSessao = getUserUnidadeId();
            $unidadeId = $unidadeSessao ?? ($dados['unidade_id'] ?? null);

            if (!$unidadeId) {
                return ['success' => false, 'message' => 'Unidade obrigatória'];
            }

            if (!$this->validarCentroCusto($dados['centro_custo_id'], $unidadeId)) {
                return ['success' => false, 'message' => 'Centro de custo inválido ou sem permissão'];
            }

            if (!$this->validarCategoria($dados['categoria_id'], $unidadeId)) {
                return ['success' => false, 'message' => 'Categoria inválida ou sem permissão'];
            }

            if (!empty($dados['conta_financeira_id']) && !$this->validarContaFinanceira($dados['conta_financeira_id'], $unidadeId)) {
                return ['success' => false, 'message' => 'Conta financeira inválida ou sem permissão'];
            }

            $diaVencimento = isset($dados['dia_vencimento']) ? (int)$dados['dia_vencimento'] : 5;
            if ($diaVencimento < 1 || $diaVencimento > 28) {
                $diaVencimento = 5;
            }

            $stmt = $this->db->prepare("
                INSERT INTO receitas_despesas_fixas
                (unidade_id, tipo, nome, descricao, valor, centro_custo_id, categoria_id, 
                 conta_financeira_id, periodicidade, dia_vencimento, ativo, observacoes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $userId = $_SESSION['user_id'] ?? null;
            $stmt->execute([
                $unidadeId,
                $dados['tipo'],
                $dados['nome'],
                $dados['descricao'] ?? null,
                $dados['valor'],
                $dados['centro_custo_id'],
                $dados['categoria_id'],
                $dados['conta_financeira_id'] ?? null,
                $dados['periodicidade'] ?? 'mensal',
                $diaVencimento,
                isset($dados['ativo']) ? (int)$dados['ativo'] : 1,
                $dados['observacoes'] ?? null,
                $userId
            ]);

            return ['success' => true, 'message' => 'Registro criado com sucesso', 'data' => ['id' => $this->db->lastInsertId()]];
        } catch (Exception $e) {
            logError("Erro ao criar receita/despesa fixa: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao criar registro'];
        }
    }

    public function atualizar($id, $dados)
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $sql = "SELECT * FROM receitas_despesas_fixas WHERE id = ?";
            $params = [$id];

            if ($unidadeSessao !== null) {
                $sql .= " AND unidade_id = ?";
                $params[] = $unidadeSessao;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registro) {
                return ['success' => false, 'message' => 'Registro não encontrado'];
            }

            $unidadeId = $unidadeSessao ?? $registro['unidade_id'];

            if (isset($dados['centro_custo_id']) && !$this->validarCentroCusto($dados['centro_custo_id'], $unidadeId)) {
                return ['success' => false, 'message' => 'Centro de custo inválido'];
            }

            if (isset($dados['categoria_id']) && !$this->validarCategoria($dados['categoria_id'], $unidadeId)) {
                return ['success' => false, 'message' => 'Categoria inválida'];
            }

            if (isset($dados['conta_financeira_id']) && !empty($dados['conta_financeira_id']) && 
                !$this->validarContaFinanceira($dados['conta_financeira_id'], $unidadeId)) {
                return ['success' => false, 'message' => 'Conta financeira inválida'];
            }

            $campos = [];
            $valores = [];

            if (isset($dados['nome'])) {
                $campos[] = 'nome = ?';
                $valores[] = $dados['nome'];
            }
            if (isset($dados['descricao'])) {
                $campos[] = 'descricao = ?';
                $valores[] = $dados['descricao'];
            }
            if (isset($dados['valor'])) {
                if ((float)$dados['valor'] <= 0) {
                    return ['success' => false, 'message' => 'Valor deve ser maior que zero'];
                }
                $campos[] = 'valor = ?';
                $valores[] = $dados['valor'];
            }
            if (isset($dados['centro_custo_id'])) {
                $campos[] = 'centro_custo_id = ?';
                $valores[] = $dados['centro_custo_id'];
            }
            if (isset($dados['categoria_id'])) {
                $campos[] = 'categoria_id = ?';
                $valores[] = $dados['categoria_id'];
            }
            if (isset($dados['conta_financeira_id'])) {
                $campos[] = 'conta_financeira_id = ?';
                $valores[] = !empty($dados['conta_financeira_id']) ? $dados['conta_financeira_id'] : null;
            }
            if (isset($dados['periodicidade'])) {
                $campos[] = 'periodicidade = ?';
                $valores[] = $dados['periodicidade'];
            }
            if (isset($dados['dia_vencimento'])) {
                $diaVencimento = (int)$dados['dia_vencimento'];
                if ($diaVencimento >= 1 && $diaVencimento <= 28) {
                    $campos[] = 'dia_vencimento = ?';
                    $valores[] = $diaVencimento;
                }
            }
            if (isset($dados['ativo'])) {
                $campos[] = 'ativo = ?';
                $valores[] = (int)$dados['ativo'];
            }
            if (isset($dados['observacoes'])) {
                $campos[] = 'observacoes = ?';
                $valores[] = $dados['observacoes'];
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $campos[] = 'updated_by = ?';
            $valores[] = $_SESSION['user_id'] ?? null;
            $valores[] = $id;

            $stmt = $this->db->prepare("
                UPDATE receitas_despesas_fixas 
                SET " . implode(', ', $campos) . ", updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute($valores);

            return ['success' => true, 'message' => 'Registro atualizado com sucesso'];
        } catch (Exception $e) {
            logError("Erro ao atualizar receita/despesa fixa: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar registro'];
        }
    }

    public function excluir($id)
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $sql = "DELETE FROM receitas_despesas_fixas WHERE id = ?";
            $params = [$id];

            if ($unidadeSessao !== null) {
                $sql .= " AND unidade_id = ?";
                $params[] = $unidadeSessao;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Registro não encontrado ou sem permissão'];
            }

            return ['success' => true, 'message' => 'Registro excluído com sucesso'];
        } catch (Exception $e) {
            logError("Erro ao excluir receita/despesa fixa: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao excluir registro'];
        }
    }

    public function gerarContas($id, $mes, $ano)
    {
        try {
            $registro = $this->obter($id);
            if (!$registro['success']) {
                return $registro;
            }

            $rd = $registro['data'];
            if ($rd['ativo'] != 1) {
                return ['success' => false, 'message' => 'Registro inativo'];
            }

            $diaVenc = min(28, max(1, (int)$rd['dia_vencimento']));
            $dataVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $diaVenc);

            $tipoLancamento = $rd['tipo'] === 'receita' ? 'receber' : 'pagar';

            $stmt = $this->db->prepare("
                INSERT INTO lancamentos_financeiros
                (unidade_id, tipo, descricao, valor_total, data_emissao, data_vencimento, origem, origem_id)
                VALUES (?, ?, ?, ?, CURDATE(), ?, 'receita_despesa_fixa', ?)
            ");

            $stmt->execute([
                $rd['unidade_id'],
                $tipoLancamento,
                $rd['nome'],
                $rd['valor'],
                $dataVencimento,
                $id
            ]);

            $lancamentoId = $this->db->lastInsertId();

            $stmt = $this->db->prepare("
                INSERT INTO lancamento_parcelas
                (lancamento_id, numero_parcela, valor_parcela, data_vencimento, status)
                VALUES (?, 1, ?, ?, 'pendente')
            ");

            $stmt->execute([
                $lancamentoId,
                $rd['valor'],
                $dataVencimento
            ]);

            return ['success' => true, 'message' => 'Conta gerada com sucesso', 'data' => ['lancamento_id' => $lancamentoId]];
        } catch (Exception $e) {
            logError("Erro ao gerar conta: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao gerar conta'];
        }
    }

    private function validarCentroCusto($id, $unidadeId)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM centros_custos WHERE id = ? AND unidade_id = ? AND status = 'ativo'");
            $stmt->execute([$id, $unidadeId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function validarCategoria($id, $unidadeId)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM categorias_financeiras WHERE id = ? AND (unidade_id = ? OR unidade_id IS NULL) AND ativo = 1");
            $stmt->execute([$id, $unidadeId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function validarContaFinanceira($id, $unidadeId)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM contas_financeiras WHERE id = ? AND unidade_id = ? AND status = 'ativa'");
            $stmt->execute([$id, $unidadeId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

$auth = financeiro_require_auth('receitas_despesas');

$api = new ReceitasDespesasAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            $filtros = [
                'tipo' => $_GET['tipo'] ?? null,
                'ativo' => isset($_GET['ativo']) ? (int)$_GET['ativo'] : null,
                'centro_custo_id' => $_GET['centro_custo_id'] ?? null,
                'categoria_id' => $_GET['categoria_id'] ?? null,
                'unidade_id' => $_GET['unidade_id'] ?? null,
                'buscar' => $_GET['buscar'] ?? null,
            ];
            financeiro_response($api->listar($filtros));
            break;
        case 'obter':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatório'], 400);
            }
            financeiro_response($api->obter($id));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Ação inválida'], 404);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = financeiro_input();
    if (!empty($input)) {
        $_POST = $input;
    }
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;

    switch ($action) {
        case 'criar':
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'tipo' => $_POST['tipo'] ?? '',
                'descricao' => $_POST['descricao'] ?? null,
                'valor' => $_POST['valor'] ?? 0,
                'centro_custo_id' => (int)($_POST['centro_custo_id'] ?? 0),
                'categoria_id' => (int)($_POST['categoria_id'] ?? 0),
                'conta_financeira_id' => !empty($_POST['conta_financeira_id']) ? (int)$_POST['conta_financeira_id'] : null,
                'periodicidade' => $_POST['periodicidade'] ?? 'mensal',
                'dia_vencimento' => isset($_POST['dia_vencimento']) ? (int)$_POST['dia_vencimento'] : 5,
                'ativo' => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1,
                'observacoes' => $_POST['observacoes'] ?? null,
                'unidade_id' => isset($_POST['unidade_id']) ? (int)$_POST['unidade_id'] : null,
            ];
            financeiro_response($api->criar($dados));
            break;
        case 'atualizar':
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatório'], 400);
            }
            $dados = [];
            if (isset($_POST['nome'])) $dados['nome'] = $_POST['nome'];
            if (isset($_POST['descricao'])) $dados['descricao'] = $_POST['descricao'];
            if (isset($_POST['valor'])) $dados['valor'] = (float)$_POST['valor'];
            if (isset($_POST['centro_custo_id'])) $dados['centro_custo_id'] = (int)$_POST['centro_custo_id'];
            if (isset($_POST['categoria_id'])) $dados['categoria_id'] = (int)$_POST['categoria_id'];
            if (isset($_POST['conta_financeira_id'])) $dados['conta_financeira_id'] = !empty($_POST['conta_financeira_id']) ? (int)$_POST['conta_financeira_id'] : null;
            if (isset($_POST['periodicidade'])) $dados['periodicidade'] = $_POST['periodicidade'];
            if (isset($_POST['dia_vencimento'])) $dados['dia_vencimento'] = (int)$_POST['dia_vencimento'];
            if (isset($_POST['ativo'])) $dados['ativo'] = (int)$_POST['ativo'];
            if (isset($_POST['observacoes'])) $dados['observacoes'] = $_POST['observacoes'];
            financeiro_response($api->atualizar($id, $dados));
            break;
        case 'excluir':
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatório'], 400);
            }
            financeiro_response($api->excluir($id));
            break;
        case 'gerar_conta':
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatório'], 400);
            }
            $mes = isset($_POST['mes']) ? (int)$_POST['mes'] : date('n');
            $ano = isset($_POST['ano']) ? (int)$_POST['ano'] : date('Y');
            financeiro_response($api->gerarContas($id, $mes, $ano));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Ação inválida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Método não permitido'], 405);

