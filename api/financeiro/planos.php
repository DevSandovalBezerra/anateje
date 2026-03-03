<?php
// API de Planos Financeiros - adaptada ao schema atual

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_bootstrap.php';

class PlanosFinanceirosAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    private function normalizarPlano(array $row): array
    {
        $valorMensalidade = (float) ($row['valor_mensalidade'] ?? 0);
        $descontoPadrao = (float) ($row['desconto_padrao'] ?? 0);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'nome' => (string) ($row['nome'] ?? ''),
            'descricao' => (string) ($row['descricao'] ?? ''),
            'valor_mensalidade' => $valorMensalidade,
            'valor_mensal' => $valorMensalidade,
            'valor_matricula' => 0.0,
            'periodicidade' => (string) ($row['periodicidade'] ?? 'mensal'),
            'desconto_padrao' => $descontoPadrao,
            'desconto_matricula' => 0.0,
            'desconto_mensalidade' => $descontoPadrao,
            'ativo' => (int) ($row['ativo'] ?? 1),
            'total_contratos_ativos' => (int) ($row['total_contratos_ativos'] ?? 0),
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    private function periodicidadeValida(string $periodicidade): bool
    {
        return in_array($periodicidade, ['mensal', 'bimestral', 'trimestral', 'semestral', 'anual'], true);
    }

    public function listar(): array
    {
        try {
            $st = $this->db->query("
                SELECT
                    pf.*,
                    COUNT(CASE WHEN c.status = 'ativo' THEN 1 END) AS total_contratos_ativos
                FROM planos_financeiros pf
                LEFT JOIN contratos c ON c.plano_id = pf.id
                GROUP BY pf.id
                ORDER BY pf.nome ASC
            ");
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            $data = [];
            foreach ($rows as $row) {
                $data[] = $this->normalizarPlano($row);
            }

            return ['success' => true, 'data' => $data, 'total' => count($data)];
        } catch (Throwable $e) {
            logError('Erro ao listar planos financeiros: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar planos financeiros'];
        }
    }

    public function obter(int $id): array
    {
        try {
            if ($id <= 0) {
                return ['success' => false, 'message' => 'ID obrigatorio'];
            }

            $st = $this->db->prepare("
                SELECT
                    pf.*,
                    COUNT(CASE WHEN c.status = 'ativo' THEN 1 END) AS total_contratos_ativos
                FROM planos_financeiros pf
                LEFT JOIN contratos c ON c.plano_id = pf.id
                WHERE pf.id = ?
                GROUP BY pf.id
                LIMIT 1
            ");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'message' => 'Plano financeiro nao encontrado'];
            }

            return ['success' => true, 'data' => $this->normalizarPlano($row)];
        } catch (Throwable $e) {
            logError('Erro ao obter plano financeiro: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter plano financeiro'];
        }
    }

    public function criar(array $dados): array
    {
        try {
            $nome = trim((string) ($dados['nome'] ?? ''));
            if ($nome === '') {
                return ['success' => false, 'message' => 'Nome e obrigatorio'];
            }

            $valorMensalidade = isset($dados['valor_mensalidade']) ? (float) $dados['valor_mensalidade'] : (float) ($dados['valor_mensal'] ?? 0);
            if ($valorMensalidade <= 0) {
                return ['success' => false, 'message' => 'Valor mensalidade deve ser maior que zero'];
            }

            $periodicidade = strtolower(trim((string) ($dados['periodicidade'] ?? 'mensal')));
            if (!$this->periodicidadeValida($periodicidade)) {
                return ['success' => false, 'message' => 'Periodicidade invalida'];
            }

            $descontoPadrao = isset($dados['desconto_padrao']) ? (float) $dados['desconto_padrao'] : (float) ($dados['desconto_mensalidade'] ?? 0);
            if ($descontoPadrao < 0 || $descontoPadrao > 100) {
                return ['success' => false, 'message' => 'Desconto padrao deve estar entre 0 e 100'];
            }

            $st = $this->db->prepare('SELECT id FROM planos_financeiros WHERE nome = ? LIMIT 1');
            $st->execute([$nome]);
            if ($st->fetch(PDO::FETCH_ASSOC)) {
                return ['success' => false, 'message' => 'Ja existe um plano com este nome'];
            }

            $st = $this->db->prepare("
                INSERT INTO planos_financeiros
                    (nome, descricao, valor_mensalidade, periodicidade, desconto_padrao, ativo)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $st->execute([
                sanitizeInput($nome),
                trim((string) ($dados['descricao'] ?? '')) !== '' ? sanitizeInput((string) $dados['descricao']) : null,
                $valorMensalidade,
                $periodicidade,
                $descontoPadrao,
                isset($dados['ativo']) && (int) $dados['ativo'] === 0 ? 0 : 1,
            ]);

            return [
                'success' => true,
                'message' => 'Plano financeiro criado com sucesso',
                'data' => ['id' => (int) $this->db->lastInsertId()]
            ];
        } catch (Throwable $e) {
            logError('Erro ao criar plano financeiro: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao criar plano financeiro'];
        }
    }

    public function atualizar(int $id, array $dados): array
    {
        try {
            $exist = $this->obter($id);
            if (empty($exist['success'])) {
                return $exist;
            }

            $campos = [];
            $valores = [];

            if (array_key_exists('nome', $dados)) {
                $nome = trim((string) $dados['nome']);
                if ($nome === '') {
                    return ['success' => false, 'message' => 'Nome e obrigatorio'];
                }
                $st = $this->db->prepare('SELECT id FROM planos_financeiros WHERE nome = ? AND id <> ? LIMIT 1');
                $st->execute([$nome, $id]);
                if ($st->fetch(PDO::FETCH_ASSOC)) {
                    return ['success' => false, 'message' => 'Ja existe um plano com este nome'];
                }
                $campos[] = 'nome = ?';
                $valores[] = sanitizeInput($nome);
            }

            if (array_key_exists('descricao', $dados)) {
                $desc = trim((string) $dados['descricao']);
                $campos[] = 'descricao = ?';
                $valores[] = $desc !== '' ? sanitizeInput($desc) : null;
            }

            if (array_key_exists('valor_mensalidade', $dados) || array_key_exists('valor_mensal', $dados)) {
                $valorMensalidade = array_key_exists('valor_mensalidade', $dados)
                    ? (float) $dados['valor_mensalidade']
                    : (float) $dados['valor_mensal'];
                if ($valorMensalidade <= 0) {
                    return ['success' => false, 'message' => 'Valor mensalidade deve ser maior que zero'];
                }
                $campos[] = 'valor_mensalidade = ?';
                $valores[] = $valorMensalidade;
            }

            if (array_key_exists('periodicidade', $dados)) {
                $periodicidade = strtolower(trim((string) $dados['periodicidade']));
                if (!$this->periodicidadeValida($periodicidade)) {
                    return ['success' => false, 'message' => 'Periodicidade invalida'];
                }
                $campos[] = 'periodicidade = ?';
                $valores[] = $periodicidade;
            }

            if (array_key_exists('desconto_padrao', $dados) || array_key_exists('desconto_mensalidade', $dados)) {
                $descontoPadrao = array_key_exists('desconto_padrao', $dados)
                    ? (float) $dados['desconto_padrao']
                    : (float) $dados['desconto_mensalidade'];
                if ($descontoPadrao < 0 || $descontoPadrao > 100) {
                    return ['success' => false, 'message' => 'Desconto padrao deve estar entre 0 e 100'];
                }
                $campos[] = 'desconto_padrao = ?';
                $valores[] = $descontoPadrao;
            }

            if (array_key_exists('ativo', $dados)) {
                $campos[] = 'ativo = ?';
                $valores[] = ((int) $dados['ativo'] === 0) ? 0 : 1;
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $valores[] = $id;
            $st = $this->db->prepare('UPDATE planos_financeiros SET ' . implode(', ', $campos) . ', updated_at = NOW() WHERE id = ?');
            $st->execute($valores);

            return ['success' => true, 'message' => 'Plano financeiro atualizado com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao atualizar plano financeiro: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar plano financeiro'];
        }
    }

    public function excluir(int $id): array
    {
        try {
            $exist = $this->obter($id);
            if (empty($exist['success'])) {
                return $exist;
            }

            $st = $this->db->prepare("SELECT COUNT(*) FROM contratos WHERE plano_id = ? AND status = 'ativo'");
            $st->execute([$id]);
            if ((int) $st->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Nao e possivel excluir plano com contratos ativos'];
            }

            $st = $this->db->prepare('DELETE FROM planos_financeiros WHERE id = ?');
            $st->execute([$id]);

            return ['success' => true, 'message' => 'Plano financeiro excluido com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao excluir plano financeiro: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao excluir plano financeiro'];
        }
    }

    public function estatisticas(): array
    {
        try {
            $st = $this->db->query("
                SELECT
                    COUNT(*) AS total_planos,
                    COUNT(CASE WHEN ativo = 1 THEN 1 END) AS planos_ativos,
                    COUNT(CASE WHEN ativo = 0 THEN 1 END) AS planos_inativos,
                    AVG(valor_mensalidade) AS valor_medio_mensalidade,
                    SUM(CASE WHEN desconto_padrao > 0 THEN 1 ELSE 0 END) AS planos_com_desconto
                FROM planos_financeiros
            ");
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
            return ['success' => true, 'data' => $row];
        } catch (Throwable $e) {
            logError('Erro ao obter estatisticas de planos: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter estatisticas de planos'];
        }
    }

    public function simularDesconto(int $planoId, float $descontoExtra = 0): array
    {
        try {
            $plano = $this->obter($planoId);
            if (empty($plano['success'])) {
                return $plano;
            }

            $base = $plano['data'];
            $valorMensal = (float) $base['valor_mensalidade'];
            $descontoBase = (float) $base['desconto_padrao'];
            if ($descontoExtra < 0 || $descontoExtra > 100) {
                return ['success' => false, 'message' => 'Desconto extra invalido'];
            }

            $descontoTotal = min(100.0, $descontoBase + $descontoExtra);
            $valorDesconto = ($valorMensal * $descontoTotal) / 100;

            return [
                'success' => true,
                'data' => [
                    'plano_id' => $planoId,
                    'valor_mensal_original' => $valorMensal,
                    'desconto_padrao_percentual' => $descontoBase,
                    'desconto_extra_percentual' => $descontoExtra,
                    'desconto_total_percentual' => $descontoTotal,
                    'valor_desconto' => round($valorDesconto, 2),
                    'valor_mensal_com_desconto' => round($valorMensal - $valorDesconto, 2),
                ]
            ];
        } catch (Throwable $e) {
            logError('Erro ao simular desconto do plano: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao simular desconto'];
        }
    }

    public function clonar(int $planoId, string $novoNome = ''): array
    {
        try {
            $orig = $this->obter($planoId);
            if (empty($orig['success'])) {
                return $orig;
            }

            $plano = $orig['data'];
            $nome = trim($novoNome);
            if ($nome === '') {
                $nome = $plano['nome'] . ' (copia)';
            }

            $st = $this->db->prepare('SELECT id FROM planos_financeiros WHERE nome = ? LIMIT 1');
            $st->execute([$nome]);
            if ($st->fetch(PDO::FETCH_ASSOC)) {
                return ['success' => false, 'message' => 'Ja existe um plano com o nome informado'];
            }

            $st = $this->db->prepare("
                INSERT INTO planos_financeiros
                    (nome, descricao, valor_mensalidade, periodicidade, desconto_padrao, ativo)
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $st->execute([
                sanitizeInput($nome),
                trim((string) $plano['descricao']) !== '' ? sanitizeInput((string) $plano['descricao']) : null,
                (float) $plano['valor_mensalidade'],
                $plano['periodicidade'],
                (float) $plano['desconto_padrao'],
            ]);

            return [
                'success' => true,
                'message' => 'Plano clonado com sucesso',
                'data' => ['id' => (int) $this->db->lastInsertId()]
            ];
        } catch (Throwable $e) {
            logError('Erro ao clonar plano financeiro: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao clonar plano financeiro'];
        }
    }
}

$auth = financeiro_require_auth('planos');
financeiro_require_tables(['planos_financeiros', 'contratos'], 'planos');
$api = new PlanosFinanceirosAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = (string) ($_GET['action'] ?? 'listar');
    switch ($action) {
        case 'listar':
            financeiro_response($api->listar());
            break;
        case 'obter':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            financeiro_response($api->obter($id));
            break;
        case 'estatisticas':
            financeiro_response($api->estatisticas());
            break;
        case 'simular_desconto':
            $planoId = (int) ($_GET['plano_id'] ?? 0);
            if ($planoId <= 0) {
                financeiro_response(['success' => false, 'message' => 'Plano ID obrigatorio'], 400);
            }
            $descontoExtra = (float) ($_GET['desconto_extra'] ?? 0);
            financeiro_response($api->simularDesconto($planoId, $descontoExtra));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao nao encontrada'], 404);
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
                financeiro_audit($auth, 'create', 'planos_financeiros', $entityId, null, $after, ['action' => 'criar']);
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
                financeiro_audit($auth, 'update', 'planos_financeiros', $id, $before, $after, ['action' => 'atualizar']);
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
                financeiro_audit($auth, 'delete', 'planos_financeiros', $id, $before, null, ['action' => 'excluir']);
            }
            financeiro_response($result);
            break;
        case 'clonar':
            $planoId = (int) ($input['plano_id'] ?? 0);
            if ($planoId <= 0) {
                financeiro_response(['success' => false, 'message' => 'Plano ID obrigatorio'], 400);
            }
            $result = $api->clonar($planoId, (string) ($input['novo_nome'] ?? ''));
            if (!empty($result['success'])) {
                $entityId = financeiro_extract_entity_id($result);
                $after = $entityId ? ($api->obter($entityId)['data'] ?? null) : null;
                financeiro_audit($auth, 'clone', 'planos_financeiros', $entityId, null, $after, ['action' => 'clonar', 'source_plan_id' => $planoId]);
            }
            financeiro_response($result);
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao nao encontrada'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Metodo nao permitido'], 405);
