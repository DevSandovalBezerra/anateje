<?php
// API de Pagamentos - adaptada ao schema atual

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_bootstrap.php';

class PagamentosAPI
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

    private function formaPagamentoValida(string $forma): bool
    {
        return in_array($forma, ['dinheiro', 'pix', 'cartao', 'transferencia', 'boleto'], true);
    }

    private function joinsRelacionados(): string
    {
        $sql = "
            INNER JOIN cobrancas c ON c.id = p.cobranca_id
            LEFT JOIN mensalidades m ON m.id = c.mensalidade_id
            LEFT JOIN contratos ct ON ct.id = m.contrato_id
            LEFT JOIN alunos a ON a.id = ct.aluno_id
        ";

        if ($this->hasTurmas) {
            $sql .= ' LEFT JOIN turmas t ON t.id = a.turma_id';
            if ($this->hasUnidades) {
                $sql .= ' LEFT JOIN unidades u ON u.id = t.unidade_id';
            }
        }

        return $sql;
    }

    private function camposRelacionadosSelect(): string
    {
        $parts = [
            'c.codigo_cobranca AS numero_cobranca',
            'c.status AS status_cobranca',
            'ct.aluno_id',
            'a.nome AS aluno_nome',
            'a.nome AS associado_nome',
            'a.foto AS aluno_foto',
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

    private function normalizarPagamento(array $row): array
    {
        $id = (int) ($row['id'] ?? 0);
        $numero = 'PAG-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);

        return [
            'id' => $id,
            'numero_pagamento' => $numero,
            'aluno_id' => isset($row['aluno_id']) ? (int) $row['aluno_id'] : null,
            'associado_id' => isset($row['aluno_id']) ? (int) $row['aluno_id'] : null,
            'cobranca_id' => (int) ($row['cobranca_id'] ?? 0),
            'valor_pago' => (float) ($row['valor_pago'] ?? 0),
            'data_pagamento' => $row['data_pagamento'] ?? null,
            'forma_pagamento' => (string) ($row['forma_pagamento'] ?? ''),
            'status' => 'confirmado',
            'comprovante_url' => $row['comprovante'] ?? null,
            'comprovante' => $row['comprovante'] ?? null,
            'observacoes' => (string) ($row['observacoes'] ?? ''),
            'numero_cobranca' => (string) ($row['numero_cobranca'] ?? ''),
            'status_cobranca' => (string) ($row['status_cobranca'] ?? ''),
            'aluno_nome' => (string) ($row['aluno_nome'] ?? ''),
            'associado_nome' => (string) ($row['associado_nome'] ?? ($row['aluno_nome'] ?? '')),
            'aluno_foto' => $row['aluno_foto'] ?? null,
            'turma_nome' => (string) ($row['turma_nome'] ?? ''),
            'unidade_nome' => (string) ($row['unidade_nome'] ?? ''),
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    private function recalcularStatusCobranca(int $cobrancaId): void
    {
        $st = $this->db->prepare('SELECT valor, mensalidade_id FROM cobrancas WHERE id = ? LIMIT 1');
        $st->execute([$cobrancaId]);
        $cobranca = $st->fetch(PDO::FETCH_ASSOC);
        if (!$cobranca) {
            return;
        }

        $valorCobranca = (float) ($cobranca['valor'] ?? 0);
        $mensalidadeId = (int) ($cobranca['mensalidade_id'] ?? 0);

        $stTotal = $this->db->prepare('SELECT COALESCE(SUM(valor_pago), 0) AS total_pago, MAX(data_pagamento) AS ultima_data FROM pagamentos WHERE cobranca_id = ?');
        $stTotal->execute([$cobrancaId]);
        $totais = $stTotal->fetch(PDO::FETCH_ASSOC) ?: ['total_pago' => 0, 'ultima_data' => null];
        $totalPago = (float) ($totais['total_pago'] ?? 0);
        $ultimaData = $totais['ultima_data'] ?? null;

        $novoStatus = 'emitida';
        if ($totalPago >= $valorCobranca && $valorCobranca > 0) {
            $novoStatus = 'paga';
        } elseif ($totalPago > 0) {
            $novoStatus = 'emitida';
        }

        $stUpCobranca = $this->db->prepare('UPDATE cobrancas SET status = ?, updated_at = NOW() WHERE id = ?');
        $stUpCobranca->execute([$novoStatus, $cobrancaId]);

        if ($mensalidadeId > 0) {
            $statusMensalidade = $novoStatus === 'paga' ? 'paga' : 'pendente';
            $valorPagoMensalidade = $totalPago > 0 ? $totalPago : null;
            $dataPagamentoMensalidade = ($novoStatus === 'paga') ? $ultimaData : null;

            $stUpMens = $this->db->prepare("
                UPDATE mensalidades
                SET status = ?, valor_pago = ?, data_pagamento = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stUpMens->execute([$statusMensalidade, $valorPagoMensalidade, $dataPagamentoMensalidade, $mensalidadeId]);
        }
    }

    public function listar(array $filtros = []): array
    {
        try {
            $where = ' WHERE 1=1';
            $params = [];

            if (!empty($filtros['forma_pagamento'])) {
                $where .= ' AND p.forma_pagamento = ?';
                $params[] = (string) $filtros['forma_pagamento'];
            }
            if (!empty($filtros['aluno_id'])) {
                $where .= ' AND ct.aluno_id = ?';
                $params[] = (int) $filtros['aluno_id'];
            }
            if (!empty($filtros['data_pagamento_inicio'])) {
                $where .= ' AND p.data_pagamento >= ?';
                $params[] = (string) $filtros['data_pagamento_inicio'];
            }
            if (!empty($filtros['data_pagamento_fim'])) {
                $where .= ' AND p.data_pagamento <= ?';
                $params[] = (string) $filtros['data_pagamento_fim'];
            }
            if (!empty($filtros['status']) && strtolower((string) $filtros['status']) === 'cancelado') {
                return ['success' => true, 'data' => [], 'total' => 0];
            }

            $st = $this->db->prepare("
                SELECT
                    p.*,
                    {$this->camposRelacionadosSelect()}
                FROM pagamentos p
                {$this->joinsRelacionados()}
                {$where}
                ORDER BY p.data_pagamento DESC, p.id DESC
            ");
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            $data = [];
            foreach ($rows as $row) {
                $data[] = $this->normalizarPagamento($row);
            }

            return ['success' => true, 'data' => $data, 'total' => count($data)];
        } catch (Throwable $e) {
            logError('Erro ao listar pagamentos: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar pagamentos'];
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
                    p.*,
                    {$this->camposRelacionadosSelect()}
                FROM pagamentos p
                {$this->joinsRelacionados()}
                WHERE p.id = ?
                LIMIT 1
            ");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'message' => 'Pagamento nao encontrado'];
            }

            return ['success' => true, 'data' => $this->normalizarPagamento($row)];
        } catch (Throwable $e) {
            logError('Erro ao obter pagamento: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter pagamento'];
        }
    }

    public function registrar(array $dados): array
    {
        try {
            $cobrancaId = (int) ($dados['cobranca_id'] ?? 0);
            if ($cobrancaId <= 0) {
                return ['success' => false, 'message' => 'Cobranca e obrigatoria'];
            }

            $valorPago = (float) ($dados['valor_pago'] ?? 0);
            if ($valorPago <= 0) {
                return ['success' => false, 'message' => 'Valor pago deve ser maior que zero'];
            }

            $dataPagamento = (string) ($dados['data_pagamento'] ?? date('Y-m-d'));
            if (!$this->dataValida($dataPagamento)) {
                return ['success' => false, 'message' => 'Data de pagamento invalida'];
            }

            $formaPagamento = strtolower(trim((string) ($dados['forma_pagamento'] ?? '')));
            if (!$this->formaPagamentoValida($formaPagamento)) {
                return ['success' => false, 'message' => 'Forma de pagamento invalida'];
            }

            $st = $this->db->prepare('SELECT id, status FROM cobrancas WHERE id = ? LIMIT 1');
            $st->execute([$cobrancaId]);
            $cobranca = $st->fetch(PDO::FETCH_ASSOC);
            if (!$cobranca) {
                return ['success' => false, 'message' => 'Cobranca nao encontrada'];
            }
            if ((string) ($cobranca['status'] ?? '') === 'cancelada') {
                return ['success' => false, 'message' => 'Nao e possivel registrar pagamento para cobranca cancelada'];
            }

            $this->db->beginTransaction();

            $st = $this->db->prepare("
                INSERT INTO pagamentos
                    (cobranca_id, valor_pago, data_pagamento, forma_pagamento, comprovante, observacoes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $st->execute([
                $cobrancaId,
                $valorPago,
                $dataPagamento,
                $formaPagamento,
                trim((string) ($dados['comprovante_url'] ?? $dados['comprovante'] ?? '')) !== '' ? (string) ($dados['comprovante_url'] ?? $dados['comprovante']) : null,
                trim((string) ($dados['observacoes'] ?? '')) !== '' ? sanitizeInput((string) $dados['observacoes']) : null,
            ]);

            $id = (int) $this->db->lastInsertId();
            $this->recalcularStatusCobranca($cobrancaId);

            $this->db->commit();

            return ['success' => true, 'message' => 'Pagamento registrado com sucesso', 'data' => ['id' => $id]];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logError('Erro ao registrar pagamento: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao registrar pagamento'];
        }
    }

    public function atualizar(int $id, array $dados): array
    {
        try {
            $exist = $this->obter($id);
            if (empty($exist['success'])) {
                return $exist;
            }
            $cobrancaId = (int) ($exist['data']['cobranca_id'] ?? 0);

            $campos = [];
            $valores = [];

            if (array_key_exists('valor_pago', $dados)) {
                $valorPago = (float) $dados['valor_pago'];
                if ($valorPago <= 0) {
                    return ['success' => false, 'message' => 'Valor pago deve ser maior que zero'];
                }
                $campos[] = 'valor_pago = ?';
                $valores[] = $valorPago;
            }

            if (array_key_exists('data_pagamento', $dados)) {
                $dataPagamento = (string) $dados['data_pagamento'];
                if (!$this->dataValida($dataPagamento)) {
                    return ['success' => false, 'message' => 'Data de pagamento invalida'];
                }
                $campos[] = 'data_pagamento = ?';
                $valores[] = $dataPagamento;
            }

            if (array_key_exists('forma_pagamento', $dados)) {
                $forma = strtolower(trim((string) $dados['forma_pagamento']));
                if (!$this->formaPagamentoValida($forma)) {
                    return ['success' => false, 'message' => 'Forma de pagamento invalida'];
                }
                $campos[] = 'forma_pagamento = ?';
                $valores[] = $forma;
            }

            if (array_key_exists('comprovante_url', $dados) || array_key_exists('comprovante', $dados)) {
                $comprovante = trim((string) ($dados['comprovante_url'] ?? $dados['comprovante']));
                $campos[] = 'comprovante = ?';
                $valores[] = $comprovante !== '' ? $comprovante : null;
            }

            if (array_key_exists('observacoes', $dados)) {
                $obs = trim((string) $dados['observacoes']);
                $campos[] = 'observacoes = ?';
                $valores[] = $obs !== '' ? sanitizeInput($obs) : null;
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $this->db->beginTransaction();

            $valores[] = $id;
            $st = $this->db->prepare('UPDATE pagamentos SET ' . implode(', ', $campos) . ' WHERE id = ?');
            $st->execute($valores);

            if ($cobrancaId > 0) {
                $this->recalcularStatusCobranca($cobrancaId);
            }

            $this->db->commit();

            return ['success' => true, 'message' => 'Pagamento atualizado com sucesso'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logError('Erro ao atualizar pagamento: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar pagamento'];
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

            $st = $this->db->prepare('SELECT id, cobranca_id FROM pagamentos WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $pag = $st->fetch(PDO::FETCH_ASSOC);
            if (!$pag) {
                return ['success' => false, 'message' => 'Pagamento nao encontrado'];
            }

            $cobrancaId = (int) ($pag['cobranca_id'] ?? 0);

            $this->db->beginTransaction();

            $st = $this->db->prepare('DELETE FROM pagamentos WHERE id = ?');
            $st->execute([$id]);

            if ($cobrancaId > 0) {
                $stObs = $this->db->prepare("
                    UPDATE cobrancas
                    SET observacoes = CONCAT(COALESCE(observacoes, ''), '\nPagamento removido em: ', NOW(), '\nMotivo: ', ?),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stObs->execute([sanitizeInput($motivo), $cobrancaId]);
                $this->recalcularStatusCobranca($cobrancaId);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Pagamento cancelado com sucesso'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logError('Erro ao cancelar pagamento: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao cancelar pagamento'];
        }
    }

    public function obterEstatisticas(?string $dataInicio = null, ?string $dataFim = null): array
    {
        try {
            $where = ' WHERE 1=1';
            $params = [];
            if ($dataInicio) {
                $where .= ' AND p.data_pagamento >= ?';
                $params[] = $dataInicio;
            }
            if ($dataFim) {
                $where .= ' AND p.data_pagamento <= ?';
                $params[] = $dataFim;
            }

            $st = $this->db->prepare("
                SELECT
                    COUNT(*) AS total_pagamentos,
                    COUNT(*) AS pagamentos_confirmados,
                    0 AS pagamentos_pendentes,
                    0 AS pagamentos_cancelados,
                    SUM(p.valor_pago) AS valor_total_recebido,
                    AVG(p.valor_pago) AS valor_medio_pagamento,
                    SUM(CASE WHEN p.forma_pagamento = 'dinheiro' THEN 1 ELSE 0 END) AS pagamentos_dinheiro,
                    SUM(CASE WHEN p.forma_pagamento = 'pix' THEN 1 ELSE 0 END) AS pagamentos_pix,
                    SUM(CASE WHEN p.forma_pagamento = 'cartao' THEN 1 ELSE 0 END) AS pagamentos_cartao,
                    SUM(CASE WHEN p.forma_pagamento = 'transferencia' THEN 1 ELSE 0 END) AS pagamentos_transferencia
                FROM pagamentos p
                {$where}
            ");
            $st->execute($params);
            return ['success' => true, 'data' => $st->fetch(PDO::FETCH_ASSOC) ?: []];
        } catch (Throwable $e) {
            logError('Erro ao obter estatisticas dos pagamentos: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter estatisticas dos pagamentos'];
        }
    }

    public function obterPorPeriodo(string $dataInicio, string $dataFim): array
    {
        try {
            if (!$this->dataValida($dataInicio) || !$this->dataValida($dataFim)) {
                return ['success' => false, 'message' => 'Datas invalidas'];
            }

            $st = $this->db->prepare("
                SELECT
                    DATE(p.data_pagamento) AS data,
                    COUNT(*) AS total_pagamentos,
                    SUM(p.valor_pago) AS valor_total,
                    SUM(CASE WHEN p.forma_pagamento = 'dinheiro' THEN 1 ELSE 0 END) AS dinheiro,
                    SUM(CASE WHEN p.forma_pagamento = 'pix' THEN 1 ELSE 0 END) AS pix,
                    SUM(CASE WHEN p.forma_pagamento = 'cartao' THEN 1 ELSE 0 END) AS cartao,
                    SUM(CASE WHEN p.forma_pagamento = 'transferencia' THEN 1 ELSE 0 END) AS transferencia
                FROM pagamentos p
                WHERE p.data_pagamento BETWEEN ? AND ?
                GROUP BY DATE(p.data_pagamento)
                ORDER BY data ASC
            ");
            $st->execute([$dataInicio, $dataFim]);
            return ['success' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Throwable $e) {
            logError('Erro ao obter pagamentos por periodo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter pagamentos por periodo'];
        }
    }

    public function obterRelatorioInadimplencia(?string $dataReferencia = null): array
    {
        try {
            $dataReferencia = $dataReferencia ?: date('Y-m-d');
            if (!$this->dataValida($dataReferencia)) {
                return ['success' => false, 'message' => 'Data de referencia invalida'];
            }

            $selectTurma = $this->hasTurmas ? 't.nome AS turma_nome' : "'' AS turma_nome";
            $joinTurma = $this->hasTurmas ? ' LEFT JOIN turmas t ON t.id = a.turma_id' : '';
            $selectUnidade = $this->hasTurmas && $this->hasUnidades ? 'u.nome AS unidade_nome' : "'' AS unidade_nome";
            $joinUnidade = $this->hasTurmas && $this->hasUnidades ? ' LEFT JOIN unidades u ON u.id = t.unidade_id' : '';

            $sql = "
                SELECT
                    a.id AS aluno_id,
                    a.nome AS aluno_nome,
                    a.nome AS associado_nome,
                    a.foto AS aluno_foto,
                    {$selectTurma},
                    {$selectUnidade},
                    COUNT(c.id) AS total_cobrancas_vencidas,
                    SUM(c.valor) AS valor_total_devido,
                    MAX(c.data_vencimento) AS ultima_cobranca_vencida,
                    DATEDIFF(?, MAX(c.data_vencimento)) AS dias_atraso
                FROM alunos a
                {$joinTurma}
                {$joinUnidade}
                INNER JOIN contratos ct ON ct.aluno_id = a.id
                INNER JOIN mensalidades m ON m.contrato_id = ct.id
                INNER JOIN cobrancas c ON c.mensalidade_id = m.id
                WHERE c.status IN ('emitida', 'vencida')
                    AND c.data_vencimento < ?
                GROUP BY a.id
                ORDER BY dias_atraso DESC, valor_total_devido DESC
            ";

            $st = $this->db->prepare($sql);
            $st->execute([$dataReferencia, $dataReferencia]);
            return ['success' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Throwable $e) {
            logError('Erro ao obter relatorio de inadimplencia: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter relatorio de inadimplencia'];
        }
    }
}

$auth = financeiro_require_auth('pagamentos');
financeiro_require_tables(['pagamentos', 'cobrancas', 'mensalidades', 'contratos', 'alunos'], 'pagamentos');
$api = new PagamentosAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = (string) ($_GET['action'] ?? 'listar');
    switch ($action) {
        case 'listar':
            $filtros = [];
            if (isset($_GET['status'])) {
                $filtros['status'] = (string) $_GET['status'];
            }
            if (isset($_GET['forma_pagamento'])) {
                $filtros['forma_pagamento'] = (string) $_GET['forma_pagamento'];
            }
            if (isset($_GET['aluno_id'])) {
                $filtros['aluno_id'] = (int) $_GET['aluno_id'];
            }
            if (isset($_GET['data_pagamento_inicio'])) {
                $filtros['data_pagamento_inicio'] = (string) $_GET['data_pagamento_inicio'];
            }
            if (isset($_GET['data_pagamento_fim'])) {
                $filtros['data_pagamento_fim'] = (string) $_GET['data_pagamento_fim'];
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
        case 'por_periodo':
            $dataInicio = (string) ($_GET['data_inicio'] ?? '');
            $dataFim = (string) ($_GET['data_fim'] ?? '');
            if ($dataInicio === '' || $dataFim === '') {
                financeiro_response(['success' => false, 'message' => 'Data de inicio e fim sao obrigatorias'], 400);
            }
            financeiro_response($api->obterPorPeriodo($dataInicio, $dataFim));
            break;
        case 'inadimplencia':
            $dataReferencia = isset($_GET['data_referencia']) ? (string) $_GET['data_referencia'] : null;
            financeiro_response($api->obterRelatorioInadimplencia($dataReferencia));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao nao encontrada'], 404);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = financeiro_input();
    $action = (string) ($input['action'] ?? '');

    switch ($action) {
        case 'registrar':
            $result = $api->registrar($input);
            if (!empty($result['success'])) {
                $entityId = financeiro_extract_entity_id($result);
                $after = $entityId ? ($api->obter($entityId)['data'] ?? null) : null;
                financeiro_audit($auth, 'create', 'pagamentos', $entityId, null, $after, ['action' => 'registrar']);
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
                financeiro_audit($auth, 'update', 'pagamentos', $id, $before, $after, ['action' => 'atualizar']);
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
                financeiro_audit($auth, 'cancel', 'pagamentos', $id, $before, null, ['action' => 'cancelar', 'motivo' => $motivo]);
            }
            financeiro_response($result);
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao nao encontrada'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Metodo nao permitido'], 405);
