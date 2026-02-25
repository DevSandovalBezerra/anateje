<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
anateje_require_admin($auth);

$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';

function campaigns_decode_json($value): array
{
    if (is_array($value)) {
        return $value;
    }

    if (!is_string($value) || trim($value) === '') {
        return [];
    }

    $json = json_decode($value, true);
    return is_array($json) ? $json : [];
}

function campaigns_normalize_filter($raw): array
{
    $in = campaigns_decode_json($raw);

    $categoria = strtoupper(trim((string) ($in['categoria'] ?? '')));
    if (!in_array($categoria, ['PARCIAL', 'INTEGRAL'], true)) {
        $categoria = '';
    }

    $status = strtoupper(trim((string) ($in['status'] ?? '')));
    if (!in_array($status, ['ATIVO', 'INATIVO'], true)) {
        $status = '';
    }

    $uf = strtoupper(trim((string) ($in['uf'] ?? '')));
    if (!preg_match('/^[A-Z]{2}$/', $uf)) {
        $uf = '';
    }

    $benefitId = (int) ($in['benefit_id'] ?? 0);
    if ($benefitId < 0) {
        $benefitId = 0;
    }

    return [
        'categoria' => $categoria,
        'status' => $status,
        'uf' => $uf,
        'benefit_id' => $benefitId,
    ];
}

function campaigns_normalize_payload($raw): array
{
    $in = campaigns_decode_json($raw);

    return [
        'mensagem' => trim((string) ($in['mensagem'] ?? '')),
        'assunto' => trim((string) ($in['assunto'] ?? '')),
    ];
}

function campaigns_row_with_meta(PDO $db, int $id): ?array
{
    $st = $db->prepare("SELECT c.*,
            (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id) AS total_logs,
            (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id AND l.status = 'sent') AS sent_logs,
            (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id AND l.status = 'queued') AS queued_logs,
            (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id AND l.status = 'failed') AS failed_logs,
            (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id AND l.status = 'skipped') AS skipped_logs
        FROM campaigns c
        WHERE c.id = ?
        LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $row['payload'] = campaigns_decode_json($row['payload_json'] ?? '');
    $row['filtro'] = campaigns_decode_json($row['filtro_json'] ?? '');
    return $row;
}

function campaigns_parse_pagination(int $defaultPerPage = 25, int $maxPerPage = 200): array
{
    $page = (int) ($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $perPage = (int) ($_GET['per_page'] ?? $defaultPerPage);
    if ($perPage < 5) {
        $perPage = 5;
    }
    if ($perPage > $maxPerPage) {
        $perPage = $maxPerPage;
    }

    return [
        'page' => $page,
        'per_page' => $perPage,
        'offset' => ($page - 1) * $perPage,
    ];
}

function campaigns_parse_log_filters(): array
{
    $status = strtolower(trim((string) ($_GET['status'] ?? '')));
    if (!in_array($status, ['queued', 'sent', 'failed', 'skipped'], true)) {
        $status = '';
    }

    $q = trim((string) ($_GET['q'] ?? ''));
    if (strlen($q) > 120) {
        $q = substr($q, 0, 120);
    }

    $runId = (int) ($_GET['run_id'] ?? 0);
    if ($runId < 0) {
        $runId = 0;
    }

    return [
        'status' => $status,
        'q' => $q,
        'run_id' => $runId,
    ];
}

function campaigns_logs_where_sql(int $campaignId, array $filters, array &$params): string
{
    $params = [$campaignId];
    $where = ' WHERE l.campaign_id = ?';

    if ((int) ($filters['run_id'] ?? 0) > 0) {
        $where .= ' AND l.run_id = ?';
        $params[] = (int) $filters['run_id'];
    }
    if (($filters['status'] ?? '') !== '') {
        $where .= ' AND l.status = ?';
        $params[] = $filters['status'];
    }
    if (($filters['q'] ?? '') !== '') {
        $where .= ' AND (l.destino LIKE ? OR m.nome LIKE ? OR m.email_funcional LIKE ? OR m.telefone LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    return $where;
}

function campaigns_fetch_runs(PDO $db, int $campaignId): array
{
    $st = $db->prepare("SELECT id, campaign_id, status, total_count, queued_count, sent_count, failed_count, skipped_count,
            error_message, started_at, finished_at, created_by, created_at
        FROM campaign_runs
        WHERE campaign_id = ?
        ORDER BY id DESC
        LIMIT 120");
    $st->execute([$campaignId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function campaigns_fetch_logs_paginated(PDO $db, int $campaignId, array $filters, array $pagination): array
{
    $params = [];
    $where = campaigns_logs_where_sql($campaignId, $filters, $params);

    $countSql = "SELECT COUNT(*) AS c
        FROM campaign_logs l
        LEFT JOIN members m ON m.id = l.member_id
        $where";
    $sc = $db->prepare($countSql);
    $sc->execute($params);
    $total = (int) ($sc->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

    $offset = (int) $pagination['offset'];
    $perPage = (int) $pagination['per_page'];

    $listSql = "SELECT l.id, l.run_id, l.member_id, l.canal, l.destino, l.status, l.erro, l.created_at,
            m.nome, m.email_funcional, m.telefone, m.categoria, m.status AS member_status,
            r.started_at AS run_started_at, r.finished_at AS run_finished_at
        FROM campaign_logs l
        LEFT JOIN members m ON m.id = l.member_id
        LEFT JOIN campaign_runs r ON r.id = l.run_id
        $where
        ORDER BY l.id DESC
        LIMIT $offset, $perPage";
    $sl = $db->prepare($listSql);
    $sl->execute($params);
    $logs = $sl->fetchAll(PDO::FETCH_ASSOC);

    return [
        'logs' => $logs,
        'total' => $total,
        'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
    ];
}

function campaigns_stream_csv(string $filename, array $header, array $rows): void
{
    if (ob_get_level() > 0) {
        ob_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header, ';');
    foreach ($rows as $line) {
        fputcsv($out, $line, ';');
    }
    fclose($out);
    exit;
}

if ($action === 'admin_list') {
    $rows = $db->query("SELECT c.*,
            (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id) AS total_logs,
            (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id AND l.status = 'sent') AS sent_logs,
            (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id AND l.status = 'queued') AS queued_logs,
            (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id AND l.status = 'failed') AS failed_logs,
            (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id AND l.status = 'skipped') AS skipped_logs,
            (SELECT MAX(r.started_at) FROM campaign_runs r WHERE r.campaign_id = c.id) AS last_run_at
        FROM campaigns c
        ORDER BY c.created_at DESC, c.id DESC")
        ->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['payload'] = campaigns_decode_json($row['payload_json'] ?? '');
        $row['filtro'] = campaigns_decode_json($row['filtro_json'] ?? '');
    }
    unset($row);

    anateje_ok(['campaigns' => $rows]);
}

if ($action === 'admin_get') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $campaign = campaigns_row_with_meta($db, $id);
    if (!$campaign) {
        anateje_error('NOT_FOUND', 'Campanha nao encontrada', 404);
    }

    $filters = campaigns_parse_log_filters();
    $pagination = campaigns_parse_pagination(25, 200);
    $runs = campaigns_fetch_runs($db, $id);
    if ((int) $filters['run_id'] <= 0 && !empty($runs)) {
        $filters['run_id'] = (int) $runs[0]['id'];
    }

    $logsData = campaigns_fetch_logs_paginated($db, $id, $filters, $pagination);

    anateje_ok([
        'campaign' => $campaign,
        'runs' => $runs,
        'selected_run_id' => (int) $filters['run_id'],
        'filters' => [
            'status' => $filters['status'],
            'q' => $filters['q'],
        ],
        'logs' => $logsData['logs'],
        'pagination' => [
            'page' => $pagination['page'],
            'per_page' => $pagination['per_page'],
            'total' => $logsData['total'],
            'total_pages' => $logsData['total_pages'],
        ],
    ]);
}

if ($action === 'admin_save') {
    anateje_require_method(['POST']);

    $in = anateje_input();

    $id = (int) ($in['id'] ?? 0);
    $canal = strtoupper(trim((string) ($in['canal'] ?? 'INAPP')));
    if (!in_array($canal, ['INAPP', 'EMAIL', 'WHATSAPP'], true)) {
        $canal = 'INAPP';
    }

    $titulo = trim((string) ($in['titulo'] ?? ''));
    if ($titulo === '') {
        anateje_error('VALIDATION', 'Titulo e obrigatorio', 422);
    }

    $status = strtolower(trim((string) ($in['status'] ?? 'draft')));
    if (!in_array($status, ['draft', 'queued', 'processing', 'done', 'failed'], true)) {
        $status = 'draft';
    }

    $payload = campaigns_normalize_payload($in['payload'] ?? []);
    if (isset($in['mensagem']) && trim((string) $in['mensagem']) !== '') {
        $payload['mensagem'] = trim((string) $in['mensagem']);
    }

    $filtro = campaigns_normalize_filter($in['filtro'] ?? []);

    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $filtroJson = json_encode($filtro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $db->beginTransaction();
    try {
        if ($id > 0) {
            $st = $db->prepare('UPDATE campaigns SET canal=?, titulo=?, payload_json=?, filtro_json=?, status=? WHERE id=?');
            $st->execute([$canal, $titulo, $payloadJson, $filtroJson, $status, $id]);
        } else {
            $st = $db->prepare('INSERT INTO campaigns (canal, titulo, payload_json, filtro_json, status) VALUES (?,?,?,?,?)');
            $st->execute([$canal, $titulo, $payloadJson, $filtroJson, $status]);
            $id = (int) $db->lastInsertId();
        }

        $db->commit();
        anateje_ok(['id' => $id]);
    } catch (Throwable $e) {
        $db->rollBack();
        logError('Erro campaigns.admin_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar campanha', 500);
    }
}

if ($action === 'admin_delete') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM campaign_logs WHERE campaign_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM campaign_runs WHERE campaign_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM campaigns WHERE id = ?')->execute([$id]);
        $db->commit();

        anateje_ok(['deleted' => true]);
    } catch (Throwable $e) {
        $db->rollBack();
        logError('Erro campaigns.admin_delete: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao excluir campanha', 500);
    }
}

if ($action === 'admin_run') {
    anateje_require_method(['POST']);

    $in = anateje_input();
    $id = (int) ($in['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $campaign = campaigns_row_with_meta($db, $id);
    if (!$campaign) {
        anateje_error('NOT_FOUND', 'Campanha nao encontrada', 404);
    }

    $canal = strtoupper((string) $campaign['canal']);
    $filtro = campaigns_normalize_filter($campaign['filtro'] ?? []);

    $sql = "SELECT m.id AS member_id, m.nome, m.email_funcional, m.telefone, m.categoria, m.status AS member_status, a.uf
        FROM members m
        LEFT JOIN addresses a ON a.member_id = m.id";

    $params = [];
    if ((int) $filtro['benefit_id'] > 0) {
        $sql .= " INNER JOIN member_benefits mb ON mb.member_id = m.id AND mb.benefit_id = ? AND mb.ativo = 1";
        $params[] = (int) $filtro['benefit_id'];
    }

    $sql .= " WHERE 1=1";

    if ($filtro['categoria'] !== '') {
        $sql .= " AND m.categoria = ?";
        $params[] = $filtro['categoria'];
    }
    if ($filtro['status'] !== '') {
        $sql .= " AND m.status = ?";
        $params[] = $filtro['status'];
    }
    if ($filtro['uf'] !== '') {
        $sql .= " AND a.uf = ?";
        $params[] = $filtro['uf'];
    }

    $st = $db->prepare($sql);
    $st->execute($params);
    $targets = $st->fetchAll(PDO::FETCH_ASSOC);

    $counts = [
        'total' => count($targets),
        'queued' => 0,
        'sent' => 0,
        'failed' => 0,
        'skipped' => 0,
    ];

    $runId = 0;
    try {
        $startedAt = date('Y-m-d H:i:s');
        $sr = $db->prepare('INSERT INTO campaign_runs (campaign_id, status, started_at, created_by) VALUES (?,?,?,?)');
        $sr->execute([$id, 'processing', $startedAt, (int) $auth['sub']]);
        $runId = (int) $db->lastInsertId();

        $db->prepare('UPDATE campaigns SET status = ? WHERE id = ?')->execute(['processing', $id]);

        $ins = $db->prepare('INSERT INTO campaign_logs (campaign_id, run_id, member_id, canal, destino, status, erro) VALUES (?,?,?,?,?,?,?)');

        foreach ($targets as $row) {
            $memberId = (int) $row['member_id'];
            $destino = '';
            $statusLog = 'queued';
            $erro = null;

            if ($canal === 'EMAIL') {
                $destino = trim((string) ($row['email_funcional'] ?? ''));
                if ($destino === '') {
                    $statusLog = 'skipped';
                    $destino = 'sem-email';
                    $erro = 'EMAIL_NAO_INFORMADO';
                } else {
                    $statusLog = 'queued';
                }
            } elseif ($canal === 'WHATSAPP') {
                $fone = anateje_only_digits((string) ($row['telefone'] ?? ''));
                if (strlen($fone) < 10) {
                    $statusLog = 'skipped';
                    $destino = 'sem-telefone';
                    $erro = 'WHATSAPP_NAO_INFORMADO';
                } else {
                    $destino = $fone;
                    $statusLog = 'queued';
                }
            } else {
                $destino = 'member:' . $memberId;
                $statusLog = 'sent';
            }

            if ($statusLog === 'queued') {
                $counts['queued']++;
            } elseif ($statusLog === 'sent') {
                $counts['sent']++;
            } elseif ($statusLog === 'failed') {
                $counts['failed']++;
            } else {
                $counts['skipped']++;
            }

            $ins->execute([$id, $runId, $memberId, $canal, $destino, $statusLog, $erro]);
        }

        $finalStatus = $counts['total'] > 0 ? 'done' : 'failed';
        $db->prepare('UPDATE campaigns SET status = ? WHERE id = ?')->execute([$finalStatus, $id]);

        $db->prepare('UPDATE campaign_runs
            SET status = ?, total_count = ?, queued_count = ?, sent_count = ?, failed_count = ?, skipped_count = ?, finished_at = ?
            WHERE id = ?')
            ->execute([
                $finalStatus,
                $counts['total'],
                $counts['queued'],
                $counts['sent'],
                $counts['failed'],
                $counts['skipped'],
                date('Y-m-d H:i:s'),
                $runId
            ]);

        $updated = campaigns_row_with_meta($db, $id);
        anateje_ok([
            'campaign' => $updated,
            'run' => $counts,
            'run_id' => $runId,
        ]);
    } catch (Throwable $e) {
        try {
            $db->prepare('UPDATE campaigns SET status = ? WHERE id = ?')->execute(['failed', $id]);
            if ($runId > 0) {
                $db->prepare('UPDATE campaign_runs
                    SET status = ?, total_count = ?, queued_count = ?, sent_count = ?, failed_count = ?, skipped_count = ?, error_message = ?, finished_at = ?
                    WHERE id = ?')
                    ->execute([
                        'failed',
                        $counts['total'],
                        $counts['queued'],
                        $counts['sent'],
                        $counts['failed'],
                        $counts['skipped'],
                        substr($e->getMessage(), 0, 600),
                        date('Y-m-d H:i:s'),
                        $runId
                    ]);
            }
        } catch (Throwable $inner) {
            logError('Erro campaigns.admin_run (update status): ' . $inner->getMessage());
        }
        logError('Erro campaigns.admin_run: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao executar campanha', 500);
    }
}

if ($action === 'export_logs_csv') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $campaign = campaigns_row_with_meta($db, $id);
    if (!$campaign) {
        anateje_error('NOT_FOUND', 'Campanha nao encontrada', 404);
    }

    $filters = campaigns_parse_log_filters();
    $params = [];
    $where = campaigns_logs_where_sql($id, $filters, $params);

    $sql = "SELECT l.id, l.run_id, l.created_at, l.canal, l.destino, l.status, l.erro,
            m.nome, m.email_funcional, m.telefone, m.categoria, m.status AS member_status
        FROM campaign_logs l
        LEFT JOIN members m ON m.id = l.member_id
        $where
        ORDER BY l.id DESC";
    $st = $db->prepare($sql);
    $st->execute($params);
    $logs = $st->fetchAll(PDO::FETCH_ASSOC);

    $rows = [];
    foreach ($logs as $log) {
        $rows[] = [
            (string) ($log['id'] ?? ''),
            (string) ($log['run_id'] ?? ''),
            (string) ($log['created_at'] ?? ''),
            (string) ($log['canal'] ?? ''),
            (string) ($log['destino'] ?? ''),
            (string) ($log['status'] ?? ''),
            (string) ($log['erro'] ?? ''),
            (string) ($log['nome'] ?? ''),
            (string) ($log['email_funcional'] ?? ''),
            (string) ($log['telefone'] ?? ''),
            (string) ($log['categoria'] ?? ''),
            (string) ($log['member_status'] ?? ''),
        ];
    }

    $safeTitle = preg_replace('/[^a-z0-9\-]+/i', '-', (string) ($campaign['titulo'] ?? 'campanha'));
    $safeTitle = trim((string) $safeTitle, '-');
    if ($safeTitle === '') {
        $safeTitle = 'campanha';
    }

    $suffix = '';
    if ((int) $filters['run_id'] > 0) {
        $suffix = '-run-' . (int) $filters['run_id'];
    }

    campaigns_stream_csv(
        'campaign-' . $id . '-' . $safeTitle . $suffix . '-logs.csv',
        ['log_id', 'run_id', 'created_at', 'canal', 'destino', 'status', 'erro', 'nome', 'email_funcional', 'telefone', 'categoria', 'member_status'],
        $rows
    );
}

if ($action === 'logs') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $filters = campaigns_parse_log_filters();
    $pagination = campaigns_parse_pagination(25, 200);
    $runs = campaigns_fetch_runs($db, $id);
    if ((int) $filters['run_id'] <= 0 && !empty($runs)) {
        $filters['run_id'] = (int) $runs[0]['id'];
    }

    $logsData = campaigns_fetch_logs_paginated($db, $id, $filters, $pagination);

    anateje_ok([
        'runs' => $runs,
        'selected_run_id' => (int) $filters['run_id'],
        'filters' => [
            'status' => $filters['status'],
            'q' => $filters['q'],
        ],
        'logs' => $logsData['logs'],
        'pagination' => [
            'page' => $pagination['page'],
            'per_page' => $pagination['per_page'],
            'total' => $logsData['total'],
            'total_pages' => $logsData['total_pages'],
        ],
    ]);
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
