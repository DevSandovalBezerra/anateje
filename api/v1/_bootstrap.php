<?php
// ANATEJE API v1 bootstrap

require_once __DIR__ . '/../../config/database.php';

function anateje_json(array $payload, int $status = 200): void
{
    if (ob_get_level() > 0) {
        ob_clean();
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function anateje_ok(array $data = []): void
{
    anateje_json(['ok' => true, 'data' => $data], 200);
}

function anateje_error(string $code, string $message, int $status = 400, $details = null): void
{
    $err = ['code' => $code, 'message' => $message];
    if ($details !== null) {
        $err['details'] = $details;
    }

    anateje_json(['ok' => false, 'error' => $err], $status);
}

function anateje_input(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function anateje_require_method(array $allowed): void
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $allowedUp = array_map('strtoupper', $allowed);

    if (!in_array($method, $allowedUp, true)) {
        anateje_error('METHOD_NOT_ALLOWED', 'Metodo nao permitido', 405);
    }
}

function anateje_csrf_token(): string
{
    $token = (string) ($_SESSION['csrf_token'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        try {
            $token = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            $token = hash('sha256', uniqid('csrf', true) . '|' . mt_rand());
        }
        $_SESSION['csrf_token'] = $token;
    }

    return $token;
}

function anateje_csrf_required(): bool
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
}

function anateje_request_csrf_token(): string
{
    $headerToken = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if ($headerToken !== '') {
        return $headerToken;
    }

    if (isset($_POST['csrf_token'])) {
        return trim((string) $_POST['csrf_token']);
    }

    return trim((string) ($_GET['csrf_token'] ?? ''));
}

function anateje_require_csrf(): void
{
    if (!anateje_csrf_required()) {
        return;
    }

    $expected = anateje_csrf_token();
    $provided = anateje_request_csrf_token();
    if ($provided === '' || !hash_equals($expected, $provided)) {
        anateje_error('CSRF_INVALID', 'Token CSRF invalido ou ausente', 403);
    }
}

function anateje_require_auth(): array
{
    $tokenData = checkAuth();
    if (!$tokenData || !isset($_SESSION['user_id'])) {
        anateje_error('UNAUTH', 'Sessao invalida ou expirada', 401);
    }

    anateje_csrf_token();
    anateje_require_csrf();

    $perfilId = (int) ($_SESSION['perfil_id'] ?? 0);

    return [
        'sub' => (int) $_SESSION['user_id'],
        'perfil_id' => $perfilId,
        'is_admin' => $perfilId === 1,
        'role' => $perfilId === 1 ? 'admin' : 'assoc',
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? ''
    ];
}

function anateje_require_admin(array $auth): void
{
    if (empty($auth['is_admin'])) {
        anateje_error('FORBIDDEN', 'Acesso negado', 403);
    }
}

function anateje_profile_permissions(PDO $db, int $perfilId): array
{
    static $cache = [];

    if ($perfilId <= 0) {
        return [];
    }
    if (isset($cache[$perfilId])) {
        return $cache[$perfilId];
    }

    try {
        $st = $db->prepare("SELECT p.codigo
            FROM perfil_permissoes pp
            INNER JOIN permissoes p ON p.id = pp.permissao_id
            WHERE pp.perfil_id = ? AND pp.concedida = 1 AND p.ativo = 1");
        $st->execute([$perfilId]);
        $rows = $st->fetchAll(PDO::FETCH_COLUMN);
        $codes = [];
        foreach ($rows as $code) {
            $code = trim((string) $code);
            if ($code !== '') {
                $codes[] = $code;
            }
        }
        $cache[$perfilId] = array_values(array_unique($codes));
        return $cache[$perfilId];
    } catch (Throwable $e) {
        logError('Falha ao carregar permissoes do perfil ' . $perfilId . ': ' . $e->getMessage());
        $cache[$perfilId] = [];
        return [];
    }
}

function anateje_has_permission_code(PDO $db, array $auth, string $permissionCode): bool
{
    $permissionCode = trim($permissionCode);
    if ($permissionCode === '') {
        return false;
    }

    $perfilId = (int) ($auth['perfil_id'] ?? 0);
    if ($perfilId === 1) {
        return true;
    }
    if ($perfilId <= 0) {
        return false;
    }

    $codes = anateje_profile_permissions($db, $perfilId);
    if (in_array($permissionCode, $codes, true)) {
        return true;
    }

    // Compatibilidade: permissao de pagina (module.page) cobre a acao (module.page.action).
    $parts = explode('.', $permissionCode);
    if (count($parts) === 3) {
        $pageCode = $parts[0] . '.' . $parts[1];
        if (in_array($pageCode, $codes, true)) {
            return true;
        }
    }

    return false;
}

function anateje_require_permission(PDO $db, array $auth, string $permissionCode): void
{
    if (!anateje_has_permission_code($db, $auth, $permissionCode)) {
        anateje_error('FORBIDDEN', 'Acesso negado', 403, ['permission' => $permissionCode]);
    }
}

function anateje_member_id(PDO $db, int $userId): ?int
{
    $st = $db->prepare('SELECT id FROM members WHERE user_id = ? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ? (int) $row['id'] : null;
}

function anateje_slug(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($ascii === false || $ascii === null) {
        $ascii = $text;
    }

    $slug = strtolower($ascii);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim((string) $slug, '-');

    return $slug;
}

function anateje_only_digits(?string $value): string
{
    return preg_replace('/\D+/', '', (string) $value);
}

function anateje_valid_cpf(string $cpf): bool
{
    $cpf = anateje_only_digits($cpf);
    if (strlen($cpf) !== 11) {
        return false;
    }

    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += ((int) $cpf[$c]) * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ((int) $cpf[$c] !== $d) {
            return false;
        }
    }

    return true;
}

function anateje_parse_datetime(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $dt = date_create($value);
    if (!$dt) {
        return null;
    }

    return $dt->format('Y-m-d H:i:s');
}

function anateje_decode_json($value): array
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

function anateje_get_integration(PDO $db, string $provider): ?array
{
    $provider = strtoupper(trim($provider));
    $st = $db->prepare('SELECT provider, enabled, api_key, endpoint, sender, config_json, updated_at FROM integration_settings WHERE provider = ? LIMIT 1');
    $st->execute([$provider]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'provider' => $provider,
        'enabled' => (int) ($row['enabled'] ?? 0) === 1,
        'api_key' => (string) ($row['api_key'] ?? ''),
        'endpoint' => (string) ($row['endpoint'] ?? ''),
        'sender' => (string) ($row['sender'] ?? ''),
        'config' => anateje_decode_json($row['config_json'] ?? ''),
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

function anateje_http_post_json(string $url, array $payload, array $headers = [], int $timeout = 15): array
{
    $baseHeaders = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    foreach ($headers as $h) {
        $h = trim((string) $h);
        if ($h !== '') {
            $baseHeaders[] = $h;
        }
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return [
            'ok' => false,
            'status' => 0,
            'body' => '',
            'error' => 'JSON_ENCODE_FAIL'
        ];
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $baseHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(10, $timeout));

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return [
                'ok' => false,
                'status' => $status,
                'body' => (string) $body,
                'error' => $err ?: ('CURL_ERR_' . $errno)
            ];
        }

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => (string) $body,
            'error' => null
        ];
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $baseHeaders),
            'content' => $json,
            'ignore_errors' => true,
            'timeout' => $timeout
        ]
    ]);

    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (!empty($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int) $m[1];
                break;
            }
        }
    }

    if ($body === false) {
        return [
            'ok' => false,
            'status' => $status,
            'body' => '',
            'error' => 'HTTP_REQUEST_FAIL'
        ];
    }

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'body' => (string) $body,
        'error' => null
    ];
}

function anateje_schema_name(PDO $db): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $name = $db->query('SELECT DATABASE()')->fetchColumn();
    $cached = is_string($name) ? $name : '';
    return $cached;
}

function anateje_schema_has_column(PDO $db, string $table, string $column): bool
{
    $schema = anateje_schema_name($db);
    if ($schema === '') {
        return false;
    }

    $st = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $st->execute([$schema, $table, $column]);
    return (int) $st->fetchColumn() > 0;
}

function anateje_schema_has_index(PDO $db, string $table, string $index): bool
{
    $schema = anateje_schema_name($db);
    if ($schema === '') {
        return false;
    }

    $st = $db->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?');
    $st->execute([$schema, $table, $index]);
    return (int) $st->fetchColumn() > 0;
}

function anateje_schema_column_type(PDO $db, string $table, string $column): ?string
{
    $schema = anateje_schema_name($db);
    if ($schema === '') {
        return null;
    }

    $st = $db->prepare('SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
    $st->execute([$schema, $table, $column]);
    $type = $st->fetchColumn();
    if (!is_string($type) || trim($type) === '') {
        return null;
    }

    return strtolower(trim($type));
}

function anateje_audit_log(
    PDO $db,
    int $userId,
    string $module,
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    $before = null,
    $after = null,
    array $meta = []
): void {
    try {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($ip === '') {
            $ip = '0.0.0.0';
        }
        $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        $beforeJson = $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $afterJson = $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $metaJson = empty($meta) ? null : json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $st = $db->prepare('INSERT INTO audit_logs
            (user_id, modulo, acao, entidade, entidade_id, antes_json, depois_json, meta_json, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $st->execute([
            $userId > 0 ? $userId : null,
            trim($module) !== '' ? trim($module) : 'system',
            trim($action) !== '' ? trim($action) : 'unknown',
            $entityType !== null && trim($entityType) !== '' ? trim($entityType) : null,
            $entityId !== null && $entityId > 0 ? $entityId : null,
            $beforeJson !== false ? $beforeJson : null,
            $afterJson !== false ? $afterJson : null,
            $metaJson !== false ? $metaJson : null,
            $ip,
            $ua !== '' ? $ua : null,
        ]);
    } catch (Throwable $e) {
        logError('Falha audit log: ' . $e->getMessage());
    }
}

function anateje_ensure_schema(PDO $db): void
{
    static $ready = false;

    if ($ready) {
        return;
    }

    $queries = [
        "CREATE TABLE IF NOT EXISTS members (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            nome VARCHAR(150) NOT NULL,
            lotacao VARCHAR(150) NULL,
            cargo VARCHAR(150) NULL,
            cpf CHAR(11) NOT NULL,
            data_filiacao DATE NULL,
            categoria ENUM('PARCIAL','INTEGRAL') NOT NULL DEFAULT 'PARCIAL',
            status ENUM('ATIVO','INATIVO') NOT NULL DEFAULT 'ATIVO',
            contribuicao_mensal DECIMAL(10,2) NULL,
            matricula VARCHAR(60) NULL,
            telefone VARCHAR(30) NULL,
            email_funcional VARCHAR(190) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_members_user_id (user_id),
            UNIQUE KEY uk_members_cpf (cpf)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS addresses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id BIGINT UNSIGNED NOT NULL,
            cep CHAR(8) NOT NULL,
            logradouro VARCHAR(190) NULL,
            numero VARCHAR(30) NULL,
            complemento VARCHAR(60) NULL,
            bairro VARCHAR(120) NULL,
            cidade VARCHAR(120) NULL,
            uf CHAR(2) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_addresses_member_id (member_id),
            KEY idx_addresses_cep (cep)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS benefits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nome VARCHAR(150) NOT NULL,
            descricao TEXT NULL,
            link VARCHAR(255) NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            eligibility_categoria ENUM('ALL','PARCIAL','INTEGRAL') NOT NULL DEFAULT 'ALL',
            eligibility_member_status ENUM('ALL','ATIVO','INATIVO') NOT NULL DEFAULT 'ALL',
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_benefits_status (status),
            KEY idx_benefits_sort_order (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS member_benefits (
            member_id BIGINT UNSIGNED NOT NULL,
            benefit_id BIGINT UNSIGNED NOT NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (member_id, benefit_id),
            KEY idx_member_benefits_benefit_id (benefit_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo VARCHAR(190) NOT NULL,
            descricao TEXT NULL,
            local VARCHAR(190) NULL,
            inicio_em DATETIME NOT NULL,
            fim_em DATETIME NULL,
            vagas INT NULL,
            status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
            access_scope ENUM('ALL','PARCIAL','INTEGRAL') NOT NULL DEFAULT 'ALL',
            waitlist_enabled TINYINT(1) NOT NULL DEFAULT 1,
            checkin_enabled TINYINT(1) NOT NULL DEFAULT 1,
            max_waitlist INT NULL,
            imagem_url VARCHAR(255) NULL,
            link VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_events_inicio_em (inicio_em),
            KEY idx_events_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS event_registrations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT UNSIGNED NOT NULL,
            member_id BIGINT UNSIGNED NOT NULL,
            status ENUM('registered','waitlisted','checked_in','canceled') NOT NULL DEFAULT 'registered',
            waitlisted_at DATETIME NULL,
            checked_in_at DATETIME NULL,
            canceled_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_event_member (event_id, member_id),
            KEY idx_event_regs_member_id (member_id),
            KEY idx_event_regs_status (status),
            KEY idx_event_regs_waitlisted_at (waitlisted_at),
            KEY idx_event_regs_checked_in_at (checked_in_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS posts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo ENUM('BLOG','COMUNICADO') NOT NULL DEFAULT 'COMUNICADO',
            titulo VARCHAR(190) NOT NULL,
            slug VARCHAR(220) NULL,
            conteudo LONGTEXT NULL,
            status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
            publicado_em DATETIME NULL,
            scheduled_for DATETIME NULL,
            target_categoria ENUM('ALL','PARCIAL','INTEGRAL') NOT NULL DEFAULT 'ALL',
            target_status ENUM('ALL','ATIVO','INATIVO') NOT NULL DEFAULT 'ALL',
            target_uf CHAR(2) NULL,
            target_lotacao VARCHAR(150) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_posts_slug (slug),
            KEY idx_posts_tipo_status (tipo, status),
            KEY idx_posts_publicado_em (publicado_em),
            KEY idx_posts_scheduled_for (scheduled_for)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS campaigns (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            canal ENUM('INAPP','EMAIL','WHATSAPP') NOT NULL,
            titulo VARCHAR(190) NOT NULL,
            payload_json LONGTEXT NULL,
            filtro_json LONGTEXT NULL,
            status ENUM('draft','queued','processing','done','failed') NOT NULL DEFAULT 'draft',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_campaigns_canal_status (canal, status),
            KEY idx_campaigns_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS campaign_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL,
            run_id BIGINT UNSIGNED NULL,
            member_id BIGINT UNSIGNED NULL,
            canal ENUM('INAPP','EMAIL','WHATSAPP') NOT NULL,
            destino VARCHAR(190) NOT NULL,
            status ENUM('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
            erro TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_campaign_logs_campaign_id (campaign_id),
            KEY idx_campaign_logs_run_id (run_id),
            KEY idx_campaign_logs_campaign_run (campaign_id, run_id),
            KEY idx_campaign_logs_member_id (member_id),
            KEY idx_campaign_logs_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS campaign_runs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL,
            status ENUM('processing','done','failed') NOT NULL DEFAULT 'processing',
            total_count INT NOT NULL DEFAULT 0,
            queued_count INT NOT NULL DEFAULT 0,
            sent_count INT NOT NULL DEFAULT 0,
            failed_count INT NOT NULL DEFAULT 0,
            skipped_count INT NOT NULL DEFAULT 0,
            error_message TEXT NULL,
            started_at DATETIME NOT NULL,
            finished_at DATETIME NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_campaign_runs_campaign_id (campaign_id),
            KEY idx_campaign_runs_status (status),
            KEY idx_campaign_runs_started_at (started_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS integration_settings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            provider ENUM('MAILCHIMP','WHATSAPP') NOT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 0,
            api_key VARCHAR(255) NULL,
            endpoint VARCHAR(255) NULL,
            sender VARCHAR(120) NULL,
            config_json LONGTEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_integration_provider (provider)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NULL,
            modulo VARCHAR(60) NOT NULL,
            acao VARCHAR(60) NOT NULL,
            entidade VARCHAR(80) NULL,
            entidade_id BIGINT UNSIGNED NULL,
            antes_json LONGTEXT NULL,
            depois_json LONGTEXT NULL,
            meta_json LONGTEXT NULL,
            ip VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_audit_logs_modulo_acao (modulo, acao),
            KEY idx_audit_logs_entidade (entidade, entidade_id),
            KEY idx_audit_logs_user_id (user_id),
            KEY idx_audit_logs_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS member_status_history (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id BIGINT UNSIGNED NOT NULL,
            old_status ENUM('ATIVO','INATIVO') NULL,
            new_status ENUM('ATIVO','INATIVO') NOT NULL,
            changed_by BIGINT UNSIGNED NULL,
            reason VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_member_status_history_member (member_id),
            KEY idx_member_status_history_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS user_saved_filters (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            module_code VARCHAR(80) NOT NULL,
            filter_key VARCHAR(80) NOT NULL DEFAULT 'default',
            filter_json LONGTEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_user_saved_filters_scope (user_id, module_code, filter_key),
            KEY idx_user_saved_filters_user_module (user_id, module_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS member_folders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id BIGINT UNSIGNED NULL,
            parent_id BIGINT UNSIGNED NULL,
            tipo ENUM('root','member','folder') NOT NULL DEFAULT 'folder',
            nome VARCHAR(255) NOT NULL,
            status ENUM('active','trash') NOT NULL DEFAULT 'active',
            created_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_member_folders_level (parent_id, nome, status),
            KEY idx_member_folders_member (member_id),
            KEY idx_member_folders_parent (parent_id),
            KEY idx_member_folders_tipo_status (tipo, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS member_files (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            folder_id BIGINT UNSIGNED NOT NULL,
            member_id BIGINT UNSIGNED NULL,
            nome_original VARCHAR(255) NOT NULL,
            nome_exibicao VARCHAR(255) NOT NULL,
            storage_path VARCHAR(500) NOT NULL,
            mime_type VARCHAR(120) NOT NULL,
            ext VARCHAR(16) NULL,
            tamanho_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status ENUM('active','trash') NOT NULL DEFAULT 'active',
            created_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_member_files_folder (folder_id),
            KEY idx_member_files_member (member_id),
            KEY idx_member_files_status (status),
            KEY idx_member_files_nome (nome_exibicao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS post_reads (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            member_id BIGINT UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_post_reads_member_post (member_id, post_id),
            KEY idx_post_reads_post_member (post_id, member_id),
            KEY idx_post_reads_read_at (read_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($queries as $sql) {
        $db->exec($sql);
    }

    if (!anateje_schema_has_column($db, 'campaign_logs', 'run_id')) {
        $db->exec('ALTER TABLE campaign_logs ADD COLUMN run_id BIGINT UNSIGNED NULL AFTER campaign_id');
    }
    if (!anateje_schema_has_index($db, 'campaign_logs', 'idx_campaign_logs_run_id')) {
        $db->exec('ALTER TABLE campaign_logs ADD INDEX idx_campaign_logs_run_id (run_id)');
    }
    if (!anateje_schema_has_index($db, 'campaign_logs', 'idx_campaign_logs_campaign_run')) {
        $db->exec('ALTER TABLE campaign_logs ADD INDEX idx_campaign_logs_campaign_run (campaign_id, run_id)');
    }

    if (!anateje_schema_has_column($db, 'events', 'access_scope')) {
        $db->exec("ALTER TABLE events ADD COLUMN access_scope ENUM('ALL','PARCIAL','INTEGRAL') NOT NULL DEFAULT 'ALL' AFTER status");
    }
    if (!anateje_schema_has_column($db, 'events', 'waitlist_enabled')) {
        $db->exec("ALTER TABLE events ADD COLUMN waitlist_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER access_scope");
    }
    if (!anateje_schema_has_column($db, 'events', 'checkin_enabled')) {
        $db->exec("ALTER TABLE events ADD COLUMN checkin_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER waitlist_enabled");
    }
    if (!anateje_schema_has_column($db, 'events', 'max_waitlist')) {
        $db->exec("ALTER TABLE events ADD COLUMN max_waitlist INT NULL AFTER checkin_enabled");
    }

    $eventRegStatusType = anateje_schema_column_type($db, 'event_registrations', 'status');
    if ($eventRegStatusType !== null) {
        if (strpos($eventRegStatusType, 'waitlisted') === false || strpos($eventRegStatusType, 'checked_in') === false) {
            $db->exec("ALTER TABLE event_registrations MODIFY COLUMN status ENUM('registered','waitlisted','checked_in','canceled') NOT NULL DEFAULT 'registered'");
        }
    }
    if (!anateje_schema_has_column($db, 'event_registrations', 'waitlisted_at')) {
        $db->exec("ALTER TABLE event_registrations ADD COLUMN waitlisted_at DATETIME NULL AFTER status");
    }
    if (!anateje_schema_has_column($db, 'event_registrations', 'checked_in_at')) {
        $db->exec("ALTER TABLE event_registrations ADD COLUMN checked_in_at DATETIME NULL AFTER waitlisted_at");
    }
    if (!anateje_schema_has_column($db, 'event_registrations', 'canceled_at')) {
        $db->exec("ALTER TABLE event_registrations ADD COLUMN canceled_at DATETIME NULL AFTER checked_in_at");
    }
    if (!anateje_schema_has_index($db, 'event_registrations', 'idx_event_regs_waitlisted_at')) {
        $db->exec('ALTER TABLE event_registrations ADD INDEX idx_event_regs_waitlisted_at (waitlisted_at)');
    }
    if (!anateje_schema_has_index($db, 'event_registrations', 'idx_event_regs_checked_in_at')) {
        $db->exec('ALTER TABLE event_registrations ADD INDEX idx_event_regs_checked_in_at (checked_in_at)');
    }

    if (!anateje_schema_has_column($db, 'posts', 'scheduled_for')) {
        $db->exec('ALTER TABLE posts ADD COLUMN scheduled_for DATETIME NULL AFTER publicado_em');
    }
    if (!anateje_schema_has_column($db, 'posts', 'target_categoria')) {
        $db->exec("ALTER TABLE posts ADD COLUMN target_categoria ENUM('ALL','PARCIAL','INTEGRAL') NOT NULL DEFAULT 'ALL' AFTER scheduled_for");
    }
    if (!anateje_schema_has_column($db, 'posts', 'target_status')) {
        $db->exec("ALTER TABLE posts ADD COLUMN target_status ENUM('ALL','ATIVO','INATIVO') NOT NULL DEFAULT 'ALL' AFTER target_categoria");
    }
    if (!anateje_schema_has_column($db, 'posts', 'target_uf')) {
        $db->exec("ALTER TABLE posts ADD COLUMN target_uf CHAR(2) NULL AFTER target_status");
    }
    if (!anateje_schema_has_column($db, 'posts', 'target_lotacao')) {
        $db->exec("ALTER TABLE posts ADD COLUMN target_lotacao VARCHAR(150) NULL AFTER target_uf");
    }
    if (!anateje_schema_has_index($db, 'posts', 'idx_posts_scheduled_for')) {
        $db->exec('ALTER TABLE posts ADD INDEX idx_posts_scheduled_for (scheduled_for)');
    }

    if (!anateje_schema_has_column($db, 'benefits', 'eligibility_categoria')) {
        $db->exec("ALTER TABLE benefits ADD COLUMN eligibility_categoria ENUM('ALL','PARCIAL','INTEGRAL') NOT NULL DEFAULT 'ALL' AFTER status");
    }
    if (!anateje_schema_has_column($db, 'benefits', 'eligibility_member_status')) {
        $db->exec("ALTER TABLE benefits ADD COLUMN eligibility_member_status ENUM('ALL','ATIVO','INATIVO') NOT NULL DEFAULT 'ALL' AFTER eligibility_categoria");
    }

    $count = (int) $db->query('SELECT COUNT(*) AS c FROM benefits')->fetch(PDO::FETCH_ASSOC)['c'];
    if ($count === 0) {
        $seed = [
            ['Assessoria Juridica', null, null, 'active', 1],
            ['Telemedicina Byteclin', null, null, 'active', 2],
            ['Ambulatej', null, null, 'active', 3],
            ['Mestrado Cesara', null, null, 'active', 4],
            ['Byte Club Descontos', null, null, 'active', 5],
            ['Wellhub / Gympass', null, null, 'active', 6],
            ['Instituto ITES', null, null, 'active', 7],
            ['TIM Telefonia', null, null, 'active', 8],
        ];

        $st = $db->prepare('INSERT INTO benefits (nome, descricao, link, status, sort_order) VALUES (?,?,?,?,?)');
        foreach ($seed as $item) {
            $st->execute($item);
        }
    }

    $ready = true;
}
