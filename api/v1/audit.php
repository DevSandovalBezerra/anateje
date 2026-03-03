<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';

function audit_parse_pagination(int $defaultPerPage = 30, int $maxPerPage = 200): array
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

function audit_parse_filters(): array
{
    $module = trim((string) ($_GET['module'] ?? ''));
    if ($module !== '' && !preg_match('/^[a-z0-9._-]{2,60}$/i', $module)) {
        $module = '';
    }

    $operation = trim((string) ($_GET['operation'] ?? ''));
    if ($operation !== '' && !preg_match('/^[a-z0-9._-]{2,60}$/i', $operation)) {
        $operation = '';
    }

    $entity = trim((string) ($_GET['entity'] ?? ''));
    if ($entity !== '' && !preg_match('/^[a-z0-9._-]{2,80}$/i', $entity)) {
        $entity = '';
    }

    $userId = (int) ($_GET['user_id'] ?? 0);
    if ($userId < 0) {
        $userId = 0;
    }

    $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
    if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $dateFrom = '';
    }

    $dateTo = trim((string) ($_GET['date_to'] ?? ''));
    if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $dateTo = '';
    }

    $q = trim((string) ($_GET['q'] ?? ''));
    if (strlen($q) > 120) {
        $q = substr($q, 0, 120);
    }

    return [
        'module' => $module,
        'operation' => $operation,
        'entity' => $entity,
        'user_id' => $userId,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'q' => $q,
    ];
}

function audit_where_sql(array $filters, array &$params): string
{
    $params = [];
    $where = ' WHERE 1=1';

    if (($filters['module'] ?? '') !== '') {
        $where .= ' AND al.modulo = ?';
        $params[] = $filters['module'];
    }
    if (($filters['operation'] ?? '') !== '') {
        $where .= ' AND al.acao = ?';
        $params[] = $filters['operation'];
    }
    if (($filters['entity'] ?? '') !== '') {
        $where .= ' AND al.entidade = ?';
        $params[] = $filters['entity'];
    }
    if ((int) ($filters['user_id'] ?? 0) > 0) {
        $where .= ' AND al.user_id = ?';
        $params[] = (int) $filters['user_id'];
    }
    if (($filters['date_from'] ?? '') !== '') {
        $where .= ' AND al.created_at >= ?';
        $params[] = $filters['date_from'] . ' 00:00:00';
    }
    if (($filters['date_to'] ?? '') !== '') {
        $where .= ' AND al.created_at <= ?';
        $params[] = $filters['date_to'] . ' 23:59:59';
    }
    if (($filters['q'] ?? '') !== '') {
        $where .= ' AND (
            al.modulo LIKE ?
            OR al.acao LIKE ?
            OR al.entidade LIKE ?
            OR CAST(al.entidade_id AS CHAR) LIKE ?
            OR u.nome LIKE ?
            OR u.email LIKE ?
            OR al.ip LIKE ?
        )';
        $like = '%' . $filters['q'] . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    return $where;
}

function audit_stream_csv(string $filename, array $header, array $rows): void
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
    anateje_require_permission($db, $auth, 'admin.auditoria.view');

    $filters = audit_parse_filters();
    $pagination = audit_parse_pagination(30, 200);
    $params = [];
    $where = audit_where_sql($filters, $params);

    $countSql = "SELECT COUNT(*) AS c
        FROM audit_logs al
        LEFT JOIN usuarios u ON u.id = al.user_id
        $where";
    $sc = $db->prepare($countSql);
    $sc->execute($params);
    $total = (int) ($sc->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

    $offset = (int) $pagination['offset'];
    $perPage = (int) $pagination['per_page'];
    $listSql = "SELECT
            al.id, al.user_id, al.modulo, al.acao, al.entidade, al.entidade_id,
            al.ip, al.user_agent, al.created_at,
            u.nome AS user_nome, u.email AS user_email
        FROM audit_logs al
        LEFT JOIN usuarios u ON u.id = al.user_id
        $where
        ORDER BY al.id DESC
        LIMIT $offset, $perPage";
    $sl = $db->prepare($listSql);
    $sl->execute($params);
    $logs = $sl->fetchAll(PDO::FETCH_ASSOC);

    anateje_ok([
        'logs' => $logs,
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

if ($action === 'admin_export_csv') {
    anateje_require_permission($db, $auth, 'admin.auditoria.export');

    $filters = audit_parse_filters();
    $params = [];
    $where = audit_where_sql($filters, $params);

    $sql = "SELECT
            al.id, al.user_id, al.modulo, al.acao, al.entidade, al.entidade_id,
            al.ip, al.user_agent, al.created_at,
            u.nome AS user_nome, u.email AS user_email
        FROM audit_logs al
        LEFT JOIN usuarios u ON u.id = al.user_id
        $where
        ORDER BY al.id DESC
        LIMIT 10000";
    $st = $db->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $csvRows = [];
    foreach ($rows as $row) {
        $csvRows[] = [
            (string) ($row['id'] ?? ''),
            (string) ($row['created_at'] ?? ''),
            (string) ($row['user_id'] ?? ''),
            (string) ($row['user_nome'] ?? ''),
            (string) ($row['user_email'] ?? ''),
            (string) ($row['modulo'] ?? ''),
            (string) ($row['acao'] ?? ''),
            (string) ($row['entidade'] ?? ''),
            (string) ($row['entidade_id'] ?? ''),
            (string) ($row['ip'] ?? ''),
            (string) ($row['user_agent'] ?? ''),
        ];
    }

    audit_stream_csv(
        'auditoria-' . date('Ymd-His') . '.csv',
        ['id', 'created_at', 'user_id', 'user_nome', 'user_email', 'modulo', 'acao', 'entidade', 'entidade_id', 'ip', 'user_agent'],
        $csvRows
    );
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
