<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

$projectRoot = realpath(__DIR__ . '/../../');
if (!is_string($projectRoot) || $projectRoot === '') {
    fwrite(STDERR, "Projeto nao encontrado\n");
    exit(1);
}

$tmpFiles = [];
$sessionDir = $projectRoot . '/tmp/phpunit_sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0775, true);
}
ini_set('session.save_path', $sessionDir);

register_shutdown_function(static function () use (&$tmpFiles): void {
    foreach ($tmpFiles as $file) {
        if (is_string($file) && $file !== '' && is_file($file)) {
            @unlink($file);
        }
    }
    fwrite(STDERR, "__HTTP_STATUS__=" . (int) http_response_code() . PHP_EOL);
});

$encoded = $argv[1] ?? '';
$options = [];
if (is_string($encoded) && $encoded !== '') {
    $json = base64_decode($encoded, true);
    if (is_string($json) && $json !== '') {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $options = $decoded;
        }
    }
}

putenv('DB_HOST=' . (getenv('DB_HOST') ?: 'localhost'));
putenv('DB_NAME=' . (getenv('DB_NAME') ?: 'brunor90_anateje_test'));
putenv('DB_USER=' . (getenv('DB_USER') ?: 'root'));
putenv('DB_PASS=' . (getenv('DB_PASS') ?: ''));

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'phpunit-runner';
$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_METHOD'] = strtoupper((string) ($options['method'] ?? 'GET'));
$_SERVER['REQUEST_URI'] = '/api/test-runner';

$_GET = is_array($options['get'] ?? null) ? $options['get'] : [];
$_POST = is_array($options['post'] ?? null) ? $options['post'] : [];
$_FILES = [];

require_once $projectRoot . '/config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$auth = !empty($options['auth']);
if ($auth) {
    $userId = (int) ($options['user_id'] ?? 1);
    $perfilId = (int) ($options['perfil_id'] ?? 1);
    $_SESSION['user_id'] = $userId;
    $_SESSION['perfil_id'] = $perfilId;
    $_SESSION['user_name'] = (string) ($options['user_name'] ?? 'Teste');
    $_SESSION['user_email'] = (string) ($options['user_email'] ?? 'teste@example.com');
    initializeAuthSessionSecurity();
    $_SESSION['token'] = generateToken($userId, $perfilId);
    $csrf = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrf;

    $method = strtoupper((string) $_SERVER['REQUEST_METHOD']);
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !isset($_POST['csrf_token'])) {
        $_POST['csrf_token'] = $csrf;
    }
}

if (!empty($options['ensure_schema'])) {
    require_once $projectRoot . '/api/v1/_bootstrap.php';
    $db = getDB();
    anateje_ensure_schema($db);
}

$preSql = $options['pre_sql'] ?? [];
if (is_array($preSql) && !empty($preSql)) {
    $db = isset($db) ? $db : getDB();
    foreach ($preSql as $sql) {
        if (!is_string($sql) || trim($sql) === '') {
            continue;
        }
        $db->exec($sql);
    }
}

$files = $options['files'] ?? [];
if (is_array($files)) {
    foreach ($files as $field => $meta) {
        if (!is_string($field) || $field === '' || !is_array($meta)) {
            continue;
        }

        $name = (string) ($meta['name'] ?? 'arquivo.txt');
        $type = (string) ($meta['type'] ?? 'application/octet-stream');
        $error = (int) ($meta['error'] ?? UPLOAD_ERR_OK);

        $tmpName = (string) ($meta['tmp_name'] ?? '');
        if ($tmpName === '') {
            $tmpName = tempnam(sys_get_temp_dir(), 'anateje_upload_');
            if ($tmpName === false) {
                $tmpName = '';
            } else {
                $content = (string) ($meta['content'] ?? 'stub');
                file_put_contents($tmpName, $content);
                $tmpFiles[] = $tmpName;
            }
        }

        $size = isset($meta['size']) ? (int) $meta['size'] : (is_file($tmpName) ? (int) filesize($tmpName) : 0);

        $_FILES[$field] = [
            'name' => $name,
            'type' => $type,
            'tmp_name' => $tmpName,
            'error' => $error,
            'size' => $size,
        ];
    }
}

$endpoint = (string) ($options['endpoint'] ?? '');
$endpoint = ltrim(str_replace('\\', '/', $endpoint), '/');
if ($endpoint === '') {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => ['code' => 'RUNNER', 'message' => 'endpoint ausente']]));
    exit(1);
}

$endpointPath = $projectRoot . '/' . $endpoint;
if (!is_file($endpointPath)) {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => ['code' => 'RUNNER', 'message' => 'endpoint nao encontrado']]));
    exit(1);
}

require $endpointPath;
