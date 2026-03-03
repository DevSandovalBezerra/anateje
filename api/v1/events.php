<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';
$actorId = (int) $auth['sub'];

function events_stream_csv(string $filename, array $header, array $rows): void
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

function events_parse_pagination(int $defaultPerPage = 20, int $maxPerPage = 100): array
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

function events_parse_registration_filters(): array
{
    $status = strtolower(trim((string) ($_GET['status'] ?? '')));
    $allowed = ['registered', 'waitlisted', 'checked_in', 'canceled'];
    if (!in_array($status, $allowed, true)) {
        $status = '';
    }

    $categoria = strtoupper(trim((string) ($_GET['categoria'] ?? '')));
    if (!in_array($categoria, ['PARCIAL', 'INTEGRAL'], true)) {
        $categoria = '';
    }

    $q = trim((string) ($_GET['q'] ?? ''));
    if (strlen($q) > 120) {
        $q = substr($q, 0, 120);
    }

    return [
        'status' => $status,
        'categoria' => $categoria,
        'q' => $q,
    ];
}

function events_normalize_bulk_ids($rawIds): array
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

function events_registration_where_sql(int $eventId, array $filters, array &$params): string
{
    $params = [$eventId];
    $where = ' WHERE er.event_id = ?';

    if (($filters['status'] ?? '') !== '') {
        $where .= ' AND er.status = ?';
        $params[] = $filters['status'];
    }
    if (($filters['categoria'] ?? '') !== '') {
        $where .= ' AND m.categoria = ?';
        $params[] = $filters['categoria'];
    }
    if (($filters['q'] ?? '') !== '') {
        $where .= ' AND (m.nome LIKE ? OR m.email_funcional LIKE ? OR m.telefone LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    return $where;
}

function events_member_profile(PDO $db, int $userId): ?array
{
    $st = $db->prepare('SELECT id, categoria, status FROM members WHERE user_id = ? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'categoria' => (string) ($row['categoria'] ?? ''),
        'status' => (string) ($row['status'] ?? ''),
    ];
}

function events_access_allowed(array $event, array $member): bool
{
    $scope = strtoupper((string) ($event['access_scope'] ?? 'ALL'));
    if ($scope === 'ALL') {
        return true;
    }
    if ($scope === 'PARCIAL') {
        return strtoupper((string) ($member['categoria'] ?? '')) === 'PARCIAL';
    }
    if ($scope === 'INTEGRAL') {
        return strtoupper((string) ($member['categoria'] ?? '')) === 'INTEGRAL';
    }
    return true;
}

function events_count_occupied(PDO $db, int $eventId): int
{
    $st = $db->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND status IN ('registered','checked_in')");
    $st->execute([$eventId]);
    return (int) $st->fetchColumn();
}

function events_count_waitlisted(PDO $db, int $eventId): int
{
    $st = $db->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND status = 'waitlisted'");
    $st->execute([$eventId]);
    return (int) $st->fetchColumn();
}

function events_promote_waitlisted(PDO $db, int $eventId): ?array
{
    $st = $db->prepare("SELECT id, member_id
        FROM event_registrations
        WHERE event_id = ? AND status = 'waitlisted'
        ORDER BY COALESCE(waitlisted_at, created_at) ASC, id ASC
        LIMIT 1
        FOR UPDATE");
    $st->execute([$eventId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $up = $db->prepare("UPDATE event_registrations
        SET status = 'registered', canceled_at = NULL
        WHERE id = ?");
    $up->execute([(int) $row['id']]);

    return [
        'registration_id' => (int) $row['id'],
        'member_id' => (int) $row['member_id'],
    ];
}

if ($action === 'list') {
    $member = events_member_profile($db, $actorId);
    $memberId = $member ? (int) $member['id'] : 0;

    $sql = "SELECT
            e.id, e.titulo, e.descricao, e.local, e.inicio_em, e.fim_em, e.vagas, e.status,
            e.access_scope, e.waitlist_enabled, e.checkin_enabled, e.max_waitlist,
            e.imagem_url, e.link,
            (SELECT COUNT(*) FROM event_registrations er1 WHERE er1.event_id = e.id AND er1.status IN ('registered','checked_in')) AS occupied_count,
            (SELECT COUNT(*) FROM event_registrations er2 WHERE er2.event_id = e.id AND er2.status = 'waitlisted') AS waitlisted_count,
            (SELECT COUNT(*) FROM event_registrations er3 WHERE er3.event_id = e.id AND er3.status = 'checked_in') AS checked_in_count";

    if ($memberId > 0) {
        $sql .= ",
            (SELECT er4.status FROM event_registrations er4 WHERE er4.event_id = e.id AND er4.member_id = " . $memberId . " LIMIT 1) AS my_registration_status,
            (SELECT er5.checked_in_at FROM event_registrations er5 WHERE er5.event_id = e.id AND er5.member_id = " . $memberId . " LIMIT 1) AS my_checked_in_at";
    } else {
        $sql .= ", NULL AS my_registration_status, NULL AS my_checked_in_at";
    }

    $sql .= " FROM events e
        WHERE e.status = 'published'
        ORDER BY e.inicio_em ASC, e.id ASC";

    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $vagas = $row['vagas'] !== null ? (int) $row['vagas'] : null;
        $occupied = (int) ($row['occupied_count'] ?? 0);
        $row['vagas_restantes'] = $vagas === null ? null : max(0, $vagas - $occupied);
    }

    anateje_ok(['events' => $rows]);
}

if ($action === 'detail') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $st = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $event = $st->fetch(PDO::FETCH_ASSOC);
    if (!$event || ($event['status'] !== 'published' && empty($auth['is_admin']))) {
        anateje_error('NOT_FOUND', 'Evento nao encontrado', 404);
    }

    $member = events_member_profile($db, $actorId);
    $registration = null;
    if ($member) {
        $sr = $db->prepare('SELECT id, status, waitlisted_at, checked_in_at, canceled_at, created_at
            FROM event_registrations
            WHERE event_id = ? AND member_id = ? LIMIT 1');
        $sr->execute([$id, (int) $member['id']]);
        $registration = $sr->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $occupied = events_count_occupied($db, $id);
    $waitlisted = events_count_waitlisted($db, $id);
    $vagas = $event['vagas'] !== null ? (int) $event['vagas'] : null;
    $event['occupied_count'] = $occupied;
    $event['waitlisted_count'] = $waitlisted;
    $event['vagas_restantes'] = $vagas === null ? null : max(0, $vagas - $occupied);

    anateje_ok(['event' => $event, 'registration' => $registration]);
}

if ($action === 'register') {
    anateje_require_method(['POST']);

    $member = events_member_profile($db, $actorId);
    if (!$member) {
        anateje_error('NO_MEMBER', 'Complete seu perfil antes', 422);
    }
    if (strtoupper((string) ($member['status'] ?? '')) !== 'ATIVO') {
        anateje_error('MEMBER_INACTIVE', 'Somente associados ativos podem se inscrever', 422);
    }

    $in = anateje_input();
    $eventId = (int) ($in['event_id'] ?? 0);
    if ($eventId <= 0) {
        anateje_error('VALIDATION', 'Evento invalido', 422);
    }

    $db->beginTransaction();
    try {
        $se = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1 FOR UPDATE');
        $se->execute([$eventId]);
        $event = $se->fetch(PDO::FETCH_ASSOC);
        if (!$event || $event['status'] !== 'published') {
            throw new RuntimeException('EVENT_NOT_FOUND');
        }
        if (!events_access_allowed($event, $member)) {
            throw new RuntimeException('EVENT_NOT_ALLOWED_FOR_CATEGORY');
        }

        $sr = $db->prepare('SELECT id, status FROM event_registrations WHERE event_id = ? AND member_id = ? LIMIT 1 FOR UPDATE');
        $sr->execute([$eventId, (int) $member['id']]);
        $existing = $sr->fetch(PDO::FETCH_ASSOC);

        if ($existing && (string) $existing['status'] === 'checked_in') {
            throw new RuntimeException('ALREADY_CHECKED_IN');
        }

        $occupied = events_count_occupied($db, $eventId);
        $hasSlot = ($event['vagas'] === null) ? true : ($occupied < (int) $event['vagas']);

        $targetStatus = 'registered';
        $waitlistedAt = null;
        $checkedInAt = null;
        $canceledAt = null;

        if (!$hasSlot) {
            $waitlistEnabled = (int) ($event['waitlist_enabled'] ?? 0) === 1;
            if (!$waitlistEnabled) {
                throw new RuntimeException('SEM_VAGAS');
            }

            $maxWaitlist = $event['max_waitlist'] !== null ? (int) $event['max_waitlist'] : null;
            if ($maxWaitlist !== null && $maxWaitlist > 0) {
                $waitCount = events_count_waitlisted($db, $eventId);
                if (!$existing || (string) $existing['status'] !== 'waitlisted') {
                    if ($waitCount >= $maxWaitlist) {
                        throw new RuntimeException('WAITLIST_FULL');
                    }
                }
            }

            $targetStatus = 'waitlisted';
            $waitlistedAt = date('Y-m-d H:i:s');
        }

        if ($existing) {
            $up = $db->prepare("UPDATE event_registrations
                SET status = ?, waitlisted_at = ?, checked_in_at = ?, canceled_at = ?
                WHERE id = ?");
            $up->execute([$targetStatus, $waitlistedAt, $checkedInAt, $canceledAt, (int) $existing['id']]);
        } else {
            $ins = $db->prepare("INSERT INTO event_registrations
                (event_id, member_id, status, waitlisted_at, checked_in_at, canceled_at)
                VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute([$eventId, (int) $member['id'], $targetStatus, $waitlistedAt, $checkedInAt, $canceledAt]);
        }

        $db->commit();
        anateje_ok([
            'registered' => $targetStatus === 'registered',
            'waitlisted' => $targetStatus === 'waitlisted',
            'status' => $targetStatus,
        ]);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $code = $e->getMessage();
        if ($code === 'EVENT_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Evento nao encontrado', 404);
        }
        if ($code === 'EVENT_NOT_ALLOWED_FOR_CATEGORY') {
            anateje_error('FORBIDDEN', 'Evento indisponivel para sua categoria', 403);
        }
        if ($code === 'SEM_VAGAS') {
            anateje_error('SEM_VAGAS', 'Evento sem vagas', 422);
        }
        if ($code === 'WAITLIST_FULL') {
            anateje_error('WAITLIST_FULL', 'Fila de espera lotada', 422);
        }
        if ($code === 'ALREADY_CHECKED_IN') {
            anateje_error('ALREADY_CHECKED_IN', 'Presenca ja confirmada neste evento', 422);
        }
        anateje_error('FAIL', 'Falha ao registrar no evento', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro events.register: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao registrar no evento', 500);
    }
}

if ($action === 'cancel') {
    anateje_require_method(['POST']);

    $member = events_member_profile($db, $actorId);
    if (!$member) {
        anateje_error('NO_MEMBER', 'Complete seu perfil antes', 422);
    }

    $in = anateje_input();
    $eventId = (int) ($in['event_id'] ?? 0);
    if ($eventId <= 0) {
        anateje_error('VALIDATION', 'Evento invalido', 422);
    }

    $db->beginTransaction();
    try {
        $se = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1 FOR UPDATE');
        $se->execute([$eventId]);
        $event = $se->fetch(PDO::FETCH_ASSOC);
        if (!$event) {
            throw new RuntimeException('EVENT_NOT_FOUND');
        }

        $sr = $db->prepare('SELECT id, status FROM event_registrations WHERE event_id = ? AND member_id = ? LIMIT 1 FOR UPDATE');
        $sr->execute([$eventId, (int) $member['id']]);
        $reg = $sr->fetch(PDO::FETCH_ASSOC);
        if (!$reg) {
            $db->commit();
            anateje_ok(['canceled' => true, 'promoted' => false]);
        }

        $prevStatus = (string) ($reg['status'] ?? '');
        if ($prevStatus !== 'canceled') {
            $up = $db->prepare("UPDATE event_registrations SET status = 'canceled', canceled_at = ? WHERE id = ?");
            $up->execute([date('Y-m-d H:i:s'), (int) $reg['id']]);
        }

        $promoted = null;
        if (in_array($prevStatus, ['registered', 'checked_in'], true)) {
            $promoted = events_promote_waitlisted($db, $eventId);
        }

        $db->commit();
        anateje_ok([
            'canceled' => true,
            'promoted' => $promoted !== null,
            'promoted_registration_id' => $promoted['registration_id'] ?? null,
        ]);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($e->getMessage() === 'EVENT_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Evento nao encontrado', 404);
        }
        anateje_error('FAIL', 'Falha ao cancelar inscricao', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro events.cancel: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao cancelar inscricao', 500);
    }
}

if ($action === 'admin_list') {
    anateje_require_permission($db, $auth, 'admin.eventos.view');

    $rows = $db->query("SELECT
            e.*,
            (SELECT COUNT(*) FROM event_registrations r1 WHERE r1.event_id = e.id AND r1.status IN ('registered','checked_in')) AS occupied_count,
            (SELECT COUNT(*) FROM event_registrations r2 WHERE r2.event_id = e.id AND r2.status = 'waitlisted') AS waitlisted_count,
            (SELECT COUNT(*) FROM event_registrations r3 WHERE r3.event_id = e.id AND r3.status = 'checked_in') AS checked_in_count
        FROM events e
        ORDER BY e.inicio_em DESC, e.id DESC")->fetchAll(PDO::FETCH_ASSOC);

    $statusCounts = [
        'draft' => 0,
        'published' => 0,
        'archived' => 0,
        'other' => 0,
    ];
    foreach ($rows as &$row) {
        $vagas = $row['vagas'] !== null ? (int) $row['vagas'] : null;
        $occupied = (int) ($row['occupied_count'] ?? 0);
        $row['vagas_restantes'] = $vagas === null ? null : max(0, $vagas - $occupied);

        $status = strtolower((string) ($row['status'] ?? ''));
        if (isset($statusCounts[$status])) {
            $statusCounts[$status]++;
        } else {
            $statusCounts['other']++;
        }
    }
    unset($row);

    anateje_ok([
        'events' => $rows,
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
    anateje_require_permission($db, $auth, $id > 0 ? 'admin.eventos.edit' : 'admin.eventos.create');

    $titulo = trim((string) ($in['titulo'] ?? ''));
    if ($titulo === '') {
        anateje_error('VALIDATION', 'Titulo e obrigatorio', 422);
    }

    $inicio = anateje_parse_datetime($in['inicio_em'] ?? '');
    if ($inicio === null) {
        anateje_error('VALIDATION', 'Data/hora de inicio invalida', 422);
    }

    $fim = anateje_parse_datetime($in['fim_em'] ?? '');
    $status = (string) ($in['status'] ?? 'draft');
    if (!in_array($status, ['draft', 'published', 'archived'], true)) {
        $status = 'draft';
    }

    $vagasRaw = $in['vagas'] ?? null;
    $vagas = ($vagasRaw === '' || $vagasRaw === null) ? null : max(0, (int) $vagasRaw);

    $accessScope = strtoupper(trim((string) ($in['access_scope'] ?? 'ALL')));
    if (!in_array($accessScope, ['ALL', 'PARCIAL', 'INTEGRAL'], true)) {
        $accessScope = 'ALL';
    }

    $waitlistEnabled = !empty($in['waitlist_enabled']) ? 1 : 0;
    $checkinEnabled = !empty($in['checkin_enabled']) ? 1 : 0;
    $maxWaitlistRaw = $in['max_waitlist'] ?? null;
    $maxWaitlist = ($maxWaitlistRaw === '' || $maxWaitlistRaw === null) ? null : max(0, (int) $maxWaitlistRaw);
    if ($waitlistEnabled === 0) {
        $maxWaitlist = null;
    }

    $db->beginTransaction();
    try {
        $before = null;
        if ($id > 0) {
            $sb = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
            $sb->execute([$id]);
            $before = $sb->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $payload = [
            $titulo,
            trim((string) ($in['descricao'] ?? '')) ?: null,
            trim((string) ($in['local'] ?? '')) ?: null,
            $inicio,
            $fim,
            $vagas,
            $status,
            $accessScope,
            $waitlistEnabled,
            $checkinEnabled,
            $maxWaitlist,
            trim((string) ($in['imagem_url'] ?? '')) ?: null,
            trim((string) ($in['link'] ?? '')) ?: null,
        ];

        if ($id > 0) {
            $st = $db->prepare('UPDATE events
                SET titulo=?, descricao=?, local=?, inicio_em=?, fim_em=?, vagas=?, status=?, access_scope=?, waitlist_enabled=?, checkin_enabled=?, max_waitlist=?, imagem_url=?, link=?
                WHERE id=?');
            $payload[] = $id;
            $st->execute($payload);
        } else {
            $st = $db->prepare('INSERT INTO events
                (titulo, descricao, local, inicio_em, fim_em, vagas, status, access_scope, waitlist_enabled, checkin_enabled, max_waitlist, imagem_url, link)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute($payload);
            $id = (int) $db->lastInsertId();
        }

        $sa = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $sa->execute([$id]);
        $after = $sa->fetch(PDO::FETCH_ASSOC) ?: null;
        anateje_audit_log($db, $actorId, 'admin.eventos', $before ? 'update' : 'create', 'event', $id, $before, $after, []);

        $db->commit();
        anateje_ok(['id' => $id]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro events.admin_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar evento', 500);
    }
}

if ($action === 'admin_delete') {
    anateje_require_permission($db, $auth, 'admin.eventos.delete');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $id = (int) ($in['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $db->beginTransaction();
    try {
        $se = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $se->execute([$id]);
        $before = $se->fetch(PDO::FETCH_ASSOC);
        if (!$before) {
            throw new RuntimeException('EVENT_NOT_FOUND');
        }

        $db->prepare('DELETE FROM event_registrations WHERE event_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
        anateje_audit_log($db, $actorId, 'admin.eventos', 'delete', 'event', $id, $before, null, []);
        $db->commit();

        anateje_ok(['deleted' => true]);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($e->getMessage() === 'EVENT_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Evento nao encontrado', 404);
        }
        anateje_error('FAIL', 'Falha ao excluir evento', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro events.admin_delete: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao excluir evento', 500);
    }
}

if ($action === 'admin_bulk_status') {
    anateje_require_permission($db, $auth, 'admin.eventos.edit');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $ids = events_normalize_bulk_ids($in['ids'] ?? []);
    $targetStatus = strtolower(trim((string) ($in['status'] ?? '')));
    if (!in_array($targetStatus, ['draft', 'published', 'archived'], true)) {
        anateje_error('VALIDATION', 'Status alvo invalido para lote', 422);
    }
    if (!$ids) {
        anateje_error('VALIDATION', 'Selecione ao menos um evento', 422);
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
        $sel = $db->prepare('SELECT id, titulo, status, inicio_em, fim_em FROM events WHERE id = ? LIMIT 1 FOR UPDATE');
        $upd = $db->prepare('UPDATE events SET status = ? WHERE id = ?');

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
                'admin.eventos',
                'bulk_status',
                'event',
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
        logError('Erro events.admin_bulk_status: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao atualizar status em lote', 500);
    }
}

if ($action === 'admin_registrations') {
    anateje_require_permission($db, $auth, 'admin.eventos.view');

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $filters = events_parse_registration_filters();
    $pagination = events_parse_pagination(20, 100);
    $params = [];
    $where = events_registration_where_sql($id, $filters, $params);

    $countSql = "SELECT COUNT(*) AS c
        FROM event_registrations er
        LEFT JOIN members m ON m.id = er.member_id
        $where";
    $sc = $db->prepare($countSql);
    $sc->execute($params);
    $total = (int) ($sc->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

    $offset = (int) $pagination['offset'];
    $perPage = (int) $pagination['per_page'];

    $listSql = "SELECT er.id, er.status, er.created_at, er.waitlisted_at, er.checked_in_at, er.canceled_at,
            m.id AS member_id, m.nome, m.email_funcional, m.telefone, m.categoria, m.status AS member_status,
            a.uf, a.cidade
        FROM event_registrations er
        LEFT JOIN members m ON m.id = er.member_id
        LEFT JOIN addresses a ON a.member_id = m.id
        $where
        ORDER BY er.created_at DESC, er.id DESC
        LIMIT $offset, $perPage";
    $sl = $db->prepare($listSql);
    $sl->execute($params);

    anateje_ok([
        'registrations' => $sl->fetchAll(PDO::FETCH_ASSOC),
        'filters' => $filters,
        'pagination' => [
            'page' => $pagination['page'],
            'per_page' => $pagination['per_page'],
            'total' => $total,
            'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
        ],
        'meta' => [
            'filters' => $filters,
            'pagination' => [
                'page' => $pagination['page'],
                'per_page' => $pagination['per_page'],
                'total' => $total,
                'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
            ]
        ],
    ]);
}

if ($action === 'admin_checkin') {
    anateje_require_permission($db, $auth, 'admin.eventos.checkin');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $registrationId = (int) ($in['registration_id'] ?? 0);
    $checked = !empty($in['checked']) ? 1 : 0;
    if ($registrationId <= 0) {
        anateje_error('VALIDATION', 'Registro invalido', 422);
    }

    $db->beginTransaction();
    try {
        $st = $db->prepare("SELECT er.*, e.id AS event_id, e.checkin_enabled
            FROM event_registrations er
            INNER JOIN events e ON e.id = er.event_id
            WHERE er.id = ?
            LIMIT 1
            FOR UPDATE");
        $st->execute([$registrationId]);
        $reg = $st->fetch(PDO::FETCH_ASSOC);
        if (!$reg) {
            throw new RuntimeException('REG_NOT_FOUND');
        }

        if ($checked === 1) {
            if ((int) ($reg['checkin_enabled'] ?? 0) !== 1) {
                throw new RuntimeException('CHECKIN_DISABLED');
            }
            if (!in_array((string) $reg['status'], ['registered', 'checked_in'], true)) {
                throw new RuntimeException('CHECKIN_INVALID_STATUS');
            }

            $up = $db->prepare("UPDATE event_registrations
                SET status = 'checked_in', checked_in_at = COALESCE(checked_in_at, ?)
                WHERE id = ?");
            $up->execute([date('Y-m-d H:i:s'), $registrationId]);
        } else {
            if ((string) $reg['status'] !== 'checked_in') {
                throw new RuntimeException('UNCHECK_INVALID_STATUS');
            }
            $up = $db->prepare("UPDATE event_registrations
                SET status = 'registered', checked_in_at = NULL
                WHERE id = ?");
            $up->execute([$registrationId]);
        }

        $after = $db->prepare('SELECT id, event_id, member_id, status, checked_in_at FROM event_registrations WHERE id = ? LIMIT 1');
        $after->execute([$registrationId]);
        anateje_audit_log(
            $db,
            $actorId,
            'admin.eventos',
            $checked === 1 ? 'checkin' : 'checkout',
            'event_registration',
            $registrationId,
            ['status' => $reg['status'], 'checked_in_at' => $reg['checked_in_at']],
            $after->fetch(PDO::FETCH_ASSOC),
            []
        );

        $db->commit();
        anateje_ok(['updated' => true]);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $code = $e->getMessage();
        if ($code === 'REG_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Inscricao nao encontrada', 404);
        }
        if ($code === 'CHECKIN_DISABLED') {
            anateje_error('VALIDATION', 'Check-in desabilitado para este evento', 422);
        }
        if ($code === 'CHECKIN_INVALID_STATUS') {
            anateje_error('VALIDATION', 'Somente inscritos confirmados podem fazer check-in', 422);
        }
        if ($code === 'UNCHECK_INVALID_STATUS') {
            anateje_error('VALIDATION', 'Registro nao esta em check-in', 422);
        }
        anateje_error('FAIL', 'Falha ao atualizar check-in', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro events.admin_checkin: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao atualizar check-in', 500);
    }
}

if ($action === 'admin_registration_status') {
    anateje_require_permission($db, $auth, 'admin.eventos.waitlist');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $registrationId = (int) ($in['registration_id'] ?? 0);
    $targetStatus = strtolower(trim((string) ($in['status'] ?? '')));
    if ($registrationId <= 0 || !in_array($targetStatus, ['registered', 'waitlisted', 'canceled'], true)) {
        anateje_error('VALIDATION', 'Dados invalidos para atualizar inscricao', 422);
    }

    $db->beginTransaction();
    try {
        $st = $db->prepare("SELECT er.*, e.id AS event_id, e.vagas, e.waitlist_enabled, e.max_waitlist
            FROM event_registrations er
            INNER JOIN events e ON e.id = er.event_id
            WHERE er.id = ?
            LIMIT 1
            FOR UPDATE");
        $st->execute([$registrationId]);
        $reg = $st->fetch(PDO::FETCH_ASSOC);
        if (!$reg) {
            throw new RuntimeException('REG_NOT_FOUND');
        }

        $oldStatus = (string) ($reg['status'] ?? '');
        $eventId = (int) ($reg['event_id'] ?? 0);
        if ($eventId <= 0) {
            throw new RuntimeException('EVENT_NOT_FOUND');
        }

        $waitlistedAt = $reg['waitlisted_at'] ?? null;
        $checkedInAt = $reg['checked_in_at'] ?? null;
        $canceledAt = $reg['canceled_at'] ?? null;

        if ($targetStatus === 'registered') {
            $occupiesBefore = in_array($oldStatus, ['registered', 'checked_in'], true);
            if (!$occupiesBefore && $reg['vagas'] !== null) {
                $occupied = events_count_occupied($db, $eventId);
                if ($occupied >= (int) $reg['vagas']) {
                    throw new RuntimeException('SEM_VAGAS');
                }
            }
            $checkedInAt = null;
            $canceledAt = null;
        } elseif ($targetStatus === 'waitlisted') {
            if ((int) ($reg['waitlist_enabled'] ?? 0) !== 1) {
                throw new RuntimeException('WAITLIST_DISABLED');
            }
            $maxWaitlist = $reg['max_waitlist'] !== null ? (int) $reg['max_waitlist'] : null;
            if ($maxWaitlist !== null && $maxWaitlist > 0 && $oldStatus !== 'waitlisted') {
                $waitCount = events_count_waitlisted($db, $eventId);
                if ($waitCount >= $maxWaitlist) {
                    throw new RuntimeException('WAITLIST_FULL');
                }
            }
            $waitlistedAt = $waitlistedAt ?: date('Y-m-d H:i:s');
            $checkedInAt = null;
            $canceledAt = null;
        } else {
            $targetStatus = 'canceled';
            $canceledAt = date('Y-m-d H:i:s');
            $checkedInAt = null;
        }

        $up = $db->prepare('UPDATE event_registrations
            SET status = ?, waitlisted_at = ?, checked_in_at = ?, canceled_at = ?
            WHERE id = ?');
        $up->execute([$targetStatus, $waitlistedAt, $checkedInAt, $canceledAt, $registrationId]);

        $promoted = null;
        if ($targetStatus === 'canceled' && in_array($oldStatus, ['registered', 'checked_in'], true)) {
            $promoted = events_promote_waitlisted($db, $eventId);
        }

        $sa = $db->prepare('SELECT id, event_id, member_id, status, waitlisted_at, checked_in_at, canceled_at
            FROM event_registrations WHERE id = ? LIMIT 1');
        $sa->execute([$registrationId]);
        $after = $sa->fetch(PDO::FETCH_ASSOC);

        anateje_audit_log(
            $db,
            $actorId,
            'admin.eventos',
            'registration_status',
            'event_registration',
            $registrationId,
            ['status' => $oldStatus],
            $after,
            ['promoted_registration_id' => $promoted['registration_id'] ?? null]
        );

        $db->commit();
        anateje_ok([
            'updated' => true,
            'promoted' => $promoted !== null,
            'promoted_registration_id' => $promoted['registration_id'] ?? null,
        ]);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $code = $e->getMessage();
        if ($code === 'REG_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Inscricao nao encontrada', 404);
        }
        if ($code === 'EVENT_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Evento nao encontrado', 404);
        }
        if ($code === 'SEM_VAGAS') {
            anateje_error('SEM_VAGAS', 'Nao ha vagas disponiveis', 422);
        }
        if ($code === 'WAITLIST_DISABLED') {
            anateje_error('VALIDATION', 'Fila de espera desabilitada para este evento', 422);
        }
        if ($code === 'WAITLIST_FULL') {
            anateje_error('WAITLIST_FULL', 'Fila de espera lotada', 422);
        }
        anateje_error('FAIL', 'Falha ao atualizar status da inscricao', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro events.admin_registration_status: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao atualizar status da inscricao', 500);
    }
}

if ($action === 'admin_promote_waitlist') {
    anateje_require_permission($db, $auth, 'admin.eventos.waitlist');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $eventId = (int) ($in['event_id'] ?? 0);
    if ($eventId <= 0) {
        anateje_error('VALIDATION', 'Evento invalido', 422);
    }

    $db->beginTransaction();
    try {
        $se = $db->prepare('SELECT id, titulo, vagas FROM events WHERE id = ? LIMIT 1 FOR UPDATE');
        $se->execute([$eventId]);
        $event = $se->fetch(PDO::FETCH_ASSOC);
        if (!$event) {
            throw new RuntimeException('EVENT_NOT_FOUND');
        }

        if ($event['vagas'] !== null) {
            $occupied = events_count_occupied($db, $eventId);
            if ($occupied >= (int) $event['vagas']) {
                throw new RuntimeException('SEM_VAGAS');
            }
        }

        $promoted = events_promote_waitlisted($db, $eventId);
        if (!$promoted) {
            throw new RuntimeException('WAITLIST_EMPTY');
        }

        anateje_audit_log($db, $actorId, 'admin.eventos', 'promote_waitlist', 'event', $eventId, null, $promoted, []);

        $db->commit();
        anateje_ok(['promoted' => true, 'registration_id' => $promoted['registration_id']]);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $code = $e->getMessage();
        if ($code === 'EVENT_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Evento nao encontrado', 404);
        }
        if ($code === 'SEM_VAGAS') {
            anateje_error('SEM_VAGAS', 'Nao ha vagas livres para promover fila', 422);
        }
        if ($code === 'WAITLIST_EMPTY') {
            anateje_error('WAITLIST_EMPTY', 'Nao ha inscritos em fila de espera', 422);
        }
        anateje_error('FAIL', 'Falha ao promover fila de espera', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro events.admin_promote_waitlist: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao promover fila de espera', 500);
    }
}

if ($action === 'admin_export_csv') {
    anateje_require_permission($db, $auth, 'admin.eventos.export');

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $se = $db->prepare('SELECT id, titulo FROM events WHERE id = ? LIMIT 1');
    $se->execute([$id]);
    $event = $se->fetch(PDO::FETCH_ASSOC);
    if (!$event) {
        anateje_error('NOT_FOUND', 'Evento nao encontrado', 404);
    }

    $filters = events_parse_registration_filters();
    $params = [];
    $where = events_registration_where_sql($id, $filters, $params);

    $st = $db->prepare("SELECT er.id, er.status, er.created_at, er.waitlisted_at, er.checked_in_at, er.canceled_at,
            m.nome, m.email_funcional, m.telefone, m.categoria, m.status AS member_status, a.uf, a.cidade
        FROM event_registrations er
        LEFT JOIN members m ON m.id = er.member_id
        LEFT JOIN addresses a ON a.member_id = m.id
        $where
        ORDER BY er.created_at DESC, er.id DESC");
    $st->execute($params);
    $registrations = $st->fetchAll(PDO::FETCH_ASSOC);

    $rows = [];
    foreach ($registrations as $reg) {
        $rows[] = [
            (string) ($reg['id'] ?? ''),
            (string) ($reg['created_at'] ?? ''),
            (string) ($reg['status'] ?? ''),
            (string) ($reg['waitlisted_at'] ?? ''),
            (string) ($reg['checked_in_at'] ?? ''),
            (string) ($reg['canceled_at'] ?? ''),
            (string) ($reg['nome'] ?? ''),
            (string) ($reg['email_funcional'] ?? ''),
            (string) ($reg['telefone'] ?? ''),
            (string) ($reg['categoria'] ?? ''),
            (string) ($reg['member_status'] ?? ''),
            (string) ($reg['uf'] ?? ''),
            (string) ($reg['cidade'] ?? ''),
        ];
    }

    $safeTitle = preg_replace('/[^a-z0-9\-]+/i', '-', (string) ($event['titulo'] ?? 'evento'));
    $safeTitle = trim((string) $safeTitle, '-');
    if ($safeTitle === '') {
        $safeTitle = 'evento';
    }

    events_stream_csv(
        'event-' . $id . '-' . $safeTitle . '-inscritos.csv',
        ['registration_id', 'created_at', 'status', 'waitlisted_at', 'checked_in_at', 'canceled_at', 'nome', 'email_funcional', 'telefone', 'categoria', 'member_status', 'uf', 'cidade'],
        $rows
    );
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
