<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';

function dashboard_stream_csv(string $filename, array $header, array $rows): void
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

if ($action === 'member_summary') {
    $userId = (int) $auth['sub'];
    $memberId = anateje_member_id($db, $userId);

    $member = null;
    $address = null;
    $missingFields = [];
    $activeBenefits = 0;
    $activeBenefitsList = [];
    $registeredUpcoming = 0;

    if ($memberId) {
        $sm = $db->prepare('SELECT id, nome, categoria, status, data_filiacao, contribuicao_mensal, telefone, email_funcional, cpf FROM members WHERE id = ? LIMIT 1');
        $sm->execute([$memberId]);
        $member = $sm->fetch(PDO::FETCH_ASSOC) ?: null;

        $sa = $db->prepare('SELECT cep, logradouro, numero, complemento, bairro, cidade, uf FROM addresses WHERE member_id = ? LIMIT 1');
        $sa->execute([$memberId]);
        $address = $sa->fetch(PDO::FETCH_ASSOC) ?: null;

        $required = [
            'cpf' => $member['cpf'] ?? '',
            'telefone' => $member['telefone'] ?? '',
            'email_funcional' => $member['email_funcional'] ?? '',
            'data_filiacao' => $member['data_filiacao'] ?? '',
        ];
        foreach ($required as $field => $value) {
            if (trim((string) $value) === '') {
                $missingFields[] = $field;
            }
        }

        $sb = $db->prepare('SELECT COUNT(*) FROM member_benefits WHERE member_id = ? AND ativo = 1');
        $sb->execute([$memberId]);
        $activeBenefits = (int) $sb->fetchColumn();

        $sbl = $db->prepare("SELECT b.id, b.nome
            FROM member_benefits mb
            INNER JOIN benefits b ON b.id = mb.benefit_id
            WHERE mb.member_id = ? AND mb.ativo = 1 AND b.status = 'active'
            ORDER BY b.sort_order ASC, b.id ASC
            LIMIT 6");
        $sbl->execute([$memberId]);
        $activeBenefitsList = $sbl->fetchAll(PDO::FETCH_ASSOC);

        $sr = $db->prepare("SELECT COUNT(*)
            FROM event_registrations er
            INNER JOIN events e ON e.id = er.event_id
            WHERE er.member_id = ? AND er.status = 'registered'
              AND e.status = 'published'
              AND e.inicio_em >= NOW()");
        $sr->execute([$memberId]);
        $registeredUpcoming = (int) $sr->fetchColumn();
    }

    $upcomingPublicEvents = (int) $db->query("SELECT COUNT(*) FROM events WHERE status = 'published' AND inicio_em >= NOW()")->fetchColumn();

    $nextEventsSql = "SELECT e.id, e.titulo, e.local, e.inicio_em, e.fim_em, e.link";
    if ($memberId) {
        $nextEventsSql .= ", er.status AS registration_status";
    } else {
        $nextEventsSql .= ", NULL AS registration_status";
    }
    $nextEventsSql .= "
        FROM events e";
    if ($memberId) {
        $nextEventsSql .= " LEFT JOIN event_registrations er ON er.event_id = e.id AND er.member_id = :member_id";
    }
    $nextEventsSql .= "
        WHERE e.status = 'published' AND e.inicio_em >= NOW()
        ORDER BY e.inicio_em ASC, e.id ASC
        LIMIT 5";

    $se = $db->prepare($nextEventsSql);
    if ($memberId) {
        $se->bindValue(':member_id', $memberId, PDO::PARAM_INT);
    }
    $se->execute();
    $nextEvents = $se->fetchAll(PDO::FETCH_ASSOC);

    $sp = $db->query("SELECT id, titulo, publicado_em, created_at
        FROM posts
        WHERE tipo = 'COMUNICADO' AND status = 'published'
        ORDER BY COALESCE(publicado_em, created_at) DESC, id DESC
        LIMIT 6");
    $comunicados = $sp->fetchAll(PDO::FETCH_ASSOC);

    anateje_ok([
        'member' => $member,
        'address' => $address,
        'profile_complete' => $memberId !== null && count($missingFields) === 0,
        'missing_fields' => $missingFields,
        'counts' => [
            'active_benefits' => $activeBenefits,
            'upcoming_public_events' => $upcomingPublicEvents,
            'registered_upcoming_events' => $registeredUpcoming,
            'recent_comunicados' => count($comunicados),
        ],
        'active_benefits_list' => $activeBenefitsList,
        'next_events' => $nextEvents,
        'comunicados' => $comunicados,
    ]);
}

if ($action === 'admin_summary') {
    anateje_require_permission($db, $auth, 'dashboard.admin');

    $normalizeDate = static function ($value): string {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        if (!checkdate($m, $d, $y)) {
            return '';
        }
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    };

    $dateFrom = $normalizeDate($_GET['date_from'] ?? '');
    $dateTo = $normalizeDate($_GET['date_to'] ?? '');
    if ($dateFrom !== '' && $dateTo !== '' && strcmp($dateFrom, $dateTo) > 0) {
        $tmp = $dateFrom;
        $dateFrom = $dateTo;
        $dateTo = $tmp;
    }

    $rangeClause = static function (string $column, string $from, string $to): array {
        $sql = '';
        $params = [];
        if ($from !== '') {
            $sql .= " AND {$column} >= ?";
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $sql .= " AND {$column} <= ?";
            $params[] = $to . ' 23:59:59';
        }
        return [$sql, $params];
    };

    $totalMembers = (int) $db->query('SELECT COUNT(*) FROM members')->fetchColumn();
    $activeMembers = (int) $db->query("SELECT COUNT(*) FROM members WHERE status = 'ATIVO'")->fetchColumn();
    $inactiveMembers = (int) $db->query("SELECT COUNT(*) FROM members WHERE status = 'INATIVO'")->fetchColumn();

    $categoryRows = $db->query("SELECT categoria, COUNT(*) AS total
        FROM members
        GROUP BY categoria
        ORDER BY categoria ASC")->fetchAll(PDO::FETCH_ASSOC);
    $membersByCategory = [];
    foreach ($categoryRows as $row) {
        $membersByCategory[(string) $row['categoria']] = (int) $row['total'];
    }

    [$eventsRangeSql, $eventsRangeParams] = $rangeClause('e.inicio_em', $dateFrom, $dateTo);
    $stUpcoming = $db->prepare("SELECT COUNT(*)
        FROM events e
        WHERE e.status = 'published' AND e.inicio_em >= NOW(){$eventsRangeSql}");
    $stUpcoming->execute($eventsRangeParams);
    $upcomingPublishedEvents = (int) $stUpcoming->fetchColumn();

    [$eventsCreatedRangeSql, $eventsCreatedRangeParams] = $rangeClause('e.created_at', $dateFrom, $dateTo);
    $stDraft = $db->prepare("SELECT COUNT(*)
        FROM events e
        WHERE e.status = 'draft'{$eventsCreatedRangeSql}");
    $stDraft->execute($eventsCreatedRangeParams);
    $draftEvents = (int) $stDraft->fetchColumn();

    [$postsRangeSql, $postsRangeParams] = $rangeClause('COALESCE(p.publicado_em, p.created_at)', $dateFrom, $dateTo);
    $stCom = $db->prepare("SELECT COUNT(*)
        FROM posts p
        WHERE p.tipo = 'COMUNICADO' AND p.status = 'published'{$postsRangeSql}");
    $stCom->execute($postsRangeParams);
    $publishedComunicados = (int) $stCom->fetchColumn();

    [$campaignRangeSql, $campaignRangeParams] = $rangeClause('c.created_at', $dateFrom, $dateTo);
    $stCampTotal = $db->prepare("SELECT COUNT(*) FROM campaigns c WHERE 1=1{$campaignRangeSql}");
    $stCampTotal->execute($campaignRangeParams);
    $totalCampaigns = (int) $stCampTotal->fetchColumn();

    $stCampProc = $db->prepare("SELECT COUNT(*)
        FROM campaigns c
        WHERE c.status IN ('queued', 'processing'){$campaignRangeSql}");
    $stCampProc->execute($campaignRangeParams);
    $processingCampaigns = (int) $stCampProc->fetchColumn();

    $stEvents = $db->prepare("SELECT e.id, e.titulo, e.local, e.status, e.inicio_em, e.vagas,
            (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id AND er.status = 'registered') AS inscritos
        FROM events e
        WHERE e.inicio_em >= NOW(){$eventsRangeSql}
        ORDER BY e.inicio_em ASC, e.id DESC
        LIMIT 6");
    $stEvents->execute($eventsRangeParams);
    $recentEvents = $stEvents->fetchAll(PDO::FETCH_ASSOC);

    $stRecentCampaigns = $db->prepare("SELECT c.id, c.titulo, c.canal, c.status, c.created_at,
            (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id) AS total_logs,
            (SELECT MAX(r.started_at) FROM campaign_runs r WHERE r.campaign_id = c.id) AS last_run_at
        FROM campaigns c
        WHERE 1=1{$campaignRangeSql}
        ORDER BY c.created_at DESC, c.id DESC
        LIMIT 6");
    $stRecentCampaigns->execute($campaignRangeParams);
    $recentCampaigns = $stRecentCampaigns->fetchAll(PDO::FETCH_ASSOC);

    [$membersRangeSql, $membersRangeParams] = $rangeClause('m.created_at', $dateFrom, $dateTo);
    $stRecentMembers = $db->prepare("SELECT m.id, m.nome, m.categoria, m.status, m.email_funcional, m.telefone, m.created_at
        FROM members m
        WHERE 1=1{$membersRangeSql}
        ORDER BY m.created_at DESC, m.id DESC
        LIMIT 6");
    $stRecentMembers->execute($membersRangeParams);
    $recentMembers = $stRecentMembers->fetchAll(PDO::FETCH_ASSOC);

    anateje_ok([
        'filters' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ],
        'counts' => [
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'inactive_members' => $inactiveMembers,
            'upcoming_published_events' => $upcomingPublishedEvents,
            'draft_events' => $draftEvents,
            'published_comunicados' => $publishedComunicados,
            'total_campaigns' => $totalCampaigns,
            'processing_campaigns' => $processingCampaigns,
        ],
        'members_by_category' => $membersByCategory,
        'upcoming_events' => $recentEvents,
        'recent_campaigns' => $recentCampaigns,
        'recent_members' => $recentMembers,
    ]);
}

if ($action === 'admin_export_csv') {
    anateje_require_permission($db, $auth, 'dashboard.admin');

    $normalizeDate = static function ($value): string {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        if (!checkdate($m, $d, $y)) {
            return '';
        }
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    };

    $dateFrom = $normalizeDate($_GET['date_from'] ?? '');
    $dateTo = $normalizeDate($_GET['date_to'] ?? '');
    if ($dateFrom !== '' && $dateTo !== '' && strcmp($dateFrom, $dateTo) > 0) {
        $tmp = $dateFrom;
        $dateFrom = $dateTo;
        $dateTo = $tmp;
    }

    $rangeClause = static function (string $column, string $from, string $to): array {
        $sql = '';
        $params = [];
        if ($from !== '') {
            $sql .= " AND {$column} >= ?";
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $sql .= " AND {$column} <= ?";
            $params[] = $to . ' 23:59:59';
        }
        return [$sql, $params];
    };

    $module = strtolower(trim((string) ($_GET['module'] ?? 'summary')));
    if (!in_array($module, ['summary', 'events', 'campaigns', 'members'], true)) {
        $module = 'summary';
    }

    if ($module === 'events') {
        [$eventsRangeSql, $eventsRangeParams] = $rangeClause('e.inicio_em', $dateFrom, $dateTo);
        $st = $db->prepare("SELECT e.id, e.titulo, e.local, e.status, e.inicio_em, e.fim_em, e.vagas,
                (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id AND er.status = 'registered') AS inscritos
            FROM events e
            WHERE e.inicio_em >= NOW(){$eventsRangeSql}
            ORDER BY e.inicio_em ASC, e.id DESC");
        $st->execute($eventsRangeParams);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $dataRows = [];
        foreach ($rows as $row) {
            $dataRows[] = [
                (string) ($row['id'] ?? ''),
                (string) ($row['titulo'] ?? ''),
                (string) ($row['local'] ?? ''),
                (string) ($row['status'] ?? ''),
                (string) ($row['inicio_em'] ?? ''),
                (string) ($row['fim_em'] ?? ''),
                (string) ($row['vagas'] ?? ''),
                (string) ($row['inscritos'] ?? ''),
            ];
        }
        dashboard_stream_csv(
            'dashboard-eventos.csv',
            ['id', 'titulo', 'local', 'status', 'inicio_em', 'fim_em', 'vagas', 'inscritos'],
            $dataRows
        );
    }

    if ($module === 'campaigns') {
        [$campaignRangeSql, $campaignRangeParams] = $rangeClause('c.created_at', $dateFrom, $dateTo);
        $st = $db->prepare("SELECT c.id, c.titulo, c.canal, c.status, c.created_at,
                (SELECT COUNT(*) FROM campaign_logs l WHERE l.campaign_id = c.id) AS total_logs,
                (SELECT MAX(r.started_at) FROM campaign_runs r WHERE r.campaign_id = c.id) AS last_run_at
            FROM campaigns c
            WHERE 1=1{$campaignRangeSql}
            ORDER BY c.created_at DESC, c.id DESC");
        $st->execute($campaignRangeParams);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $dataRows = [];
        foreach ($rows as $row) {
            $dataRows[] = [
                (string) ($row['id'] ?? ''),
                (string) ($row['titulo'] ?? ''),
                (string) ($row['canal'] ?? ''),
                (string) ($row['status'] ?? ''),
                (string) ($row['created_at'] ?? ''),
                (string) ($row['total_logs'] ?? ''),
                (string) ($row['last_run_at'] ?? ''),
            ];
        }
        dashboard_stream_csv(
            'dashboard-campanhas.csv',
            ['id', 'titulo', 'canal', 'status', 'created_at', 'total_logs', 'last_run_at'],
            $dataRows
        );
    }

    if ($module === 'members') {
        [$membersRangeSql, $membersRangeParams] = $rangeClause('m.created_at', $dateFrom, $dateTo);
        $st = $db->prepare("SELECT m.id, m.nome, m.categoria, m.status, m.email_funcional, m.telefone, m.created_at
            FROM members m
            WHERE 1=1{$membersRangeSql}
            ORDER BY m.created_at DESC, m.id DESC");
        $st->execute($membersRangeParams);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $dataRows = [];
        foreach ($rows as $row) {
            $dataRows[] = [
                (string) ($row['id'] ?? ''),
                (string) ($row['nome'] ?? ''),
                (string) ($row['categoria'] ?? ''),
                (string) ($row['status'] ?? ''),
                (string) ($row['email_funcional'] ?? ''),
                (string) ($row['telefone'] ?? ''),
                (string) ($row['created_at'] ?? ''),
            ];
        }
        dashboard_stream_csv(
            'dashboard-associados.csv',
            ['id', 'nome', 'categoria', 'status', 'email_funcional', 'telefone', 'created_at'],
            $dataRows
        );
    }

    $totalMembers = (int) $db->query('SELECT COUNT(*) FROM members')->fetchColumn();
    $activeMembers = (int) $db->query("SELECT COUNT(*) FROM members WHERE status = 'ATIVO'")->fetchColumn();
    $inactiveMembers = (int) $db->query("SELECT COUNT(*) FROM members WHERE status = 'INATIVO'")->fetchColumn();

    [$eventsRangeSql, $eventsRangeParams] = $rangeClause('e.inicio_em', $dateFrom, $dateTo);
    $stUpcoming = $db->prepare("SELECT COUNT(*)
        FROM events e
        WHERE e.status = 'published' AND e.inicio_em >= NOW(){$eventsRangeSql}");
    $stUpcoming->execute($eventsRangeParams);
    $upcomingPublishedEvents = (int) $stUpcoming->fetchColumn();

    [$eventsCreatedRangeSql, $eventsCreatedRangeParams] = $rangeClause('e.created_at', $dateFrom, $dateTo);
    $stDraft = $db->prepare("SELECT COUNT(*)
        FROM events e
        WHERE e.status = 'draft'{$eventsCreatedRangeSql}");
    $stDraft->execute($eventsCreatedRangeParams);
    $draftEvents = (int) $stDraft->fetchColumn();

    [$postsRangeSql, $postsRangeParams] = $rangeClause('COALESCE(p.publicado_em, p.created_at)', $dateFrom, $dateTo);
    $stCom = $db->prepare("SELECT COUNT(*)
        FROM posts p
        WHERE p.tipo = 'COMUNICADO' AND p.status = 'published'{$postsRangeSql}");
    $stCom->execute($postsRangeParams);
    $publishedComunicados = (int) $stCom->fetchColumn();

    [$campaignRangeSql, $campaignRangeParams] = $rangeClause('c.created_at', $dateFrom, $dateTo);
    $stCampTotal = $db->prepare("SELECT COUNT(*) FROM campaigns c WHERE 1=1{$campaignRangeSql}");
    $stCampTotal->execute($campaignRangeParams);
    $totalCampaigns = (int) $stCampTotal->fetchColumn();

    $stCampProc = $db->prepare("SELECT COUNT(*)
        FROM campaigns c
        WHERE c.status IN ('queued', 'processing'){$campaignRangeSql}");
    $stCampProc->execute($campaignRangeParams);
    $processingCampaigns = (int) $stCampProc->fetchColumn();

    dashboard_stream_csv(
        'dashboard-resumo.csv',
        ['metric', 'value', 'date_from', 'date_to'],
        [
            ['total_members', (string) $totalMembers, $dateFrom, $dateTo],
            ['active_members', (string) $activeMembers, $dateFrom, $dateTo],
            ['inactive_members', (string) $inactiveMembers, $dateFrom, $dateTo],
            ['upcoming_published_events', (string) $upcomingPublishedEvents, $dateFrom, $dateTo],
            ['draft_events', (string) $draftEvents, $dateFrom, $dateTo],
            ['published_comunicados', (string) $publishedComunicados, $dateFrom, $dateTo],
            ['total_campaigns', (string) $totalCampaigns, $dateFrom, $dateTo],
            ['processing_campaigns', (string) $processingCampaigns, $dateFrom, $dateTo],
        ]
    );
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
