<?php
// API de Centros de Custo - padrao ANATEJE

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/unidade_helper.php';
require_once __DIR__ . '/_bootstrap.php';

class CentrosCustoAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function listar(array $filtros = []): array
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $sql = "SELECT c.*, u.nome AS unidade_nome, r.nome AS responsavel_nome
                    FROM centros_custos c
                    LEFT JOIN unidades u ON c.unidade_id = u.id
                    LEFT JOIN usuarios r ON c.responsavel_id = r.id
                    WHERE 1=1";
            $params = [];

            if ($unidadeSessao !== null) {
                $sql .= " AND c.unidade_id = ?";
                $params[] = $unidadeSessao;
            } elseif (!empty($filtros['unidade_id'])) {
                $sql .= " AND c.unidade_id = ?";
                $params[] = (int) $filtros['unidade_id'];
            }

            if (!empty($filtros['status'])) {
                $sql .= " AND c.status = ?";
                $params[] = (string) $filtros['status'];
            }
            if (!empty($filtros['buscar'])) {
                $sql .= " AND c.nome LIKE ?";
                $params[] = '%' . (string) $filtros['buscar'] . '%';
            }

            $sql .= " ORDER BY c.nome ASC";
            $st = $this->db->prepare($sql);
            $st->execute($params);

            return ['success' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Throwable $e) {
            logError('Erro ao listar centros de custo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar centros'];
        }
    }

    public function obter(int $id): array
    {
        try {
            if ($id <= 0) {
                return ['success' => false, 'message' => 'ID obrigatorio'];
            }

            $unidadeSessao = getUserUnidadeId();
            $sql = "SELECT * FROM centros_custos WHERE id = ?";
            $params = [$id];
            if ($unidadeSessao !== null) {
                $sql .= " AND unidade_id = ?";
                $params[] = $unidadeSessao;
            }

            $st = $this->db->prepare($sql);
            $st->execute($params);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'message' => 'Centro de custo nao encontrado'];
            }

            return ['success' => true, 'data' => $row];
        } catch (Throwable $e) {
            logError('Erro ao obter centro de custo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter centro'];
        }
    }

    public function criar(array $dados): array
    {
        try {
            $nome = trim((string) ($dados['nome'] ?? ''));
            if ($nome === '') {
                return ['success' => false, 'message' => 'Nome e obrigatorio'];
            }

            $unidadeSessao = getUserUnidadeId();
            $unidadeId = $unidadeSessao ?? (!empty($dados['unidade_id']) ? (int) $dados['unidade_id'] : null);
            if (empty($unidadeId)) {
                return ['success' => false, 'message' => 'Unidade obrigatoria'];
            }

            $status = strtolower(trim((string) ($dados['status'] ?? 'ativo')));
            if (!in_array($status, ['ativo', 'inativo'], true)) {
                return ['success' => false, 'message' => 'Status invalido'];
            }

            $descricao = trim((string) ($dados['descricao'] ?? ''));
            $responsavelId = !empty($dados['responsavel_id']) ? (int) $dados['responsavel_id'] : null;

            $st = $this->db->prepare("INSERT INTO centros_custos
                (unidade_id, nome, descricao, responsavel_id, status)
                VALUES (?, ?, ?, ?, ?)");
            $st->execute([
                (int) $unidadeId,
                sanitizeInput($nome),
                $descricao !== '' ? sanitizeInput($descricao) : null,
                $responsavelId,
                $status
            ]);

            return [
                'success' => true,
                'message' => 'Centro de custo criado',
                'data' => ['id' => (int) $this->db->lastInsertId()]
            ];
        } catch (Throwable $e) {
            logError('Erro ao criar centro de custo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao criar centro'];
        }
    }

    public function atualizar(int $id, array $dados): array
    {
        try {
            $atual = $this->obter($id);
            if (empty($atual['success'])) {
                return $atual;
            }

            $campos = [];
            $valores = [];

            if (isset($dados['nome'])) {
                $nome = trim((string) $dados['nome']);
                if ($nome === '') {
                    return ['success' => false, 'message' => 'Nome e obrigatorio'];
                }
                $campos[] = 'nome = ?';
                $valores[] = sanitizeInput($nome);
            }
            if (array_key_exists('descricao', $dados)) {
                $descricao = trim((string) $dados['descricao']);
                $campos[] = 'descricao = ?';
                $valores[] = $descricao !== '' ? sanitizeInput($descricao) : null;
            }
            if (array_key_exists('responsavel_id', $dados)) {
                $campos[] = 'responsavel_id = ?';
                $valores[] = !empty($dados['responsavel_id']) ? (int) $dados['responsavel_id'] : null;
            }
            if (isset($dados['status'])) {
                $status = strtolower(trim((string) $dados['status']));
                if (!in_array($status, ['ativo', 'inativo'], true)) {
                    return ['success' => false, 'message' => 'Status invalido'];
                }
                $campos[] = 'status = ?';
                $valores[] = $status;
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $valores[] = $id;
            $sql = "UPDATE centros_custos SET " . implode(', ', $campos) . ", updated_at = NOW() WHERE id = ?";
            $st = $this->db->prepare($sql);
            $st->execute($valores);

            return ['success' => true, 'message' => 'Centro atualizado com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao atualizar centro de custo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar centro'];
        }
    }

    public function excluir(int $id): array
    {
        try {
            $atual = $this->obter($id);
            if (empty($atual['success'])) {
                return $atual;
            }

            $st = $this->db->prepare("SELECT COUNT(*) FROM lancamentos_financeiros WHERE centro_custo_id = ?");
            $st->execute([$id]);
            if ((int) $st->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Nao e possivel excluir centro com lancamentos vinculados'];
            }

            $st = $this->db->prepare("DELETE FROM centros_custos WHERE id = ?");
            $st->execute([$id]);

            return ['success' => true, 'message' => 'Centro excluido com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao excluir centro de custo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao excluir centro'];
        }
    }
}

$auth = financeiro_require_auth('centros_custos');
$api = new CentrosCustoAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            financeiro_response($api->listar([
                'status' => $_GET['status'] ?? null,
                'buscar' => $_GET['buscar'] ?? null,
                'unidade_id' => $_GET['unidade_id'] ?? null,
            ]));
            break;
        case 'obter':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            financeiro_response($api->obter($id));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao invalida'], 404);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = financeiro_input();
    $action = (string) ($input['action'] ?? '');

    switch ($action) {
        case 'criar':
            $result = $api->criar($input);
            if (!empty($result['success'])) {
                $entityId = financeiro_extract_entity_id($result);
                $after = $entityId ? ($api->obter($entityId)['data'] ?? null) : null;
                financeiro_audit($auth, 'create', 'centros_custos', $entityId, null, $after, ['action' => 'criar']);
            }
            financeiro_response($result);
            break;
        case 'atualizar':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            $before = $api->obter($id)['data'] ?? null;
            $result = $api->atualizar($id, $input);
            if (!empty($result['success'])) {
                $after = $api->obter($id)['data'] ?? null;
                financeiro_audit($auth, 'update', 'centros_custos', $id, $before, $after, ['action' => 'atualizar']);
            }
            financeiro_response($result);
            break;
        case 'excluir':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            $before = $api->obter($id)['data'] ?? null;
            $result = $api->excluir($id);
            if (!empty($result['success'])) {
                financeiro_audit($auth, 'delete', 'centros_custos', $id, $before, null, ['action' => 'excluir']);
            }
            financeiro_response($result);
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao invalida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Metodo nao permitido'], 405);
