<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
anateje_require_admin($auth);

$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';

function integrations_default_rows(): array
{
    return [
        'MAILCHIMP' => [
            'provider' => 'MAILCHIMP',
            'enabled' => 0,
            'api_key' => '',
            'endpoint' => '',
            'sender' => '',
            'config' => []
        ],
        'WHATSAPP' => [
            'provider' => 'WHATSAPP',
            'enabled' => 0,
            'api_key' => '',
            'endpoint' => '',
            'sender' => '',
            'config' => []
        ],
    ];
}

function integrations_load(PDO $db): array
{
    $rows = integrations_default_rows();

    $dbRows = $db->query('SELECT provider, enabled, api_key, endpoint, sender, config_json, updated_at FROM integration_settings')
        ->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dbRows as $r) {
        $provider = strtoupper((string) $r['provider']);
        if (!isset($rows[$provider])) {
            continue;
        }

        $rows[$provider] = [
            'provider' => $provider,
            'enabled' => (int) $r['enabled'] === 1 ? 1 : 0,
            'api_key' => (string) ($r['api_key'] ?? ''),
            'endpoint' => (string) ($r['endpoint'] ?? ''),
            'sender' => (string) ($r['sender'] ?? ''),
            'config' => anateje_decode_json($r['config_json'] ?? ''),
            'updated_at' => $r['updated_at'] ?? null,
        ];
    }

    return $rows;
}

if ($action === 'admin_get' || $action === 'admin_list') {
    $providers = integrations_load($db);
    anateje_ok(['providers' => array_values($providers)]);
}

if ($action === 'admin_save') {
    anateje_require_method(['POST']);

    $in = anateje_input();
    $provider = strtoupper(trim((string) ($in['provider'] ?? '')));
    if (!in_array($provider, ['MAILCHIMP', 'WHATSAPP'], true)) {
        anateje_error('VALIDATION', 'Provider invalido', 422);
    }

    $enabled = !empty($in['enabled']) ? 1 : 0;
    $apiKey = trim((string) ($in['api_key'] ?? ''));
    $endpoint = trim((string) ($in['endpoint'] ?? ''));
    $sender = trim((string) ($in['sender'] ?? ''));

    $config = $in['config'] ?? [];
    if (is_string($config)) {
        $parsed = json_decode($config, true);
        $config = is_array($parsed) ? $parsed : [];
    }
    if (!is_array($config)) {
        $config = [];
    }

    $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $sql = "INSERT INTO integration_settings (provider, enabled, api_key, endpoint, sender, config_json)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            enabled = VALUES(enabled),
            api_key = VALUES(api_key),
            endpoint = VALUES(endpoint),
            sender = VALUES(sender),
            config_json = VALUES(config_json)";

    $st = $db->prepare($sql);
    $st->execute([$provider, $enabled, $apiKey ?: null, $endpoint ?: null, $sender ?: null, $configJson]);

    $providers = integrations_load($db);
    anateje_ok(['saved' => true, 'provider' => $provider, 'providers' => array_values($providers)]);
}

if ($action === 'admin_test') {
    anateje_require_method(['POST']);

    $in = anateje_input();
    $provider = strtoupper(trim((string) ($in['provider'] ?? '')));
    if (!in_array($provider, ['MAILCHIMP', 'WHATSAPP'], true)) {
        anateje_error('VALIDATION', 'Provider invalido', 422);
    }

    $providers = integrations_load($db);
    $cfg = $providers[$provider] ?? null;
    if (!$cfg) {
        anateje_error('NOT_FOUND', 'Configuracao nao encontrada', 404);
    }

    if ((int) $cfg['enabled'] !== 1) {
        anateje_error('VALIDATION', 'Integracao desativada', 422);
    }

    if (trim((string) $cfg['api_key']) === '' || trim((string) $cfg['endpoint']) === '') {
        anateje_error('VALIDATION', 'Informe API key e endpoint para testar', 422);
    }

    $config = is_array($cfg['config'] ?? null) ? $cfg['config'] : [];
    $extraHeaders = [];
    if (!empty($cfg['api_key'])) {
        $extraHeaders[] = 'Authorization: Bearer ' . $cfg['api_key'];
        $extraHeaders[] = 'X-API-Key: ' . $cfg['api_key'];
    }
    if (is_array($config['headers'] ?? null)) {
        foreach ($config['headers'] as $h) {
            $h = trim((string) $h);
            if ($h !== '') {
                $extraHeaders[] = $h;
            }
        }
    }

    $payload = [
        'type' => 'integration_test',
        'provider' => $provider,
        'timestamp' => date('c'),
        'sender' => $cfg['sender'] ?? '',
        'config' => $config,
    ];

    $res = anateje_http_post_json((string) $cfg['endpoint'], $payload, $extraHeaders, 15);
    if (!$res['ok']) {
        $msg = 'Teste falhou';
        if (!empty($res['status'])) {
            $msg .= ' (HTTP ' . $res['status'] . ')';
        }
        if (!empty($res['error'])) {
            $msg .= ': ' . $res['error'];
        }
        anateje_error('TEST_FAIL', $msg, 422, ['response' => $res['body'] ?? '']);
    }

    anateje_ok([
        'tested' => true,
        'provider' => $provider,
        'message' => 'Teste realizado com sucesso no endpoint configurado.',
        'http_status' => $res['status'] ?? 0
    ]);
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
