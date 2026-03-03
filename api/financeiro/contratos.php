<?php
// API de Contratos - adaptada ao schema atual

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_bootstrap.php';

class ContratosAPI
{
    private $db;
    private $hasTurmas;
    private $hasUnidades;

    public function __construct()
    {
        $this->db = getDB();
        $this->hasTurmas = financeiro_table_exists($this->db, 'turmas');
        $this->hasUnidades = financeiro_table_exists($this->db, 'unidades');
    }

    private function dataValida(?string $date): bool
    {
        $date = trim((string) $date);
        if ($date === '') {
            return false;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt instanceof DateTime && $dt->format('Y-m-d') === $date;
    }

    private function camposRelacionadosSelect(): string
    {
        $parts = [
            'a.nome AS aluno_nome',
            'a.nome AS associado_nome',
            'a.foto AS aluno_foto',
            'a.cpf AS aluno_cpf',
        ];

        if ($this->hasTurmas) {
            $parts[] = 't.nome AS turma_nome';
            if ($this->hasUnidades) {
                $parts[] = 'u.nome AS unidade_nome';
            } else {
                $parts[] = "'' AS unidade_nome";
            }
        } else {
            $parts[] = "'' AS turma_nome";
            $parts[] = "'' AS unidade_nome";
        }

        return implode(",\n                    ", $parts);
    }

    private function joinsRelacionados(): string
    {
        $sql = ' LEFT JOIN alunos a ON c.aluno_id = a.id';
        if ($this->hasTurmas) {
            $sql .= ' LEFT JOIN turmas t ON a.turma_id = t.id';
            if ($this->hasUnidades) {
                $sql .= ' LEFT JOIN unidades u ON t.unidade_id = u.id';
            }
        }

        return $sql;
    }

    private function normalizarContrato(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'numero_contrato' => (string) ($row['numero_contrato'] ?? ''),
            'aluno_id' => (int) ($row['aluno_id'] ?? 0),
            'associado_id' => (int) ($row['aluno_id'] ?? 0),
            'responsavel_id' => isset($row['responsavel_id']) ? (int) $row['responsavel_id'] : null,
            'plano_id' => (int) ($row['plano_id'] ?? 0),
            'plano_financeiro_id' => (int) ($row['plano_id'] ?? 0),
            'plano_nome' => (string) ($row['plano_nome'] ?? ''),
            'data_inicio' => $row['data_inicio'] ?? null,
            'data_fim' => $row['data_fim'] ?? null,
            'valor_mensalidade' => (float) ($row['valor_mensalidade'] ?? 0),
            'valor_mensal' => (float) ($row['valor_mensalidade'] ?? 0),
            'valor_matricula' => 0.0,
            'desconto_especial' => (float) ($row['desconto_especial'] ?? 0),
            'status' => (string) ($row['status'] ?? ''),
            'observacoes' => (string) ($row['observacoes'] ?? ''),
            'aluno_nome' => (string) ($row['aluno_nome'] ?? ''),
            'associado_nome' => (string) ($row['associado_nome'] ?? ($row['aluno_nome'] ?? '')),
            'aluno_foto' => $row['aluno_foto'] ?? null,
            'aluno_cpf' => $row['aluno_cpf'] ?? null,
            'turma_nome' => (string) ($row['turma_nome'] ?? ''),
            'unidade_nome' => (string) ($row['unidade_nome'] ?? ''),
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    private function montarMensalidades(int $contratoId, string $dataInicio, float $valorMensalidade, int $quantidade = 12): void
    {
        if (!financeiro_table_exists($this->db, 'mensalidades')) {
            return;
        }

        $inicio = DateTime::createFromFormat('Y-m-d', $dataInicio);
        if (!$inicio) {
            $inicio = new DateTime('today');
        }
        $inicio->modify('first day of this month');

        $st = $this->db->prepare("
            INSERT INTO mensalidades
                (contrato_id, referencia, valor, data_vencimento, status)
            VALUES (?, ?, ?, ?, 'pendente')
        ");

        for ($i = 0; $i < $quantidade; $i++) {
            $base = clone $inicio;
            $base->modify('+' . $i . ' month');

            $referencia = $base->format('Y-m');
            $vencimento = clone $base;
            $vencimento->modify('+' . 4 . ' days');

            $st->execute([
                $contratoId,
                $referencia,
                $valorMensalidade,
                $vencimento->format('Y-m-d'),
            ]);
        }
    }

    public function listar(array $filtros = []): array
    {
        try {
            $where = ' WHERE 1=1';
            $params = [];

            if (!empty($filtros['status'])) {
                $where .= ' AND c.status = ?';
                $params[] = (string) $filtros['status'];
            }
            if (!empty($filtros['plano_id'])) {
                $where .= ' AND c.plano_id = ?';
                $params[] = (int) $filtros['plano_id'];
            }
            if (!empty($filtros['aluno_id'])) {
                $where .= ' AND c.aluno_id = ?';
                $params[] = (int) $filtros['aluno_id'];
            }
            if (!empty($filtros['data_inicio'])) {
                $where .= ' AND c.data_inicio >= ?';
                $params[] = (string) $filtros['data_inicio'];
            }
            if (!empty($filtros['data_fim'])) {
                $where .= ' AND c.data_inicio <= ?';
                $params[] = (string) $filtros['data_fim'];
            }

            $st = $this->db->prepare("
                SELECT
                    c.*,
                    pf.nome AS plano_nome,
                    {$this->camposRelacionadosSelect()}
                FROM contratos c
                LEFT JOIN planos_financeiros pf ON pf.id = c.plano_id
                {$this->joinsRelacionados()}
                {$where}
                ORDER BY c.created_at DESC, c.id DESC
            ");
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            $data = [];
            foreach ($rows as $row) {
                $data[] = $this->normalizarContrato($row);
            }

            return ['success' => true, 'data' => $data, 'total' => count($data)];
        } catch (Throwable $e) {
            logError('Erro ao listar contratos: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar contratos'];
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
                    c.*,
                    pf.nome AS plano_nome,
                    {$this->camposRelacionadosSelect()}
                FROM contratos c
                LEFT JOIN planos_financeiros pf ON pf.id = c.plano_id
                {$this->joinsRelacionados()}
                WHERE c.id = ?
                LIMIT 1
            ");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'message' => 'Contrato nao encontrado'];
            }

            return ['success' => true, 'data' => $this->normalizarContrato($row)];
        } catch (Throwable $e) {
            logError('Erro ao obter contrato: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter contrato'];
        }
    }

    public function criar(array $dados): array
    {
        try {
            $alunoId = (int) ($dados['aluno_id'] ?? 0);
            if ($alunoId <= 0) {
                return ['success' => false, 'message' => 'Associado e obrigatorio'];
            }

            $planoId = (int) ($dados['plano_id'] ?? ($dados['plano_financeiro_id'] ?? 0));
            if ($planoId <= 0) {
                return ['success' => false, 'message' => 'Plano financeiro e obrigatorio'];
            }

            $dataInicio = (string) ($dados['data_inicio'] ?? date('Y-m-d'));
            if (!$this->dataValida($dataInicio)) {
                return ['success' => false, 'message' => 'Data de inicio invalida'];
            }

            $dataFim = trim((string) ($dados['data_fim'] ?? ''));
            if ($dataFim !== '' && !$this->dataValida($dataFim)) {
                return ['success' => false, 'message' => 'Data de fim invalida'];
            }

            $st = $this->db->prepare('SELECT id FROM alunos WHERE id = ? LIMIT 1');
            $st->execute([$alunoId]);
            if (!$st->fetch(PDO::FETCH_ASSOC)) {
                return ['success' => false, 'message' => 'Associado nao encontrado'];
            }

            $st = $this->db->prepare('SELECT id, valor_mensalidade, ativo FROM planos_financeiros WHERE id = ? LIMIT 1');
            $st->execute([$planoId]);
            $plano = $st->fetch(PDO::FETCH_ASSOC);
            if (!$plano) {
                return ['success' => false, 'message' => 'Plano financeiro nao encontrado'];
            }
            if ((int) ($plano['ativo'] ?? 0) !== 1) {
                return ['success' => false, 'message' => 'Plano financeiro inativo'];
            }

            $st = $this->db->prepare("SELECT id FROM contratos WHERE aluno_id = ? AND status = 'ativo' LIMIT 1");
            $st->execute([$alunoId]);
            if ($st->fetch(PDO::FETCH_ASSOC)) {
                return ['success' => false, 'message' => 'Associado ja possui contrato ativo'];
            }

            $valorMensalidade = isset($dados['valor_mensalidade'])
                ? (float) $dados['valor_mensalidade']
                : (isset($dados['valor_mensal']) ? (float) $dados['valor_mensal'] : (float) $plano['valor_mensalidade']);
            if ($valorMensalidade <= 0) {
                return ['success' => false, 'message' => 'Valor mensalidade invalido'];
            }

            $descontoEspecial = isset($dados['desconto_especial']) ? (float) $dados['desconto_especial'] : 0.0;
            if ($descontoEspecial < 0 || $descontoEspecial > 100) {
                return ['success' => false, 'message' => 'Desconto especial deve estar entre 0 e 100'];
            }

            $status = strtolower(trim((string) ($dados['status'] ?? 'ativo')));
            if (!in_array($status, ['ativo', 'suspenso', 'cancelado', 'finalizado'], true)) {
                return ['success' => false, 'message' => 'Status invalido'];
            }

            $numeroContrato = trim((string) ($dados['numero_contrato'] ?? ''));
            if ($numeroContrato === '') {
                $numeroContrato = generateContractNumber();
            }

            $responsavelId = (int) ($dados['responsavel_id'] ?? 1);
            if ($responsavelId <= 0) {
                $responsavelId = 1;
            }

            $observacoes = trim((string) ($dados['observacoes'] ?? ''));

            $this->db->beginTransaction();

            $st = $this->db->prepare("
                INSERT INTO contratos
                    (aluno_id, responsavel_id, plano_id, numero_contrato, data_inicio, data_fim, valor_mensalidade, desconto_especial, observacoes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $st->execute([
                $alunoId,
                $responsavelId,
                $planoId,
                $numeroContrato,
                $dataInicio,
                $dataFim !== '' ? $dataFim : null,
                $valorMensalidade,
                $descontoEspecial,
                $observacoes !== '' ? sanitizeInput($observacoes) : null,
                $status,
            ]);

            $contratoId = (int) $this->db->lastInsertId();
            $this->montarMensalidades($contratoId, $dataInicio, $valorMensalidade);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Contrato criado com sucesso',
                'data' => ['id' => $contratoId, 'numero_contrato' => $numeroContrato],
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logError('Erro ao criar contrato: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao criar contrato'];
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

            if (array_key_exists('plano_id', $dados) || array_key_exists('plano_financeiro_id', $dados)) {
                $planoId = (int) ($dados['plano_id'] ?? $dados['plano_financeiro_id']);
                if ($planoId <= 0) {
                    return ['success' => false, 'message' => 'Plano financeiro invalido'];
                }
                $st = $this->db->prepare('SELECT id FROM planos_financeiros WHERE id = ? LIMIT 1');
                $st->execute([$planoId]);
                if (!$st->fetch(PDO::FETCH_ASSOC)) {
                    return ['success' => false, 'message' => 'Plano financeiro nao encontrado'];
                }
                $campos[] = 'plano_id = ?';
                $valores[] = $planoId;
            }

            if (array_key_exists('data_inicio', $dados)) {
                $dataInicio = (string) $dados['data_inicio'];
                if (!$this->dataValida($dataInicio)) {
                    return ['success' => false, 'message' => 'Data de inicio invalida'];
                }
                $campos[] = 'data_inicio = ?';
                $valores[] = $dataInicio;
            }

            if (array_key_exists('data_fim', $dados)) {
                $dataFim = trim((string) $dados['data_fim']);
                if ($dataFim !== '' && !$this->dataValida($dataFim)) {
                    return ['success' => false, 'message' => 'Data de fim invalida'];
                }
                $campos[] = 'data_fim = ?';
                $valores[] = $dataFim !== '' ? $dataFim : null;
            }

            if (array_key_exists('valor_mensalidade', $dados) || array_key_exists('valor_mensal', $dados)) {
                $valorMensalidade = array_key_exists('valor_mensalidade', $dados)
                    ? (float) $dados['valor_mensalidade']
                    : (float) $dados['valor_mensal'];
                if ($valorMensalidade <= 0) {
                    return ['success' => false, 'message' => 'Valor mensalidade invalido'];
                }
                $campos[] = 'valor_mensalidade = ?';
                $valores[] = $valorMensalidade;
            }

            if (array_key_exists('desconto_especial', $dados)) {
                $descontoEspecial = (float) $dados['desconto_especial'];
                if ($descontoEspecial < 0 || $descontoEspecial > 100) {
                    return ['success' => false, 'message' => 'Desconto especial deve estar entre 0 e 100'];
                }
                $campos[] = 'desconto_especial = ?';
                $valores[] = $descontoEspecial;
            }

            if (array_key_exists('status', $dados)) {
                $status = strtolower(trim((string) $dados['status']));
                if (!in_array($status, ['ativo', 'suspenso', 'cancelado', 'finalizado'], true)) {
                    return ['success' => false, 'message' => 'Status invalido'];
                }
                $campos[] = 'status = ?';
                $valores[] = $status;
            }

            if (array_key_exists('observacoes', $dados)) {
                $obs = trim((string) $dados['observacoes']);
                $campos[] = 'observacoes = ?';
                $valores[] = $obs !== '' ? sanitizeInput($obs) : null;
            }

            if (array_key_exists('responsavel_id', $dados)) {
                $responsavelId = (int) $dados['responsavel_id'];
                if ($responsavelId <= 0) {
                    $responsavelId = 1;
                }
                $campos[] = 'responsavel_id = ?';
                $valores[] = $responsavelId;
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $valores[] = $id;
            $st = $this->db->prepare('UPDATE contratos SET ' . implode(', ', $campos) . ', updated_at = NOW() WHERE id = ?');
            $st->execute($valores);

            return ['success' => true, 'message' => 'Contrato atualizado com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao atualizar contrato: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar contrato'];
        }
    }

    public function cancelar(int $id, string $motivo): array
    {
        try {
            if ($id <= 0) {
                return ['success' => false, 'message' => 'ID obrigatorio'];
            }
            $motivo = trim($motivo);
            if ($motivo === '') {
                return ['success' => false, 'message' => 'Motivo do cancelamento e obrigatorio'];
            }

            $st = $this->db->prepare("
                UPDATE contratos
                SET status = 'cancelado',
                    observacoes = CONCAT(COALESCE(observacoes, ''), '\nCancelado em: ', NOW(), '\nMotivo: ', ?),
                    updated_at = NOW()
                WHERE id = ? AND status <> 'cancelado'
            ");
            $st->execute([sanitizeInput($motivo), $id]);

            if ($st->rowCount() === 0) {
                return ['success' => false, 'message' => 'Contrato nao encontrado ou ja cancelado'];
            }

            return ['success' => true, 'message' => 'Contrato cancelado com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao cancelar contrato: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao cancelar contrato'];
        }
    }

    public function renovar(int $id, string $novaDataFim): array
    {
        try {
            if ($id <= 0) {
                return ['success' => false, 'message' => 'ID obrigatorio'];
            }
            if (!$this->dataValida($novaDataFim)) {
                return ['success' => false, 'message' => 'Nova data de fim invalida'];
            }

            $st = $this->db->prepare("
                UPDATE contratos
                SET data_fim = ?, status = 'ativo', updated_at = NOW()
                WHERE id = ?
            ");
            $st->execute([$novaDataFim, $id]);
            if ($st->rowCount() === 0) {
                return ['success' => false, 'message' => 'Contrato nao encontrado'];
            }

            return ['success' => true, 'message' => 'Contrato renovado com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao renovar contrato: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao renovar contrato'];
        }
    }

    public function obterEstatisticas(?string $dataInicio = null, ?string $dataFim = null): array
    {
        try {
            $where = ' WHERE 1=1';
            $params = [];
            if ($dataInicio) {
                $where .= ' AND c.created_at >= ?';
                $params[] = $dataInicio;
            }
            if ($dataFim) {
                $where .= ' AND c.created_at <= ?';
                $params[] = $dataFim;
            }

            $st = $this->db->prepare("
                SELECT
                    COUNT(*) AS total_contratos,
                    COUNT(CASE WHEN c.status = 'ativo' THEN 1 END) AS contratos_ativos,
                    COUNT(CASE WHEN c.status = 'finalizado' THEN 1 END) AS contratos_finalizados,
                    COUNT(CASE WHEN c.status = 'cancelado' THEN 1 END) AS contratos_cancelados,
                    SUM(CASE WHEN c.status = 'ativo' THEN c.valor_mensalidade ELSE 0 END) AS receita_mensal_total,
                    AVG(c.valor_mensalidade) AS valor_medio_mensalidade
                FROM contratos c
                {$where}
            ");
            $st->execute($params);
            return ['success' => true, 'data' => $st->fetch(PDO::FETCH_ASSOC) ?: []];
        } catch (Throwable $e) {
            logError('Erro ao obter estatisticas dos contratos: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter estatisticas dos contratos'];
        }
    }

    public function obterProximosVencimento(int $dias = 30): array
    {
        try {
            if ($dias < 0) {
                $dias = 0;
            }

            $st = $this->db->prepare("
                SELECT
                    c.*,
                    pf.nome AS plano_nome,
                    {$this->camposRelacionadosSelect()},
                    DATEDIFF(c.data_fim, CURDATE()) AS dias_para_vencimento
                FROM contratos c
                LEFT JOIN planos_financeiros pf ON pf.id = c.plano_id
                {$this->joinsRelacionados()}
                WHERE c.status = 'ativo'
                    AND c.data_fim IS NOT NULL
                    AND DATEDIFF(c.data_fim, CURDATE()) BETWEEN 0 AND ?
                ORDER BY c.data_fim ASC
            ");
            $st->execute([$dias]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $item = $this->normalizarContrato($row);
                $item['dias_para_vencimento'] = (int) ($row['dias_para_vencimento'] ?? 0);
                $data[] = $item;
            }

            return ['success' => true, 'data' => $data];
        } catch (Throwable $e) {
            logError('Erro ao obter contratos proximos ao vencimento: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter contratos proximos ao vencimento'];
        }
    }

    public function excluir(int $id): array
    {
        try {
            $exist = $this->obter($id);
            if (empty($exist['success'])) {
                return $exist;
            }

            $st = $this->db->prepare('SELECT COUNT(*) FROM mensalidades WHERE contrato_id = ?');
            $st->execute([$id]);
            if ((int) $st->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Nao e possivel excluir contrato com mensalidades vinculadas'];
            }

            $st = $this->db->prepare('DELETE FROM contratos WHERE id = ?');
            $st->execute([$id]);
            return ['success' => true, 'message' => 'Contrato excluido com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao excluir contrato: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao excluir contrato'];
        }
    }
}

$auth = financeiro_require_auth('contratos');
financeiro_require_tables(['contratos', 'alunos', 'planos_financeiros', 'mensalidades'], 'contratos');
$api = new ContratosAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = (string) ($_GET['action'] ?? 'listar');
    switch ($action) {
        case 'listar':
            $filtros = [];
            if (isset($_GET['status'])) {
                $filtros['status'] = (string) $_GET['status'];
            }
            if (isset($_GET['plano_id'])) {
                $filtros['plano_id'] = (int) $_GET['plano_id'];
            }
            if (isset($_GET['aluno_id'])) {
                $filtros['aluno_id'] = (int) $_GET['aluno_id'];
            }
            if (isset($_GET['data_inicio'])) {
                $filtros['data_inicio'] = (string) $_GET['data_inicio'];
            }
            if (isset($_GET['data_fim'])) {
                $filtros['data_fim'] = (string) $_GET['data_fim'];
            }
            financeiro_response($api->listar($filtros));
            break;
        case 'obter':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            financeiro_response($api->obter($id));
            break;
        case 'estatisticas':
            $dataInicio = isset($_GET['data_inicio']) ? (string) $_GET['data_inicio'] : null;
            $dataFim = isset($_GET['data_fim']) ? (string) $_GET['data_fim'] : null;
            financeiro_response($api->obterEstatisticas($dataInicio, $dataFim));
            break;
        case 'proximos_vencimento':
            $dias = (int) ($_GET['dias'] ?? 30);
            financeiro_response($api->obterProximosVencimento($dias));
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
                financeiro_audit($auth, 'create', 'contratos', $entityId, null, $after, ['action' => 'criar']);
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
                financeiro_audit($auth, 'update', 'contratos', $id, $before, $after, ['action' => 'atualizar']);
            }
            financeiro_response($result);
            break;
        case 'cancelar':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            $motivo = trim((string) ($input['motivo'] ?? ''));
            $before = $api->obter($id)['data'] ?? null;
            $result = $api->cancelar($id, $motivo);
            if (!empty($result['success'])) {
                $after = $api->obter($id)['data'] ?? null;
                financeiro_audit($auth, 'cancel', 'contratos', $id, $before, $after, ['action' => 'cancelar', 'motivo' => $motivo]);
            }
            financeiro_response($result);
            break;
        case 'renovar':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            $novaDataFim = (string) ($input['nova_data_fim'] ?? '');
            $before = $api->obter($id)['data'] ?? null;
            $result = $api->renovar($id, $novaDataFim);
            if (!empty($result['success'])) {
                $after = $api->obter($id)['data'] ?? null;
                financeiro_audit($auth, 'renew', 'contratos', $id, $before, $after, ['action' => 'renovar', 'nova_data_fim' => $novaDataFim]);
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
                financeiro_audit($auth, 'delete', 'contratos', $id, $before, null, ['action' => 'excluir']);
            }
            financeiro_response($result);
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao nao encontrada'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Metodo nao permitido'], 405);
