<?php
// Template Base - Sistema RBAC (Role-Based Access Control)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/base_path.php';

class RBAC
{
    private $db;
    private $permissionsCache = [];

    private function normalizeDashboardName($dashboard)
    {
        $dashboard = strtolower(trim((string) $dashboard));
        if ($dashboard === 'responsavel' || $dashboard === 'associado' || $dashboard === 'membro') {
            return 'user';
        }

        return $dashboard;
    }

    private function normalizeDashboardList($dashboards)
    {
        if (!is_array($dashboards)) {
            return [];
        }

        $normalized = [];
        foreach ($dashboards as $dashboard) {
            $dashboard = $this->normalizeDashboardName($dashboard);
            if ($dashboard !== '' && !in_array($dashboard, $normalized, true)) {
                $normalized[] = $dashboard;
            }
        }

        return $normalized;
    }

    // Estrutura generica de fallback
    private $permissionsFallback = [
        1 => [ // Admin Global
            'dashboard' => ['admin'],
            'admin' => ['associados', 'pastas_associados', 'beneficios', 'eventos', 'comunicados', 'campanhas', 'integracoes', 'permissoes', 'auditoria'],
            'financeiro' => ['manual', 'lancamentos', 'contas_bancarias', 'pessoas', 'categorias_financeiras', 'centros_custos', 'receitas_despesas', 'planos', 'orcamentos', 'contratos', 'cobrancas', 'rematricula', 'dashboard', 'fluxo_caixa', 'conciliacao', 'relatorios', 'contas', 'contas_financeiras', 'pagamentos', 'transferencias'],
            'cadastros' => ['usuarios'],
            'associado' => ['perfil', 'meus_beneficios', 'meus_eventos', 'comunicados'],
        ],
        2 => [ // User
            'dashboard' => ['user'],
            'associado' => ['perfil', 'meus_beneficios', 'meus_eventos', 'comunicados'],
        ]
    ];

    public function __construct()
    {
        $this->db = getDB();
    }

    private function loadPermissionsFromDB($perfil_id)
    {
        if (isset($this->permissionsCache[$perfil_id])) {
            return $this->permissionsCache[$perfil_id];
        }

        try {
            $permissions = $this->permissionsFallback[$perfil_id] ?? [];

            $stmt = $this->db->prepare("
                SELECT p.codigo, p.modulo, pp.concedida
                FROM perfil_permissoes pp
                INNER JOIN permissoes p ON pp.permissao_id = p.id
                WHERE pp.perfil_id = ? AND p.ativo = 1 AND pp.concedida = 1
                ORDER BY p.modulo, p.ordem
            ");
            $stmt->execute([$perfil_id]);
            $permissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $permissoesDB = [];
            foreach ($permissoes as $perm) {
                $modulo = $perm['modulo'];
                $codigo = $perm['codigo'];

                $parts = explode('.', $codigo);
                if (count($parts) === 2) {
                    $page = $parts[1];
                    if ($modulo === 'dashboard') {
                        $page = $this->normalizeDashboardName($page);
                    }
                    if (!isset($permissoesDB[$modulo])) {
                        $permissoesDB[$modulo] = [];
                    }
                    if (!in_array($page, $permissoesDB[$modulo], true)) {
                        $permissoesDB[$modulo][] = $page;
                    }
                }
            }

            foreach ($permissions as $modulo => $pagesFallback) {
                if (isset($permissoesDB[$modulo])) {
                    // Dashboard deve refletir o perfil real do banco quando existir.
                    if ($modulo === 'dashboard') {
                        $permissions[$modulo] = $permissoesDB[$modulo];
                    } else {
                        $permissions[$modulo] = array_unique(array_merge($permissoesDB[$modulo], $pagesFallback));
                    }
                } else {
                    $permissions[$modulo] = $pagesFallback;
                }
            }

            foreach ($permissoesDB as $modulo => $pages) {
                if (!isset($permissions[$modulo])) {
                    $permissions[$modulo] = $pages;
                }
            }

            if (isset($permissions['dashboard'])) {
                $permissions['dashboard'] = $this->normalizeDashboardList($permissions['dashboard']);
            }

            $this->permissionsCache[$perfil_id] = $permissions;
            return $permissions;

        } catch (Exception $e) {
            logError("Erro ao carregar permissoes do banco: " . $e->getMessage());
            $permissions = $this->permissionsFallback[$perfil_id] ?? [];
            $this->permissionsCache[$perfil_id] = $permissions;
            return $permissions;
        }
    }

    public function hasPermission($perfil_id, $module, $page = null)
    {
        if ($perfil_id == 1) {
            return true; // Admin sempre tem acesso a tudo
        }

        $userPermissions = $this->loadPermissionsFromDB($perfil_id);

        if (!isset($userPermissions[$module])) {
            return false;
        }

        if ($page === null) {
            return true;
        }

        if (in_array($page, $userPermissions[$module], true)) {
            return true;
        }

        // Alias de compatibilidade para transicao de nomenclatura no financeiro.
        if ($module === 'financeiro' && $page === 'renovacao_filiacao') {
            return in_array('rematricula', $userPermissions[$module], true);
        }
        if ($module === 'financeiro' && $page === 'rematricula') {
            return in_array('renovacao_filiacao', $userPermissions[$module], true);
        }
        if ($module === 'financeiro' && $page === 'lancamentos') {
            return in_array('contas', $userPermissions[$module], true);
        }
        if ($module === 'financeiro' && $page === 'contas') {
            return in_array('lancamentos', $userPermissions[$module], true);
        }

        return false;
    }

    public function canAccessDashboard($perfil_id, $dashboard)
    {
        if ($perfil_id == 1) {
            return true;
        }

        $dashboard = $this->normalizeDashboardName($dashboard);
        $userPermissions = $this->loadPermissionsFromDB($perfil_id);
        if (!isset($userPermissions['dashboard'])) {
            return false;
        }

        $dashboards = $this->normalizeDashboardList($userPermissions['dashboard']);
        return in_array($dashboard, $dashboards, true);
    }

    public function getUserPermissions($perfil_id)
    {
        return $this->loadPermissionsFromDB($perfil_id);
    }

    public function protectPage($module, $page = null)
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['perfil_id'])) {
            header('Location: ' . lidergest_base_prefix() . 'frontend/auth/login.html');
            exit;
        }

        $perfil_id = $_SESSION['perfil_id'];
        if (!$this->hasPermission($perfil_id, $module, $page)) {
            $this->redirectToDashboard($perfil_id);
        }
    }

    public function protectDashboard($dashboard)
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['perfil_id'])) {
            header('Location: ' . lidergest_base_prefix() . 'frontend/auth/login.html');
            exit;
        }

        $perfil_id = $_SESSION['perfil_id'];
        if (!$this->canAccessDashboard($perfil_id, $dashboard)) {
            $this->redirectToDashboard($perfil_id);
        }
    }

    public function canAccessPage($perfil_id, $module, $page = null)
    {
        if ($module === 'dashboard') {
            return $this->canAccessDashboard($perfil_id, $page);
        }

        return $this->hasPermission($perfil_id, $module, $page);
    }

    public function hasPermissionForPage($perfil_id, $module, $page)
    {
        if ($module === 'dashboard') {
            return $this->canAccessDashboard($perfil_id, $page);
        }

        return $this->hasPermission($perfil_id, $module, $page);
    }

    private function redirectToDashboard($perfil_id)
    {
        $prefix = lidergest_base_prefix();
        $permissions = $this->getUserPermissions($perfil_id);

        $dashboards = $this->normalizeDashboardList($permissions['dashboard'] ?? []);
        $target = '';
        if (in_array('admin', $dashboards, true)) {
            $target = 'dashboard/admin';
        } elseif (in_array('financeiro', $dashboards, true)) {
            $target = 'dashboard/financeiro';
        } elseif (in_array('user', $dashboards, true)) {
            $target = 'dashboard/user';
        } elseif (!empty($dashboards)) {
            $target = 'dashboard/' . (string) reset($dashboards);
        } else {
            foreach ($permissions as $module => $pages) {
                if ($module === 'dashboard' || empty($pages)) {
                    continue;
                }
                $firstPage = (string) reset($pages);
                if ($firstPage !== '') {
                    $target = $module . '/' . $firstPage;
                    break;
                }
            }
        }

        if ($target !== '') {
            header("Location: {$prefix}index.php?page={$target}");
        } else {
            header("Location: {$prefix}index.php");
        }
        exit;
    }

    public function generateMenu($perfil_id)
    {
        $permissions = $this->getUserPermissions($perfil_id);
        $menu = [];

        if (!empty($permissions['dashboard'])) {
            $dashboardsPermitidos = [];
            foreach ($this->normalizeDashboardList($permissions['dashboard']) as $dash) {
                $dash = trim((string) $dash);
                if ($dash !== '' && !in_array($dash, $dashboardsPermitidos, true)) {
                    $dashboardsPermitidos[] = $dash;
                }
            }

            if (!empty($dashboardsPermitidos)) {
                $menu['dashboard'] = [
                    'name' => 'Dashboard',
                    'icon' => 'layout-dashboard',
                    'pages' => $dashboardsPermitidos
                ];
            }
        }

        $iconMap = [
            'admin' => 'shield',
            'financeiro' => 'wallet',
            'associado' => 'user-round',
            'cadastros' => 'users',
            'beneficios' => 'gift',
            'eventos' => 'calendar',
            'comunicados' => 'megaphone',
            'campanhas' => 'send',
            'integracoes' => 'plug'
        ];

        // Outros modulos extraidos do DB/fallback
        foreach ($permissions as $mod => $pages) {
            if ($mod !== 'dashboard' && !empty($pages)) {
                $name = ucfirst(str_replace('_', ' ', $mod));
                $icon = $iconMap[$mod] ?? 'folder';

                $menu[$mod] = [
                    'name' => $name,
                    'icon' => $icon,
                    'pages' => $pages
                ];
            }
        }

        return $menu;
    }
}

function checkPermission($module, $page = null)
{
    $rbac = new RBAC();
    $rbac->protectPage($module, $page);
}

function checkDashboardAccess($dashboard)
{
    $rbac = new RBAC();
    $rbac->protectDashboard($dashboard);
}

function getUserMenu($perfil_id)
{
    $rbac = new RBAC();
    return $rbac->generateMenu($perfil_id);
}
