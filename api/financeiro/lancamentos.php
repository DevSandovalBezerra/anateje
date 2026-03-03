<?php
// ANATEJE - API de LanÃ§amentos Financeiros Completa
// Sistema de Gestao Financeira Associativa ANATEJE
// API completa conforme PRD, compatÃ­vel com sistema existente

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/unidade_helper.php';

require_once __DIR__ . '/_bootstrap.php';
class LancamentosAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    private function registrarAuditoria($usuario_id, $entidade, $entidade_id, $acao, $dados_anteriores = null, $dados_novos = null)
    {
        financeiro_audit(
            ['sub' => (int) $usuario_id],
            (string) $acao,
            (string) $entidade,
            (int) $entidade_id,
            $dados_anteriores,
            $dados_novos
        );
    }

    public function listar($filtros = [])
    {
        try {
            $unidadeSessao = getUserUnidadeId();
            $sql = "
                SELECT lf.*,
                       p.nome as pessoa_nome,
                       p.tipo as pessoa_tipo,
                       cb.nome_conta as conta_bancaria_nome,
                       cf.nome as categoria_nome,
                       cc.nome as centro_custo_nome,
                       (SELECT COUNT(*) FROM lancamento_parcelas lp WHERE lp.lancamento_id = lf.id) AS total_parcelas,
                       (SELECT COUNT(*) FROM lancamento_parcelas lp WHERE lp.lancamento_id = lf.id AND lp.status = 'paga') AS parcelas_pagas
                FROM lancamentos_financeiros lf
                LEFT JOIN pessoas p ON lf.pessoa_id = p.id
                LEFT JOIN contas_bancarias cb ON lf.conta_bancaria_id = cb.id
                LEFT JOIN categorias_financeiras cf ON lf.categoria_id = cf.id
                LEFT JOIN centros_custos cc ON lf.centro_custo_id = cc.id
                WHERE 1=1
            ";
            $params = [];

            if ($unidadeSessao !== null) {
                $sql .= " AND (lf.unidade_id = ? OR lf.unidade_id IS NULL)";
                $params[] = $unidadeSessao;
            } elseif (!empty($filtros['unidade_id'])) {
                $sql .= " AND lf.unidade_id = ?";
                $params[] = (int)$filtros['unidade_id'];
            }

            if (!empty($filtros['tipo_semantico'])) {
                $sql .= " AND lf.tipo_semantico = ?";
                $params[] = $filtros['tipo_semantico'];
            } elseif (!empty($filtros['tipo'])) {
                $tipo = $filtros['tipo'];
                if (in_array($tipo, ['receita', 'despesa', 'transferencia'])) {
                    $sql .= " AND lf.tipo_semantico = ?";
                    $params[] = $tipo;
                } else {
                    $sql .= " AND lf.tipo = ?";
                    $params[] = $tipo;
                }
            }

            if (!empty($filtros['status'])) {
                $sql .= " AND lf.status = ?";
                $params[] = $filtros['status'];
            }

            if (!empty($filtros['conta_bancaria_id'])) {
                $sql .= " AND lf.conta_bancaria_id = ?";
                $params[] = (int)$filtros['conta_bancaria_id'];
            }

            if (!empty($filtros['categoria_id'])) {
                $sql .= " AND lf.categoria_id = ?";
                $params[] = (int)$filtros['categoria_id'];
            }

            if (!empty($filtros['centro_custo_id'])) {
                $sql .= " AND lf.centro_custo_id = ?";
                $params[] = (int)$filtros['centro_custo_id'];
            }

            if (!empty($filtros['pessoa_id'])) {
                $sql .= " AND lf.pessoa_id = ?";
                $params[] = (int)$filtros['pessoa_id'];
            }

            if (!empty($filtros['origem'])) {
                $sql .= " AND lf.origem = ?";
                $params[] = $filtros['origem'];
            }

            if (!empty($filtros['data_inicio'])) {
                $sql .= " AND lf.data_vencimento >= ?";
                $params[] = $filtros['data_inicio'];
            }

            if (!empty($filtros['data_fim'])) {
                $sql .= " AND lf.data_vencimento <= ?";
                $params[] = $filtros['data_fim'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (lf.titulo LIKE ? OR lf.descricao LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY lf.data_vencimento IS NULL, lf.data_vencimento ASC, lf.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $lancamentos];
        } catch (Exception $e) {
            logError("Erro ao listar lanÃ§amentos: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar lanÃ§amentos'];
        }
    }

    public function obter($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT lf.*,
                       p.nome as pessoa_nome,
                       p.tipo as pessoa_tipo,
                       cb.nome_conta as conta_bancaria_nome,
                       cf.nome as categoria_nome,
                       cc.nome as centro_custo_nome
                FROM lancamentos_financeiros lf
                LEFT JOIN pessoas p ON lf.pessoa_id = p.id
                LEFT JOIN contas_bancarias cb ON lf.conta_bancaria_id = cb.id
                LEFT JOIN categorias_financeiras cf ON lf.categoria_id = cf.id
                LEFT JOIN centros_custos cc ON lf.centro_custo_id = cc.id
                WHERE lf.id = ?
            ");
            $stmt->execute([$id]);
            $lancamento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lancamento) {
                return ['success' => false, 'message' => 'LanÃ§amento nÃ£o encontrado'];
            }

            $stmt = $this->db->prepare("SELECT * FROM lancamento_parcelas WHERE lancamento_id = ? ORDER BY numero_parcela ASC");
            $stmt->execute([$id]);
            $parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $lancamento['parcelas'] = $parcelas;

            if ($lancamento['tipo_semantico'] == 'transferencia' && !empty($lancamento['transferencia_id'])) {
                $stmt = $this->db->prepare("
                    SELECT tc.*, 
                           cb_origem.nome_conta as conta_origem_nome,
                           cb_destino.nome_conta as conta_destino_nome
                    FROM transferencias_contas tc
                    LEFT JOIN contas_bancarias cb_origem ON tc.conta_origem_id = cb_origem.id
                    LEFT JOIN contas_bancarias cb_destino ON tc.conta_destino_id = cb_destino.id
                    WHERE tc.id = ?
                ");
                $stmt->execute([$lancamento['transferencia_id']]);
                $transferencia = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($transferencia) {
                    $lancamento['transferencia'] = $transferencia;
                    
                    $lancamento_vinculado_id = ($lancamento['id'] == $transferencia['lancamento_saida_id']) 
                        ? $transferencia['lancamento_entrada_id'] 
                        : $transferencia['lancamento_saida_id'];
                    
                    $stmt = $this->db->prepare("
                        SELECT lf.*, cb.nome_conta as conta_bancaria_nome
                        FROM lancamentos_financeiros lf
                        LEFT JOIN contas_bancarias cb ON lf.conta_bancaria_id = cb.id
                        WHERE lf.id = ?
                    ");
                    $stmt->execute([$lancamento_vinculado_id]);
                    $lancamento_vinculado = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($lancamento_vinculado) {
                        $lancamento['lancamento_vinculado'] = $lancamento_vinculado;
                    }
                }
            }

            return ['success' => true, 'data' => $lancamento];
        } catch (Exception $e) {
            logError("Erro ao obter lanÃ§amento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter lanÃ§amento'];
        }
    }

    private function gerarParcelas($lancamento_id, $valor_total, $qtd_parcelas, $primeiro_vencimento, $periodicidade = 'mensal')
    {
        if ($qtd_parcelas <= 1) {
            return;
        }

        $valor_parcela_base = floor($valor_total * 100 / $qtd_parcelas) / 100;
        $restante = round($valor_total - ($valor_parcela_base * $qtd_parcelas), 2);

        $data = new DateTime($primeiro_vencimento);
        $incremento = $this->getPeriodicidadeIncremento($periodicidade);

        for ($i = 1; $i <= $qtd_parcelas; $i++) {
            $valor_parcela = $valor_parcela_base;
            if ($restante > 0) {
                $valor_parcela = round($valor_parcela + 0.01, 2);
                $restante = round($restante - 0.01, 2);
            }

            $stmt = $this->db->prepare("
                INSERT INTO lancamento_parcelas 
                (lancamento_id, numero_parcela, valor_parcela, data_vencimento, status)
                VALUES (?, ?, ?, ?, 'pendente')
            ");
            $stmt->execute([$lancamento_id, $i, $valor_parcela, $data->format('Y-m-d')]);

            $data->modify($incremento);
        }
    }

    private function getPeriodicidadeIncremento($periodicidade)
    {
        $incrementos = [
            'mensal' => '+1 month',
            'bimestral' => '+2 months',
            'trimestral' => '+3 months',
            'semestral' => '+6 months',
            'anual' => '+1 year',
            'quinzenal' => '+15 days',
            'semanal' => '+1 week'
        ];
        return $incrementos[$periodicidade] ?? '+1 month';
    }

    public function criar($dados)
    {
        try {
            $tipo_semantico = $dados['tipo_semantico'] ?? null;
            $tipo_legado = $dados['tipo'] ?? null;
            
            if (!$tipo_semantico && $tipo_legado) {
                if (in_array($tipo_legado, ['receita', 'despesa'])) {
                    $tipo_semantico = $tipo_legado;
                } elseif ($tipo_legado == 'receber') {
                    $tipo_semantico = 'receita';
                } elseif ($tipo_legado == 'pagar') {
                    $tipo_semantico = 'despesa';
                }
            }
            
            if (!$tipo_semantico || !in_array($tipo_semantico, ['receita', 'despesa', 'transferencia'])) {
                return ['success' => false, 'message' => 'Tipo semÃ¢ntico invÃ¡lido. Use: receita, despesa ou transferencia'];
            }

            if ($tipo_semantico == 'transferencia') {
                return $this->criarTransferencia($dados);
            }

            if (empty($dados['descricao']) || empty($dados['valor_total'])) {
                return ['success' => false, 'message' => 'DescriÃ§Ã£o e valor sÃ£o obrigatÃ³rios'];
            }

            $valor_total = (float)$dados['valor_total'];
            if ($valor_total <= 0) {
                return ['success' => false, 'message' => 'Valor deve ser maior que zero'];
            }

            $unidadeSessao = getUserUnidadeId();
            $usuario_id = $_SESSION['user_id'] ?? null;

            $this->db->beginTransaction();

            $tipo_banco = ($tipo_semantico == 'receita') ? 'receber' : 'pagar';
            
            $titulo = !empty($dados['titulo']) ? sanitizeInput($dados['titulo']) : sanitizeInput($dados['descricao']);
            $descricao = sanitizeInput($dados['descricao']);
            $data_emissao = $dados['data_emissao'] ?? date('Y-m-d');
            $data_vencimento = $dados['data_vencimento'] ?? null;
            $origem = $dados['origem'] ?? 'manual';
            $origem_id = !empty($dados['origem_id']) ? (int)$dados['origem_id'] : null;

            $status = 'previsto';
            if (!empty($dados['status']) && in_array($dados['status'], ['previsto', 'aberto', 'pendente'])) {
                $status = $dados['status'];
            } elseif (empty($dados['status'])) {
                $status = $data_vencimento && $data_vencimento <= date('Y-m-d') ? 'aberto' : 'previsto';
            }

            $stmt = $this->db->prepare("
                INSERT INTO lancamentos_financeiros 
                (unidade_id, tipo, tipo_semantico, titulo, descricao, valor_total, data_emissao, data_vencimento, status, 
                 origem, origem_id, pessoa_id, conta_bancaria_id, categoria_id, centro_custo_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $unidadeSessao,
                $tipo_banco,
                $tipo_semantico,
                $titulo,
                $descricao,
                $valor_total,
                $data_emissao,
                $data_vencimento,
                $status,
                $origem,
                $origem_id,
                !empty($dados['pessoa_id']) ? (int)$dados['pessoa_id'] : null,
                !empty($dados['conta_bancaria_id']) ? (int)$dados['conta_bancaria_id'] : null,
                !empty($dados['categoria_id']) ? (int)$dados['categoria_id'] : null,
                !empty($dados['centro_custo_id']) ? (int)$dados['centro_custo_id'] : null
            ]);

            $lancamento_id = (int)$this->db->lastInsertId();

            $qtd_parcelas = isset($dados['qtd_parcelas']) ? (int)$dados['qtd_parcelas'] : 1;
            $periodicidade = $dados['periodicidade'] ?? 'mensal';
            $primeiro_vencimento = $dados['primeiro_vencimento'] ?? $data_vencimento ?? date('Y-m-d');

            if ($qtd_parcelas > 1) {
                $this->gerarParcelas($lancamento_id, $valor_total, $qtd_parcelas, $primeiro_vencimento, $periodicidade);
            }

            if ($usuario_id) {
                $this->registrarAuditoria($usuario_id, 'lancamentos_financeiros', $lancamento_id, 'create', null, $dados);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'LanÃ§amento criado com sucesso', 'id' => $lancamento_id];
        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Erro ao criar lanÃ§amento: " . $e->getMessage(), ['dados' => $dados]);
            return ['success' => false, 'message' => 'Erro ao criar lanÃ§amento'];
        }
    }

    private function criarTransferencia($dados)
    {
        try {
            if (empty($dados['conta_origem_id']) || empty($dados['conta_destino_id']) || empty($dados['valor_total'])) {
                return ['success' => false, 'message' => 'Conta origem, conta destino e valor sÃ£o obrigatÃ³rios'];
            }

            if ($dados['conta_origem_id'] == $dados['conta_destino_id']) {
                return ['success' => false, 'message' => 'Conta origem e destino nÃ£o podem ser iguais'];
            }

            $conta_origem_id = (int)$dados['conta_origem_id'];
            $conta_destino_id = (int)$dados['conta_destino_id'];
            $valor = (float)$dados['valor_total'];
            $data_transferencia = $dados['data_vencimento'] ?? $dados['data_transferencia'] ?? date('Y-m-d');

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

            $descricao_saida = $titulo . ' - SaÃ­da';
            $descricao_entrada = $titulo . ' - Entrada';

            $stmt = $this->db->prepare("
                INSERT INTO lancamentos_financeiros 
                (unidade_id, tipo, tipo_semantico, titulo, descricao, valor_total, data_emissao, data_vencimento, status, 
                 origem, conta_bancaria_id)
                VALUES (?, 'pagar', 'transferencia', ?, ?, ?, ?, ?, 'quitado', 'transferencia', ?)
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
                (unidade_id, tipo, tipo_semantico, titulo, descricao, valor_total, data_emissao, data_vencimento, status, 
                 origem, conta_bancaria_id)
                VALUES (?, 'receber', 'transferencia', ?, ?, ?, ?, ?, 'quitado', 'transferencia', ?)
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
            if (isset($dados['criar_registro_transferencia']) && $dados['criar_registro_transferencia']) {
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
            }

            if ($usuario_id) {
                $this->registrarAuditoria($usuario_id, 'lancamentos_financeiros', $lancamento_saida_id, 'create', null, $dados);
                $this->registrarAuditoria($usuario_id, 'lancamentos_financeiros', $lancamento_entrada_id, 'create', null, $dados);
            }

            return [
                'success' => true,
                'message' => 'TransferÃªncia realizada com sucesso',
                'id' => $lancamento_saida_id,
                'transferencia_id' => $transferencia_id,
                'lancamento_saida_id' => $lancamento_saida_id,
                'lancamento_entrada_id' => $lancamento_entrada_id
            ];
        } catch (Exception $e) {
            logError("Erro ao criar transferÃªncia: " . $e->getMessage(), ['dados' => $dados]);
            return ['success' => false, 'message' => 'Erro ao realizar transferÃªncia'];
        }
    }

    public function atualizar($id, $dados)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM lancamentos_financeiros WHERE id = ?");
            $stmt->execute([$id]);
            $lancamento_antigo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lancamento_antigo) {
                return ['success' => false, 'message' => 'LanÃ§amento nÃ£o encontrado'];
            }

            if (in_array($lancamento_antigo['status'], ['quitado', 'pago']) && empty($dados['permitir_alteracao_quitado'])) {
                return ['success' => false, 'message' => 'NÃ£o Ã© possÃ­vel alterar lanÃ§amento quitado'];
            }

            if ($lancamento_antigo['tipo_semantico'] == 'transferencia') {
                return ['success' => false, 'message' => 'TransferÃªncias nÃ£o podem ser alteradas diretamente. Use a funcionalidade de transferÃªncias.'];
            }

            $usuario_id = $_SESSION['user_id'] ?? null;
            $campos = [];
            $params = [];

            if (isset($dados['tipo_semantico'])) {
                $tipo_semantico = $dados['tipo_semantico'];
                if (!in_array($tipo_semantico, ['receita', 'despesa'])) {
                    return ['success' => false, 'message' => 'Tipo semÃ¢ntico invÃ¡lido para atualizaÃ§Ã£o'];
                }
                $campos[] = "tipo_semantico = ?";
                $params[] = $tipo_semantico;
                
                $tipo_banco = ($tipo_semantico == 'receita') ? 'receber' : 'pagar';
                $campos[] = "tipo = ?";
                $params[] = $tipo_banco;
            }

            if (isset($dados['titulo'])) {
                $campos[] = "titulo = ?";
                $params[] = sanitizeInput($dados['titulo']);
            }

            if (isset($dados['descricao'])) {
                $campos[] = "descricao = ?";
                $params[] = sanitizeInput($dados['descricao']);
            }

            if (isset($dados['valor_total'])) {
                $campos[] = "valor_total = ?";
                $params[] = (float)$dados['valor_total'];
            }

            if (isset($dados['data_vencimento'])) {
                $campos[] = "data_vencimento = ?";
                $params[] = $dados['data_vencimento'];
            }

            if (isset($dados['status'])) {
                $campos[] = "status = ?";
                $params[] = $dados['status'];
            }

            if (isset($dados['pessoa_id'])) {
                $campos[] = "pessoa_id = ?";
                $params[] = !empty($dados['pessoa_id']) ? (int)$dados['pessoa_id'] : null;
            }

            if (isset($dados['conta_bancaria_id'])) {
                $campos[] = "conta_bancaria_id = ?";
                $params[] = !empty($dados['conta_bancaria_id']) ? (int)$dados['conta_bancaria_id'] : null;
            }

            if (isset($dados['categoria_id'])) {
                $campos[] = "categoria_id = ?";
                $params[] = !empty($dados['categoria_id']) ? (int)$dados['categoria_id'] : null;
            }

            if (isset($dados['centro_custo_id'])) {
                $campos[] = "centro_custo_id = ?";
                $params[] = !empty($dados['centro_custo_id']) ? (int)$dados['centro_custo_id'] : null;
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $params[] = $id;
            $sql = "UPDATE lancamentos_financeiros SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if ($usuario_id) {
                $stmt = $this->db->prepare("SELECT * FROM lancamentos_financeiros WHERE id = ?");
                $stmt->execute([$id]);
                $lancamento_novo = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->registrarAuditoria($usuario_id, 'lancamentos_financeiros', $id, 'update', $lancamento_antigo, $lancamento_novo);
            }

            return ['success' => true, 'message' => 'LanÃ§amento atualizado com sucesso'];
        } catch (Exception $e) {
            logError("Erro ao atualizar lanÃ§amento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar lanÃ§amento'];
        }
    }

    public function excluir($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM lancamentos_financeiros WHERE id = ?");
            $stmt->execute([$id]);
            $lancamento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lancamento) {
                return ['success' => false, 'message' => 'LanÃ§amento nÃ£o encontrado'];
            }

            if (in_array($lancamento['status'], ['quitado', 'pago'])) {
                return ['success' => false, 'message' => 'NÃ£o Ã© possÃ­vel excluir lanÃ§amento quitado. Use cancelar.'];
            }

            $usuario_id = $_SESSION['user_id'] ?? null;

            $this->db->beginTransaction();

            if ($lancamento['tipo_semantico'] == 'transferencia' && !empty($lancamento['transferencia_id'])) {
                $stmt = $this->db->prepare("SELECT * FROM transferencias_contas WHERE id = ?");
                $stmt->execute([$lancamento['transferencia_id']]);
                $transferencia = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($transferencia) {
                    $lancamento_vinculado_id = ($id == $transferencia['lancamento_saida_id']) 
                        ? $transferencia['lancamento_entrada_id'] 
                        : $transferencia['lancamento_saida_id'];
                    
                    if ($lancamento_vinculado_id) {
                        $stmt = $this->db->prepare("DELETE FROM lancamento_parcelas WHERE lancamento_id = ?");
                        $stmt->execute([$lancamento_vinculado_id]);
                        
                        $stmt = $this->db->prepare("DELETE FROM lancamentos_financeiros WHERE id = ?");
                        $stmt->execute([$lancamento_vinculado_id]);
                        
                        if ($usuario_id) {
                            $stmt = $this->db->prepare("SELECT * FROM lancamentos_financeiros WHERE id = ?");
                            $stmt->execute([$lancamento_vinculado_id]);
                            $lancamento_vinculado = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($lancamento_vinculado) {
                                $this->registrarAuditoria($usuario_id, 'lancamentos_financeiros', $lancamento_vinculado_id, 'delete', $lancamento_vinculado, null);
                            }
                        }
                    }
                    
                    $stmt = $this->db->prepare("DELETE FROM transferencias_contas WHERE id = ?");
                    $stmt->execute([$lancamento['transferencia_id']]);
                }
            }

            $stmt = $this->db->prepare("DELETE FROM lancamento_parcelas WHERE lancamento_id = ?");
            $stmt->execute([$id]);

            $stmt = $this->db->prepare("DELETE FROM lancamentos_financeiros WHERE id = ?");
            $stmt->execute([$id]);

            if ($usuario_id) {
                $this->registrarAuditoria($usuario_id, 'lancamentos_financeiros', $id, 'delete', $lancamento, null);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'LanÃ§amento excluÃ­do com sucesso'];
        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Erro ao excluir lanÃ§amento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao excluir lanÃ§amento'];
        }
    }

    public function duplicar($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM lancamentos_financeiros WHERE id = ?");
            $stmt->execute([$id]);
            $original = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$original) {
                return ['success' => false, 'message' => 'LanÃ§amento nÃ£o encontrado'];
            }

            if ($original['tipo_semantico'] == 'transferencia') {
                return ['success' => false, 'message' => 'TransferÃªncias nÃ£o podem ser duplicadas'];
            }

            $dados = [
                'tipo_semantico' => $original['tipo_semantico'] ?? ($original['tipo'] == 'receber' ? 'receita' : 'despesa'),
                'titulo' => $original['titulo'] . ' (CÃ³pia)',
                'descricao' => $original['descricao'],
                'valor_total' => $original['valor_total'],
                'data_emissao' => date('Y-m-d'),
                'data_vencimento' => $original['data_vencimento'],
                'pessoa_id' => $original['pessoa_id'],
                'conta_bancaria_id' => $original['conta_bancaria_id'],
                'categoria_id' => $original['categoria_id'],
                'centro_custo_id' => $original['centro_custo_id'],
                'origem' => 'manual',
                'status' => 'previsto'
            ];

            return $this->criar($dados);
        } catch (Exception $e) {
            logError("Erro ao duplicar lanÃ§amento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao duplicar lanÃ§amento'];
        }
    }

    public function acoesMassa($ids, $acao, $dados = [])
    {
        try {
            if (empty($ids) || !is_array($ids)) {
                return ['success' => false, 'message' => 'IDs sÃ£o obrigatÃ³rios'];
            }

            $usuario_id = $_SESSION['user_id'] ?? null;
            $this->db->beginTransaction();

            $ids_limpos = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids_limpos), '?'));

            switch ($acao) {
                case 'marcar_quitado':
                    $data_pagamento = $dados['data_pagamento'] ?? date('Y-m-d');
                    $stmt = $this->db->prepare("
                        UPDATE lancamentos_financeiros 
                        SET status = 'quitado', updated_at = NOW()
                        WHERE id IN ($placeholders) AND status != 'cancelado'
                    ");
                    $stmt->execute($ids_limpos);

                    $stmt = $this->db->prepare("
                        UPDATE lancamento_parcelas 
                        SET status = 'paga', data_pagamento = ?, valor_pago = valor_parcela
                        WHERE lancamento_id IN ($placeholders) AND status = 'pendente'
                    ");
                    $stmt->execute(array_merge([$data_pagamento], $ids_limpos));
                    break;

                case 'definir_data_pagamento':
                    $data_pagamento = $dados['data_pagamento'] ?? null;
                    if (!$data_pagamento) {
                        throw new Exception('Data de pagamento Ã© obrigatÃ³ria');
                    }
                    $stmt = $this->db->prepare("
                        UPDATE lancamento_parcelas 
                        SET data_pagamento = ?
                        WHERE lancamento_id IN ($placeholders)
                    ");
                    $stmt->execute(array_merge([$data_pagamento], $ids_limpos));
                    break;

                case 'alterar_conta':
                    $conta_id = (int)($dados['conta_bancaria_id'] ?? 0);
                    if (!$conta_id) {
                        throw new Exception('Conta bancÃ¡ria Ã© obrigatÃ³ria');
                    }
                    $stmt = $this->db->prepare("
                        UPDATE lancamentos_financeiros 
                        SET conta_bancaria_id = ?
                        WHERE id IN ($placeholders)
                    ");
                    $stmt->execute(array_merge([$conta_id], $ids_limpos));
                    break;

                case 'cancelar':
                    $stmt = $this->db->prepare("
                        UPDATE lancamentos_financeiros 
                        SET status = 'cancelado'
                        WHERE id IN ($placeholders) AND status NOT IN ('quitado', 'pago')
                    ");
                    $stmt->execute($ids_limpos);

                    $stmt = $this->db->prepare("
                        UPDATE lancamento_parcelas 
                        SET status = 'cancelada'
                        WHERE lancamento_id IN ($placeholders) AND status != 'paga'
                    ");
                    $stmt->execute($ids_limpos);
                    break;

                default:
                    throw new Exception('AÃ§Ã£o invÃ¡lida');
            }

            if ($usuario_id) {
                foreach ($ids_limpos as $lancamento_id) {
                    $this->registrarAuditoria($usuario_id, 'lancamentos_financeiros', $lancamento_id, $acao, null, $dados);
                }
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'AÃ§Ã£o em massa executada com sucesso'];
        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Erro em aÃ§Ã£o em massa: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao executar aÃ§Ã£o em massa'];
        }
    }

    public function quitar($id, $dados = [])
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM lancamentos_financeiros WHERE id = ?");
            $stmt->execute([$id]);
            $lancamento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lancamento) {
                return ['success' => false, 'message' => 'LanÃ§amento nÃ£o encontrado'];
            }

            $usuario_id = $_SESSION['user_id'] ?? null;
            $data_pagamento = $dados['data_pagamento'] ?? date('Y-m-d');
            $valor_pago = isset($dados['valor_pago']) ? (float)$dados['valor_pago'] : $lancamento['valor_total'];

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE lancamentos_financeiros SET status = 'quitado' WHERE id = ?");
            $stmt->execute([$id]);

            $stmt = $this->db->prepare("
                UPDATE lancamento_parcelas 
                SET status = 'paga', data_pagamento = ?, valor_pago = valor_parcela
                WHERE lancamento_id = ? AND status = 'pendente'
            ");
            $stmt->execute([$data_pagamento, $id]);

            if ($usuario_id) {
                $this->registrarAuditoria($usuario_id, 'lancamentos_financeiros', $id, 'quitacao', $lancamento, ['data_pagamento' => $data_pagamento, 'valor_pago' => $valor_pago]);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'LanÃ§amento quitado com sucesso'];
        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Erro ao quitar lanÃ§amento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao quitar lanÃ§amento'];
        }
    }
}

$auth = financeiro_require_auth('lancamentos');

$api = new LancamentosAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            $filtros = [
                'tipo_semantico' => $_GET['tipo_semantico'] ?? null,
                'tipo' => $_GET['tipo'] ?? null,
                'status' => $_GET['status'] ?? null,
                'unidade_id' => $_GET['unidade_id'] ?? null,
                'conta_bancaria_id' => $_GET['conta_bancaria_id'] ?? null,
                'categoria_id' => $_GET['categoria_id'] ?? null,
                'centro_custo_id' => $_GET['centro_custo_id'] ?? null,
                'pessoa_id' => $_GET['pessoa_id'] ?? null,
                'origem' => $_GET['origem'] ?? null,
                'data_inicio' => $_GET['data_inicio'] ?? null,
                'data_fim' => $_GET['data_fim'] ?? null,
                'buscar' => $_GET['buscar'] ?? null,
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
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'criar':
            financeiro_response($api->criar($input));
            break;
        case 'atualizar':
            $id = (int)($input['id'] ?? 0);
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatÃ³rio'], 400);
            }
            financeiro_response($api->atualizar($id, $input));
            break;
        case 'excluir':
            $id = (int)($input['id'] ?? 0);
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatÃ³rio'], 400);
            }
            financeiro_response($api->excluir($id));
            break;
        case 'duplicar':
            $id = (int)($input['id'] ?? 0);
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatÃ³rio'], 400);
            }
            financeiro_response($api->duplicar($id));
            break;
        case 'acoes_massa':
            $ids = $input['ids'] ?? [];
            $acao = $input['acao'] ?? '';
            $dados = $input['dados'] ?? [];
            if (empty($ids) || empty($acao)) {
                financeiro_response(['success' => false, 'message' => 'IDs e aÃ§Ã£o sÃ£o obrigatÃ³rios'], 400);
            }
            financeiro_response($api->acoesMassa($ids, $acao, $dados));
            break;
        case 'quitar':
            $id = (int)($input['id'] ?? 0);
            if (!$id) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatÃ³rio'], 400);
            }
            financeiro_response($api->quitar($id, $input));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'AÃ§Ã£o invÃ¡lida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'MÃ©todo nÃ£o permitido'], 405);



