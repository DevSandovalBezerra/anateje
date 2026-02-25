<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $memberId = anateje_member_id($db, (int) $auth['sub']);

    $benefits = $db->query("SELECT id, nome, descricao, link, status, sort_order FROM benefits WHERE status='active' ORDER BY sort_order ASC, id ASC")
        ->fetchAll(PDO::FETCH_ASSOC);

    $activeMap = [];
    if ($memberId) {
        $st = $db->prepare('SELECT benefit_id, ativo FROM member_benefits WHERE member_id = ?');
        $st->execute([$memberId]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $activeMap[(int) $row['benefit_id']] = (int) $row['ativo'] === 1;
        }
    }

    foreach ($benefits as &$b) {
        $id = (int) $b['id'];
        $b['active_for_me'] = $memberId ? (bool) ($activeMap[$id] ?? false) : false;
    }
    unset($b);

    anateje_ok(['benefits' => $benefits]);
}

if ($action === 'set_member_benefits') {
    anateje_require_method(['POST']);

    $memberId = anateje_member_id($db, (int) $auth['sub']);
    if (!$memberId) {
        anateje_error('NO_MEMBER', 'Complete seu perfil antes', 422);
    }

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
            $check = $db->query('SELECT id FROM benefits WHERE status = "active"')->fetchAll(PDO::FETCH_COLUMN);
            $valid = array_map('intval', $check);
            $set = $db->prepare('INSERT INTO member_benefits (member_id, benefit_id, ativo) VALUES (?,?,1)');

            foreach ($ids as $id) {
                if (in_array((int) $id, $valid, true)) {
                    $set->execute([$memberId, $id]);
                }
            }
        }

        $db->commit();
        anateje_ok(['updated' => true]);
    } catch (Throwable $e) {
        $db->rollBack();
        logError('Erro benefits.set_member_benefits: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao atualizar beneficios', 500);
    }
}

if ($action === 'admin_list') {
    anateje_require_admin($auth);

    $rows = $db->query('SELECT * FROM benefits ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
    anateje_ok(['benefits' => $rows]);
}

if ($action === 'admin_save') {
    anateje_require_admin($auth);
    anateje_require_method(['POST']);

    $in = anateje_input();

    $id = (int) ($in['id'] ?? 0);
    $nome = trim((string) ($in['nome'] ?? ''));
    if ($nome === '') {
        anateje_error('VALIDATION', 'Nome e obrigatorio', 422);
    }

    $descricao = trim((string) ($in['descricao'] ?? ''));
    $link = trim((string) ($in['link'] ?? ''));
    $status = ($in['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $sortOrder = (int) ($in['sort_order'] ?? 0);

    $db->beginTransaction();
    try {
        if ($id > 0) {
            $st = $db->prepare('UPDATE benefits SET nome=?, descricao=?, link=?, status=?, sort_order=? WHERE id=?');
            $st->execute([$nome, $descricao ?: null, $link ?: null, $status, $sortOrder, $id]);
        } else {
            $st = $db->prepare('INSERT INTO benefits (nome, descricao, link, status, sort_order) VALUES (?,?,?,?,?)');
            $st->execute([$nome, $descricao ?: null, $link ?: null, $status, $sortOrder]);
            $id = (int) $db->lastInsertId();
        }

        $db->commit();
        anateje_ok(['id' => $id]);
    } catch (Throwable $e) {
        $db->rollBack();
        logError('Erro benefits.admin_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar beneficio', 500);
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
        $db->prepare('DELETE FROM member_benefits WHERE benefit_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM benefits WHERE id = ?')->execute([$id]);

        $db->commit();
        anateje_ok(['deleted' => true]);
    } catch (Throwable $e) {
        $db->rollBack();
        logError('Erro benefits.admin_delete: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao excluir beneficio', 500);
    }
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
