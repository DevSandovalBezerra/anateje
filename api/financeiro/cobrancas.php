<?php
// API de Cobrancas - adaptada ao schema atual

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_bootstrap.php';

class CobrancasAPI
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

    private function joinsRelacionados(): string
    {
        $sql = "
            LEFT JOIN mensalidades m ON m.id = c.mensalidade_id
            LEFT JOIN contratos ct ON ct.id = m.contrato_id
            LEFT JOIN planos_financeiros pf ON pf.id = ct.plano_id
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
            'm.referencia AS mensalidade_referencia',
            'm.contrato_id',
            'ct.numero_contrato',
            'ct.aluno_id',
            'a.nome AS aluno_nome',
            'a.nome AS associado_nome',
            'a.foto AS aluno_foto',
            'a.cpf AS aluno_cpf',
            'pf.nome AS plano_nome',
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

    private function normalizarCobranca(array $row): array
    {
        $status = (string) ($row['status'] ?? 'emitida');
        $dias = isset($row['dias_para_vencimento']) ? (int) $row['dias_para_vencimento'] : null;
        $detalhado = (string) ($row['status_detalhado'] ?? '');
        if ($detalhado === '') {
            $detalhado = $status;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'mensalidade_id' => (int) ($row['mensalidade_id'] ?? 0),
            'numero_cobranca' => (string) ($row['codigo_cobranca'] ?? ''),
            'codigo_cobranca' => (string) ($row['codigo_cobranca'] ?? ''),
            'tipo' => (string) ($row['tipo_cobranca'] ?? ''),
            'tipo_cobranca' => (string) ($row['tipo_cobranca'] ?? ''),
            'valor' => (float) ($row['valor'] ?? 0),
            'data_emissao' => $row['data_emissao'] ?? null,
            'data_vencimento' => $row['data_vencimento'] ?? null,
            'status' => $status,
            'status_detalhado' => $detalhado,
            'dias_para_vencimento' => $dias,
            'link_pagamento' => $row['link_pagamento'] ?? null,
            'descricao' => (string) ($row['observacoes'] ?? ''),
            'observacoes' => (string) ($row['observacoes'] ?? ''),
            'contrato_id' => isset($row['contrato_id']) ? (int) $row['contrato_id'] : null,
            'mensalidade_referencia' => $row['mensalidade_referencia'] ?? null,
            'numero_contrato' => (string) ($row['numero_contrato'] ?? ''),
            'aluno_id' => isset($row['aluno_id']) ? (int) $row['aluno_id'] : null,
            'associado_id' => isset($row['aluno_id']) ? (int) $row['aluno_id'] : null,
            'aluno_nome' => (string) ($row['aluno_nome'] ?? ''),
            'associado_nome' => (string) ($row['associado_nome'] ?? ($row['aluno_nome'] ?? '')),
            'aluno_foto' => $row['aluno_foto'] ?? null,
            'aluno_cpf' => $row['aluno_cpf'] ?? null,
            'plano_nome' => (string) ($row['plano_nome'] ?? ''),
            'turma_nome' => (string) ($row['turma_nome'] ?? ''),
            'unidade_nome' => (string) ($row['unidade_nome'] ?? ''),
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    private function obterMensalidadePorAluno(int $alunoId): ?int
    {
        $st = $this->db->prepare("
            SELECT m.id
            FROM mensalidades m
            INNER JOIN contratos c ON c.id = m.contrato_id
            WHERE c.aluno_id = ?
            ORDER BY
                CASE WHEN m.status = 'pendente' THEN 0 ELSE 1 END,
                m.data_vencimento ASC,
                m.id ASC
            LIMIT 1
        ");
        $st->execute([$alunoId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['id'] : null;
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
            if (!empty($filtros['tipo'])) {
                $where .= ' AND c.tipo_cobranca = ?';
                $params[] = (string) $filtros['tipo'];
            }
            if (!empty($filtros['aluno_id'])) {
                $where .= ' AND ct.aluno_id = ?';
                $params[] = (int) $filtros['aluno_id'];
            }
            if (!empty($filtros['data_vencimento_inicio'])) {
                $where .= ' AND c.data_vencimento >= ?';
                $params[] = (string) $filtros['data_vencimento_inicio'];
            }
            if (!empty($filtros['data_vencimento_fim'])) {
                $where .= ' AND c.data_vencimento <= ?';
                $params[] = (string) $filtros['data_vencimento_fim'];
            }
            if (!empty($filtros['vencidas'])) {
                $where .= " AND c.data_vencimento < CURDATE() AND c.status IN ('emitida','vencida')";
            }

            $st = $this->db->prepare("
                SELECT
                    c.*,
                    {$this->camposRelacionadosSelect()},
                    CASE
                        WHEN c.data_vencimento < CURDATE() AND c.status IN ('emitida','vencida') THEN 'vencida'
                        WHEN c.data_vencimento <= DATE_ADD(CURDATE(), INTERVAL 5 DAY) AND c.status IN ('emitida','vencida') THEN 'proximo_vencimento'
                        ELSE c.status
                    END AS status_detalhado,
                    DATEDIFF(c.data_vencimento, CURDATE()) AS dias_para_vencimento
                FROM cobrancas c
                {$this->joinsRelacionados()}
                {$where}
                ORDER BY c.data_vencimento ASC, c.created_at DESC
            ");
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = $this->normalizarCobranca($row);
            }

            return ['success' => true, 'data' => $data, 'total' => count($data)];
        } catch (Throwable $e) {
            logError('Erro ao listar cobrancas: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar cobrancas'];
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
                    {$this->camposRelacionadosSelect()},
                    CASE
                        WHEN c.data_vencimento < CURDATE() AND c.status IN ('emitida','vencida') THEN 'vencida'
                        WHEN c.data_vencimento <= DATE_ADD(CURDATE(), INTERVAL 5 DAY) AND c.status IN ('emitida','vencida') THEN 'proximo_vencimento'
                        ELSE c.status
                    END AS status_detalhado,
                    DATEDIFF(c.data_vencimento, CURDATE()) AS dias_para_vencimento
                FROM cobrancas c
                {$this->joinsRelacionados()}
                WHERE c.id = ?
                LIMIT 1
            ");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'message' => 'Cobranca nao encontrada'];
            }

            return ['success' => true, 'data' => $this->normalizarCobranca($row)];
        } catch (Throwable $e) {
            logError('Erro ao obter cobranca: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter cobranca'];
        }
    }

    public function criar(array $dados): array
    {
        try {
            $mensalidadeId = (int) ($dados['mensalidade_id'] ?? 0);
            $alunoId = (int) ($dados['aluno_id'] ?? 0);

            if ($mensalidadeId <= 0 && $alunoId > 0) {
                $mensalidadeId = (int) ($this->obterMensalidadePorAluno($alunoId) ?? 0);
            }

            if ($mensalidadeId <= 0) {
                return ['success' => false, 'message' => 'Mensalidade e obrigatoria para criar cobranca'];
            }

            $st = $this->db->prepare('SELECT id, valor, data_vencimento FROM mensalidades WHERE id = ? LIMIT 1');
            $st->execute([$mensalidadeId]);
            $mensalidade = $st->fetch(PDO::FETCH_ASSOC);
            if (!$mensalidade) {
                return ['success' => false, 'message' => 'Mensalidade nao encontrada'];
            }

            $tipo = strtolower(trim((string) ($dados['tipo'] ?? ($dados['tipo_cobranca'] ?? 'mensalidade'))));
            if ($tipo === '') {
                $tipo = 'mensalidade';
            }

            $valor = isset($dados['valor']) ? (float) $dados['valor'] : (float) $mensalidade['valor'];
            if ($valor <= 0) {
                return ['success' => false, 'message' => 'Valor deve ser maior que zero'];
            }

            $dataEmissao = (string) ($dados['data_emissao'] ?? date('Y-m-d'));
            if (!$this->dataValida($dataEmissao)) {
                return ['success' => false, 'message' => 'Data de emissao invalida'];
            }

            $dataVencimento = (string) ($dados['data_vencimento'] ?? $mensalidade['data_vencimento']);
            if (!$this->dataValida($dataVencimento)) {
                return ['success' => false, 'message' => 'Data de vencimento invalida'];
            }

            $status = strtolower(trim((string) ($dados['status'] ?? 'emitida')));
            if (!in_array($status, ['emitida', 'paga', 'cancelada', 'vencida'], true)) {
                return ['success' => false, 'message' => 'Status invalido'];
            }

            $codigo = trim((string) ($dados['numero_cobranca'] ?? ($dados['codigo_cobranca'] ?? '')));
            if ($codigo === '') {
                $codigo = generatePaymentCode('COB');
            }

            $st = $this->db->prepare("
                INSERT INTO cobrancas
                    (mensalidade_id, tipo_cobranca, codigo_cobranca, link_pagamento, valor, data_emissao, data_vencimento, status, observacoes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $st->execute([
                $mensalidadeId,
                $tipo,
                $codigo,
                trim((string) ($dados['link_pagamento'] ?? '')) !== '' ? (string) $dados['link_pagamento'] : null,
                $valor,
                $dataEmissao,
                $dataVencimento,
                $status,
                trim((string) ($dados['observacoes'] ?? $dados['descricao'] ?? '')) !== '' ? sanitizeInput((string) ($dados['observacoes'] ?? $dados['descricao'])) : null,
            ]);

            return [
                'success' => true,
                'message' => 'Cobranca criada com sucesso',
                'data' => ['id' => (int) $this->db->lastInsertId(), 'numero_cobranca' => $codigo]
            ];
        } catch (Throwable $e) {
            logError('Erro ao criar cobranca: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao criar cobranca'];
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

            if (array_key_exists('tipo', $dados) || array_key_exists('tipo_cobranca', $dados)) {
                $tipo = strtolower(trim((string) ($dados['tipo'] ?? $dados['tipo_cobranca'])));
                if ($tipo === '') {
                    return ['success' => false, 'message' => 'Tipo invalido'];
                }
                $campos[] = 'tipo_cobranca = ?';
                $valores[] = $tipo;
            }

            if (array_key_exists('valor', $dados)) {
                $valor = (float) $dados['valor'];
                if ($valor <= 0) {
                    return ['success' => false, 'message' => 'Valor deve ser maior que zero'];
                }
                $campos[] = 'valor = ?';
                $valores[] = $valor;
            }

            if (array_key_exists('data_vencimento', $dados)) {
                $dataVencimento = (string) $dados['data_vencimento'];
                if (!$this->dataValida($dataVencimento)) {
                    return ['success' => false, 'message' => 'Data de vencimento invalida'];
                }
                $campos[] = 'data_vencimento = ?';
                $valores[] = $dataVencimento;
            }

            if (array_key_exists('status', $dados)) {
                $status = strtolower(trim((string) $dados['status']));
                if (!in_array($status, ['emitida', 'paga', 'cancelada', 'vencida'], true)) {
                    return ['success' => false, 'message' => 'Status invalido'];
                }
                $campos[] = 'status = ?';
                $valores[] = $status;
            }

            if (array_key_exists('link_pagamento', $dados)) {
                $link = trim((string) $dados['link_pagamento']);
                $campos[] = 'link_pagamento = ?';
                $valores[] = $link !== '' ? $link : null;
            }

            if (array_key_exists('observacoes', $dados) || array_key_exists('descricao', $dados)) {
                $obs = trim((string) ($dados['observacoes'] ?? $dados['descricao']));
                $campos[] = 'observacoes = ?';
                $valores[] = $obs !== '' ? sanitizeInput($obs) : null;
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $valores[] = $id;
            $st = $this->db->prepare('UPDATE cobrancas SET ' . implode(', ', $campos) . ', updated_at = NOW() WHERE id = ?');
            $st->execute($valores);

            return ['success' => true, 'message' => 'Cobranca atualizada com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao atualizar cobranca: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar cobranca'];
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
                UPDATE cobrancas
                SET status = 'cancelada',
                    observacoes = CONCAT(COALESCE(observacoes, ''), '\nCancelada em: ', NOW(), '\nMotivo: ', ?),
                    updated_at = NOW()
                WHERE id = ? AND status <> 'paga'
            ");
            $st->execute([sanitizeInput($motivo), $id]);

            if ($st->rowCount() === 0) {
                return ['success' => false, 'message' => 'Cobranca nao encontrada ou ja paga'];
            }

            return ['success' => true, 'message' => 'Cobranca cancelada com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao cancelar cobranca: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao cancelar cobranca'];
        }
    }

    public function gerarCobrancasLote(array $dados): array
    {
        try {
            $alvos = $dados['alunos'] ?? [];
            if (!is_array($alvos) || empty($alvos)) {
                return ['success' => false, 'message' => 'Informe associados para gerar cobrancas'];
            }

            $tipo = strtolower(trim((string) ($dados['tipo'] ?? 'mensalidade')));
            $dataVencimentoPadrao = (string) ($dados['data_vencimento'] ?? date('Y-m-d', strtotime('+30 days')));
            if (!$this->dataValida($dataVencimentoPadrao)) {
                return ['success' => false, 'message' => 'Data de vencimento invalida'];
            }

            $valorPadrao = (float) ($dados['valor_padrao'] ?? 0);
            $obs = trim((string) ($dados['observacoes'] ?? $dados['descricao'] ?? ''));

            $this->db->beginTransaction();
            $geradas = 0;

            foreach ($alvos as $item) {
                $alunoId = (int) ($item['id'] ?? $item['aluno_id'] ?? 0);
                $mensalidadeId = (int) ($item['mensalidade_id'] ?? 0);
                if ($mensalidadeId <= 0 && $alunoId > 0) {
                    $mensalidadeId = (int) ($this->obterMensalidadePorAluno($alunoId) ?? 0);
                }
                if ($mensalidadeId <= 0) {
                    continue;
                }

                $stMens = $this->db->prepare('SELECT id, valor FROM mensalidades WHERE id = ? LIMIT 1');
                $stMens->execute([$mensalidadeId]);
                $mensalidade = $stMens->fetch(PDO::FETCH_ASSOC);
                if (!$mensalidade) {
                    continue;
                }

                $valor = isset($item['valor']) ? (float) $item['valor'] : $valorPadrao;
                if ($valor <= 0) {
                    $valor = (float) ($mensalidade['valor'] ?? 0);
                }
                if ($valor <= 0) {
                    continue;
                }

                $codigo = generatePaymentCode('COB');
                $stIns = $this->db->prepare("
                    INSERT INTO cobrancas
                        (mensalidade_id, tipo_cobranca, codigo_cobranca, valor, data_emissao, data_vencimento, status, observacoes)
                    VALUES (?, ?, ?, ?, CURDATE(), ?, 'emitida', ?)
                ");
                $stIns->execute([
                    $mensalidadeId,
                    $tipo !== '' ? $tipo : 'mensalidade',
                    $codigo,
                    $valor,
                    $dataVencimentoPadrao,
                    $obs !== '' ? sanitizeInput($obs) : null,
                ]);

                $geradas++;
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Cobrancas geradas com sucesso',
                'data' => ['cobrancas_geradas' => $geradas]
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logError('Erro ao gerar cobrancas em lote: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao gerar cobrancas em lote'];
        }
    }

    public function obterEstatisticas(?string $dataInicio = null, ?string $dataFim = null): array
    {
        try {
            $where = ' WHERE 1=1';
            $params = [];
            if ($dataInicio) {
                $where .= ' AND c.data_emissao >= ?';
                $params[] = $dataInicio;
            }
            if ($dataFim) {
                $where .= ' AND c.data_emissao <= ?';
                $params[] = $dataFim;
            }

            $st = $this->db->prepare("
                SELECT
                    COUNT(*) AS total_cobrancas,
                    COUNT(CASE WHEN c.status = 'emitida' THEN 1 END) AS cobrancas_emitidas,
                    0 AS cobrancas_parciais,
                    COUNT(CASE WHEN c.status = 'paga' THEN 1 END) AS cobrancas_pagas,
                    COUNT(CASE WHEN c.status = 'cancelada' THEN 1 END) AS cobrancas_canceladas,
                    SUM(CASE WHEN c.status <> 'cancelada' THEN c.valor ELSE 0 END) AS valor_total
                FROM cobrancas c
                {$where}
            ");
            $st->execute($params);
            return ['success' => true, 'data' => $st->fetch(PDO::FETCH_ASSOC) ?: []];
        } catch (Throwable $e) {
            logError('Erro ao obter estatisticas de cobrancas: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter estatisticas de cobrancas'];
        }
    }

    public function obterCobrancasVencidas(int $diasAtraso = 0): array
    {
        try {
            if ($diasAtraso < 0) {
                $diasAtraso = 0;
            }

            $st = $this->db->prepare("
                SELECT
                    c.*,
                    {$this->camposRelacionadosSelect()},
                    'vencida' AS status_detalhado,
                    DATEDIFF(CURDATE(), c.data_vencimento) AS dias_para_vencimento
                FROM cobrancas c
                {$this->joinsRelacionados()}
                WHERE c.status IN ('emitida','vencida')
                    AND c.data_vencimento < CURDATE()
                    AND DATEDIFF(CURDATE(), c.data_vencimento) >= ?
                ORDER BY c.data_vencimento ASC
            ");
            $st->execute([$diasAtraso]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            $data = [];
            foreach ($rows as $row) {
                $data[] = $this->normalizarCobranca($row);
            }
            return ['success' => true, 'data' => $data];
        } catch (Throwable $e) {
            logError('Erro ao obter cobrancas vencidas: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter cobrancas vencidas'];
        }
    }

    public function obterCobrancasProximasVencimento(int $dias = 5): array
    {
        try {
            if ($dias < 0) {
                $dias = 0;
            }

            $st = $this->db->prepare("
                SELECT
                    c.*,
                    {$this->camposRelacionadosSelect()},
                    'proximo_vencimento' AS status_detalhado,
                    DATEDIFF(c.data_vencimento, CURDATE()) AS dias_para_vencimento
                FROM cobrancas c
                {$this->joinsRelacionados()}
                WHERE c.status IN ('emitida','vencida')
                    AND c.data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY c.data_vencimento ASC
            ");
            $st->execute([$dias]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            $data = [];
            foreach ($rows as $row) {
                $data[] = $this->normalizarCobranca($row);
            }
            return ['success' => true, 'data' => $data];
        } catch (Throwable $e) {
            logError('Erro ao obter cobrancas proximas ao vencimento: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter cobrancas proximas ao vencimento'];
        }
    }

    public function enviarLembretes(array $cobrancaIds): array
    {
        try {
            if (empty($cobrancaIds)) {
                return ['success' => false, 'message' => 'Selecione pelo menos uma cobranca'];
            }

            $ids = [];
            foreach ($cobrancaIds as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
            $ids = array_values(array_unique($ids));
            if (empty($ids)) {
                return ['success' => false, 'message' => 'Lista de cobrancas invalida'];
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "
                UPDATE cobrancas
                SET observacoes = CONCAT(COALESCE(observacoes, ''), '\nLembrete enviado em: ', NOW()),
                    updated_at = NOW()
                WHERE id IN ({$placeholders}) AND status IN ('emitida','vencida')
            ";
            $st = $this->db->prepare($sql);
            $st->execute($ids);

            return [
                'success' => true,
                'message' => 'Lembretes registrados com sucesso',
                'data' => ['lembretes_enviados' => $st->rowCount()],
            ];
        } catch (Throwable $e) {
            logError('Erro ao enviar lembretes de cobranca: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao enviar lembretes de cobranca'];
        }
    }
}

$auth = financeiro_require_auth('cobrancas');
financeiro_require_tables(['cobrancas', 'mensalidades', 'contratos', 'alunos', 'planos_financeiros'], 'cobrancas');
$api = new CobrancasAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = (string) ($_GET['action'] ?? 'listar');
    switch ($action) {
        case 'listar':
            $filtros = [];
            if (isset($_GET['status'])) {
                $filtros['status'] = (string) $_GET['status'];
            }
            if (isset($_GET['tipo'])) {
                $filtros['tipo'] = (string) $_GET['tipo'];
            }
            if (isset($_GET['aluno_id'])) {
                $filtros['aluno_id'] = (int) $_GET['aluno_id'];
            }
            if (isset($_GET['data_vencimento_inicio'])) {
                $filtros['data_vencimento_inicio'] = (string) $_GET['data_vencimento_inicio'];
            }
            if (isset($_GET['data_vencimento_fim'])) {
                $filtros['data_vencimento_fim'] = (string) $_GET['data_vencimento_fim'];
            }
            if (isset($_GET['vencidas'])) {
                $filtros['vencidas'] = true;
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
        case 'vencidas':
            $diasAtraso = (int) ($_GET['dias_atraso'] ?? 0);
            financeiro_response($api->obterCobrancasVencidas($diasAtraso));
            break;
        case 'proximas_vencimento':
            $dias = (int) ($_GET['dias'] ?? 5);
            financeiro_response($api->obterCobrancasProximasVencimento($dias));
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
                financeiro_audit($auth, 'create', 'cobrancas', $entityId, null, $after, ['action' => 'criar']);
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
                financeiro_audit($auth, 'update', 'cobrancas', $id, $before, $after, ['action' => 'atualizar']);
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
                financeiro_audit($auth, 'cancel', 'cobrancas', $id, $before, $after, ['action' => 'cancelar', 'motivo' => $motivo]);
            }
            financeiro_response($result);
            break;
        case 'gerar_lote':
            $result = $api->gerarCobrancasLote($input);
            if (!empty($result['success'])) {
                financeiro_audit($auth, 'batch_create', 'cobrancas', null, null, $result['data'] ?? null, ['action' => 'gerar_lote']);
            }
            financeiro_response($result);
            break;
        case 'enviar_lembretes':
            $idsRaw = $input['cobranca_ids'] ?? [];
            if (is_string($idsRaw)) {
                $decoded = json_decode($idsRaw, true);
                if (is_array($decoded)) {
                    $idsRaw = $decoded;
                }
            }
            $ids = is_array($idsRaw) ? $idsRaw : [];
            $result = $api->enviarLembretes($ids);
            if (!empty($result['success'])) {
                financeiro_audit($auth, 'notify', 'cobrancas', null, null, ['ids' => $ids], ['action' => 'enviar_lembretes']);
            }
            financeiro_response($result);
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao nao encontrada'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Metodo nao permitido'], 405);
