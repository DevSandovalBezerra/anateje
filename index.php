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
$publicRouteMap = [
    'public/home' => 'home',
    'public/sobre' => 'sobre',
    'public/beneficios' => 'beneficios',
    'public/eventos' => 'eventos',
    'public/blog' => 'blog',
    'public/contato' => 'contato',
    'public/filiacao' => 'filiacao',
];
$prefix = lidergest_base_prefix();

function public_target_for_page(string $prefix, string $slug): string
{
    return "{$prefix}frontend/public/{$slug}.php";
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    if ($requestedPage === '') {
        header("Location: " . public_target_for_page($prefix, 'home'));
        exit;
    }

    if (isset($publicRouteMap[$requestedPage])) {
        header("Location: " . public_target_for_page($prefix, $publicRouteMap[$requestedPage]));
        exit;
    }

    $target = "{$prefix}frontend/auth/login.html";
    header("Location: {$target}");
    exit;
}

$tokenData = verifyToken($_SESSION['token']);
if (!$tokenData || (int)($tokenData['user_id'] ?? 0) !== (int)$_SESSION['user_id']) {
    session_destroy();
    if ($requestedPage === '' || isset($publicRouteMap[$requestedPage])) {
        $slug = $requestedPage === '' ? 'home' : $publicRouteMap[$requestedPage];
        header("Location: " . public_target_for_page($prefix, $slug));
        exit;
    }

    $target = "{$prefix}frontend/auth/login.html";
    header("Location: {$target}");
    exit;
}

if (isset($publicRouteMap[$requestedPage])) {
    header("Location: " . public_target_for_page($prefix, $publicRouteMap[$requestedPage]));
    exit;
}

$perfilId = (int)($_SESSION['perfil_id'] ?? 0);
$user = [
    'id' => (int)$_SESSION['user_id'],
    'nome' => $_SESSION['user_name'] ?? 'Usuario',
    'email' => $_SESSION['user_email'] ?? '',
    'perfil_id' => $perfilId,
    'perfil_nome' => $_SESSION['perfil_nome'] ?? '',
    'permission_codes' => []
];

$rbac = new RBAC();
$menu = $rbac->generateMenu($perfilId);

try {
    $db = getDB();
    $stPerms = $db->prepare("SELECT p.codigo
        FROM perfil_permissoes pp
        INNER JOIN permissoes p ON p.id = pp.permissao_id
        WHERE pp.perfil_id = ? AND pp.concedida = 1 AND p.ativo = 1");
    $stPerms->execute([$perfilId]);
    $codes = $stPerms->fetchAll(PDO::FETCH_COLUMN);
    if (is_array($codes)) {
        $user['permission_codes'] = array_values(array_unique(array_filter(array_map(function ($code) {
            return trim((string) $code);
        }, $codes), function ($code) {
            return $code !== '';
        })));
    }
} catch (Throwable $e) {
    logError('Falha ao carregar permission_codes do perfil ' . $perfilId . ': ' . $e->getMessage());
}

if (empty($user['permission_codes']) && isset($_SESSION['permissoes']) && is_array($_SESSION['permissoes'])) {
    $fallbackCodes = [];
    foreach ($_SESSION['permissoes'] as $module => $pages) {
        if (!is_array($pages)) {
            continue;
        }
        foreach ($pages as $page) {
            $module = trim((string) $module);
            $page = trim((string) $page);
            if ($module !== '' && $page !== '') {
                $fallbackCodes[] = $module . '.' . $page;
            }
        }
    }
    if (!empty($fallbackCodes)) {
        $user['permission_codes'] = array_values(array_unique($fallbackCodes));
    }
}

$router = new PageRouter();
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
    'dashboard/admin' => 'Dashboard Admin',
    'dashboard/financeiro' => 'Dashboard Financeiro',
    'dashboard/user' => 'Dashboard do Usuario',
    'associado/perfil' => 'Meu Perfil',
    'associado/meus_beneficios' => 'Meus Beneficios',
    'associado/meus_eventos' => 'Meus Eventos',
    'associado/comunicados' => 'Comunicados',
    'admin/associados' => 'Admin - Associados',
    'admin/pastas_associados' => 'Admin - Pastas de Associados',
    'admin/beneficios' => 'Admin - Beneficios',
    'admin/eventos' => 'Admin - Eventos',
    'admin/comunicados' => 'Admin - Comunicados',
    'admin/campanhas' => 'Admin - Campanhas',
    'admin/integracoes' => 'Admin - Integracoes',
    'admin/permissoes' => 'Gerenciamento de Permissoes',
    'admin/auditoria' => 'Admin - Auditoria',
    'financeiro/manual' => 'Financeiro - Manual',
    'financeiro/lancamentos' => 'Financeiro - Lancamentos',
    'financeiro/contas_bancarias' => 'Financeiro - Contas Bancarias',
    'financeiro/pessoas' => 'Financeiro - Pessoas',
    'financeiro/categorias_financeiras' => 'Financeiro - Categorias Financeiras',
    'financeiro/centros_custos' => 'Financeiro - Centros de Custos',
    'financeiro/receitas_despesas' => 'Financeiro - Receitas e Despesas',
    'financeiro/planos' => 'Financeiro - Planos',
    'financeiro/orcamentos' => 'Financeiro - Orcamentos',
    'financeiro/contratos' => 'Financeiro - Contratos',
    'financeiro/cobrancas' => 'Financeiro - Cobrancas',
    'financeiro/renovacao_filiacao' => 'Financeiro - Renovacao de Filiacao',
    'financeiro/rematricula' => 'Financeiro - Renovacao de Filiacao',
    'financeiro/dashboard' => 'Financeiro - Dashboard',
    'financeiro/fluxo_caixa' => 'Financeiro - Fluxo de Caixa',
    'financeiro/conciliacao' => 'Financeiro - Conciliacao',
    'financeiro/relatorios' => 'Financeiro - Relatorios',
    'financeiro/contas' => 'Financeiro - Contas',
    'financeiro/contas_financeiras' => 'Financeiro - Contas Financeiras',
    'financeiro/pagamentos' => 'Financeiro - Pagamentos',
    'financeiro/transferencias' => 'Financeiro - Transferencias',
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
