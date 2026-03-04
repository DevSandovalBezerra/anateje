<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = trim((string) ($_GET['action'] ?? ''));
$actorId = (int) ($auth['sub'] ?? 0);

function users_allowed_tipo_usuario(): array
{
    return ['ASSOCIADO', 'FUNCIONARIO', 'ADMIN'];
}

function users_allowed_tipo_funcionario(): array
{
    return ['CONTADOR', 'ATENDENTE', 'FINANCEIRO', 'COORDENACAO', 'SUPORTE', 'GESTOR', 'OUTRO'];
}

function users_ensure_schema(PDO $db): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

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

    $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        nome VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL,
        senha VARCHAR(255) NOT NULL,
        perfil_id INT UNSIGNED NOT NULL DEFAULT 2,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        unidade_id BIGINT UNSIGNED NULL,
        tipo_usuario ENUM('ASSOCIADO','FUNCIONARIO','ADMIN') NOT NULL DEFAULT 'ASSOCIADO',
        tipo_funcionario ENUM('CONTADOR','ATENDENTE','FINANCEIRO','COORDENACAO','SUPORTE','GESTOR','OUTRO') NULL,
        ultimo_login DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_usuarios_email (email),
        KEY idx_usuarios_perfil_id (perfil_id),
        KEY idx_usuarios_ativo (ativo),
        KEY idx_usuarios_tipo_usuario (tipo_usuario),
        KEY idx_usuarios_tipo_funcionario (tipo_funcionario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!anateje_schema_has_column($db, 'usuarios', 'nome')) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN nome VARCHAR(150) NOT NULL DEFAULT ''");
    }
    if (!anateje_schema_has_column($db, 'usuarios', 'email')) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN email VARCHAR(190) NOT NULL DEFAULT ''");
    }
    if (!anateje_schema_has_column($db, 'usuarios', 'senha')) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN senha VARCHAR(255) NOT NULL DEFAULT ''");
    }
    if (!anateje_schema_has_column($db, 'usuarios', 'perfil_id')) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN perfil_id INT UNSIGNED NOT NULL DEFAULT 2");
    }
    if (!anateje_schema_has_column($db, 'usuarios', 'ativo')) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1");
    }
    if (!anateje_schema_has_column($db, 'usuarios', 'unidade_id')) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN unidade_id BIGINT UNSIGNED NULL");
    }
    if (!anateje_schema_has_column($db, 'usuarios', 'tipo_usuario')) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN tipo_usuario ENUM('ASSOCIADO','FUNCIONARIO','ADMIN') NOT NULL DEFAULT 'ASSOCIADO'");
    }
    if (!anateje_schema_has_column($db, 'usuarios', 'tipo_funcionario')) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN tipo_funcionario ENUM('CONTADOR','ATENDENTE','FINANCEIRO','COORDENACAO','SUPORTE','GESTOR','OUTRO') NULL");
    }
    if (!anateje_schema_has_column($db, 'usuarios', 'ultimo_login')) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN ultimo_login DATETIME NULL");
    }
    if (!anateje_schema_has_column($db, 'usuarios', 'created_at')) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }
    if (!anateje_schema_has_column($db, 'usuarios', 'updated_at')) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
    }

    if (!anateje_schema_has_index($db, 'usuarios', 'uk_usuarios_email')) {
        try {
            $db->exec('ALTER TABLE usuarios ADD UNIQUE KEY uk_usuarios_email (email)');
        } catch (Throwable $e) {
            logError('Nao foi possivel criar indice unico de email em usuarios: ' . $e->getMessage());
            if (!anateje_schema_has_index($db, 'usuarios', 'idx_usuarios_email')) {
                $db->exec('ALTER TABLE usuarios ADD KEY idx_usuarios_email (email)');
            }
        }
    }
    if (!anateje_schema_has_index($db, 'usuarios', 'idx_usuarios_perfil_id')) {
        $db->exec('ALTER TABLE usuarios ADD KEY idx_usuarios_perfil_id (perfil_id)');
    }
    if (!anateje_schema_has_index($db, 'usuarios', 'idx_usuarios_ativo')) {
        $db->exec('ALTER TABLE usuarios ADD KEY idx_usuarios_ativo (ativo)');
    }
    if (!anateje_schema_has_index($db, 'usuarios', 'idx_usuarios_tipo_usuario')) {
        $db->exec('ALTER TABLE usuarios ADD KEY idx_usuarios_tipo_usuario (tipo_usuario)');
    }
    if (!anateje_schema_has_index($db, 'usuarios', 'idx_usuarios_tipo_funcionario')) {
        $db->exec('ALTER TABLE usuarios ADD KEY idx_usuarios_tipo_funcionario (tipo_funcionario)');
    }

    // Perfis base esperados pelo sistema
    $db->exec("INSERT INTO perfis_acesso (id, nome, descricao, ativo)
        SELECT 1, 'Admin', 'Administrador global', 1
        WHERE NOT EXISTS (SELECT 1 FROM perfis_acesso WHERE id = 1)");
    $db->exec("INSERT INTO perfis_acesso (id, nome, descricao, ativo)
        SELECT 2, 'Associado', 'Perfil base do associado', 1
        WHERE NOT EXISTS (SELECT 1 FROM perfis_acesso WHERE id = 2)");
    $db->exec("INSERT INTO perfis_acesso (id, nome, descricao, ativo)
        SELECT 6, 'Responsavel', 'Perfil padrao de cadastro publico', 1
        WHERE NOT EXISTS (SELECT 1 FROM perfis_acesso WHERE id = 6)");

    $seedByName = [
        ['contador', 'Perfil de funcionario contador'],
        ['atendente', 'Perfil de funcionario atendente'],
        ['financeiro', 'Perfil de funcionario financeiro'],
    ];
    $insName = $db->prepare("INSERT INTO perfis_acesso (nome, descricao, ativo)
        SELECT ?, ?, 1
        WHERE NOT EXISTS (
            SELECT 1 FROM perfis_acesso WHERE LOWER(TRIM(nome)) = LOWER(TRIM(?)) LIMIT 1
        )");
    foreach ($seedByName as $row) {
        $insName->execute([$row[0], $row[1], $row[0]]);
    }

    $ready = true;
}

function users_fetch_profiles(PDO $db): array
{
    $rows = $db->query("SELECT id, nome, descricao, ativo
        FROM perfis_acesso
        ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    return is_array($rows) ? $rows : [];
}

function users_fetch_one(PDO $db, int $id): ?array
{
    $st = $db->prepare("SELECT
            u.id, u.nome, u.email, u.perfil_id, u.ativo, u.unidade_id,
            u.tipo_usuario, u.tipo_funcionario, u.ultimo_login, u.created_at, u.updated_at,
            p.nome AS perfil_nome
        FROM usuarios u
        LEFT JOIN perfis_acesso p ON p.id = u.perfil_id
        WHERE u.id = ?
        LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    return $row;
}

function users_normalize_tipo_usuario(string $value): string
{
    $value = strtoupper(trim($value));
    if ($value === '') {
        return 'ASSOCIADO';
    }
    return $value;
}

function users_normalize_tipo_funcionario(?string $value): ?string
{
    $value = strtoupper(trim((string) $value));
    return $value === '' ? null : $value;
}

users_ensure_schema($db);
anateje_require_permission($db, $auth, 'cadastros.usuarios');

if ($action === 'admin_list') {
    $q = trim((string) ($_GET['q'] ?? ''));
    $perfilId = (int) ($_GET['perfil_id'] ?? 0);
    $ativoRaw = trim((string) ($_GET['ativo'] ?? ''));
    $tipoUsuario = users_normalize_tipo_usuario((string) ($_GET['tipo_usuario'] ?? ''));

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = '(u.nome LIKE ? OR u.email LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    }

    if ($perfilId > 0) {
        $where[] = 'u.perfil_id = ?';
        $params[] = $perfilId;
    }

    if ($ativoRaw === '0' || $ativoRaw === '1') {
        $where[] = 'u.ativo = ?';
        $params[] = (int) $ativoRaw;
    }

    if (in_array($tipoUsuario, users_allowed_tipo_usuario(), true)) {
        if (($tipoUsuario !== 'ASSOCIADO') || (isset($_GET['tipo_usuario']) && trim((string) $_GET['tipo_usuario']) !== '')) {
            $where[] = 'u.tipo_usuario = ?';
            $params[] = $tipoUsuario;
        }
    }

    $sql = "SELECT
            u.id, u.nome, u.email, u.perfil_id, u.ativo, u.unidade_id,
            u.tipo_usuario, u.tipo_funcionario, u.ultimo_login, u.created_at,
            p.nome AS perfil_nome
        FROM usuarios u
        LEFT JOIN perfis_acesso p ON p.id = u.perfil_id";
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY u.id DESC LIMIT 500';

    $st = $db->prepare($sql);
    $st->execute($params);
    $users = $st->fetchAll(PDO::FETCH_ASSOC);

    anateje_ok([
        'users' => is_array($users) ? $users : [],
        'profiles' => users_fetch_profiles($db),
        'tipos_usuario' => users_allowed_tipo_usuario(),
        'tipos_funcionario' => users_allowed_tipo_funcionario(),
    ]);
}

if ($action === 'admin_save') {
    anateje_require_method(['POST']);
    $in = anateje_input();

    $id = (int) ($in['id'] ?? 0);
    $nome = trim((string) ($in['nome'] ?? ''));
    $email = strtolower(trim((string) ($in['email'] ?? '')));
    $perfilId = (int) ($in['perfil_id'] ?? 0);
    $ativo = !empty($in['ativo']) ? 1 : 0;
    $unidadeId = (int) ($in['unidade_id'] ?? 0);
    $tipoUsuario = users_normalize_tipo_usuario((string) ($in['tipo_usuario'] ?? 'ASSOCIADO'));
    $tipoFuncionario = users_normalize_tipo_funcionario((string) ($in['tipo_funcionario'] ?? ''));
    $senha = trim((string) ($in['senha'] ?? ''));

    if ($nome === '') {
        anateje_error('VALIDATION', 'Nome e obrigatorio', 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        anateje_error('VALIDATION', 'Email invalido', 422);
    }
    if ($perfilId <= 0) {
        anateje_error('VALIDATION', 'Perfil invalido', 422);
    }
    if (!in_array($tipoUsuario, users_allowed_tipo_usuario(), true)) {
        anateje_error('VALIDATION', 'Tipo de usuario invalido', 422);
    }
    if ($tipoUsuario === 'FUNCIONARIO') {
        if ($tipoFuncionario === null || !in_array($tipoFuncionario, users_allowed_tipo_funcionario(), true)) {
            anateje_error('VALIDATION', 'Tipo de funcionario invalido', 422);
        }
    } else {
        $tipoFuncionario = null;
    }

    if ($id === $actorId && $ativo === 0) {
        anateje_error('VALIDATION', 'Nao e permitido inativar o proprio usuario logado', 422);
    }

    $stProfile = $db->prepare('SELECT id FROM perfis_acesso WHERE id = ? LIMIT 1');
    $stProfile->execute([$perfilId]);
    if (!$stProfile->fetchColumn()) {
        anateje_error('VALIDATION', 'Perfil nao encontrado', 422);
    }

    $stDupe = $db->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1');
    $stDupe->execute([$email, $id]);
    if ($stDupe->fetchColumn()) {
        anateje_error('VALIDATION', 'Ja existe usuario com este email', 422);
    }

    if ($id <= 0 && $senha === '') {
        anateje_error('VALIDATION', 'Senha e obrigatoria para novo usuario', 422);
    }
    if ($senha !== '' && strlen($senha) < (int) PASSWORD_MIN_LENGTH) {
        anateje_error('VALIDATION', 'Senha deve ter pelo menos ' . (int) PASSWORD_MIN_LENGTH . ' caracteres', 422);
    }

    $before = null;
    if ($id > 0) {
        $before = users_fetch_one($db, $id);
        if (!$before) {
            anateje_error('NOT_FOUND', 'Usuario nao encontrado', 404);
        }
    }

    $unidade = $unidadeId > 0 ? $unidadeId : null;

    try {
        $db->beginTransaction();

        if ($id > 0) {
            if ($senha !== '') {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $st = $db->prepare("UPDATE usuarios
                    SET nome = ?, email = ?, perfil_id = ?, ativo = ?, unidade_id = ?, tipo_usuario = ?, tipo_funcionario = ?, senha = ?
                    WHERE id = ?");
                $st->execute([$nome, $email, $perfilId, $ativo, $unidade, $tipoUsuario, $tipoFuncionario, $hash, $id]);
            } else {
                $st = $db->prepare("UPDATE usuarios
                    SET nome = ?, email = ?, perfil_id = ?, ativo = ?, unidade_id = ?, tipo_usuario = ?, tipo_funcionario = ?
                    WHERE id = ?");
                $st->execute([$nome, $email, $perfilId, $ativo, $unidade, $tipoUsuario, $tipoFuncionario, $id]);
            }
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $st = $db->prepare("INSERT INTO usuarios
                (nome, email, senha, perfil_id, ativo, unidade_id, tipo_usuario, tipo_funcionario)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $st->execute([$nome, $email, $hash, $perfilId, $ativo, $unidade, $tipoUsuario, $tipoFuncionario]);
            $id = (int) $db->lastInsertId();
        }

        $after = users_fetch_one($db, $id);
        if (!$after) {
            throw new RuntimeException('Falha ao carregar usuario apos salvar');
        }

        anateje_audit_log(
            $db,
            $actorId,
            'cadastros.usuarios',
            $before ? 'update' : 'create',
            'usuario',
            $id,
            $before,
            $after,
            ['action' => $before ? 'atualizar' : 'criar']
        );

        $db->commit();
        anateje_ok([
            'saved' => true,
            'id' => $id,
            'user' => $after
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        anateje_error('FAIL', 'Falha ao salvar usuario', 500);
    }
}

if ($action === 'admin_delete') {
    anateje_require_method(['POST']);
    $in = anateje_input();
    $id = (int) ($in['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'Usuario invalido', 422);
    }
    if ($id === $actorId) {
        anateje_error('VALIDATION', 'Nao e permitido excluir o proprio usuario logado', 422);
    }

    $before = users_fetch_one($db, $id);
    if (!$before) {
        anateje_error('NOT_FOUND', 'Usuario nao encontrado', 404);
    }

    try {
        $db->beginTransaction();

        $stMember = $db->prepare('SELECT id FROM members WHERE user_id = ? LIMIT 1');
        $stMember->execute([$id]);
        $memberId = (int) ($stMember->fetchColumn() ?: 0);

        if ($memberId > 0) {
            $db->prepare('UPDATE usuarios SET ativo = 0 WHERE id = ?')->execute([$id]);
            $after = users_fetch_one($db, $id);
            anateje_audit_log(
                $db,
                $actorId,
                'cadastros.usuarios',
                'deactivate',
                'usuario',
                $id,
                $before,
                $after,
                ['action' => 'inativar_por_vinculo_member', 'member_id' => $memberId]
            );
            $db->commit();
            anateje_ok([
                'deleted' => false,
                'deactivated' => true,
                'id' => $id,
                'message' => 'Usuario vinculado a associado foi apenas inativado.'
            ]);
        }

        $db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        anateje_audit_log(
            $db,
            $actorId,
            'cadastros.usuarios',
            'delete',
            'usuario',
            $id,
            $before,
            null,
            ['action' => 'excluir']
        );

        $db->commit();
        anateje_ok([
            'deleted' => true,
            'id' => $id
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        anateje_error('FAIL', 'Falha ao excluir usuario', 500);
    }
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
