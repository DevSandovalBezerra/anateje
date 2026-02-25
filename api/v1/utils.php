<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';

if ($action === 'viacep') {
    $cep = anateje_only_digits($_GET['cep'] ?? '');
    if (strlen($cep) !== 8) {
        anateje_error('VALIDATION', 'CEP invalido', 422);
    }

    $url = 'https://viacep.com.br/ws/' . $cep . '/json/';
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => "Accept: application/json\r\n"
        ]
    ]);

    $resp = @file_get_contents($url, false, $ctx);
    if (!$resp) {
        anateje_error('VIACEP_FAIL', 'Falha ao consultar ViaCEP', 502);
    }

    $json = json_decode($resp, true);
    if (!is_array($json) || !empty($json['erro'])) {
        anateje_error('CEP_NOT_FOUND', 'CEP nao encontrado', 404);
    }

    anateje_ok([
        'cep' => anateje_only_digits($json['cep'] ?? ''),
        'logradouro' => $json['logradouro'] ?? null,
        'bairro' => $json['bairro'] ?? null,
        'cidade' => $json['localidade'] ?? null,
        'uf' => $json['uf'] ?? null,
        'by' => $auth['sub']
    ]);
}

if ($action === 'ping') {
    anateje_ok(['status' => 'ok']);
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
