<?php
// ANATEJE - Bootstrap comum dos endpoints financeiros.

require_once __DIR__ . '/../v1/_bootstrap.php';

function financeiro_require_auth(?string $pageCode = null): array
{
    $auth = anateje_require_auth();
    $perfilId = (int) ($auth['perfil_id'] ?? 0);

    // Compatibilidade com o legado financeiro enquanto a matriz granular nao esta completa.
    if (in_array($perfilId, [1, 2, 7], true)) {
        return $auth;
    }

    $db = getDB();
    $candidates = ['dashboard.financeiro', 'financeiro.dashboard'];
    $pageCode = trim((string) $pageCode);
    if ($pageCode !== '') {
        $candidates[] = 'financeiro.' . $pageCode;
        $candidates[] = 'financeiro.' . $pageCode . '.view';
    }

    foreach (array_values(array_unique($candidates)) as $code) {
        if (anateje_has_permission_code($db, $auth, $code)) {
            return $auth;
        }
    }

    anateje_error('FORBIDDEN', 'Sem permissao para acessar modulo financeiro', 403, [
        'perfil_id' => $perfilId,
        'page' => $pageCode,
    ]);
}

function financeiro_input(): array
{
    $input = anateje_input();
    if (!empty($input)) {
        return $input;
    }

    if (!empty($_POST) && is_array($_POST)) {
        return $_POST;
    }

    return [];
}

function financeiro_response($payload, int $status = 200): void
{
    if (!is_array($payload)) {
        anateje_json([
            'ok' => false,
            'success' => false,
            'message' => 'Resposta invalida do endpoint financeiro',
            'error' => ['code' => 'FINANCEIRO_RESPONSE_INVALID']
        ], 500);
    }

    $isSuccess = array_key_exists('success', $payload) ? (bool) $payload['success'] : ($status < 400);
    $message = trim((string) ($payload['message'] ?? ''));

    if ($isSuccess) {
        $resp = $payload;
        $resp['ok'] = true;
        $resp['success'] = true;
        if ($message === '') {
            $resp['message'] = 'OK';
        }
        $httpStatus = ($status >= 100 && $status <= 599) ? $status : 200;
        anateje_json($resp, $httpStatus);
    }

    $errorCode = trim((string) ($payload['code'] ?? 'FINANCEIRO_ERROR'));
    if ($errorCode === '') {
        $errorCode = 'FINANCEIRO_ERROR';
    }
    if ($message === '') {
        $message = 'Erro ao processar solicitacao financeira';
    }

    // Compatibilidade legado: manter 200 quando o endpoint nao explicitar codigo HTTP de erro.
    $httpStatus = $status;
    if (isset($payload['status'])) {
        $httpStatus = (int) $payload['status'];
    }
    if ($httpStatus < 100 || $httpStatus > 599) {
        $httpStatus = 200;
    }

    $resp = $payload;
    $resp['ok'] = false;
    $resp['success'] = false;
    $resp['message'] = $message;
    if (!isset($resp['error']) || !is_array($resp['error'])) {
        $resp['error'] = ['code' => $errorCode, 'message' => $message];
    }

    anateje_json($resp, $httpStatus);
}

function financeiro_extract_entity_id(array $result): ?int
{
    if (isset($result['id']) && (int) $result['id'] > 0) {
        return (int) $result['id'];
    }
    if (isset($result['data']) && is_array($result['data']) && isset($result['data']['id']) && (int) $result['data']['id'] > 0) {
        return (int) $result['data']['id'];
    }
    return null;
}

function financeiro_audit(
    array $auth,
    string $action,
    string $entity,
    ?int $entityId = null,
    $before = null,
    $after = null,
    array $meta = []
): void {
    try {
        $userId = (int) ($auth['sub'] ?? 0);
        $cleanAction = trim($action) !== '' ? trim($action) : 'unknown';
        $cleanEntity = trim($entity) !== '' ? trim($entity) : 'financeiro_entidade';
        $db = getDB();
        financeiro_ensure_audit_table($db);

        anateje_audit_log(
            $db,
            $userId,
            'financeiro',
            $cleanAction,
            $cleanEntity,
            $entityId !== null && $entityId > 0 ? $entityId : null,
            $before,
            $after,
            $meta
        );
    } catch (Throwable $e) {
        logError('Falha ao registrar auditoria financeira: ' . $e->getMessage());
    }
}

function financeiro_ensure_audit_table(PDO $db): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $db->exec("CREATE TABLE IF NOT EXISTS audit_logs (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $ready = true;
}

function financeiro_table_exists(PDO $db, string $table): bool
{
    static $cache = [];
    $table = trim($table);
    if ($table === '') {
        return false;
    }

    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    try {
        $schema = (string) $db->query('SELECT DATABASE()')->fetchColumn();
        if ($schema === '') {
            $cache[$table] = false;
            return false;
        }
        $st = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
        $st->execute([$schema, $table]);
        $cache[$table] = ((int) $st->fetchColumn()) > 0;
        return $cache[$table];
    } catch (Throwable $e) {
        logError('Falha ao verificar tabela ' . $table . ': ' . $e->getMessage());
        $cache[$table] = false;
        return false;
    }
}

function financeiro_require_tables(array $tables, string $module): void
{
    $db = getDB();
    $missing = [];
    foreach ($tables as $table) {
        $table = trim((string) $table);
        if ($table === '') {
            continue;
        }
        if (!financeiro_table_exists($db, $table)) {
            $missing[] = $table;
        }
    }

    if (!empty($missing)) {
        financeiro_response([
            'success' => false,
            'code' => 'FINANCEIRO_SCHEMA_MISSING',
            'message' => 'Modulo financeiro em adaptacao de dominio',
            'details' => [
                'module' => $module,
                'missing_tables' => $missing
            ]
        ], 503);
    }
}
