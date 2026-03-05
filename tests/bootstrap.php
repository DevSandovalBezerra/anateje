<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('America/Manaus');

define('TEST_PROJECT_ROOT', dirname(__DIR__));
define('TEST_DB_HOST', 'localhost');
define('TEST_DB_NAME', 'brunor90_anateje_test');
define('TEST_DB_USER', 'root');
define('TEST_DB_PASS', '');

putenv('DB_HOST=' . TEST_DB_HOST);
putenv('DB_NAME=' . TEST_DB_NAME);
putenv('DB_USER=' . TEST_DB_USER);
putenv('DB_PASS=' . TEST_DB_PASS);

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/api/tests';
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'phpunit';

require_once TEST_PROJECT_ROOT . '/vendor/autoload.php';

$sessionDir = TEST_PROJECT_ROOT . '/tmp/phpunit_sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0775, true);
}
ini_set('session.save_path', $sessionDir);

/**
 * Protege contra execucao acidental em banco nao de teste.
 */
function tests_assert_safe_db_name(string $dbName): void
{
    if (!str_ends_with($dbName, '_test')) {
        throw new RuntimeException('Banco de testes inseguro: ' . $dbName);
    }
}

/**
 * Recria banco de teste limpo a cada execucao da suite.
 */
function tests_reset_database(): void
{
    tests_assert_safe_db_name(TEST_DB_NAME);

    $dsn = sprintf('mysql:host=%s;charset=utf8mb4', TEST_DB_HOST);
    $pdo = new PDO($dsn, TEST_DB_USER, TEST_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $quotedDb = '`' . str_replace('`', '``', TEST_DB_NAME) . '`';
    $pdo->exec('DROP DATABASE IF EXISTS ' . $quotedDb);
    $pdo->exec('CREATE DATABASE ' . $quotedDb . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}

tests_reset_database();
