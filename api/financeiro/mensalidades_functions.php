<?php
// ANATEJE - FunÃ§Ã£o para Gerar Mensalidades
// Sistema de Gestao Financeira Associativa ANATEJE
// Substitui a stored procedure sp_gerar_mensalidades para hospedagem compartilhada

require_once __DIR__ . '/../config/database.php';

/**
 * Gera mensalidades para todos os contratos ativos de um mÃªs especÃ­fico
 * 
 * @param string $mes_referencia Formato YYYY-MM (ex: 2024-10)
 * @return array Resultado da operaÃ§Ã£o
 */
function gerarMensalidades($mes_referencia)
{
    try {
        $db = getDB();

        // Validar formato da data
        if (!preg_match('/^\d{4}-\d{2}$/', $mes_referencia)) {
            return [
                'success' => false,
                'message' => 'Formato de data invÃ¡lido. Use YYYY-MM (ex: 2024-10)'
            ];
        }

        // Calcular data de vencimento (primeiro dia do mÃªs seguinte)
        $data_vencimento = date('Y-m-01', strtotime($mes_referencia . '-01 +1 month'));

        // Buscar contratos ativos que ainda nÃ£o tÃªm mensalidade para o mÃªs
        $stmt = $db->prepare("
            SELECT c.id, c.valor_mensalidade
            FROM contratos c
            WHERE c.status = 'ativo' 
            AND NOT EXISTS (
                SELECT 1 FROM mensalidades m 
                WHERE m.contrato_id = c.id 
                AND m.referencia = ?
            )
        ");
        $stmt->execute([$mes_referencia]);
        $contratos = $stmt->fetchAll();

        if (empty($contratos)) {
            return [
                'success' => true,
                'message' => 'Nenhuma mensalidade nova para gerar',
                'geradas' => 0
            ];
        }

        $geradas = 0;
        $erros = [];

        // Gerar mensalidades para cada contrato
        foreach ($contratos as $contrato) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO mensalidades (contrato_id, referencia, valor, data_vencimento, status)
                    VALUES (?, ?, ?, ?, 'pendente')
                ");

                $stmt->execute([
                    $contrato['id'],
                    $mes_referencia,
                    $contrato['valor_mensalidade'],
                    $data_vencimento
                ]);

                $geradas++;
            } catch (Exception $e) {
                $erros[] = "Erro ao gerar mensalidade para contrato {$contrato['id']}: " . $e->getMessage();
            }
        }

        $resultado = [
            'success' => true,
            'message' => "Mensalidades geradas com sucesso",
            'geradas' => $geradas,
            'mes_referencia' => $mes_referencia,
            'data_vencimento' => $data_vencimento
        ];

        if (!empty($erros)) {
            $resultado['erros'] = $erros;
            $resultado['message'] = "Mensalidades geradas com alguns erros";
        }

        return $resultado;
    } catch (Exception $e) {
        logError("Erro ao gerar mensalidades: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro interno do servidor ao gerar mensalidades'
        ];
    }
}

/**
 * Gera mensalidades para o mÃªs atual
 * 
 * @return array Resultado da operaÃ§Ã£o
 */
function gerarMensalidadesMesAtual()
{
    $mes_atual = date('Y-m');
    return gerarMensalidades($mes_atual);
}

/**
 * Gera mensalidades para o prÃ³ximo mÃªs
 * 
 * @return array Resultado da operaÃ§Ã£o
 */
function gerarMensalidadesProximoMes()
{
    $proximo_mes = date('Y-m', strtotime('+1 month'));
    return gerarMensalidades($proximo_mes);
}

/**
 * Atualiza status das mensalidades vencidas
 * 
 * @return array Resultado da operaÃ§Ã£o
 */
function atualizarStatusMensalidadesVencidas()
{
    try {
        $db = getDB();

        // Atualizar mensalidades vencidas
        $stmt = $db->prepare("
            UPDATE mensalidades 
            SET status = 'atrasada'
            WHERE status = 'pendente' 
            AND data_vencimento < CURDATE()
        ");
        $stmt->execute();
        $atualizadas = $stmt->rowCount();

        return [
            'success' => true,
            'message' => "Status atualizado para {$atualizadas} mensalidades vencidas",
            'atualizadas' => $atualizadas
        ];
    } catch (Exception $e) {
        logError("Erro ao atualizar status das mensalidades: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro interno do servidor ao atualizar status'
        ];
    }
}

/**
 * Processa pagamento de mensalidade
 * 
 * @param int $mensalidade_id ID da mensalidade
 * @param float $valor_pago Valor pago
 * @param string $forma_pagamento Forma de pagamento
 * @param string $comprovante Caminho do comprovante (opcional)
 * @return array Resultado da operaÃ§Ã£o
 */
function processarPagamentoMensalidade($mensalidade_id, $valor_pago, $forma_pagamento, $comprovante = null)
{
    try {
        $db = getDB();

        // Buscar mensalidade
        $stmt = $db->prepare("
            SELECT m.*, c.aluno_id, a.nome as aluno_nome
            FROM mensalidades m
            JOIN contratos c ON m.contrato_id = c.id
            JOIN alunos a ON c.aluno_id = a.id
            WHERE m.id = ?
        ");
        $stmt->execute([$mensalidade_id]);
        $mensalidade = $stmt->fetch();

        if (!$mensalidade) {
            return [
                'success' => false,
                'message' => 'Mensalidade nÃ£o encontrada'
            ];
        }

        if ($mensalidade['status'] === 'paga') {
            return [
                'success' => false,
                'message' => 'Mensalidade jÃ¡ foi paga'
            ];
        }

        // Iniciar transaÃ§Ã£o
        $db->beginTransaction();

        try {
            // Atualizar mensalidade
            $stmt = $db->prepare("
                UPDATE mensalidades 
                SET status = 'paga', 
                    data_pagamento = CURDATE(), 
                    valor_pago = ?
                WHERE id = ?
            ");
            $stmt->execute([$valor_pago, $mensalidade_id]);

            // Buscar cobranÃ§a associada (se existir)
            $stmt = $db->prepare("
                SELECT id FROM cobrancas 
                WHERE mensalidade_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$mensalidade_id]);
            $cobranca = $stmt->fetch();

            // Registrar pagamento
            $stmt = $db->prepare("
                INSERT INTO pagamentos (cobranca_id, valor_pago, data_pagamento, forma_pagamento, comprovante)
                VALUES (?, ?, CURDATE(), ?, ?)
            ");
            $stmt->execute([
                $cobranca ? $cobranca['id'] : null,
                $valor_pago,
                $forma_pagamento,
                $comprovante
            ]);

            // Atualizar status da cobranÃ§a (se existir)
            if ($cobranca) {
                $stmt = $db->prepare("
                    UPDATE cobrancas 
                    SET status = 'paga'
                    WHERE id = ?
                ");
                $stmt->execute([$cobranca['id']]);
            }

            $db->commit();

            return [
                'success' => true,
                'message' => 'Pagamento processado com sucesso',
                'mensalidade_id' => $mensalidade_id,
                'aluno_nome' => $mensalidade['aluno_nome'],
                'valor_pago' => $valor_pago
            ];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        logError("Erro ao processar pagamento: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro interno do servidor ao processar pagamento'
        ];
    }
}

/**
 * Gera relatÃ³rio de inadimplÃªncia
 * 
 * @param string $data_referencia Data de referÃªncia (opcional)
 * @return array Resultado da operaÃ§Ã£o
 */
function gerarRelatorioInadimplencia($data_referencia = null)
{
    try {
        $db = getDB();
        $data_referencia = $data_referencia ?? date('Y-m-d');

        $stmt = $db->prepare("
            SELECT 
                a.id as aluno_id,
                a.nome as aluno_nome,
                a.foto as aluno_foto,
                t.nome as turma_nome,
                u.nome as unidade_nome,
                COUNT(m.id) as total_mensalidades_vencidas,
                SUM(m.valor) as valor_total_devido,
                MAX(m.data_vencimento) as ultima_cobranca_vencida,
                DATEDIFF(?, MAX(m.data_vencimento)) as dias_atraso
            FROM alunos a
            JOIN turmas t ON a.turma_id = t.id
            JOIN unidades u ON t.unidade_id = u.id
            JOIN contratos c ON a.id = c.aluno_id
            JOIN mensalidades m ON c.id = m.contrato_id
            WHERE m.status IN ('pendente', 'atrasada')
            AND m.data_vencimento < ?
            AND a.status = 'matriculado'
            GROUP BY a.id
            ORDER BY dias_atraso DESC, valor_total_devido DESC
        ");
        $stmt->execute([$data_referencia, $data_referencia]);
        $inadimplentes = $stmt->fetchAll();

        return [
            'success' => true,
            'data' => $inadimplentes,
            'total_inadimplentes' => count($inadimplentes),
            'data_referencia' => $data_referencia
        ];
    } catch (Exception $e) {
        logError("Erro ao gerar relatÃ³rio de inadimplÃªncia: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro interno do servidor ao gerar relatÃ³rio'
        ];
    }
}

// Se chamado diretamente via POST (para uso em APIs)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];
    $result = [];

    switch ($action) {
        case 'gerar_mensalidades':
            $mes_referencia = $_POST['mes_referencia'] ?? date('Y-m');
            $result = gerarMensalidades($mes_referencia);
            break;

        case 'gerar_mes_atual':
            $result = gerarMensalidadesMesAtual();
            break;

        case 'gerar_proximo_mes':
            $result = gerarMensalidadesProximoMes();
            break;

        case 'atualizar_status':
            $result = atualizarStatusMensalidadesVencidas();
            break;

        case 'processar_pagamento':
            $mensalidade_id = $_POST['mensalidade_id'] ?? null;
            $valor_pago = $_POST['valor_pago'] ?? null;
            $forma_pagamento = $_POST['forma_pagamento'] ?? null;
            $comprovante = $_POST['comprovante'] ?? null;

            if (!$mensalidade_id || !$valor_pago || !$forma_pagamento) {
                $result = [
                    'success' => false,
                    'message' => 'ParÃ¢metros obrigatÃ³rios nÃ£o fornecidos'
                ];
            } else {
                $result = processarPagamentoMensalidade($mensalidade_id, $valor_pago, $forma_pagamento, $comprovante);
            }
            break;

        case 'relatorio_inadimplencia':
            $data_referencia = $_POST['data_referencia'] ?? null;
            $result = gerarRelatorioInadimplencia($data_referencia);
            break;

        default:
            $result = [
                'success' => false,
                'message' => 'AÃ§Ã£o nÃ£o reconhecida'
            ];
    }

    echo json_encode($result);
    exit;
}


