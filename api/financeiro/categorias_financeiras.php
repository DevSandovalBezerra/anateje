<?php
// API de Categorias Financeiras - padrao ANATEJE

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/unidade_helper.php';
require_once __DIR__ . '/_bootstrap.php';

class CategoriasFinanceirasAPI
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
            $sql = "SELECT c.*, u.nome AS unidade_nome, parent.nome AS parent_nome
                    FROM categorias_financeiras c
                    LEFT JOIN unidades u ON c.unidade_id = u.id
                    LEFT JOIN categorias_financeiras parent ON c.parent_id = parent.id
                    WHERE 1=1";
            $params = [];

            if ($unidadeSessao !== null) {
                $sql .= " AND (c.unidade_id = ? OR c.unidade_id IS NULL)";
                $params[] = $unidadeSessao;
            } elseif (!empty($filtros['unidade_id'])) {
                $sql .= " AND (c.unidade_id = ? OR c.unidade_id IS NULL)";
                $params[] = (int) $filtros['unidade_id'];
            }

            if (!empty($filtros['tipo'])) {
                $sql .= " AND c.tipo = ?";
                $params[] = (string) $filtros['tipo'];
            }
            if (isset($filtros['ativo'])) {
                $sql .= " AND c.ativo = ?";
                $params[] = (int) $filtros['ativo'];
            }

            $sql .= " ORDER BY c.tipo, c.nome ASC";
            $st = $this->db->prepare($sql);
            $st->execute($params);

            return ['success' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Throwable $e) {
            logError('Erro ao listar categorias financeiras: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar categorias'];
        }
    }

    public function obter(int $id): array
    {
        try {
            if ($id <= 0) {
                return ['success' => false, 'message' => 'ID obrigatorio'];
            }

            $unidadeSessao = getUserUnidadeId();
            $sql = "SELECT * FROM categorias_financeiras WHERE id = ?";
            $params = [$id];
            if ($unidadeSessao !== null) {
                $sql .= " AND (unidade_id = ? OR unidade_id IS NULL)";
                $params[] = $unidadeSessao;
            }

            $st = $this->db->prepare($sql);
            $st->execute($params);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'message' => 'Categoria nao encontrada'];
            }
            return ['success' => true, 'data' => $row];
        } catch (Throwable $e) {
            logError('Erro ao obter categoria financeira: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter categoria'];
        }
    }

    public function criar(array $dados): array
    {
        try {
            $nome = trim((string) ($dados['nome'] ?? ''));
            $tipo = strtolower(trim((string) ($dados['tipo'] ?? '')));
            if ($nome === '' || $tipo === '') {
                return ['success' => false, 'message' => 'Nome e tipo sao obrigatorios'];
            }
            if (!in_array($tipo, ['receita', 'despesa'], true)) {
                return ['success' => false, 'message' => 'Tipo invalido. Use receita ou despesa'];
            }

            $unidadeSessao = getUserUnidadeId();
            $unidadeId = $unidadeSessao ?? (!empty($dados['unidade_id']) ? (int) $dados['unidade_id'] : null);
            $codigo = trim((string) ($dados['codigo'] ?? ''));
            $descricao = trim((string) ($dados['descricao'] ?? ''));
            $parentId = !empty($dados['parent_id']) ? (int) $dados['parent_id'] : null;
            if ($parentId !== null && $parentId <= 0) {
                $parentId = null;
            }
            $ativo = isset($dados['ativo']) && (int) $dados['ativo'] === 0 ? 0 : 1;

            $st = $this->db->prepare("INSERT INTO categorias_financeiras
                (unidade_id, nome, tipo, codigo, descricao, parent_id, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $st->execute([
                $unidadeId,
                sanitizeInput($nome),
                $tipo,
                $codigo !== '' ? sanitizeInput($codigo) : null,
                $descricao !== '' ? sanitizeInput($descricao) : null,
                $parentId,
                $ativo
            ]);

            return [
                'success' => true,
                'message' => 'Categoria criada com sucesso',
                'data' => ['id' => (int) $this->db->lastInsertId()]
            ];
        } catch (Throwable $e) {
            logError('Erro ao criar categoria financeira: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao criar categoria'];
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
            if (isset($dados['tipo'])) {
                $tipo = strtolower(trim((string) $dados['tipo']));
                if (!in_array($tipo, ['receita', 'despesa'], true)) {
                    return ['success' => false, 'message' => 'Tipo invalido. Use receita ou despesa'];
                }
                $campos[] = 'tipo = ?';
                $valores[] = $tipo;
            }
            if (array_key_exists('codigo', $dados)) {
                $codigo = trim((string) $dados['codigo']);
                $campos[] = 'codigo = ?';
                $valores[] = $codigo !== '' ? sanitizeInput($codigo) : null;
            }
            if (array_key_exists('descricao', $dados)) {
                $descricao = trim((string) $dados['descricao']);
                $campos[] = 'descricao = ?';
                $valores[] = $descricao !== '' ? sanitizeInput($descricao) : null;
            }
            if (array_key_exists('parent_id', $dados)) {
                $parentId = !empty($dados['parent_id']) ? (int) $dados['parent_id'] : null;
                if ($parentId !== null && $parentId === $id) {
                    return ['success' => false, 'message' => 'Categoria pai invalida'];
                }
                $campos[] = 'parent_id = ?';
                $valores[] = $parentId;
            }
            if (isset($dados['ativo'])) {
                $campos[] = 'ativo = ?';
                $valores[] = ((int) $dados['ativo'] === 0) ? 0 : 1;
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $valores[] = $id;
            $sql = "UPDATE categorias_financeiras SET " . implode(', ', $campos) . ", updated_at = NOW() WHERE id = ?";
            $st = $this->db->prepare($sql);
            $st->execute($valores);

            return ['success' => true, 'message' => 'Categoria atualizada com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao atualizar categoria financeira: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar categoria'];
        }
    }

    public function excluir(int $id): array
    {
        try {
            $atual = $this->obter($id);
            if (empty($atual['success'])) {
                return $atual;
            }

            $st = $this->db->prepare("SELECT COUNT(*) FROM categorias_financeiras WHERE parent_id = ?");
            $st->execute([$id]);
            if ((int) $st->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Existem subcategorias vinculadas'];
            }

            $st = $this->db->prepare("SELECT COUNT(*) FROM lancamentos_financeiros WHERE categoria_id = ?");
            $st->execute([$id]);
            if ((int) $st->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Nao e possivel excluir categoria com lancamentos vinculados'];
            }

            $st = $this->db->prepare("DELETE FROM categorias_financeiras WHERE id = ?");
            $st->execute([$id]);

            return ['success' => true, 'message' => 'Categoria excluida com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao excluir categoria financeira: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao excluir categoria'];
        }
    }
}

$auth = financeiro_require_auth('categorias_financeiras');
$api = new CategoriasFinanceirasAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            financeiro_response($api->listar([
                'tipo' => $_GET['tipo'] ?? null,
                'ativo' => isset($_GET['ativo']) ? (int) $_GET['ativo'] : null,
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
                financeiro_audit($auth, 'create', 'categorias_financeiras', $entityId, null, $after, ['action' => 'criar']);
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
                financeiro_audit($auth, 'update', 'categorias_financeiras', $id, $before, $after, ['action' => 'atualizar']);
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
                financeiro_audit($auth, 'delete', 'categorias_financeiras', $id, $before, null, ['action' => 'excluir']);
            }
            financeiro_response($result);
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao invalida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Metodo nao permitido'], 405);
