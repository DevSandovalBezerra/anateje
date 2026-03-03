<?php
// ANATEJE - API de OrÃ§amentos Financeiros

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/unidade_helper.php';

require_once __DIR__ . '/_bootstrap.php';
class OrcamentosAPI
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
                SELECT o.*, u.nome AS unidade_nome, cc.nome AS centro_nome, cf.nome AS categoria_nome
                FROM orcamentos o
                INNER JOIN centros_custos cc ON o.centro_custo_id = cc.id
                INNER JOIN categorias_financeiras cf ON o.categoria_id = cf.id
                LEFT JOIN unidades u ON o.unidade_id = u.id
                WHERE 1=1
            ";

            $params = [];

            if ($unidadeSessao !== null) {
                $sql .= " AND o.unidade_id = ?";
                $params[] = $unidadeSessao;
            } elseif (!empty($filtros['unidade_id'])) {
                $sql .= " AND o.unidade_id = ?";
                $params[] = (int)$filtros['unidade_id'];
            }

            if (!empty($filtros['ano'])) {
                $sql .= " AND o.ano = ?";
                $params[] = (int)$filtros['ano'];
            }

            if (!empty($filtros['mes'])) {
                $sql .= " AND o.mes = ?";
                $params[] = (int)$filtros['mes'];
            }

            if (!empty($filtros['centro_custo_id'])) {
                $sql .= " AND o.centro_custo_id = ?";
                $params[] = (int)$filtros['centro_custo_id'];
            }

            if (!empty($filtros['categoria_id'])) {
                $sql .= " AND o.categoria_id = ?";
                $params[] = (int)$filtros['categoria_id'];
            }

            $sql .= " ORDER BY o.ano DESC, o.mes DESC, cc.nome ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $orcamentos];
        } catch (Exception $e) {
            logError("Erro ao listar orÃ§amentos: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar orÃ§amentos'];
        }
    }

    public function obter($id)
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $sql = "SELECT * FROM orcamentos WHERE id = ?";
            $params = [$id];

            if ($unidadeSessao !== null) {
                $sql .= " AND unidade_id = ?";
                $params[] = $unidadeSessao;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $orcamento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$orcamento) {
                return ['success' => false, 'message' => 'OrÃ§amento nÃ£o encontrado'];
            }

            return ['success' => true, 'data' => $orcamento];
        } catch (Exception $e) {
            logError("Erro ao obter orÃ§amento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter orÃ§amento'];
        }
    }

    public function criar($dados)
    {
        try {
            foreach (['ano', 'mes', 'centro_custo_id', 'categoria_id', 'valor_orcado'] as $campo) {
                if (!isset($dados[$campo])) {
                    return ['success' => false, 'message' => "Campo {$campo} Ã© obrigatÃ³rio"];
                }
            }

            $ano = (int)$dados['ano'];
            $mes = (int)$dados['mes'];
            if ($mes < 1 || $mes > 12) {
                return ['success' => false, 'message' => 'MÃªs invÃ¡lido'];
            }

            $unidadeSessao = getUserUnidadeId();
            $centro = $this->buscarCentro((int)$dados['centro_custo_id'], $unidadeSessao);
            if (!$centro) {
                return ['success' => false, 'message' => 'Centro de custo invÃ¡lido para esta unidade'];
            }

            $categoria = $this->buscarCategoria((int)$dados['categoria_id'], $unidadeSessao);
            if (!$categoria) {
                return ['success' => false, 'message' => 'Categoria invÃ¡lida para esta unidade'];
            }

            $stmt = $this->db->prepare("
                INSERT INTO orcamentos
                (unidade_id, ano, mes, centro_custo_id, categoria_id, valor_orcado, valor_revisado,
                 observacoes, created_by, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $centro['unidade_id'],
                $ano,
                $mes,
                $centro['id'],
                $categoria['id'],
                (float)$dados['valor_orcado'],
                isset($dados['valor_revisado']) ? (float)$dados['valor_revisado'] : null,
                $dados['observacoes'] ?? null,
                $_SESSION['user_id'] ?? null,
                $_SESSION['user_id'] ?? null
            ]);

            return ['success' => true, 'message' => 'OrÃ§amento cadastrado', 'data' => ['id' => $this->db->lastInsertId()]];
        } catch (Exception $e) {
            if ($e->getCode() === '23000') {
                return ['success' => false, 'message' => 'JÃ¡ existe orÃ§amento para esse perÃ­odo/centro/categoria'];
            }
            logError("Erro ao criar orÃ§amento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao criar orÃ§amento'];
        }
    }

    public function atualizar($id, $dados)
    {
        try {
            $orcamento = $this->obter($id);
            if (!$orcamento['success']) {
                return $orcamento;
            }

            $campos = [];
            $valores = [];

            foreach (['valor_orcado', 'valor_revisado', 'observacoes'] as $campo) {
                if (isset($dados[$campo])) {
                    $campos[] = "$campo = ?";
                    $valores[] = $dados[$campo];
                }
            }

            if (isset($dados['centro_custo_id'])) {
                $centro = $this->buscarCentro((int)$dados['centro_custo_id'], getUserUnidadeId());
                if (!$centro) {
                    return ['success' => false, 'message' => 'Centro de custo invÃ¡lido'];
                }
                $campos[] = "centro_custo_id = ?";
                $valores[] = $centro['id'];
            }

            if (isset($dados['categoria_id'])) {
                $categoria = $this->buscarCategoria((int)$dados['categoria_id'], getUserUnidadeId());
                if (!$categoria) {
                    return ['success' => false, 'message' => 'Categoria invÃ¡lida'];
                }
                $campos[] = "categoria_id = ?";
                $valores[] = $categoria['id'];
            }

            if (isset($dados['ano'])) {
                $campos[] = "ano = ?";
                $valores[] = (int)$dados['ano'];
            }

            if (isset($dados['mes'])) {
                $mes = (int)$dados['mes'];
                if ($mes < 1 || $mes > 12) {
                    return ['success' => false, 'message' => 'MÃªs invÃ¡lido'];
                }
                $campos[] = "mes = ?";
                $valores[] = $mes;
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $campos[] = 'updated_by = ?';
            $valores[] = $_SESSION['user_id'] ?? null;
            $campos[] = 'updated_at = NOW()';
            $valores[] = $id;

            $sql = "UPDATE orcamentos SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($valores);

            return ['success' => true, 'message' => 'OrÃ§amento atualizado'];
        } catch (Exception $e) {
            logError("Erro ao atualizar orÃ§amento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar orÃ§amento'];
        }
    }

    public function excluir($id)
    {
        try {
            $orcamento = $this->obter($id);
            if (!$orcamento['success']) {
                return $orcamento;
            }

            $stmt = $this->db->prepare("DELETE FROM orcamentos WHERE id = ?");
            $stmt->execute([$id]);

            return ['success' => true, 'message' => 'OrÃ§amento excluÃ­do'];
        } catch (Exception $e) {
            logError("Erro ao excluir orÃ§amento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao excluir orÃ§amento'];
        }
    }

    private function buscarCentro($id, $unidadeSessao)
    {
        $sql = "SELECT * FROM centros_custos WHERE id = ?";
        $params = [$id];
        if ($unidadeSessao !== null) {
            $sql .= " AND unidade_id = ?";
            $params[] = $unidadeSessao;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function buscarCategoria($id, $unidadeSessao)
    {
        $sql = "SELECT * FROM categorias_financeiras WHERE id = ?";
        $params = [$id];
        if ($unidadeSessao !== null) {
            $sql .= " AND (unidade_id = ? OR unidade_id IS NULL)";
            $params[] = $unidadeSessao;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$auth = financeiro_require_auth('orcamentos');

$api = new OrcamentosAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            $filtros = [
                'ano' => $_GET['ano'] ?? null,
                'mes' => $_GET['mes'] ?? null,
                'centro_custo_id' => $_GET['centro_custo_id'] ?? null,
                'categoria_id' => $_GET['categoria_id'] ?? null,
                'unidade_id' => $_GET['unidade_id'] ?? null,
            ];
            financeiro_response($api->listar($filtros));
            break;
        case 'obter':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatÃ³rio'], 400);
            }
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
        case 'atualizar':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatÃ³rio'], 400);
            }
            financeiro_response($api->atualizar($id, $_POST));
            break;
        case 'excluir':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatÃ³rio'], 400);
            }
            financeiro_response($api->excluir($id));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'AÃ§Ã£o invÃ¡lida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'MÃ©todo nÃ£o permitido'], 405);


