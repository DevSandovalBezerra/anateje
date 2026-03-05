<?php

if (PHP_SAPI === 'cli') {
    fwrite(STDERR, "Este script e para navegador (HTTP), nao para CLI.\n");
    exit(1);
}

header('Content-Type: text/plain; charset=utf-8');

$remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
$isLocalRequest = in_array($remoteAddr, ['127.0.0.1', '::1'], true);

if (!$isLocalRequest) {
    $expectedToken = trim((string) getenv('MIGRATE_WEB_TOKEN'));
    if ($expectedToken === '') {
        http_response_code(500);
        echo "MIGRATE_WEB_TOKEN nao configurado no ambiente.\n";
        echo "Defina a variavel e tente novamente.\n";
        exit(1);
    }

    $providedToken = trim((string) ($_GET['token'] ?? ''));
    if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
        http_response_code(403);
        echo "Token invalido.\n";
        exit(1);
    }
}

$cmd = strtolower(trim((string) ($_GET['cmd'] ?? 'status')));
if (!in_array($cmd, ['status', 'up'], true)) {
    http_response_code(400);
    echo "Comando invalido. Use cmd=status ou cmd=up.\n";
    exit(1);
}

define('MIGRATE_ALLOW_WEB', true);
$_GET['cmd'] = $cmd;

require __DIR__ . '/migrate.php';
