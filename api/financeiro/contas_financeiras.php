<?php
// ANATEJE - API de Contas Financeiras
// Sistema de Gestao Financeira Associativa ANATEJE

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/unidade_helper.php';

require_once __DIR__ . '/_bootstrap.php';
class ContasFinanceirasAPI
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
                SELECT c.*, u.nome AS unidade_nome, cu.nome AS criador_nome, uu.nome AS atualizador_nome
                FROM contas_financeiras c
                LEFT JOIN unidades u ON c.unidade_id = u.id
                LEFT JOIN usuarios cu ON c.created_by = cu.id
                LEFT JOIN usuarios uu ON c.updated_by = uu.id
                WHERE 1=1
            ";

            $params = [];

            if ($unidadeSessao !== null) {
                $sql .= " AND c.unidade_id = ?";
                $params[] = $unidadeSessao;
            } elseif (!empty($filtros['unidade_id'])) {
                $sql .= " AND c.unidade_id = ?";
                $params[] = (int)$filtros['unidade_id'];
            }

            if (!empty($filtros['status'])) {
                $sql .= " AND c.status = ?";
                $params[] = $filtros['status'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (c.nome LIKE ? OR c.banco LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY c.nome ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $contas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $contas];
        } catch (Exception $e) {
            logError("Erro ao listar contas financeiras: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno ao listar contas'];
        }
    }

    public function obter($id)
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $sql = "
                SELECT c.*, u.nome AS unidade_nome
                FROM contas_financeiras c
                LEFT JOIN unidades u ON c.unidade_id = u.id
                WHERE c.id = ?
            ";

            $params = [$id];
            if ($unidadeSessao !== null) {
                $sql .= " AND c.unidade_id = ?";
                $params[] = $unidadeSessao;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $conta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conta) {
                return ['success' => false, 'message' => 'Conta não encontrada'];
            }

            return ['success' => true, 'data' => $conta];
        } catch (Exception $e) {
            logError("Erro ao obter conta financeira: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno ao obter conta'];
        }
    }

    public function criar($dados)
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $unidadeId = $unidadeSessao ?? ($dados['unidade_id'] ?? null);

            if (!$unidadeId) {
                return ['success' => false, 'message' => 'Unidade obrigatória'];
            }

            if (empty($dados['nome'])) {
                return ['success' => false, 'message' => 'Nome da conta é obrigatório'];
            }

            if (empty($dados['tipo'])) {
                return ['success' => false, 'message' => 'Tipo da conta é obrigatório'];
            }

            $stmt = $this->db->prepare("
                INSERT INTO contas_financeiras
                (unidade_id, nome, tipo, banco, agencia, numero_conta, titular, documento,
                 saldo_inicial, saldo_atual, data_saldo, status, observacoes, created_by, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $saldoInicial = isset($dados['saldo_inicial']) ? (float)$dados['saldo_inicial'] : 0;

            $stmt->execute([
                $unidadeId,
                $dados['nome'],
                $dados['tipo'],
                $dados['banco'] ?? null,
                $dados['agencia'] ?? null,
                $dados['numero_conta'] ?? null,
                $dados['titular'] ?? null,
                $dados['documento'] ?? null,
                $saldoInicial,
                $saldoInicial,
                $dados['data_saldo'] ?? null,
                $dados['status'] ?? 'ativa',
                $dados['observacoes'] ?? null,
                $_SESSION['user_id'] ?? null,
                $_SESSION['user_id'] ?? null
            ]);

            return ['success' => true, 'message' => 'Conta criada com sucesso', 'data' => ['id' => $this->db->lastInsertId()]];
        } catch (Exception $e) {
            logError("Erro ao criar conta financeira: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno ao criar conta'];
        }
    }

    public function atualizar($id, $dados)
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $sql = "SELECT * FROM contas_financeiras WHERE id = ?";
            $params = [$id];
            if ($unidadeSessao !== null) {
                $sql .= " AND unidade_id = ?";
                $params[] = $unidadeSessao;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $conta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conta) {
                return ['success' => false, 'message' => 'Conta não encontrada'];
            }

            $campos = [];
            $valores = [];

            $colunas = [
                'nome', 'tipo', 'banco', 'agencia', 'numero_conta', 'titular', 'documento',
                'saldo_inicial', 'saldo_atual', 'data_saldo', 'status', 'observacoes'
            ];

            foreach ($colunas as $coluna) {
                if (isset($dados[$coluna])) {
                    $campos[] = "$coluna = ?";
                    $valores[] = $dados[$coluna];
                }
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $campos[] = 'updated_by = ?';
            $valores[] = $_SESSION['user_id'] ?? null;
            $campos[] = 'updated_at = NOW()';
            $valores[] = $id;

            $sqlUpdate = "UPDATE contas_financeiras SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $this->db->prepare($sqlUpdate);
            $stmt->execute($valores);

            return ['success' => true, 'message' => 'Conta atualizada com sucesso'];
        } catch (Exception $e) {
            logError("Erro ao atualizar conta financeira: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno ao atualizar conta'];
        }
    }

    public function excluir($id)
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $sql = "DELETE FROM contas_financeiras WHERE id = ?";
            $params = [$id];

            if ($unidadeSessao !== null) {
                $sql .= " AND unidade_id = ?";
                $params[] = $unidadeSessao;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Conta não encontrada ou sem permissão'];
            }

            return ['success' => true, 'message' => 'Conta excluída com sucesso'];
        } catch (Exception $e) {
            logError("Erro ao excluir conta financeira: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno ao excluir conta'];
        }
    }
}

// ======= Controle da Requisição =======
$auth = financeiro_require_auth('contas_financeiras');

$api = new ContasFinanceirasAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            $filtros = [
                'status' => $_GET['status'] ?? null,
                'buscar' => $_GET['buscar'] ?? null,
                'unidade_id' => $_GET['unidade_id'] ?? null,
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
    switch ($action) {
        case 'criar':
            financeiro_response($api->criar($_POST));
            break;
        case 'atualizar':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatório'], 400);
            }
            financeiro_response($api->atualizar($id, $_POST));
            break;
        case 'excluir':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatório'], 400);
            }
            financeiro_response($api->excluir($id));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Ação inválida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Método não permitido'], 405);



