<?php
// Main container for authenticated area + public landing redirect

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/rbac.php';
require_once __DIR__ . '/includes/page_router.php';
require_once __DIR__ . '/includes/base_path.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$requestedPage = $_GET['page'] ?? '';
$publicPages = ['', 'public/home'];
$prefix = lidergest_base_prefix();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    $target = in_array($requestedPage, $publicPages, true)
        ? "{$prefix}frontend/public/home.php"
        : "{$prefix}frontend/auth/login.html";
    header("Location: {$target}");
    exit;
}

$tokenData = verifyToken($_SESSION['token']);
if (!$tokenData || (int)($tokenData['user_id'] ?? 0) !== (int)$_SESSION['user_id']) {
    session_destroy();
    $target = in_array($requestedPage, $publicPages, true)
        ? "{$prefix}frontend/public/home.php"
        : "{$prefix}frontend/auth/login.html";
    header("Location: {$target}");
    exit;
}

if ($requestedPage === 'public/home') {
    header("Location: {$prefix}frontend/public/home.php");
    exit;
}

$perfilId = (int)($_SESSION['perfil_id'] ?? 0);
$user = [
    'id' => (int)$_SESSION['user_id'],
    'nome' => $_SESSION['user_name'] ?? 'Usuario',
    'email' => $_SESSION['user_email'] ?? '',
    'perfil_id' => $perfilId,
    'perfil_nome' => $_SESSION['perfil_nome'] ?? ''
];

$rbac = new RBAC();
$menu = $rbac->generateMenu($perfilId);

$router = new PageRouter();
if ($requestedPage === '') {
    $requestedPage = 'home';
}

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';
$route = $router->resolve($requestedPage, $perfilId);

if (!$route['success']) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Redirecionando para pagina padrao',
            'redirect' => 'index.php?page=' . $route['redirect']
        ]);
        exit;
    }

    header("Location: {$prefix}index.php?page=" . $route['redirect']);
    exit;
}

$currentPage = $route['page'];
$pageFile = $route['file'];

$pageTitles = [
    'home' => 'Home',
    'dashboard/admin' => 'Dashboard',
    'admin/permissoes' => 'Gerenciamento de Permissoes',
    'cadastros/usuarios' => 'Gerenciamento de Usuarios'
];

$pageTitle = $pageTitles[$currentPage] ?? 'ANATEJE';

ob_start();
define('TEMPLATE_ROUTED', true);
include $pageFile;
$pageContent = ob_get_clean();

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'content' => $pageContent,
        'page' => $currentPage,
        'title' => $pageTitle
    ]);
    exit;
}

include __DIR__ . '/includes/layout.php';
