<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';
$actorId = (int) $auth['sub'];

function benefits_member_profile(PDO $db, int $userId): ?array
{
    $st = $db->prepare('SELECT id, categoria, status FROM members WHERE user_id = ? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'categoria' => strtoupper((string) ($row['categoria'] ?? '')),
        'status' => strtoupper((string) ($row['status'] ?? '')),
    ];
}

function benefits_is_eligible(array $benefit, array $member): bool
{
    $catRule = strtoupper((string) ($benefit['eligibility_categoria'] ?? 'ALL'));
    $statusRule = strtoupper((string) ($benefit['eligibility_member_status'] ?? 'ALL'));

    $catOk = $catRule === 'ALL' || $catRule === strtoupper((string) ($member['categoria'] ?? ''));
    $statusOk = $statusRule === 'ALL' || $statusRule === strtoupper((string) ($member['status'] ?? ''));

    return $catOk && $statusOk;
}

function benefits_stream_csv(string $filename, array $header, array $rows): void
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

function benefits_normalize_bulk_ids($rawIds): array
{
    if (!is_array($rawIds)) {
        return [];
    }

    $ids = [];
    foreach ($rawIds as $raw) {
        $id = (int) $raw;
        if ($id > 0) {
            $ids[$id] = true;
        }
        if (count($ids) >= 500) {
            break;
        }
    }

    return array_map('intval', array_keys($ids));
}

function benefits_parse_member_filters($raw): array
{
    $in = is_array($raw) ? $raw : [];

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

    $q = trim((string) ($in['q'] ?? ''));
    if (strlen($q) > 120) {
        $q = substr($q, 0, 120);
    }

    return [
        'categoria' => $categoria,
        'status' => $status,
        'uf' => $uf,
        'q' => $q,
    ];
}

function benefits_member_where_sql(array $filters, array &$params): string
{
    $params = [];
    $where = ' WHERE 1=1';

    if (($filters['categoria'] ?? '') !== '') {
        $where .= ' AND m.categoria = ?';
        $params[] = $filters['categoria'];
    }
    if (($filters['status'] ?? '') !== '') {
        $where .= ' AND m.status = ?';
        $params[] = $filters['status'];
    }
    if (($filters['uf'] ?? '') !== '') {
        $where .= ' AND a.uf = ?';
        $params[] = $filters['uf'];
    }
    if (($filters['q'] ?? '') !== '') {
        $where .= ' AND (m.nome LIKE ? OR m.cpf LIKE ? OR COALESCE(m.matricula, \'\') LIKE ? OR COALESCE(m.email_funcional, \'\') LIKE ? OR COALESCE(m.telefone, \'\') LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    return $where;
}

function benefits_normalize_member_ids(array $ids, int $max = 5000): array
{
    $out = [];
    foreach ($ids as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $out[$id] = true;
        }
        if (count($out) >= $max) {
            break;
        }
    }
    return array_map('intval', array_keys($out));
}

if ($action === 'list') {
    $member = benefits_member_profile($db, (int) $auth['sub']);
    $memberId = $member ? (int) $member['id'] : 0;

    $benefits = $db->query("SELECT id, nome, descricao, link, status, sort_order, eligibility_categoria, eligibility_member_status
        FROM benefits
        WHERE status='active'
        ORDER BY sort_order ASC, id ASC")
        ->fetchAll(PDO::FETCH_ASSOC);

    $activeMap = [];
    if ($memberId > 0) {
        $st = $db->prepare('SELECT benefit_id, ativo FROM member_benefits WHERE member_id = ?');
        $st->execute([$memberId]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $activeMap[(int) $row['benefit_id']] = (int) $row['ativo'] === 1;
        }
    }

    $result = [];
    foreach ($benefits as $b) {
        $eligible = $member ? benefits_is_eligible($b, $member) : true;
        if (!$eligible) {
            continue;
        }
        $id = (int) $b['id'];
        $b['active_for_me'] = $memberId ? (bool) ($activeMap[$id] ?? false) : false;
        $result[] = $b;
    }

    anateje_ok(['benefits' => $result]);
}

if ($action === 'set_member_benefits') {
    anateje_require_method(['POST']);

    $member = benefits_member_profile($db, (int) $auth['sub']);
    if (!$member) {
        anateje_error('NO_MEMBER', 'Complete seu perfil antes', 422);
    }
    $memberId = (int) $member['id'];

    $in = anateje_input();
    $ids = $in['benefit_ids'] ?? [];
    if (!is_array($ids)) {
        $ids = [];
    }

    $ids = array_values(array_unique(array_filter(array_map(function ($id) {
        return (int) $id;
    }, $ids), function ($id) {
        return $id > 0;
    })));

    $db->beginTransaction();
    try {
        $del = $db->prepare('DELETE FROM member_benefits WHERE member_id = ?');
        $del->execute([$memberId]);

        if (!empty($ids)) {
            $st = $db->query("SELECT id, eligibility_categoria, eligibility_member_status FROM benefits WHERE status = 'active'");
            $catalog = $st->fetchAll(PDO::FETCH_ASSOC);
            $eligibleMap = [];
            foreach ($catalog as $benefit) {
                if (benefits_is_eligible($benefit, $member)) {
                    $eligibleMap[(int) $benefit['id']] = true;
                }
            }

            $set = $db->prepare('INSERT INTO member_benefits (member_id, benefit_id, ativo) VALUES (?,?,1)');
            foreach ($ids as $id) {
                if (!empty($eligibleMap[(int) $id])) {
                    $set->execute([$memberId, $id]);
                }
            }
        }

        $db->commit();
        anateje_ok(['updated' => true]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro benefits.set_member_benefits: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao atualizar beneficios', 500);
    }
}

if ($action === 'admin_list') {
    anateje_require_permission($db, $auth, 'admin.beneficios.view');

    $rows = $db->query('SELECT * FROM benefits ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
    $statusCounts = [
        'active' => 0,
        'inactive' => 0,
        'other' => 0,
    ];
    foreach ($rows as $row) {
        $status = strtolower((string) ($row['status'] ?? ''));
        if (isset($statusCounts[$status])) {
            $statusCounts[$status]++;
        } else {
            $statusCounts['other']++;
        }
    }

    anateje_ok([
        'benefits' => $rows,
        'meta' => [
            'total' => count($rows),
            'status_counts' => $statusCounts,
        ],
    ]);
}

if ($action === 'admin_save') {
    anateje_require_method(['POST']);

    $in = anateje_input();

    $id = (int) ($in['id'] ?? 0);
    anateje_require_permission($db, $auth, $id > 0 ? 'admin.beneficios.edit' : 'admin.beneficios.create');
    $nome = trim((string) ($in['nome'] ?? ''));
    if ($nome === '') {
        anateje_error('VALIDATION', 'Nome e obrigatorio', 422);
    }

    $descricao = trim((string) ($in['descricao'] ?? ''));
    $link = trim((string) ($in['link'] ?? ''));
    $status = ($in['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $sortOrder = (int) ($in['sort_order'] ?? 0);

    $eligibilityCategoria = strtoupper(trim((string) ($in['eligibility_categoria'] ?? 'ALL')));
    if (!in_array($eligibilityCategoria, ['ALL', 'PARCIAL', 'INTEGRAL'], true)) {
        $eligibilityCategoria = 'ALL';
    }

    $eligibilityMemberStatus = strtoupper(trim((string) ($in['eligibility_member_status'] ?? 'ALL')));
    if (!in_array($eligibilityMemberStatus, ['ALL', 'ATIVO', 'INATIVO'], true)) {
        $eligibilityMemberStatus = 'ALL';
    }

    $db->beginTransaction();
    try {
        $before = null;
        if ($id > 0) {
            $sb = $db->prepare('SELECT * FROM benefits WHERE id = ? LIMIT 1');
            $sb->execute([$id]);
            $before = $sb->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if ($id > 0) {
            $st = $db->prepare('UPDATE benefits
                SET nome=?, descricao=?, link=?, status=?, eligibility_categoria=?, eligibility_member_status=?, sort_order=?
                WHERE id=?');
            $st->execute([$nome, $descricao ?: null, $link ?: null, $status, $eligibilityCategoria, $eligibilityMemberStatus, $sortOrder, $id]);
        } else {
            $st = $db->prepare('INSERT INTO benefits
                (nome, descricao, link, status, eligibility_categoria, eligibility_member_status, sort_order)
                VALUES (?,?,?,?,?,?,?)');
            $st->execute([$nome, $descricao ?: null, $link ?: null, $status, $eligibilityCategoria, $eligibilityMemberStatus, $sortOrder]);
            $id = (int) $db->lastInsertId();
        }

        $sa = $db->prepare('SELECT * FROM benefits WHERE id = ? LIMIT 1');
        $sa->execute([$id]);
        $after = $sa->fetch(PDO::FETCH_ASSOC) ?: null;
        anateje_audit_log($db, $actorId, 'admin.beneficios', $before ? 'update' : 'create', 'benefit', $id, $before, $after, []);

        $db->commit();
        anateje_ok(['id' => $id]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro benefits.admin_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar beneficio', 500);
    }
}

if ($action === 'admin_member_link_preview') {
    anateje_require_permission($db, $auth, 'admin.beneficios.view');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $benefitId = (int) ($in['benefit_id'] ?? 0);
    if ($benefitId <= 0) {
        anateje_error('VALIDATION', 'Beneficio invalido', 422);
    }

    $sb = $db->prepare('SELECT id, nome, status FROM benefits WHERE id = ? LIMIT 1');
    $sb->execute([$benefitId]);
    $benefit = $sb->fetch(PDO::FETCH_ASSOC);
    if (!$benefit) {
        anateje_error('NOT_FOUND', 'Beneficio nao encontrado', 404);
    }

    $filters = benefits_parse_member_filters($in['filters'] ?? []);
    $params = [];
    $where = benefits_member_where_sql($filters, $params);

    $sc = $db->prepare("SELECT COUNT(*) FROM members m
        LEFT JOIN addresses a ON a.member_id = m.id
        $where");
    $sc->execute($params);
    $total = (int) $sc->fetchColumn();

    $ss = $db->prepare("SELECT m.id, m.nome, m.categoria, m.status, a.uf
        FROM members m
        LEFT JOIN addresses a ON a.member_id = m.id
        $where
        ORDER BY m.nome ASC, m.id ASC
        LIMIT 10");
    $ss->execute($params);
    $sample = $ss->fetchAll(PDO::FETCH_ASSOC);

    anateje_ok([
        'benefit' => $benefit,
        'filters' => $filters,
        'total' => $total,
        'sample' => $sample,
    ]);
}

if ($action === 'admin_member_link_apply') {
    anateje_require_permission($db, $auth, 'admin.beneficios.edit');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $benefitId = (int) ($in['benefit_id'] ?? 0);
    if ($benefitId <= 0) {
        anateje_error('VALIDATION', 'Beneficio invalido', 422);
    }

    $mode = strtolower(trim((string) ($in['mode'] ?? 'assign')));
    if (!in_array($mode, ['assign', 'remove'], true)) {
        $mode = 'assign';
    }

    $sb = $db->prepare('SELECT id, nome, status FROM benefits WHERE id = ? LIMIT 1');
    $sb->execute([$benefitId]);
    $benefit = $sb->fetch(PDO::FETCH_ASSOC);
    if (!$benefit) {
        anateje_error('NOT_FOUND', 'Beneficio nao encontrado', 404);
    }

    $filters = benefits_parse_member_filters($in['filters'] ?? []);
    $params = [];
    $where = benefits_member_where_sql($filters, $params);
    $sqlIds = "SELECT m.id
        FROM members m
        LEFT JOIN addresses a ON a.member_id = m.id
        $where
        ORDER BY m.id ASC
        LIMIT 5000";
    $stIds = $db->prepare($sqlIds);
    $stIds->execute($params);
    $memberIds = benefits_normalize_member_ids($stIds->fetchAll(PDO::FETCH_COLUMN, 0), 5000);

    if (!$memberIds) {
        anateje_ok([
            'benefit_id' => $benefitId,
            'mode' => $mode,
            'matched' => 0,
            'assigned' => 0,
            'reactivated' => 0,
            'removed' => 0,
            'unchanged' => 0,
            'filters' => $filters,
        ]);
    }

    $matched = count($memberIds);
    if ($matched >= 5000) {
        anateje_error('VALIDATION', 'Filtro retornou 5000+ associados. Refine os filtros para operar em lote.', 422);
    }

    $summary = [
        'assigned' => 0,
        'reactivated' => 0,
        'removed' => 0,
        'unchanged' => 0,
    ];

    $db->beginTransaction();
    try {
        $existingMap = [];
        foreach (array_chunk($memberIds, 500) as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '?'));
            $paramsEx = array_merge([$benefitId], $chunk);
            $sx = $db->prepare("SELECT member_id, ativo FROM member_benefits WHERE benefit_id = ? AND member_id IN ($ph)");
            $sx->execute($paramsEx);
            foreach ($sx->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $existingMap[(int) $row['member_id']] = (int) ($row['ativo'] ?? 0);
            }
        }

        if ($mode === 'assign') {
            $ins = $db->prepare('INSERT INTO member_benefits (member_id, benefit_id, ativo) VALUES (?,?,1)
                ON DUPLICATE KEY UPDATE ativo = VALUES(ativo)');

            foreach ($memberIds as $memberId) {
                $prev = $existingMap[$memberId] ?? null;
                if ($prev === null) {
                    $ins->execute([$memberId, $benefitId]);
                    $summary['assigned']++;
                    continue;
                }
                if ((int) $prev === 0) {
                    $ins->execute([$memberId, $benefitId]);
                    $summary['reactivated']++;
                    continue;
                }
                $summary['unchanged']++;
            }
        } else {
            foreach (array_chunk($memberIds, 500) as $chunk) {
                $ph = implode(',', array_fill(0, count($chunk), '?'));
                $paramsRm = array_merge([$benefitId], $chunk);
                $sr = $db->prepare("UPDATE member_benefits SET ativo = 0 WHERE benefit_id = ? AND member_id IN ($ph) AND ativo = 1");
                $sr->execute($paramsRm);
                $summary['removed'] += (int) $sr->rowCount();
            }
            $summary['unchanged'] = max(0, $matched - $summary['removed']);
        }

        anateje_audit_log(
            $db,
            $actorId,
            'admin.beneficios',
            $mode === 'assign' ? 'member_link_assign' : 'member_link_remove',
            'benefit',
            $benefitId,
            null,
            null,
            [
                'benefit' => ['id' => $benefit['id'], 'nome' => $benefit['nome']],
                'filters' => $filters,
                'matched' => $matched,
                'summary' => $summary,
            ]
        );

        $db->commit();
        anateje_ok([
            'benefit_id' => $benefitId,
            'mode' => $mode,
            'matched' => $matched,
            'assigned' => $summary['assigned'],
            'reactivated' => $summary['reactivated'],
            'removed' => $summary['removed'],
            'unchanged' => $summary['unchanged'],
            'filters' => $filters,
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro benefits.admin_member_link_apply: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao aplicar vinculo em lote', 500);
    }
}

if ($action === 'admin_delete') {
    anateje_require_permission($db, $auth, 'admin.beneficios.delete');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $id = (int) ($in['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $db->beginTransaction();
    try {
        $sb = $db->prepare('SELECT * FROM benefits WHERE id = ? LIMIT 1');
        $sb->execute([$id]);
        $before = $sb->fetch(PDO::FETCH_ASSOC);
        if (!$before) {
            throw new RuntimeException('BENEFIT_NOT_FOUND');
        }

        $db->prepare('DELETE FROM member_benefits WHERE benefit_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM benefits WHERE id = ?')->execute([$id]);
        anateje_audit_log($db, $actorId, 'admin.beneficios', 'delete', 'benefit', $id, $before, null, []);

        $db->commit();
        anateje_ok(['deleted' => true]);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($e->getMessage() === 'BENEFIT_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Beneficio nao encontrado', 404);
        }
        anateje_error('FAIL', 'Falha ao excluir beneficio', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro benefits.admin_delete: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao excluir beneficio', 500);
    }
}

if ($action === 'admin_bulk_status') {
    anateje_require_permission($db, $auth, 'admin.beneficios.edit');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $ids = benefits_normalize_bulk_ids($in['ids'] ?? []);
    $targetStatus = strtolower(trim((string) ($in['status'] ?? '')));
    if (!in_array($targetStatus, ['active', 'inactive'], true)) {
        anateje_error('VALIDATION', 'Status alvo invalido para lote', 422);
    }
    if (!$ids) {
        anateje_error('VALIDATION', 'Selecione ao menos um beneficio', 422);
    }

    $reason = trim((string) ($in['reason'] ?? ''));
    if (strlen($reason) > 180) {
        $reason = substr($reason, 0, 180);
    }

    $summary = [
        'requested' => count($ids),
        'updated' => 0,
        'unchanged' => 0,
        'not_found' => 0,
    ];

    $db->beginTransaction();
    try {
        $sel = $db->prepare('SELECT id, nome, status, sort_order, eligibility_categoria, eligibility_member_status FROM benefits WHERE id = ? LIMIT 1 FOR UPDATE');
        $upd = $db->prepare('UPDATE benefits SET status = ? WHERE id = ?');

        foreach ($ids as $id) {
            $sel->execute([$id]);
            $before = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$before) {
                $summary['not_found']++;
                continue;
            }

            $oldStatus = strtolower((string) ($before['status'] ?? ''));
            if ($oldStatus === $targetStatus) {
                $summary['unchanged']++;
                continue;
            }

            $upd->execute([$targetStatus, $id]);
            $after = $before;
            $after['status'] = $targetStatus;

            anateje_audit_log(
                $db,
                $actorId,
                'admin.beneficios',
                'bulk_status',
                'benefit',
                $id,
                $before,
                $after,
                ['reason' => $reason !== '' ? $reason : null]
            );
            $summary['updated']++;
        }

        $db->commit();
        anateje_ok([
            'target_status' => $targetStatus,
            'requested' => $summary['requested'],
            'updated' => $summary['updated'],
            'unchanged' => $summary['unchanged'],
            'not_found' => $summary['not_found'],
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro benefits.admin_bulk_status: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao atualizar beneficios em lote', 500);
    }
}

if ($action === 'admin_export_csv') {
    anateje_require_permission($db, $auth, 'admin.beneficios.export');

    $rows = $db->query('SELECT id, nome, descricao, link, status, eligibility_categoria, eligibility_member_status, sort_order, created_at
        FROM benefits
        ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

    $dataRows = [];
    foreach ($rows as $row) {
        $dataRows[] = [
            (string) ($row['id'] ?? ''),
            (string) ($row['nome'] ?? ''),
            (string) ($row['status'] ?? ''),
            (string) ($row['eligibility_categoria'] ?? ''),
            (string) ($row['eligibility_member_status'] ?? ''),
            (string) ($row['sort_order'] ?? ''),
            (string) ($row['link'] ?? ''),
            (string) ($row['descricao'] ?? ''),
            (string) ($row['created_at'] ?? ''),
        ];
    }

    benefits_stream_csv(
        'beneficios-admin.csv',
        ['id', 'nome', 'status', 'eligibility_categoria', 'eligibility_member_status', 'sort_order', 'link', 'descricao', 'created_at'],
        $dataRows
    );
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
