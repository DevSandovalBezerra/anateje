<?php
// ANATEJE - API de Auditoria Financeira
// Sistema de Gestao Financeira Associativa ANATEJE
// Consulta de histÃ³rico de auditoria conforme PRD

require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/_bootstrap.php';
class AuditoriaFinanceiraAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function listar($filtros = [])
    {
        try {
            $sql = "
                SELECT a.*,
                       u.nome as usuario_nome,
                       u.email as usuario_email
                FROM auditoria_financeira a
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($filtros['entidade'])) {
                $sql .= " AND a.entidade = ?";
                $params[] = $filtros['entidade'];
            }

            if (!empty($filtros['entidade_id'])) {
                $sql .= " AND a.entidade_id = ?";
                $params[] = (int)$filtros['entidade_id'];
            }

            if (!empty($filtros['acao'])) {
                $sql .= " AND a.acao = ?";
                $params[] = $filtros['acao'];
            }

            if (!empty($filtros['usuario_id'])) {
                $sql .= " AND a.usuario_id = ?";
                $params[] = (int)$filtros['usuario_id'];
            }

            if (!empty($filtros['data_inicio'])) {
                $sql .= " AND DATE(a.created_at) >= ?";
                $params[] = $filtros['data_inicio'];
            }

            if (!empty($filtros['data_fim'])) {
                $sql .= " AND DATE(a.created_at) <= ?";
                $params[] = $filtros['data_fim'];
            }

            $sql .= " ORDER BY a.created_at DESC LIMIT 1000";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $auditoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($auditoria as &$registro) {
                if ($registro['dados_anteriores']) {
                    $registro['dados_anteriores'] = json_decode($registro['dados_anteriores'], true);
                }
                if ($registro['dados_novos']) {
                    $registro['dados_novos'] = json_decode($registro['dados_novos'], true);
                }
            }

            return ['success' => true, 'data' => $auditoria];
        } catch (Exception $e) {
            logError("Erro ao listar auditoria: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar auditoria'];
        }
    }

    public function obterPorEntidade($entidade, $entidade_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*,
                       u.nome as usuario_nome,
                       u.email as usuario_email
                FROM auditoria_financeira a
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE a.entidade = ? AND a.entidade_id = ?
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$entidade, $entidade_id]);
            $auditoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($auditoria as &$registro) {
                if ($registro['dados_anteriores']) {
                    $registro['dados_anteriores'] = json_decode($registro['dados_anteriores'], true);
                }
                if ($registro['dados_novos']) {
                    $registro['dados_novos'] = json_decode($registro['dados_novos'], true);
                }
            }

            return ['success' => true, 'data' => $auditoria];
        } catch (Exception $e) {
            logError("Erro ao obter auditoria por entidade: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter auditoria'];
        }
    }
}

$auth = financeiro_require_auth('auditoria');

$api = new AuditoriaFinanceiraAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            $filtros = [
                'entidade' => $_GET['entidade'] ?? null,
                'entidade_id' => $_GET['entidade_id'] ?? null,
                'acao' => $_GET['acao'] ?? null,
                'usuario_id' => $_GET['usuario_id'] ?? null,
                'data_inicio' => $_GET['data_inicio'] ?? null,
                'data_fim' => $_GET['data_fim'] ?? null,
            ];
            financeiro_response($api->listar($filtros));
            break;
        case 'por_entidade':
            $entidade = $_GET['entidade'] ?? '';
            $entidade_id = (int)($_GET['entidade_id'] ?? 0);
            if (empty($entidade) || !$entidade_id) {
                financeiro_response(['success' => false, 'message' => 'Entidade e ID sÃ£o obrigatÃ³rios'], 400);
            }
            financeiro_response($api->obterPorEntidade($entidade, $entidade_id));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'AÃ§Ã£o invÃ¡lida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'MÃ©todo nÃ£o permitido'], 405);



