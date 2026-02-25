<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';

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
    if (!in_array($status, ['registered', 'canceled'], true)) {
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

if ($action === 'list') {
    $rows = $db->query("SELECT id, titulo, descricao, local, inicio_em, fim_em, vagas, status, imagem_url, link FROM events WHERE status='published' ORDER BY inicio_em ASC")
        ->fetchAll(PDO::FETCH_ASSOC);

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

    $registration = null;
    $memberId = anateje_member_id($db, (int) $auth['sub']);
    if ($memberId) {
        $sr = $db->prepare('SELECT status FROM event_registrations WHERE event_id = ? AND member_id = ? LIMIT 1');
        $sr->execute([$id, $memberId]);
        $registration = $sr->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    anateje_ok(['event' => $event, 'registration' => $registration]);
}

if ($action === 'register') {
    anateje_require_method(['POST']);

    $memberId = anateje_member_id($db, (int) $auth['sub']);
    if (!$memberId) {
        anateje_error('NO_MEMBER', 'Complete seu perfil antes', 422);
    }

    $in = anateje_input();
    $eventId = (int) ($in['event_id'] ?? 0);
    if ($eventId <= 0) {
        anateje_error('VALIDATION', 'Evento invalido', 422);
    }

    $se = $db->prepare('SELECT id, vagas, status FROM events WHERE id = ? LIMIT 1');
    $se->execute([$eventId]);
    $event = $se->fetch(PDO::FETCH_ASSOC);

    if (!$event || $event['status'] !== 'published') {
        anateje_error('NOT_FOUND', 'Evento nao encontrado', 404);
    }

    $db->beginTransaction();
    try {
        if ($event['vagas'] !== null) {
            $sc = $db->prepare("SELECT COUNT(*) AS c FROM event_registrations WHERE event_id = ? AND status = 'registered'");
            $sc->execute([$eventId]);
            $count = (int) ($sc->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
            if ($count >= (int) $event['vagas']) {
                throw new RuntimeException('SEM_VAGAS');
            }
        }

        $si = $db->prepare("INSERT INTO event_registrations (event_id, member_id, status) VALUES (?, ?, 'registered')
            ON DUPLICATE KEY UPDATE status = VALUES(status)");
        $si->execute([$eventId, $memberId]);

        $db->commit();
        anateje_ok(['registered' => true]);
    } catch (RuntimeException $e) {
        $db->rollBack();
        if ($e->getMessage() === 'SEM_VAGAS') {
            anateje_error('SEM_VAGAS', 'Evento sem vagas', 422);
        }
        anateje_error('FAIL', 'Falha ao registrar no evento', 500);
    } catch (Throwable $e) {
        $db->rollBack();
        logError('Erro events.register: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao registrar no evento', 500);
    }
}

if ($action === 'cancel') {
    anateje_require_method(['POST']);

    $memberId = anateje_member_id($db, (int) $auth['sub']);
    if (!$memberId) {
        anateje_error('NO_MEMBER', 'Complete seu perfil antes', 422);
    }

    $in = anateje_input();
    $eventId = (int) ($in['event_id'] ?? 0);
    if ($eventId <= 0) {
        anateje_error('VALIDATION', 'Evento invalido', 422);
    }

    $st = $db->prepare("UPDATE event_registrations SET status = 'canceled' WHERE event_id = ? AND member_id = ?");
    $st->execute([$eventId, $memberId]);

    anateje_ok(['canceled' => true]);
}

if ($action === 'admin_list') {
    anateje_require_admin($auth);

    $rows = $db->query('SELECT * FROM events ORDER BY inicio_em DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
    anateje_ok(['events' => $rows]);
}

if ($action === 'admin_save') {
    anateje_require_admin($auth);
    anateje_require_method(['POST']);

    $in = anateje_input();
    $id = (int) ($in['id'] ?? 0);

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

    $db->beginTransaction();
    try {
        $payload = [
            $titulo,
            trim((string) ($in['descricao'] ?? '')) ?: null,
            trim((string) ($in['local'] ?? '')) ?: null,
            $inicio,
            $fim,
            $vagas,
            $status,
            trim((string) ($in['imagem_url'] ?? '')) ?: null,
            trim((string) ($in['link'] ?? '')) ?: null,
        ];

        if ($id > 0) {
            $st = $db->prepare('UPDATE events
                SET titulo=?, descricao=?, local=?, inicio_em=?, fim_em=?, vagas=?, status=?, imagem_url=?, link=?
                WHERE id=?');
            $payload[] = $id;
            $st->execute($payload);
        } else {
            $st = $db->prepare('INSERT INTO events
                (titulo, descricao, local, inicio_em, fim_em, vagas, status, imagem_url, link)
                VALUES (?,?,?,?,?,?,?,?,?)');
            $st->execute($payload);
            $id = (int) $db->lastInsertId();
        }

        $db->commit();
        anateje_ok(['id' => $id]);
    } catch (Throwable $e) {
        $db->rollBack();
        logError('Erro events.admin_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar evento', 500);
    }
}

if ($action === 'admin_delete') {
    anateje_require_admin($auth);

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM event_registrations WHERE event_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
        $db->commit();

        anateje_ok(['deleted' => true]);
    } catch (Throwable $e) {
        $db->rollBack();
        logError('Erro events.admin_delete: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao excluir evento', 500);
    }
}

if ($action === 'admin_registrations') {
    anateje_require_admin($auth);

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

    $listSql = "SELECT er.id, er.status, er.created_at,
            m.nome, m.email_funcional, m.telefone, m.categoria, m.status AS member_status,
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
    ]);
}

if ($action === 'admin_export_csv') {
    anateje_require_admin($auth);

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

    $st = $db->prepare("SELECT er.id, er.status, er.created_at,
            m.nome, m.email_funcional, m.telefone, m.categoria, m.status AS member_status,
            a.uf, a.cidade
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
        ['registration_id', 'created_at', 'status', 'nome', 'email_funcional', 'telefone', 'categoria', 'member_status', 'uf', 'cidade'],
        $rows
    );
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
