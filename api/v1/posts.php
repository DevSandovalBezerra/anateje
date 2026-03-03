<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';
$actorId = (int) $auth['sub'];

function posts_member_profile(PDO $db, int $userId): ?array
{
    $st = $db->prepare("SELECT m.id, m.categoria, m.status, m.lotacao, a.uf
        FROM members m
        LEFT JOIN addresses a ON a.member_id = m.id
        WHERE m.user_id = ?
        LIMIT 1");
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'categoria' => strtoupper((string) ($row['categoria'] ?? '')),
        'status' => strtoupper((string) ($row['status'] ?? '')),
        'lotacao' => trim((string) ($row['lotacao'] ?? '')),
        'uf' => strtoupper(trim((string) ($row['uf'] ?? ''))),
    ];
}

function posts_parse_target(array $in, string $tipo): array
{
    $targetCategoria = strtoupper(trim((string) ($in['target_categoria'] ?? 'ALL')));
    if (!in_array($targetCategoria, ['ALL', 'PARCIAL', 'INTEGRAL'], true)) {
        $targetCategoria = 'ALL';
    }

    $targetStatus = strtoupper(trim((string) ($in['target_status'] ?? 'ALL')));
    if (!in_array($targetStatus, ['ALL', 'ATIVO', 'INATIVO'], true)) {
        $targetStatus = 'ALL';
    }

    $targetUf = strtoupper(trim((string) ($in['target_uf'] ?? '')));
    if ($targetUf !== '' && !preg_match('/^[A-Z]{2}$/', $targetUf)) {
        anateje_error('VALIDATION', 'UF de segmentacao invalida', 422);
    }

    $targetLotacao = trim((string) ($in['target_lotacao'] ?? ''));
    if (strlen($targetLotacao) > 150) {
        anateje_error('VALIDATION', 'Lotacao de segmentacao invalida', 422);
    }

    if ($tipo !== 'COMUNICADO') {
        return [
            'target_categoria' => 'ALL',
            'target_status' => 'ALL',
            'target_uf' => null,
            'target_lotacao' => null,
        ];
    }

    return [
        'target_categoria' => $targetCategoria,
        'target_status' => $targetStatus,
        'target_uf' => $targetUf !== '' ? $targetUf : null,
        'target_lotacao' => $targetLotacao !== '' ? $targetLotacao : null,
    ];
}

function posts_count_audience(PDO $db, array $target): int
{
    $where = " WHERE m.status IN ('ATIVO','INATIVO')";
    $params = [];

    if (($target['target_categoria'] ?? 'ALL') !== 'ALL') {
        $where .= ' AND m.categoria = ?';
        $params[] = $target['target_categoria'];
    }
    if (($target['target_status'] ?? 'ALL') !== 'ALL') {
        $where .= ' AND m.status = ?';
        $params[] = $target['target_status'];
    }
    if (!empty($target['target_uf'])) {
        $where .= ' AND a.uf = ?';
        $params[] = $target['target_uf'];
    }
    if (!empty($target['target_lotacao'])) {
        $where .= ' AND m.lotacao = ?';
        $params[] = $target['target_lotacao'];
    }

    $sql = "SELECT COUNT(*)
        FROM members m
        LEFT JOIN addresses a ON a.member_id = m.id
        $where";
    $st = $db->prepare($sql);
    $st->execute($params);
    return (int) $st->fetchColumn();
}

function posts_stream_csv(string $filename, array $header, array $rows): void
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

function posts_normalize_bulk_ids($rawIds): array
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

if ($action === 'list') {
    $type = strtoupper(trim((string) ($_GET['type'] ?? 'COMUNICADO')));
    if (!in_array($type, ['COMUNICADO', 'BLOG'], true)) {
        $type = 'COMUNICADO';
    }

    $member = null;
    $readColumns = 'NULL AS read_at, 0 AS is_read';
    if (empty($auth['is_admin']) && $type === 'COMUNICADO') {
        $member = posts_member_profile($db, $actorId);
        if (!$member) {
            anateje_ok(['posts' => []]);
        }

        $memberId = (int) ($member['id'] ?? 0);
        $readColumns = "(SELECT pr.read_at FROM post_reads pr WHERE pr.post_id = p.id AND pr.member_id = {$memberId} LIMIT 1) AS read_at,
            EXISTS(SELECT 1 FROM post_reads pr2 WHERE pr2.post_id = p.id AND pr2.member_id = {$memberId}) AS is_read";
    }

    $sql = "SELECT p.id, p.tipo, p.titulo, p.slug, p.status, p.publicado_em, p.scheduled_for, p.created_at,
            p.target_categoria, p.target_status, p.target_uf, p.target_lotacao,
            {$readColumns}
        FROM posts p
        WHERE tipo = ?
          AND status = 'published'
          AND (scheduled_for IS NULL OR scheduled_for <= NOW())";
    $params = [$type];

    if ($member && $type === 'COMUNICADO') {
        $sql .= " AND (target_categoria = 'ALL' OR target_categoria = ?)
            AND (target_status = 'ALL' OR target_status = ?)
            AND (target_uf IS NULL OR target_uf = '' OR target_uf = ?)
            AND (target_lotacao IS NULL OR target_lotacao = '' OR target_lotacao = ?)";
        $params[] = $member['categoria'];
        $params[] = $member['status'];
        $params[] = $member['uf'];
        $params[] = $member['lotacao'];
    }

    $sql .= ' ORDER BY COALESCE(publicado_em, created_at) DESC, id DESC LIMIT 100';

    $st = $db->prepare($sql);
    $st->execute($params);

    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['is_read'] = (int) ($row['is_read'] ?? 0) === 1;
        $row['read_at'] = trim((string) ($row['read_at'] ?? '')) !== '' ? (string) $row['read_at'] : null;
    }
    unset($row);

    anateje_ok(['posts' => $rows]);
}

if ($action === 'detail') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $st = $db->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $post = $st->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        anateje_error('NOT_FOUND', 'Post nao encontrado', 404);
    }

    $memberForRead = null;
    if (empty($auth['is_admin'])) {
        if ((string) ($post['status'] ?? '') !== 'published') {
            anateje_error('NOT_FOUND', 'Post nao encontrado', 404);
        }
        $scheduledFor = trim((string) ($post['scheduled_for'] ?? ''));
        if ($scheduledFor !== '' && strtotime($scheduledFor) > time()) {
            anateje_error('NOT_FOUND', 'Post nao encontrado', 404);
        }

        if ((string) ($post['tipo'] ?? '') === 'COMUNICADO') {
            $member = posts_member_profile($db, $actorId);
            if (!$member) {
                anateje_error('FORBIDDEN', 'Comunicado indisponivel para este perfil', 403);
            }

            $okCategoria = ((string) ($post['target_categoria'] ?? 'ALL') === 'ALL') || ((string) ($post['target_categoria'] ?? '') === $member['categoria']);
            $okStatus = ((string) ($post['target_status'] ?? 'ALL') === 'ALL') || ((string) ($post['target_status'] ?? '') === $member['status']);
            $targetUf = strtoupper(trim((string) ($post['target_uf'] ?? '')));
            $okUf = ($targetUf === '') || ($targetUf === $member['uf']);
            $targetLotacao = trim((string) ($post['target_lotacao'] ?? ''));
            $okLotacao = ($targetLotacao === '') || ($targetLotacao === $member['lotacao']);

            if (!($okCategoria && $okStatus && $okUf && $okLotacao)) {
                anateje_error('FORBIDDEN', 'Comunicado indisponivel para este perfil', 403);
            }
            $memberForRead = $member;
        }
    }

    if ($memberForRead && (int) ($memberForRead['id'] ?? 0) > 0) {
        $readAt = date('Y-m-d H:i:s');
        $sm = $db->prepare('INSERT INTO post_reads (post_id, member_id, read_at) VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)');
        $sm->execute([$id, (int) $memberForRead['id'], $readAt]);
        $post['is_read'] = true;
        $post['read_at'] = $readAt;
    } else {
        $post['is_read'] = false;
        $post['read_at'] = null;
    }

    anateje_ok(['post' => $post]);
}

if ($action === 'admin_list') {
    anateje_require_permission($db, $auth, 'admin.comunicados.view');

    $rows = $db->query('SELECT id, tipo, titulo, slug, conteudo, status, publicado_em, scheduled_for, target_categoria, target_status, target_uf, target_lotacao, created_at
        FROM posts
        ORDER BY created_at DESC, id DESC')
        ->fetchAll(PDO::FETCH_ASSOC);

    $statusCounts = [
        'draft' => 0,
        'published' => 0,
        'archived' => 0,
        'other' => 0,
    ];
    $tipoCounts = [];
    foreach ($rows as $row) {
        $status = strtolower((string) ($row['status'] ?? ''));
        if (isset($statusCounts[$status])) {
            $statusCounts[$status]++;
        } else {
            $statusCounts['other']++;
        }

        $tipo = strtoupper((string) ($row['tipo'] ?? ''));
        if (!isset($tipoCounts[$tipo])) {
            $tipoCounts[$tipo] = 0;
        }
        $tipoCounts[$tipo]++;
    }

    anateje_ok([
        'posts' => $rows,
        'meta' => [
            'total' => count($rows),
            'status_counts' => $statusCounts,
            'tipo_counts' => $tipoCounts,
        ],
    ]);
}

if ($action === 'admin_get') {
    anateje_require_permission($db, $auth, 'admin.comunicados.view');

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $st = $db->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $post = $st->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        anateje_error('NOT_FOUND', 'Post nao encontrado', 404);
    }

    $target = [
        'target_categoria' => $post['target_categoria'] ?? 'ALL',
        'target_status' => $post['target_status'] ?? 'ALL',
        'target_uf' => $post['target_uf'] ?? null,
        'target_lotacao' => $post['target_lotacao'] ?? null,
    ];

    $estimate = ((string) ($post['tipo'] ?? '') === 'COMUNICADO') ? posts_count_audience($db, $target) : null;

    anateje_ok(['post' => $post, 'audience_estimate' => $estimate]);
}

if ($action === 'admin_preview_audience') {
    anateje_require_permission($db, $auth, 'admin.comunicados.view');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $target = posts_parse_target($in, 'COMUNICADO');
    $count = posts_count_audience($db, $target);

    anateje_ok(['audience_estimate' => $count]);
}

if ($action === 'admin_save') {
    anateje_require_method(['POST']);

    $in = anateje_input();
    $id = (int) ($in['id'] ?? 0);
    anateje_require_permission($db, $auth, $id > 0 ? 'admin.comunicados.edit' : 'admin.comunicados.create');

    $tipo = strtoupper(trim((string) ($in['tipo'] ?? 'COMUNICADO')));
    if (!in_array($tipo, ['COMUNICADO', 'BLOG'], true)) {
        $tipo = 'COMUNICADO';
    }

    $titulo = trim((string) ($in['titulo'] ?? ''));
    if ($titulo === '') {
        anateje_error('VALIDATION', 'Titulo e obrigatorio', 422);
    }

    $status = strtolower(trim((string) ($in['status'] ?? 'draft')));
    if (!in_array($status, ['draft', 'published', 'archived'], true)) {
        $status = 'draft';
    }
    if ($status === 'published') {
        anateje_require_permission($db, $auth, 'admin.comunicados.publish');
    }

    $slug = trim((string) ($in['slug'] ?? ''));
    if ($slug === '') {
        $slug = anateje_slug($titulo);
    } else {
        $slug = anateje_slug($slug);
    }

    $conteudo = trim((string) ($in['conteudo'] ?? ''));
    $publicadoEm = anateje_parse_datetime($in['publicado_em'] ?? '');
    $scheduledFor = anateje_parse_datetime($in['scheduled_for'] ?? '');

    if ($status === 'published') {
        if ($publicadoEm === null) {
            $publicadoEm = date('Y-m-d H:i:s');
        }
    } else {
        $publicadoEm = null;
        $scheduledFor = null;
    }

    if ($scheduledFor !== null && $status !== 'published') {
        anateje_error('VALIDATION', 'Agendamento exige status publicado', 422);
    }

    $target = posts_parse_target($in, $tipo);

    $db->beginTransaction();
    try {
        $before = null;
        if ($id > 0) {
            $sb = $db->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
            $sb->execute([$id]);
            $before = $sb->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if ($id > 0) {
            $st = $db->prepare('UPDATE posts
                SET tipo=?, titulo=?, slug=?, conteudo=?, status=?, publicado_em=?, scheduled_for=?, target_categoria=?, target_status=?, target_uf=?, target_lotacao=?
                WHERE id=?');
            $st->execute([
                $tipo,
                $titulo,
                $slug ?: null,
                $conteudo ?: null,
                $status,
                $publicadoEm,
                $scheduledFor,
                $target['target_categoria'],
                $target['target_status'],
                $target['target_uf'],
                $target['target_lotacao'],
                $id
            ]);
        } else {
            $st = $db->prepare('INSERT INTO posts
                (tipo, titulo, slug, conteudo, status, publicado_em, scheduled_for, target_categoria, target_status, target_uf, target_lotacao)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([
                $tipo,
                $titulo,
                $slug ?: null,
                $conteudo ?: null,
                $status,
                $publicadoEm,
                $scheduledFor,
                $target['target_categoria'],
                $target['target_status'],
                $target['target_uf'],
                $target['target_lotacao']
            ]);
            $id = (int) $db->lastInsertId();
        }

        $sa = $db->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
        $sa->execute([$id]);
        $after = $sa->fetch(PDO::FETCH_ASSOC) ?: null;
        anateje_audit_log($db, $actorId, 'admin.comunicados', $before ? 'update' : 'create', 'post', $id, $before, $after, []);

        $db->commit();

        $estimate = ($tipo === 'COMUNICADO') ? posts_count_audience($db, $target) : null;
        anateje_ok(['id' => $id, 'audience_estimate' => $estimate]);
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            anateje_error('SLUG_DUPLICADO', 'Slug ja existente, informe outro slug', 422);
        }

        logError('Erro posts.admin_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar post', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro posts.admin_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar post', 500);
    }
}

if ($action === 'admin_delete') {
    anateje_require_permission($db, $auth, 'admin.comunicados.delete');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $id = (int) ($in['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $db->beginTransaction();
    try {
        $sb = $db->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
        $sb->execute([$id]);
        $before = $sb->fetch(PDO::FETCH_ASSOC);
        if (!$before) {
            throw new RuntimeException('POST_NOT_FOUND');
        }

        $st = $db->prepare('DELETE FROM posts WHERE id = ?');
        $st->execute([$id]);

        anateje_audit_log($db, $actorId, 'admin.comunicados', 'delete', 'post', $id, $before, null, []);
        $db->commit();
        anateje_ok(['deleted' => true]);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($e->getMessage() === 'POST_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Post nao encontrado', 404);
        }
        anateje_error('FAIL', 'Falha ao excluir post', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro posts.admin_delete: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao excluir post', 500);
    }
}

if ($action === 'admin_export_csv') {
    anateje_require_permission($db, $auth, 'admin.comunicados.export');

    $type = strtoupper(trim((string) ($_GET['type'] ?? 'COMUNICADO')));
    if (!in_array($type, ['COMUNICADO', 'BLOG'], true)) {
        $type = 'COMUNICADO';
    }

    $status = strtolower(trim((string) ($_GET['status'] ?? '')));
    if (!in_array($status, ['draft', 'published', 'archived', 'scheduled'], true)) {
        $status = '';
    }

    $q = trim((string) ($_GET['q'] ?? ''));
    if (strlen($q) > 120) {
        $q = substr($q, 0, 120);
    }

    $sql = "SELECT id, tipo, titulo, slug, status, publicado_em, scheduled_for,
            target_categoria, target_status, target_uf, target_lotacao, created_at
        FROM posts
        WHERE tipo = ?";
    $params = [$type];

    if ($status === 'scheduled') {
        $sql .= " AND status = 'published' AND scheduled_for IS NOT NULL AND scheduled_for > NOW()";
    } elseif ($status !== '') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    if ($q !== '') {
        $sql .= " AND (titulo LIKE ? OR slug LIKE ? OR conteudo LIKE ?)";
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' ORDER BY created_at DESC, id DESC';
    $st = $db->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $dataRows = [];
    foreach ($rows as $row) {
        $dataRows[] = [
            (string) ($row['id'] ?? ''),
            (string) ($row['tipo'] ?? ''),
            (string) ($row['titulo'] ?? ''),
            (string) ($row['slug'] ?? ''),
            (string) ($row['status'] ?? ''),
            (string) ($row['publicado_em'] ?? ''),
            (string) ($row['scheduled_for'] ?? ''),
            (string) ($row['target_categoria'] ?? ''),
            (string) ($row['target_status'] ?? ''),
            (string) ($row['target_uf'] ?? ''),
            (string) ($row['target_lotacao'] ?? ''),
            (string) ($row['created_at'] ?? ''),
        ];
    }

    $fileType = strtolower($type);
    posts_stream_csv(
        'posts-' . $fileType . '-admin.csv',
        ['id', 'tipo', 'titulo', 'slug', 'status', 'publicado_em', 'scheduled_for', 'target_categoria', 'target_status', 'target_uf', 'target_lotacao', 'created_at'],
        $dataRows
    );
}

if ($action === 'admin_bulk_status') {
    anateje_require_permission($db, $auth, 'admin.comunicados.edit');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $ids = posts_normalize_bulk_ids($in['ids'] ?? []);
    $targetStatus = strtolower(trim((string) ($in['status'] ?? '')));
    if (!in_array($targetStatus, ['draft', 'published', 'archived'], true)) {
        anateje_error('VALIDATION', 'Status alvo invalido para lote', 422);
    }
    if (!$ids) {
        anateje_error('VALIDATION', 'Selecione ao menos um comunicado', 422);
    }

    $targetTipo = strtoupper(trim((string) ($in['tipo'] ?? 'COMUNICADO')));
    if (!in_array($targetTipo, ['COMUNICADO', 'BLOG'], true)) {
        $targetTipo = 'COMUNICADO';
    }

    if ($targetStatus === 'published') {
        anateje_require_permission($db, $auth, 'admin.comunicados.publish');
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
        'ignored_type' => 0,
    ];

    $db->beginTransaction();
    try {
        $sel = $db->prepare('SELECT id, tipo, titulo, status, publicado_em, scheduled_for FROM posts WHERE id = ? LIMIT 1 FOR UPDATE');
        $upd = $db->prepare('UPDATE posts SET status = ?, publicado_em = ?, scheduled_for = ? WHERE id = ?');

        foreach ($ids as $id) {
            $sel->execute([$id]);
            $before = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$before) {
                $summary['not_found']++;
                continue;
            }

            if (strtoupper((string) ($before['tipo'] ?? '')) !== $targetTipo) {
                $summary['ignored_type']++;
                continue;
            }

            $oldStatus = strtolower((string) ($before['status'] ?? ''));
            $hasSchedule = trim((string) ($before['scheduled_for'] ?? '')) !== '';
            $hasPublishedAt = trim((string) ($before['publicado_em'] ?? '')) !== '';
            $needsUpdate = $oldStatus !== $targetStatus;

            if ($targetStatus === 'published') {
                if ($hasSchedule || !$hasPublishedAt) {
                    $needsUpdate = true;
                }
            } elseif ($hasSchedule || $hasPublishedAt) {
                $needsUpdate = true;
            }

            if (!$needsUpdate) {
                $summary['unchanged']++;
                continue;
            }

            $newPublishedAt = null;
            $newScheduledFor = null;
            if ($targetStatus === 'published') {
                $newPublishedAt = $hasPublishedAt ? (string) $before['publicado_em'] : date('Y-m-d H:i:s');
            }

            $upd->execute([$targetStatus, $newPublishedAt, $newScheduledFor, $id]);
            $after = $before;
            $after['status'] = $targetStatus;
            $after['publicado_em'] = $newPublishedAt;
            $after['scheduled_for'] = $newScheduledFor;

            anateje_audit_log(
                $db,
                $actorId,
                'admin.comunicados',
                'bulk_status',
                'post',
                $id,
                $before,
                $after,
                ['reason' => $reason !== '' ? $reason : null]
            );
            $summary['updated']++;
        }

        $db->commit();
        anateje_ok([
            'tipo' => $targetTipo,
            'target_status' => $targetStatus,
            'requested' => $summary['requested'],
            'updated' => $summary['updated'],
            'unchanged' => $summary['unchanged'],
            'not_found' => $summary['not_found'],
            'ignored_type' => $summary['ignored_type'],
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro posts.admin_bulk_status: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao atualizar comunicados em lote', 500);
    }
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
