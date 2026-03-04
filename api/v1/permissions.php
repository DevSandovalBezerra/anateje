<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();

$db = getDB();
anateje_ensure_schema($db);

function permissions_default_catalog(): array
{
    return [
        ['dashboard.admin', 'dashboard', 'Dashboard Admin', 10],
        ['dashboard.user', 'dashboard', 'Dashboard Membro', 20],
        ['associado.perfil', 'associado', 'Perfil', 30],
        ['associado.meus_beneficios', 'associado', 'Meus Beneficios', 40],
        ['associado.meus_eventos', 'associado', 'Meus Eventos', 50],
        ['associado.comunicados', 'associado', 'Comunicados', 60],
        ['admin.associados', 'admin', 'Associados', 70],
        ['admin.pastas_associados', 'admin', 'Pastas de Associados', 80],
        ['admin.beneficios', 'admin', 'Beneficios', 90],
        ['admin.eventos', 'admin', 'Eventos', 100],
        ['admin.comunicados', 'admin', 'Comunicados', 110],
        ['admin.campanhas', 'admin', 'Campanhas', 120],
        ['admin.integracoes', 'admin', 'Integracoes', 130],
        ['admin.permissoes', 'admin', 'Permissoes', 140],
        ['admin.auditoria', 'admin', 'Auditoria', 150],
        ['cadastros.usuarios', 'cadastros', 'Usuarios', 160],

        ['admin.associados.view', 'admin_associados', 'Associados - Visualizar', 1000],
        ['admin.associados.create', 'admin_associados', 'Associados - Criar', 1010],
        ['admin.associados.edit', 'admin_associados', 'Associados - Editar', 1020],
        ['admin.associados.delete', 'admin_associados', 'Associados - Excluir', 1030],
        ['admin.associados.export', 'admin_associados', 'Associados - Exportar', 1040],

        ['admin.pastas_associados.view', 'admin_pastas_associados', 'Pastas de Associados - Visualizar', 1050],
        ['admin.pastas_associados.create', 'admin_pastas_associados', 'Pastas de Associados - Criar pastas', 1060],
        ['admin.pastas_associados.edit', 'admin_pastas_associados', 'Pastas de Associados - Renomear/Mover', 1070],
        ['admin.pastas_associados.delete', 'admin_pastas_associados', 'Pastas de Associados - Excluir', 1080],
        ['admin.pastas_associados.upload', 'admin_pastas_associados', 'Pastas de Associados - Upload', 1090],
        ['admin.pastas_associados.download', 'admin_pastas_associados', 'Pastas de Associados - Download', 1095],

        ['admin.beneficios.view', 'admin_beneficios', 'Beneficios - Visualizar', 1100],
        ['admin.beneficios.create', 'admin_beneficios', 'Beneficios - Criar', 1110],
        ['admin.beneficios.edit', 'admin_beneficios', 'Beneficios - Editar', 1120],
        ['admin.beneficios.delete', 'admin_beneficios', 'Beneficios - Excluir', 1130],
        ['admin.beneficios.export', 'admin_beneficios', 'Beneficios - Exportar', 1140],

        ['admin.eventos.view', 'admin_eventos', 'Eventos - Visualizar', 1200],
        ['admin.eventos.create', 'admin_eventos', 'Eventos - Criar', 1210],
        ['admin.eventos.edit', 'admin_eventos', 'Eventos - Editar', 1220],
        ['admin.eventos.delete', 'admin_eventos', 'Eventos - Excluir', 1230],
        ['admin.eventos.export', 'admin_eventos', 'Eventos - Exportar', 1240],
        ['admin.eventos.checkin', 'admin_eventos', 'Eventos - Check-in', 1250],
        ['admin.eventos.waitlist', 'admin_eventos', 'Eventos - Gerenciar fila', 1260],

        ['admin.comunicados.view', 'admin_comunicados', 'Comunicados - Visualizar', 1300],
        ['admin.comunicados.create', 'admin_comunicados', 'Comunicados - Criar', 1310],
        ['admin.comunicados.edit', 'admin_comunicados', 'Comunicados - Editar', 1320],
        ['admin.comunicados.delete', 'admin_comunicados', 'Comunicados - Excluir', 1330],
        ['admin.comunicados.publish', 'admin_comunicados', 'Comunicados - Publicar/Agendar', 1340],
        ['admin.comunicados.export', 'admin_comunicados', 'Comunicados - Exportar', 1350],

        ['admin.campanhas.view', 'admin_campanhas', 'Campanhas - Visualizar', 1400],
        ['admin.campanhas.create', 'admin_campanhas', 'Campanhas - Criar', 1410],
        ['admin.campanhas.edit', 'admin_campanhas', 'Campanhas - Editar', 1420],
        ['admin.campanhas.delete', 'admin_campanhas', 'Campanhas - Excluir', 1430],
        ['admin.campanhas.run', 'admin_campanhas', 'Campanhas - Executar', 1440],
        ['admin.campanhas.export', 'admin_campanhas', 'Campanhas - Exportar logs', 1450],

        ['admin.permissoes.view', 'admin_permissoes', 'Permissoes - Visualizar', 1500],
        ['admin.permissoes.edit', 'admin_permissoes', 'Permissoes - Editar', 1510],
        ['admin.auditoria.view', 'admin_auditoria', 'Auditoria - Visualizar', 1520],
        ['admin.auditoria.export', 'admin_auditoria', 'Auditoria - Exportar', 1530],
    ];
}

function permissions_ensure_schema(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS perfis_acesso (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        nome VARCHAR(120) NOT NULL,
        descricao VARCHAR(255) NULL,
        permissoes LONGTEXT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS permissoes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        codigo VARCHAR(120) NOT NULL,
        modulo VARCHAR(60) NOT NULL,
        nome VARCHAR(120) NOT NULL,
        ordem INT NOT NULL DEFAULT 0,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_permissoes_codigo (codigo),
        KEY idx_permissoes_modulo (modulo),
        KEY idx_permissoes_ativo_ordem (ativo, ordem)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS perfil_permissoes (
        perfil_id INT UNSIGNED NOT NULL,
        permissao_id INT UNSIGNED NOT NULL,
        concedida TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (perfil_id, permissao_id),
        KEY idx_perfil_permissoes_permissao (permissao_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $catalog = permissions_default_catalog();
    $insPerm = $db->prepare('INSERT INTO permissoes (codigo, modulo, nome, ordem, ativo) VALUES (?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE modulo=VALUES(modulo), nome=VALUES(nome), ordem=VALUES(ordem), ativo=1');
    foreach ($catalog as $item) {
        $insPerm->execute([$item[0], $item[1], $item[2], $item[3]]);
    }

    $db->exec("INSERT INTO perfis_acesso (id, nome, descricao, ativo)
        SELECT 1, 'Admin', 'Administrador global', 1
        WHERE NOT EXISTS (SELECT 1 FROM perfis_acesso WHERE id = 1)");

    $db->exec("INSERT INTO perfis_acesso (id, nome, descricao, ativo)
        SELECT 2, 'Associado', 'Perfil base do associado', 1
        WHERE NOT EXISTS (SELECT 1 FROM perfis_acesso WHERE id = 2)");

    $allPerms = $db->query('SELECT id FROM permissoes')->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($allPerms)) {
        $ins = $db->prepare('INSERT INTO perfil_permissoes (perfil_id, permissao_id, concedida) VALUES (1, ?, 1)
            ON DUPLICATE KEY UPDATE concedida = 1');
        foreach ($allPerms as $pid) {
            $ins->execute([(int) $pid]);
        }
    }

    $baseCodes = ['dashboard.user', 'associado.perfil', 'associado.meus_beneficios', 'associado.meus_eventos', 'associado.comunicados'];
    if (!empty($baseCodes)) {
        $placeholders = implode(',', array_fill(0, count($baseCodes), '?'));
        $st = $db->prepare("SELECT id FROM permissoes WHERE codigo IN ({$placeholders})");
        $st->execute($baseCodes);
        $basePerms = $st->fetchAll(PDO::FETCH_COLUMN);
        $ins = $db->prepare('INSERT INTO perfil_permissoes (perfil_id, permissao_id, concedida) VALUES (2, ?, 1)
            ON DUPLICATE KEY UPDATE concedida = 1');
        foreach ($basePerms as $pid) {
            $ins->execute([(int) $pid]);
        }
    }
}

function permissions_sync_profile_json(PDO $db, int $profileId): void
{
    $st = $db->prepare("SELECT p.modulo, p.codigo
        FROM perfil_permissoes pp
        INNER JOIN permissoes p ON p.id = pp.permissao_id
        WHERE pp.perfil_id = ? AND pp.concedida = 1 AND p.ativo = 1
        ORDER BY p.modulo, p.ordem, p.id");
    $st->execute([$profileId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $row) {
        $mod = (string) ($row['modulo'] ?? '');
        $code = (string) ($row['codigo'] ?? '');
        if ($mod === '' || $code === '') {
            continue;
        }
        if (substr_count($code, '.') !== 1) {
            continue;
        }
        if (!isset($map[$mod])) {
            $map[$mod] = [];
        }
        $parts = explode('.', $code, 2);
        $page = $parts[1] ?? $code;
        if (!in_array($page, $map[$mod], true)) {
            $map[$mod][] = $page;
        }
    }

    $json = json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $up = $db->prepare('UPDATE perfis_acesso SET permissoes = ? WHERE id = ?');
    $up->execute([$json ?: '{}', $profileId]);
}

function permissions_load_payload(PDO $db): array
{
    $profilesSql = "SELECT p.id, p.nome, p.descricao, p.ativo, p.created_at,
        (SELECT COUNT(*) FROM usuarios u WHERE u.perfil_id = p.id) AS users_count
        FROM perfis_acesso p
        ORDER BY p.id ASC";
    $profiles = $db->query($profilesSql)->fetchAll(PDO::FETCH_ASSOC);

    $permissions = $db->query('SELECT id, codigo, modulo, nome, ordem, ativo FROM permissoes ORDER BY modulo ASC, ordem ASC, id ASC')
        ->fetchAll(PDO::FETCH_ASSOC);

    $links = $db->query('SELECT perfil_id, permissao_id FROM perfil_permissoes WHERE concedida = 1')
        ->fetchAll(PDO::FETCH_ASSOC);

    $matrix = [];
    foreach ($links as $row) {
        $profileId = (int) $row['perfil_id'];
        $permId = (int) $row['permissao_id'];
        if (!isset($matrix[$profileId])) {
            $matrix[$profileId] = [];
        }
        $matrix[$profileId][] = $permId;
    }

    return [
        'profiles' => $profiles,
        'permissions' => $permissions,
        'profile_permissions' => $matrix
    ];
}

function permissions_pick_reassign_profile(PDO $db, int $excludeProfileId): ?int
{
    $excludeProfileId = (int) $excludeProfileId;

    // Preferir um perfil chamado "Associado" quando existir.
    $stAssoc = $db->prepare('SELECT id FROM perfis_acesso WHERE LOWER(TRIM(nome)) = ? AND id <> ? LIMIT 1');
    $stAssoc->execute(['associado', $excludeProfileId]);
    $assocId = (int) ($stAssoc->fetchColumn() ?: 0);
    if ($assocId > 0) {
        return $assocId;
    }

    // Evitar, quando possivel, o perfil 1 (admin global) como destino automatico.
    $st = $db->prepare("SELECT p.id
        FROM perfis_acesso p
        LEFT JOIN usuarios u ON u.perfil_id = p.id
        WHERE p.id <> ? AND p.ativo = 1 AND p.id <> 1
        GROUP BY p.id
        ORDER BY COUNT(u.id) ASC, p.id ASC
        LIMIT 1");
    $st->execute([$excludeProfileId]);
    $candidate = (int) ($st->fetchColumn() ?: 0);
    if ($candidate > 0) {
        return $candidate;
    }

    $stAny = $db->prepare("SELECT p.id
        FROM perfis_acesso p
        LEFT JOIN usuarios u ON u.perfil_id = p.id
        WHERE p.id <> ?
        GROUP BY p.id
        ORDER BY COUNT(u.id) ASC, p.id ASC
        LIMIT 1");
    $stAny->execute([$excludeProfileId]);
    $fallback = (int) ($stAny->fetchColumn() ?: 0);

    return $fallback > 0 ? $fallback : null;
}

permissions_ensure_schema($db);

$action = $_GET['action'] ?? '';

if ($action === 'admin_list') {
    anateje_require_permission($db, $auth, 'admin.permissoes.view');
    anateje_ok(permissions_load_payload($db));
}

if ($action === 'admin_profile_save') {
    anateje_require_permission($db, $auth, 'admin.permissoes.edit');
    anateje_require_method(['POST']);
    $in = anateje_input();

    $id = (int) ($in['id'] ?? 0);
    $nome = trim((string) ($in['nome'] ?? ''));
    $descricao = trim((string) ($in['descricao'] ?? ''));
    $ativo = !empty($in['ativo']) ? 1 : 0;

    if ($nome === '') {
        anateje_error('VALIDATION', 'Nome do perfil e obrigatorio', 422);
    }

    $db->beginTransaction();
    try {
        if ($id > 0) {
            $st = $db->prepare('UPDATE perfis_acesso SET nome = ?, descricao = ?, ativo = ? WHERE id = ?');
            $st->execute([$nome, $descricao !== '' ? $descricao : null, $ativo, $id]);
        } else {
            $st = $db->prepare('INSERT INTO perfis_acesso (nome, descricao, ativo) VALUES (?, ?, ?)');
            $st->execute([$nome, $descricao !== '' ? $descricao : null, $ativo]);
            $id = (int) $db->lastInsertId();
        }

        permissions_sync_profile_json($db, $id);
        $db->commit();
        anateje_ok(['saved' => true, 'id' => $id]);
    } catch (Exception $e) {
        $db->rollBack();
        logError('Erro permissions.admin_profile_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar perfil', 500);
    }
}

if ($action === 'admin_profile_delete') {
    anateje_require_permission($db, $auth, 'admin.permissoes.edit');
    anateje_require_method(['POST']);
    $in = anateje_input();
    $id = (int) ($in['id'] ?? 0);
    $reassignProfileId = (int) ($in['reassign_profile_id'] ?? 0);

    if ($id <= 0) {
        anateje_error('VALIDATION', 'Perfil invalido', 422);
    }
    if ($id === 1) {
        anateje_error('VALIDATION', 'Perfil Admin nao pode ser removido', 422);
    }

    $stProfile = $db->prepare('SELECT id FROM perfis_acesso WHERE id = ? LIMIT 1');
    $stProfile->execute([$id]);
    if (!$stProfile->fetch(PDO::FETCH_ASSOC)) {
        anateje_error('NOT_FOUND', 'Perfil nao encontrado', 404);
    }

    $stUsers = $db->prepare('SELECT COUNT(*) FROM usuarios WHERE perfil_id = ?');
    $stUsers->execute([$id]);
    $usersCount = (int) $stUsers->fetchColumn();
    if ($usersCount > 0 && $reassignProfileId <= 0) {
        $picked = permissions_pick_reassign_profile($db, $id);
        $reassignProfileId = $picked !== null ? (int) $picked : 0;
    }
    if ($usersCount > 0) {
        if ($reassignProfileId <= 0 || $reassignProfileId === $id) {
            anateje_error('VALIDATION', 'Nao foi possivel encontrar um perfil de destino para os usuarios vinculados', 422, [
                'linked_users' => $usersCount
            ]);
        }
        $stTarget = $db->prepare('SELECT id FROM perfis_acesso WHERE id = ? LIMIT 1');
        $stTarget->execute([$reassignProfileId]);
        if (!$stTarget->fetch(PDO::FETCH_ASSOC)) {
            anateje_error('VALIDATION', 'Perfil de destino para realocacao dos usuarios nao encontrado', 422, [
                'reassign_profile_id' => $reassignProfileId
            ]);
        }
    }

    $db->beginTransaction();
    try {
        $movedUsers = 0;
        if ($usersCount > 0) {
            $upUsers = $db->prepare('UPDATE usuarios SET perfil_id = ? WHERE perfil_id = ?');
            $upUsers->execute([$reassignProfileId, $id]);
            $movedUsers = (int) $upUsers->rowCount();
        }

        $db->prepare('DELETE FROM perfil_permissoes WHERE perfil_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM perfis_acesso WHERE id = ?')->execute([$id]);
        $db->commit();
        anateje_ok([
            'deleted' => true,
            'moved_users' => $movedUsers,
            'reassign_profile_id' => $usersCount > 0 ? $reassignProfileId : null
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        logError('Erro permissions.admin_profile_delete: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao excluir perfil', 500);
    }
}

if ($action === 'admin_profile_permissions_save') {
    anateje_require_permission($db, $auth, 'admin.permissoes.edit');
    anateje_require_method(['POST']);
    $in = anateje_input();
    $profileId = (int) ($in['profile_id'] ?? 0);
    $permissionIds = $in['permission_ids'] ?? [];
    if (!is_array($permissionIds)) {
        $permissionIds = [];
    }

    if ($profileId <= 0) {
        anateje_error('VALIDATION', 'Perfil invalido', 422);
    }

    $stProfile = $db->prepare('SELECT id FROM perfis_acesso WHERE id = ? LIMIT 1');
    $stProfile->execute([$profileId]);
    if (!$stProfile->fetch(PDO::FETCH_ASSOC)) {
        anateje_error('NOT_FOUND', 'Perfil nao encontrado', 404);
    }

    $normalized = [];
    foreach ($permissionIds as $pid) {
        $pid = (int) $pid;
        if ($pid > 0 && !in_array($pid, $normalized, true)) {
            $normalized[] = $pid;
        }
    }

    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM perfil_permissoes WHERE perfil_id = ?')->execute([$profileId]);
        if (!empty($normalized)) {
            $ins = $db->prepare('INSERT INTO perfil_permissoes (perfil_id, permissao_id, concedida) VALUES (?, ?, 1)');
            foreach ($normalized as $pid) {
                $ins->execute([$profileId, $pid]);
            }
        }

        permissions_sync_profile_json($db, $profileId);
        $db->commit();
        anateje_ok(['saved' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        logError('Erro permissions.admin_profile_permissions_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar permissoes do perfil', 500);
    }
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
