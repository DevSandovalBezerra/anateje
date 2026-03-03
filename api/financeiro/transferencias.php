<?php
// ANATEJE - API de TransferÃªncias entre Contas
// Sistema de Gestao Financeira Associativa ANATEJE
// Cada transferÃªncia gera dois lanÃ§amentos vinculados conforme PRD
//
// NOTA: Esta API estÃ¡ sendo mantida para compatibilidade.
// Para novas implementaÃ§Ãµes, use api/financeiro/lancamentos.php com tipo_semantico='transferencia'

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/unidade_helper.php';

require_once __DIR__ . '/_bootstrap.php';
class TransferenciasAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    private function registrarAuditoria($usuario_id, $entidade, $entidade_id, $acao, $dados_anteriores = null, $dados_novos = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO auditoria_financeira 
                (usuario_id, entidade, entidade_id, acao, dados_anteriores, dados_novos, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $usuario_id,
                $entidade,
                $entidade_id,
                $acao,
                $dados_anteriores ? json_encode($dados_anteriores) : null,
                $dados_novos ? json_encode($dados_novos) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            logError("Erro ao registrar auditoria: " . $e->getMessage());
        }
    }

    public function listar($filtros = [])
    {
        try {
            $sql = "
                SELECT t.*,
                       co.nome_conta as conta_origem_nome,
                       cd.nome_conta as conta_destino_nome,
                       ls.titulo as lancamento_saida_titulo,
                       le.titulo as lancamento_entrada_titulo
                FROM transferencias_contas t
                JOIN contas_bancarias co ON t.conta_origem_id = co.id
                JOIN contas_bancarias cd ON t.conta_destino_id = cd.id
                LEFT JOIN lancamentos_financeiros ls ON t.lancamento_saida_id = ls.id
                LEFT JOIN lancamentos_financeiros le ON t.lancamento_entrada_id = le.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($filtros['conta_origem_id'])) {
                $sql .= " AND t.conta_origem_id = ?";
                $params[] = (int)$filtros['conta_origem_id'];
            }

            if (!empty($filtros['conta_destino_id'])) {
                $sql .= " AND t.conta_destino_id = ?";
                $params[] = (int)$filtros['conta_destino_id'];
            }

            if (!empty($filtros['data_inicio'])) {
                $sql .= " AND t.data_transferencia >= ?";
                $params[] = $filtros['data_inicio'];
            }

            if (!empty($filtros['data_fim'])) {
                $sql .= " AND t.data_transferencia <= ?";
                $params[] = $filtros['data_fim'];
            }

            $sql .= " ORDER BY t.data_transferencia DESC, t.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $transferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $transferencias];
        } catch (Exception $e) {
            logError("Erro ao listar transferÃªncias: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar transferÃªncias'];
        }
    }

    public function obter($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*,
                       co.nome_conta as conta_origem_nome,
                       co.banco as conta_origem_banco,
                       cd.nome_conta as conta_destino_nome,
                       cd.banco as conta_destino_banco,
                       ls.* as lancamento_saida,
                       le.* as lancamento_entrada
                FROM transferencias_contas t
                JOIN contas_bancarias co ON t.conta_origem_id = co.id
                JOIN contas_bancarias cd ON t.conta_destino_id = cd.id
                LEFT JOIN lancamentos_financeiros ls ON t.lancamento_saida_id = ls.id
                LEFT JOIN lancamentos_financeiros le ON t.lancamento_entrada_id = le.id
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            $transferencia = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transferencia) {
                return ['success' => false, 'message' => 'TransferÃªncia nÃ£o encontrada'];
            }

            return ['success' => true, 'data' => $transferencia];
        } catch (Exception $e) {
            logError("Erro ao obter transferÃªncia: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter transferÃªncia'];
        }
    }

    public function criar($dados)
    {
        try {
            if (empty($dados['conta_origem_id']) || empty($dados['conta_destino_id']) || empty($dados['valor']) || empty($dados['data_transferencia'])) {
                return ['success' => false, 'message' => 'Conta origem, conta destino, valor e data sÃ£o obrigatÃ³rios'];
            }

            if ($dados['conta_origem_id'] == $dados['conta_destino_id']) {
                return ['success' => false, 'message' => 'Conta origem e destino nÃ£o podem ser iguais'];
            }

            $conta_origem_id = (int)$dados['conta_origem_id'];
            $conta_destino_id = (int)$dados['conta_destino_id'];
            $valor = (float)$dados['valor'];
            $data_transferencia = $dados['data_transferencia'];

            if ($valor <= 0) {
                return ['success' => false, 'message' => 'Valor deve ser maior que zero'];
            }

            $stmt = $this->db->prepare("SELECT * FROM contas_bancarias WHERE id = ?");
            $stmt->execute([$conta_origem_id]);
            $conta_origem = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conta_origem || $conta_origem['ativo'] != 1) {
                return ['success' => false, 'message' => 'Conta origem nÃ£o encontrada ou inativa'];
            }

            $stmt = $this->db->prepare("SELECT * FROM contas_bancarias WHERE id = ?");
            $stmt->execute([$conta_destino_id]);
            $conta_destino = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conta_destino || $conta_destino['ativo'] != 1) {
                return ['success' => false, 'message' => 'Conta destino nÃ£o encontrada ou inativa'];
            }

            $usuario_id = $_SESSION['user_id'] ?? null;
            $unidadeSessao = getUserUnidadeId();
            $titulo = !empty($dados['titulo']) ? sanitizeInput($dados['titulo']) : 'TransferÃªncia entre contas';

            $this->db->beginTransaction();

            $tipo_transferencia = $dados['tipo'] ?? 'aplicacao';
            if ($conta_origem['tipo_conta'] == 'corrente' && $conta_destino['tipo_conta'] == 'investimento') {
                $tipo_transferencia = 'aplicacao';
            } elseif ($conta_origem['tipo_conta'] == 'investimento' && $conta_destino['tipo_conta'] == 'corrente') {
                $tipo_transferencia = 'resgate';
            }

            $descricao_saida = $titulo . ' - SaÃ­da';
            $descricao_entrada = $titulo . ' - Entrada';

            $stmt = $this->db->prepare("
                INSERT INTO lancamentos_financeiros 
                (unidade_id, tipo, titulo, descricao, valor_total, data_emissao, data_vencimento, status, 
                 origem, conta_bancaria_id)
                VALUES (?, 'pagar', ?, ?, ?, ?, ?, 'quitado', 'transferencia', ?)
            ");

            $stmt->execute([
                $unidadeSessao,
                $descricao_saida,
                $descricao_saida,
                $valor,
                $data_transferencia,
                $data_transferencia,
                $conta_origem_id
            ]);

            $lancamento_saida_id = (int)$this->db->lastInsertId();

            $stmt = $this->db->prepare("
                INSERT INTO lancamentos_financeiros 
                (unidade_id, tipo, titulo, descricao, valor_total, data_emissao, data_vencimento, status, 
                 origem, conta_bancaria_id)
                VALUES (?, 'receber', ?, ?, ?, ?, ?, 'quitado', 'transferencia', ?)
            ");

            $stmt->execute([
                $unidadeSessao,
                $descricao_entrada,
                $descricao_entrada,
                $valor,
                $data_transferencia,
                $data_transferencia,
                $conta_destino_id
            ]);

            $lancamento_entrada_id = (int)$this->db->lastInsertId();

            $transferencia_id = null;
            $stmt = $this->db->prepare("
                INSERT INTO transferencias_contas 
                (titulo, conta_origem_id, conta_destino_id, valor, data_transferencia, 
                 lancamento_saida_id, lancamento_entrada_id, observacoes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $titulo,
                $conta_origem_id,
                $conta_destino_id,
                $valor,
                $data_transferencia,
                $lancamento_saida_id,
                $lancamento_entrada_id,
                !empty($dados['observacoes']) ? sanitizeInput($dados['observacoes']) : null,
                $usuario_id
            ]);

            $transferencia_id = (int)$this->db->lastInsertId();

            $stmt = $this->db->prepare("UPDATE lancamentos_financeiros SET transferencia_id = ? WHERE id IN (?, ?)");
            $stmt->execute([$transferencia_id, $lancamento_saida_id, $lancamento_entrada_id]);

            if ($usuario_id) {
                $this->registrarAuditoria($usuario_id, 'transferencias_contas', $transferencia_id, 'create', null, $dados);
            }

            $this->db->commit();
            return [
                'success' => true,
                'message' => 'TransferÃªncia realizada com sucesso',
                'id' => $transferencia_id,
                'lancamento_saida_id' => $lancamento_saida_id,
                'lancamento_entrada_id' => $lancamento_entrada_id
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Erro ao criar transferÃªncia: " . $e->getMessage(), ['dados' => $dados]);
            return ['success' => false, 'message' => 'Erro ao realizar transferÃªncia'];
        }
    }

    public function excluir($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM transferencias_contas WHERE id = ?");
            $stmt->execute([$id]);
            $transferencia = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transferencia) {
                return ['success' => false, 'message' => 'TransferÃªncia nÃ£o encontrada'];
            }

            $usuario_id = $_SESSION['user_id'] ?? null;

            $this->db->beginTransaction();

            if ($transferencia['lancamento_saida_id']) {
                $stmt = $this->db->prepare("DELETE FROM lancamentos_financeiros WHERE id = ?");
                $stmt->execute([$transferencia['lancamento_saida_id']]);
            }

            if ($transferencia['lancamento_entrada_id']) {
                $stmt = $this->db->prepare("DELETE FROM lancamentos_financeiros WHERE id = ?");
                $stmt->execute([$transferencia['lancamento_entrada_id']]);
            }

            $stmt = $this->db->prepare("DELETE FROM transferencias_contas WHERE id = ?");
            $stmt->execute([$id]);

            if ($usuario_id) {
                $this->registrarAuditoria($usuario_id, 'transferencias_contas', $id, 'delete', $transferencia, null);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'TransferÃªncia excluÃ­da com sucesso'];
        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Erro ao excluir transferÃªncia: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao excluir transferÃªncia'];
        }
    }
}

$auth = financeiro_require_auth('transferencias');

$api = new TransferenciasAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            $filtros = [
                'conta_origem_id' => $_GET['conta_origem_id'] ?? null,
                'conta_destino_id' => $_GET['conta_destino_id'] ?? null,
                'data_inicio' => $_GET['data_inicio'] ?? null,
                'data_fim' => $_GET['data_fim'] ?? null,
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



