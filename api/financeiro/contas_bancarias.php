<?php
// API de Contas Bancarias - padrao ANATEJE

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_bootstrap.php';

class ContasBancariasAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
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

    private function calcularSaldoAtual(int $contaId): array
    {
        try {
            $st = $this->db->prepare("SELECT saldo_inicial FROM contas_bancarias WHERE id = ?");
            $st->execute([$contaId]);
            $conta = $st->fetch(PDO::FETCH_ASSOC);
            if (!$conta) {
                return ['saldo_inicial' => 0.0, 'saldo_real' => 0.0, 'saldo_previsto' => 0.0];
            }

            $saldoInicial = (float) $conta['saldo_inicial'];
            $st = $this->db->prepare("
                SELECT
                    SUM(CASE
                        WHEN (lf.tipo = 'receber' OR lf.tipo = 'receita') AND (lf.status = 'quitado' OR lf.status = 'pago') THEN lf.valor_total
                        WHEN (lf.tipo = 'pagar' OR lf.tipo = 'despesa') AND (lf.status = 'quitado' OR lf.status = 'pago') THEN -lf.valor_total
                        ELSE 0
                    END) AS saldo_real,
                    SUM(CASE
                        WHEN (lf.tipo = 'receber' OR lf.tipo = 'receita') AND lf.status IN ('previsto', 'aberto', 'pendente', 'parcial') THEN lf.valor_total
                        WHEN (lf.tipo = 'pagar' OR lf.tipo = 'despesa') AND lf.status IN ('previsto', 'aberto', 'pendente', 'parcial') THEN -lf.valor_total
                        ELSE 0
                    END) AS saldo_previsto
                FROM lancamentos_financeiros lf
                WHERE lf.conta_bancaria_id = ?
            ");
            $st->execute([$contaId]);
            $totals = $st->fetch(PDO::FETCH_ASSOC) ?: [];

            $saldoReal = $saldoInicial + (float) ($totals['saldo_real'] ?? 0);
            $saldoPrevisto = $saldoReal + (float) ($totals['saldo_previsto'] ?? 0);

            return [
                'saldo_inicial' => $saldoInicial,
                'saldo_real' => $saldoReal,
                'saldo_previsto' => $saldoPrevisto
            ];
        } catch (Throwable $e) {
            logError('Erro ao calcular saldo de conta bancaria: ' . $e->getMessage());
            return ['saldo_inicial' => 0.0, 'saldo_real' => 0.0, 'saldo_previsto' => 0.0];
        }
    }

    public function listar(array $filtros = []): array
    {
        try {
            $sql = "SELECT * FROM contas_bancarias WHERE 1=1";
            $params = [];

            if (isset($filtros['ativo'])) {
                $sql .= " AND ativo = ?";
                $params[] = (int) $filtros['ativo'];
            }
            if (!empty($filtros['tipo_conta'])) {
                $sql .= " AND tipo_conta = ?";
                $params[] = (string) $filtros['tipo_conta'];
            }
            if (!empty($filtros['buscar'])) {
                $sql .= " AND (nome_conta LIKE ? OR banco LIKE ?)";
                $term = '%' . (string) $filtros['buscar'] . '%';
                $params[] = $term;
                $params[] = $term;
            }

            $sql .= " ORDER BY nome_conta ASC";
            $st = $this->db->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                $saldos = $this->calcularSaldoAtual((int) $row['id']);
                $row['saldo_real'] = $saldos['saldo_real'];
                $row['saldo_previsto'] = $saldos['saldo_previsto'];
            }

            return ['success' => true, 'data' => $rows];
        } catch (Throwable $e) {
            logError('Erro ao listar contas bancarias: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar contas bancarias'];
        }
    }

    public function obter(int $id): array
    {
        try {
            if ($id <= 0) {
                return ['success' => false, 'message' => 'ID obrigatorio'];
            }

            $st = $this->db->prepare("SELECT * FROM contas_bancarias WHERE id = ?");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'message' => 'Conta bancaria nao encontrada'];
            }
            $saldos = $this->calcularSaldoAtual($id);
            $row['saldo_real'] = $saldos['saldo_real'];
            $row['saldo_previsto'] = $saldos['saldo_previsto'];
            return ['success' => true, 'data' => $row];
        } catch (Throwable $e) {
            logError('Erro ao obter conta bancaria: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter conta bancaria'];
        }
    }

    public function criar(array $dados): array
    {
        try {
            $nome = trim((string) ($dados['nome_conta'] ?? ''));
            $banco = trim((string) ($dados['banco'] ?? ''));
            $tipo = strtolower(trim((string) ($dados['tipo_conta'] ?? '')));
            if ($nome === '' || $banco === '' || $tipo === '') {
                return ['success' => false, 'message' => 'Nome da conta, banco e tipo sao obrigatorios'];
            }
            if (!in_array($tipo, ['corrente', 'investimento', 'caixa'], true)) {
                return ['success' => false, 'message' => 'Tipo de conta invalido'];
            }

            $dataSaldoInicial = trim((string) ($dados['data_saldo_inicial'] ?? ''));
            if (!$this->dataValida($dataSaldoInicial)) {
                return ['success' => false, 'message' => 'Data do saldo inicial invalida'];
            }

            $agencia = trim((string) ($dados['agencia'] ?? ''));
            $numeroConta = trim((string) ($dados['numero_conta'] ?? ''));
            $observacoes = trim((string) ($dados['observacoes'] ?? ''));
            $saldoInicial = isset($dados['saldo_inicial']) ? (float) $dados['saldo_inicial'] : 0.0;
            $ativo = isset($dados['ativo']) && (int) $dados['ativo'] === 0 ? 0 : 1;

            $st = $this->db->prepare("INSERT INTO contas_bancarias
                (nome_conta, banco, tipo_conta, agencia, numero_conta, saldo_inicial, data_saldo_inicial, observacoes, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $st->execute([
                sanitizeInput($nome),
                sanitizeInput($banco),
                $tipo,
                $agencia !== '' ? sanitizeInput($agencia) : null,
                $numeroConta !== '' ? sanitizeInput($numeroConta) : null,
                $saldoInicial,
                $dataSaldoInicial,
                $observacoes !== '' ? sanitizeInput($observacoes) : null,
                $ativo
            ]);

            return [
                'success' => true,
                'message' => 'Conta bancaria criada com sucesso',
                'data' => ['id' => (int) $this->db->lastInsertId()]
            ];
        } catch (Throwable $e) {
            logError('Erro ao criar conta bancaria: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao criar conta bancaria'];
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

            if (isset($dados['nome_conta'])) {
                $nome = trim((string) $dados['nome_conta']);
                if ($nome === '') {
                    return ['success' => false, 'message' => 'Nome da conta e obrigatorio'];
                }
                $campos[] = "nome_conta = ?";
                $valores[] = sanitizeInput($nome);
            }
            if (isset($dados['banco'])) {
                $banco = trim((string) $dados['banco']);
                if ($banco === '') {
                    return ['success' => false, 'message' => 'Banco e obrigatorio'];
                }
                $campos[] = "banco = ?";
                $valores[] = sanitizeInput($banco);
            }
            if (isset($dados['tipo_conta'])) {
                $tipo = strtolower(trim((string) $dados['tipo_conta']));
                if (!in_array($tipo, ['corrente', 'investimento', 'caixa'], true)) {
                    return ['success' => false, 'message' => 'Tipo de conta invalido'];
                }
                $campos[] = "tipo_conta = ?";
                $valores[] = $tipo;
            }
            if (array_key_exists('agencia', $dados)) {
                $agencia = trim((string) $dados['agencia']);
                $campos[] = "agencia = ?";
                $valores[] = $agencia !== '' ? sanitizeInput($agencia) : null;
            }
            if (array_key_exists('numero_conta', $dados)) {
                $numeroConta = trim((string) $dados['numero_conta']);
                $campos[] = "numero_conta = ?";
                $valores[] = $numeroConta !== '' ? sanitizeInput($numeroConta) : null;
            }
            if (isset($dados['saldo_inicial'])) {
                $campos[] = "saldo_inicial = ?";
                $valores[] = (float) $dados['saldo_inicial'];
            }
            if (isset($dados['data_saldo_inicial'])) {
                $dataSaldoInicial = trim((string) $dados['data_saldo_inicial']);
                if (!$this->dataValida($dataSaldoInicial)) {
                    return ['success' => false, 'message' => 'Data do saldo inicial invalida'];
                }
                $campos[] = "data_saldo_inicial = ?";
                $valores[] = $dataSaldoInicial;
            }
            if (array_key_exists('observacoes', $dados)) {
                $obs = trim((string) $dados['observacoes']);
                $campos[] = "observacoes = ?";
                $valores[] = $obs !== '' ? sanitizeInput($obs) : null;
            }
            if (isset($dados['ativo'])) {
                $campos[] = "ativo = ?";
                $valores[] = ((int) $dados['ativo'] === 0) ? 0 : 1;
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $valores[] = $id;
            $sql = "UPDATE contas_bancarias SET " . implode(', ', $campos) . " WHERE id = ?";
            $st = $this->db->prepare($sql);
            $st->execute($valores);

            return ['success' => true, 'message' => 'Conta bancaria atualizada com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao atualizar conta bancaria: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar conta bancaria'];
        }
    }

    public function excluir(int $id): array
    {
        try {
            $atual = $this->obter($id);
            if (empty($atual['success'])) {
                return $atual;
            }

            $st = $this->db->prepare("SELECT COUNT(*) AS total FROM lancamentos_financeiros WHERE conta_bancaria_id = ?");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
            if ((int) ($row['total'] ?? 0) > 0) {
                return ['success' => false, 'message' => 'Nao e possivel excluir conta com lancamentos vinculados'];
            }

            $st = $this->db->prepare("DELETE FROM contas_bancarias WHERE id = ?");
            $st->execute([$id]);
            return ['success' => true, 'message' => 'Conta bancaria excluida com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao excluir conta bancaria: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao excluir conta bancaria'];
        }
    }

    public function calcularSaldo(int $id): array
    {
        $atual = $this->obter($id);
        if (empty($atual['success'])) {
            return $atual;
        }
        return ['success' => true, 'data' => $this->calcularSaldoAtual($id)];
    }
}

$auth = financeiro_require_auth('contas_bancarias');
$api = new ContasBancariasAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            financeiro_response($api->listar([
                'ativo' => isset($_GET['ativo']) ? (int) $_GET['ativo'] : null,
                'tipo_conta' => $_GET['tipo_conta'] ?? null,
                'buscar' => $_GET['buscar'] ?? null,
            ]));
            break;
        case 'obter':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            financeiro_response($api->obter($id));
            break;
        case 'calcular_saldo':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            financeiro_response($api->calcularSaldo($id));
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
                financeiro_audit($auth, 'create', 'contas_bancarias', $entityId, null, $after, ['action' => 'criar']);
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
                financeiro_audit($auth, 'update', 'contas_bancarias', $id, $before, $after, ['action' => 'atualizar']);
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
                financeiro_audit($auth, 'delete', 'contas_bancarias', $id, $before, null, ['action' => 'excluir']);
            }
            financeiro_response($result);
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao invalida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Metodo nao permitido'], 405);
