<?php
// API de Pagamentos Parciais - padrao ANATEJE

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_bootstrap.php';

class PagamentosParciaisAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    private function dataValida($date): bool
    {
        $date = trim((string) $date);
        if ($date === '') {
            return false;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt instanceof DateTime && $dt->format('Y-m-d') === $date;
    }

    private function atualizarStatusLancamento(int $lancamentoId): void
    {
        try {
            $st = $this->db->prepare("
                SELECT lf.valor_total, COALESCE(SUM(pl.valor_pago), 0) AS total_pago
                FROM lancamentos_financeiros lf
                LEFT JOIN pagamentos_lancamentos pl ON pl.lancamento_id = lf.id
                WHERE lf.id = ?
                GROUP BY lf.id
            ");
            $st->execute([$lancamentoId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return;
            }

            $valorTotal = (float) ($row['valor_total'] ?? 0);
            $totalPago = (float) ($row['total_pago'] ?? 0);
            if ($totalPago >= $valorTotal) {
                $status = 'quitado';
            } elseif ($totalPago > 0) {
                $status = 'parcial';
            } else {
                $status = 'pendente';
            }

            $st = $this->db->prepare("UPDATE lancamentos_financeiros SET status = ? WHERE id = ?");
            $st->execute([$status, $lancamentoId]);
        } catch (Throwable $e) {
            logError('Erro ao atualizar status do lancamento: ' . $e->getMessage());
        }
    }

    private function resumoPagamentos(int $lancamentoId, ?int $ignorePagamentoId = null): ?array
    {
        $sql = "SELECT lf.valor_total,
                       COALESCE(SUM(CASE WHEN pl.id <> ? THEN pl.valor_pago ELSE 0 END), 0) AS total_pago_outros
                FROM lancamentos_financeiros lf
                LEFT JOIN pagamentos_lancamentos pl ON pl.lancamento_id = lf.id
                WHERE lf.id = ?
                GROUP BY lf.id";
        $st = $this->db->prepare($sql);
        $st->execute([(int) ($ignorePagamentoId ?? 0), $lancamentoId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listar(array $filtros = []): array
    {
        try {
            $sql = "SELECT pl.*,
                           lf.titulo AS lancamento_titulo,
                           lf.descricao AS lancamento_descricao,
                           lf.valor_total AS lancamento_valor_total,
                           lf.tipo AS lancamento_tipo,
                           cb.nome_conta AS conta_bancaria_nome
                    FROM pagamentos_lancamentos pl
                    JOIN lancamentos_financeiros lf ON pl.lancamento_id = lf.id
                    LEFT JOIN contas_bancarias cb ON pl.conta_bancaria_id = cb.id
                    WHERE 1=1";
            $params = [];

            if (!empty($filtros['lancamento_id'])) {
                $sql .= " AND pl.lancamento_id = ?";
                $params[] = (int) $filtros['lancamento_id'];
            }
            if (!empty($filtros['data_inicio'])) {
                $sql .= " AND pl.data_pagamento >= ?";
                $params[] = (string) $filtros['data_inicio'];
            }
            if (!empty($filtros['data_fim'])) {
                $sql .= " AND pl.data_pagamento <= ?";
                $params[] = (string) $filtros['data_fim'];
            }
            if (!empty($filtros['conta_bancaria_id'])) {
                $sql .= " AND pl.conta_bancaria_id = ?";
                $params[] = (int) $filtros['conta_bancaria_id'];
            }

            $sql .= " ORDER BY pl.data_pagamento DESC, pl.created_at DESC";
            $st = $this->db->prepare($sql);
            $st->execute($params);

            return ['success' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Throwable $e) {
            logError('Erro ao listar pagamentos parciais: ' . $e->getMessage());
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
                SELECT pl.*,
                       lf.titulo AS lancamento_titulo,
                       lf.descricao AS lancamento_descricao,
                       lf.valor_total AS lancamento_valor_total,
                       lf.tipo AS lancamento_tipo,
                       cb.nome_conta AS conta_bancaria_nome
                FROM pagamentos_lancamentos pl
                JOIN lancamentos_financeiros lf ON pl.lancamento_id = lf.id
                LEFT JOIN contas_bancarias cb ON pl.conta_bancaria_id = cb.id
                WHERE pl.id = ?
            ");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'message' => 'Pagamento nao encontrado'];
            }
            return ['success' => true, 'data' => $row];
        } catch (Throwable $e) {
            logError('Erro ao obter pagamento parcial: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter pagamento'];
        }
    }

    public function criar(array $dados): array
    {
        try {
            $lancamentoId = (int) ($dados['lancamento_id'] ?? 0);
            $valorPago = (float) ($dados['valor_pago'] ?? 0);
            $dataPagamento = trim((string) ($dados['data_pagamento'] ?? ''));

            if ($lancamentoId <= 0 || $valorPago <= 0 || $dataPagamento === '') {
                return ['success' => false, 'message' => 'Lancamento, valor e data sao obrigatorios'];
            }
            if (!$this->dataValida($dataPagamento)) {
                return ['success' => false, 'message' => 'Data de pagamento invalida'];
            }

            $st = $this->db->prepare("SELECT * FROM lancamentos_financeiros WHERE id = ?");
            $st->execute([$lancamentoId]);
            $lancamento = $st->fetch(PDO::FETCH_ASSOC);
            if (!$lancamento) {
                return ['success' => false, 'message' => 'Lancamento nao encontrado'];
            }

            $st = $this->db->prepare("SELECT COALESCE(SUM(valor_pago), 0) AS total_pago FROM pagamentos_lancamentos WHERE lancamento_id = ?");
            $st->execute([$lancamentoId]);
            $totalPago = (float) ($st->fetch(PDO::FETCH_ASSOC)['total_pago'] ?? 0);
            $valorTotal = (float) ($lancamento['valor_total'] ?? 0);
            if (($totalPago + $valorPago) > $valorTotal) {
                return ['success' => false, 'message' => 'Valor excede o total do lancamento'];
            }

            $usuarioId = (int) ($_SESSION['user_id'] ?? 0);
            $contaBancariaId = !empty($dados['conta_bancaria_id']) ? (int) $dados['conta_bancaria_id'] : null;
            $formaPagamento = trim((string) ($dados['forma_pagamento'] ?? ''));
            $comprovanteUrl = trim((string) ($dados['comprovante_url'] ?? ''));
            $observacao = trim((string) ($dados['observacao'] ?? ''));

            $this->db->beginTransaction();
            $st = $this->db->prepare("INSERT INTO pagamentos_lancamentos
                (lancamento_id, valor_pago, data_pagamento, conta_bancaria_id, forma_pagamento, comprovante_url, observacao, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $st->execute([
                $lancamentoId,
                $valorPago,
                $dataPagamento,
                $contaBancariaId,
                $formaPagamento !== '' ? sanitizeInput($formaPagamento) : null,
                $comprovanteUrl !== '' ? sanitizeInput($comprovanteUrl) : null,
                $observacao !== '' ? sanitizeInput($observacao) : null,
                $usuarioId > 0 ? $usuarioId : null
            ]);

            $pagamentoId = (int) $this->db->lastInsertId();
            $this->atualizarStatusLancamento($lancamentoId);
            $this->db->commit();

            if ($usuarioId > 0) {
                financeiro_audit(
                    ['sub' => $usuarioId],
                    'create',
                    'pagamentos_lancamentos',
                    $pagamentoId,
                    null,
                    $dados,
                    ['action' => 'criar', 'lancamento_id' => $lancamentoId]
                );
            }

            return ['success' => true, 'message' => 'Pagamento registrado com sucesso', 'id' => $pagamentoId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logError('Erro ao criar pagamento parcial: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao registrar pagamento'];
        }
    }

    public function atualizar(int $id, array $dados): array
    {
        try {
            $st = $this->db->prepare("SELECT * FROM pagamentos_lancamentos WHERE id = ?");
            $st->execute([$id]);
            $old = $st->fetch(PDO::FETCH_ASSOC);
            if (!$old) {
                return ['success' => false, 'message' => 'Pagamento nao encontrado'];
            }

            $campos = [];
            $params = [];

            if (isset($dados['valor_pago'])) {
                $novoValor = (float) $dados['valor_pago'];
                if ($novoValor <= 0) {
                    return ['success' => false, 'message' => 'Valor deve ser maior que zero'];
                }
                $resumo = $this->resumoPagamentos((int) $old['lancamento_id'], $id);
                $valorTotal = (float) ($resumo['valor_total'] ?? 0);
                $outros = (float) ($resumo['total_pago_outros'] ?? 0);
                if (($outros + $novoValor) > $valorTotal) {
                    return ['success' => false, 'message' => 'Valor excede o total do lancamento'];
                }
                $campos[] = "valor_pago = ?";
                $params[] = $novoValor;
            }

            if (isset($dados['data_pagamento'])) {
                $dataPagamento = trim((string) $dados['data_pagamento']);
                if (!$this->dataValida($dataPagamento)) {
                    return ['success' => false, 'message' => 'Data de pagamento invalida'];
                }
                $campos[] = "data_pagamento = ?";
                $params[] = $dataPagamento;
            }

            if (array_key_exists('conta_bancaria_id', $dados)) {
                $campos[] = "conta_bancaria_id = ?";
                $params[] = !empty($dados['conta_bancaria_id']) ? (int) $dados['conta_bancaria_id'] : null;
            }

            if (array_key_exists('forma_pagamento', $dados)) {
                $forma = trim((string) $dados['forma_pagamento']);
                $campos[] = "forma_pagamento = ?";
                $params[] = $forma !== '' ? sanitizeInput($forma) : null;
            }

            if (array_key_exists('comprovante_url', $dados)) {
                $url = trim((string) $dados['comprovante_url']);
                $campos[] = "comprovante_url = ?";
                $params[] = $url !== '' ? sanitizeInput($url) : null;
            }

            if (array_key_exists('observacao', $dados)) {
                $obs = trim((string) $dados['observacao']);
                $campos[] = "observacao = ?";
                $params[] = $obs !== '' ? sanitizeInput($obs) : null;
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $params[] = $id;
            $sql = "UPDATE pagamentos_lancamentos SET " . implode(', ', $campos) . " WHERE id = ?";
            $st = $this->db->prepare($sql);
            $st->execute($params);

            $this->atualizarStatusLancamento((int) $old['lancamento_id']);
            $st = $this->db->prepare("SELECT * FROM pagamentos_lancamentos WHERE id = ?");
            $st->execute([$id]);
            $new = $st->fetch(PDO::FETCH_ASSOC);

            $usuarioId = (int) ($_SESSION['user_id'] ?? 0);
            if ($usuarioId > 0) {
                financeiro_audit(
                    ['sub' => $usuarioId],
                    'update',
                    'pagamentos_lancamentos',
                    $id,
                    $old,
                    $new,
                    ['action' => 'atualizar', 'lancamento_id' => (int) $old['lancamento_id']]
                );
            }

            return ['success' => true, 'message' => 'Pagamento atualizado com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao atualizar pagamento parcial: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar pagamento'];
        }
    }

    public function excluir(int $id): array
    {
        try {
            $st = $this->db->prepare("SELECT * FROM pagamentos_lancamentos WHERE id = ?");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'message' => 'Pagamento nao encontrado'];
            }

            $lancamentoId = (int) $row['lancamento_id'];
            $this->db->beginTransaction();
            $st = $this->db->prepare("DELETE FROM pagamentos_lancamentos WHERE id = ?");
            $st->execute([$id]);
            $this->atualizarStatusLancamento($lancamentoId);
            $this->db->commit();

            $usuarioId = (int) ($_SESSION['user_id'] ?? 0);
            if ($usuarioId > 0) {
                financeiro_audit(
                    ['sub' => $usuarioId],
                    'delete',
                    'pagamentos_lancamentos',
                    $id,
                    $row,
                    null,
                    ['action' => 'excluir', 'lancamento_id' => $lancamentoId]
                );
            }

            return ['success' => true, 'message' => 'Pagamento excluido com sucesso'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logError('Erro ao excluir pagamento parcial: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao excluir pagamento'];
        }
    }

    public function obterHistorico(int $lancamentoId): array
    {
        try {
            if ($lancamentoId <= 0) {
                return ['success' => false, 'message' => 'ID do lancamento obrigatorio'];
            }

            $st = $this->db->prepare("
                SELECT pl.*,
                       cb.nome_conta AS conta_bancaria_nome,
                       u.nome AS usuario_nome
                FROM pagamentos_lancamentos pl
                LEFT JOIN contas_bancarias cb ON pl.conta_bancaria_id = cb.id
                LEFT JOIN usuarios u ON pl.created_by = u.id
                WHERE pl.lancamento_id = ?
                ORDER BY pl.data_pagamento DESC, pl.created_at DESC
            ");
            $st->execute([$lancamentoId]);
            $pagamentos = $st->fetchAll(PDO::FETCH_ASSOC);

            $st = $this->db->prepare("
                SELECT lf.valor_total, COALESCE(SUM(pl.valor_pago), 0) AS total_pago
                FROM lancamentos_financeiros lf
                LEFT JOIN pagamentos_lancamentos pl ON pl.lancamento_id = lf.id
                WHERE lf.id = ?
                GROUP BY lf.id
            ");
            $st->execute([$lancamentoId]);
            $resumo = $st->fetch(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => ['pagamentos' => $pagamentos, 'resumo' => $resumo]];
        } catch (Throwable $e) {
            logError('Erro ao obter historico de pagamentos: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter historico'];
        }
    }
}

$auth = financeiro_require_auth('pagamentos_parciais');
$api = new PagamentosParciaisAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            financeiro_response($api->listar([
                'lancamento_id' => $_GET['lancamento_id'] ?? null,
                'data_inicio' => $_GET['data_inicio'] ?? null,
                'data_fim' => $_GET['data_fim'] ?? null,
                'conta_bancaria_id' => $_GET['conta_bancaria_id'] ?? null,
            ]));
            break;
        case 'obter':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            financeiro_response($api->obter($id));
            break;
        case 'historico':
            $lancamentoId = (int) ($_GET['lancamento_id'] ?? 0);
            if ($lancamentoId <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID do lancamento obrigatorio'], 400);
            }
            financeiro_response($api->obterHistorico($lancamentoId));
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
            financeiro_response($api->criar($input));
            break;
        case 'atualizar':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            financeiro_response($api->atualizar($id, $input));
            break;
        case 'excluir':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            financeiro_response($api->excluir($id));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao invalida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Metodo nao permitido'], 405);
