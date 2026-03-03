<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = trim((string) ($_GET['action'] ?? ''));
$actorId = (int) ($auth['sub'] ?? 0);

function filters_normalize_module_code(string $value): string
{
    $value = strtolower(trim($value));
    if ($value === '') {
        return '';
    }
    if (!preg_match('/^[a-z0-9][a-z0-9\.\-_]{1,78}[a-z0-9]$/', $value)) {
        return '';
    }
    return $value;
}

function filters_normalize_key(string $value): string
{
    $value = strtolower(trim($value));
    if ($value === '') {
        return 'default';
    }
    if (!preg_match('/^[a-z0-9][a-z0-9\-_]{0,38}[a-z0-9]$/', $value)) {
        return 'default';
    }
    return $value;
}

function filters_permission_for_module(string $moduleCode): ?string
{
    $map = [
        'admin.associados.list' => 'admin.associados.view',
        'admin.beneficios.list' => 'admin.beneficios.view',
        'admin.eventos.list' => 'admin.eventos.view',
        'admin.eventos.registrations' => 'admin.eventos.view',
        'admin.comunicados.list' => 'admin.comunicados.view',
        'admin.campanhas.list' => 'admin.campanhas.view',
        'admin.campanhas.logs' => 'admin.campanhas.view',
        'admin.auditoria.list' => 'admin.auditoria.view',
    ];

    return $map[$moduleCode] ?? null;
}

function filters_get_saved(PDO $db, int $userId, string $moduleCode, string $key): ?array
{
    $st = $db->prepare('SELECT filter_json, updated_at
        FROM user_saved_filters
        WHERE user_id = ? AND module_code = ? AND filter_key = ?
        LIMIT 1');
    $st->execute([$userId, $moduleCode, $key]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'filters' => anateje_decode_json($row['filter_json'] ?? ''),
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

if ($action === 'get') {
    $moduleCode = filters_normalize_module_code((string) ($_GET['module'] ?? ''));
    $key = filters_normalize_key((string) ($_GET['key'] ?? 'default'));
    if ($moduleCode === '') {
        anateje_error('VALIDATION', 'Modulo invalido', 422);
    }

    $permission = filters_permission_for_module($moduleCode);
    if ($permission === null) {
        anateje_error('VALIDATION', 'Modulo nao permitido para filtro salvo', 422);
    }
    anateje_require_permission($db, $auth, $permission);

    $saved = filters_get_saved($db, $actorId, $moduleCode, $key);
    if (!$saved) {
        anateje_ok([
            'module' => $moduleCode,
            'key' => $key,
            'found' => false,
            'filters' => null,
        ]);
    }

    anateje_ok([
        'module' => $moduleCode,
        'key' => $key,
        'found' => true,
        'filters' => $saved['filters'],
        'updated_at' => $saved['updated_at'],
    ]);
}

if ($action === 'save') {
    anateje_require_method(['POST']);
    $in = anateje_input();

    $moduleCode = filters_normalize_module_code((string) ($in['module'] ?? ''));
    $key = filters_normalize_key((string) ($in['key'] ?? 'default'));
    if ($moduleCode === '') {
        anateje_error('VALIDATION', 'Modulo invalido', 422);
    }

    $permission = filters_permission_for_module($moduleCode);
    if ($permission === null) {
        anateje_error('VALIDATION', 'Modulo nao permitido para filtro salvo', 422);
    }
    anateje_require_permission($db, $auth, $permission);

    $filters = $in['filters'] ?? [];
    if (!is_array($filters)) {
        $filters = [];
    }
    $filterJson = json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($filterJson)) {
        anateje_error('VALIDATION', 'Dados de filtro invalidos', 422);
    }
    if (strlen($filterJson) > 10000) {
        anateje_error('VALIDATION', 'Filtro excede tamanho maximo permitido', 422);
    }

    $st = $db->prepare("INSERT INTO user_saved_filters (user_id, module_code, filter_key, filter_json)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE filter_json = VALUES(filter_json), updated_at = CURRENT_TIMESTAMP");
    $st->execute([$actorId, $moduleCode, $key, $filterJson]);

    anateje_ok([
        'saved' => true,
        'module' => $moduleCode,
        'key' => $key,
        'filters' => $filters,
    ]);
}

if ($action === 'delete') {
    anateje_require_method(['POST']);
    $in = anateje_input();

    $moduleCode = filters_normalize_module_code((string) ($in['module'] ?? ''));
    $key = filters_normalize_key((string) ($in['key'] ?? 'default'));
    if ($moduleCode === '') {
        anateje_error('VALIDATION', 'Modulo invalido', 422);
    }

    $permission = filters_permission_for_module($moduleCode);
    if ($permission === null) {
        anateje_error('VALIDATION', 'Modulo nao permitido para filtro salvo', 422);
    }
    anateje_require_permission($db, $auth, $permission);

    $st = $db->prepare('DELETE FROM user_saved_filters WHERE user_id = ? AND module_code = ? AND filter_key = ?');
    $st->execute([$actorId, $moduleCode, $key]);

    anateje_ok([
        'deleted' => true,
        'module' => $moduleCode,
        'key' => $key,
    ]);
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
