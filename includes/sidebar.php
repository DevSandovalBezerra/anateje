<?php
// Template Base - Sidebar Dinâmica

if (!isset($menu) || !isset($currentPage)) {
    return;
}

require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/base_path.php';

$baseUrl = lidergest_base_url();
$prefix = lidergest_base_prefix();
$currentModule = '';
if (!empty($currentPage)) {
    $parts = explode('/', $currentPage);
    $currentModule = $parts[0] ?: '';
}

function sidebar_page_exists($page)
{
    $frontendBase = realpath(__DIR__ . '/../frontend');
    if ($frontendBase === false) {
        return false;
    }

    if ($page === 'home') {
        return file_exists($frontendBase . DIRECTORY_SEPARATOR . 'home.php');
    }

    if (!preg_match('/^[a-z0-9_]+\/[a-z0-9_]+$/', $page)) {
        return false;
    }

    return file_exists($frontendBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $page) . '.php');
}

function sidebar_sequence_for_profile($perfilId)
{
    if ((int) $perfilId === 1) {
        return [
            'dashboard/admin',
            'admin/associados',
            'admin/beneficios',
            'admin/eventos',
            'admin/comunicados',
            'admin/campanhas',
            'admin/integracoes',
            'admin/permissoes',
            'cadastros/usuarios',
        ];
    }

    return [
        'dashboard/user',
        'associado/perfil',
        'associado/meus_beneficios',
        'associado/meus_eventos',
        'associado/comunicados',
    ];
}

function sidebar_route_meta($route)
{
    $map = [
        'dashboard/admin' => ['Dashboard Admin', 'layout-dashboard'],
        'dashboard/user' => ['Dashboard Membro', 'layout-dashboard'],
        'associado/perfil' => ['Meu Perfil', 'user-round'],
        'associado/meus_beneficios' => ['Meus Beneficios', 'gift'],
        'associado/meus_eventos' => ['Meus Eventos', 'calendar'],
        'associado/comunicados' => ['Comunicados', 'megaphone'],
        'admin/associados' => ['Associados', 'users'],
        'admin/beneficios' => ['Beneficios', 'gift'],
        'admin/eventos' => ['Eventos', 'calendar'],
        'admin/comunicados' => ['Comunicados', 'megaphone'],
        'admin/campanhas' => ['Campanhas', 'send'],
        'admin/integracoes' => ['Integracoes', 'plug'],
        'admin/permissoes' => ['Permissoes', 'shield'],
        'cadastros/usuarios' => ['Usuarios', 'user-cog'],
    ];

    if (isset($map[$route])) {
        return $map[$route];
    }

    $parts = explode('/', $route);
    $label = ucfirst(str_replace('_', ' ', $parts[1] ?? $route));
    return [$label, 'folder'];
}
?>
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-64 sidebar-primary shadow-lg sidebar-transition transform -translate-x-full lg:translate-x-0">
    <div class="sidebar-brand-strip flex items-center justify-center h-20">
        <a href="<?php echo $prefix; ?>index.php"
            class="sidebar-brand-link flex items-center space-x-3 hover:opacity-95 transition-opacity">
            <img src="<?php echo $baseUrl; ?>/assets/images/logo.png" alt="Logo ANATEJE"
                class="sidebar-brand-logo w-10 h-10 rounded-full object-cover">
            <span class="sidebar-brand-text font-bold text-2xl">ANATEJE</span>
        </a>
    </div>

    <nav id="sidebar-scroll" class="mt-8 flex-1 overflow-y-auto overflow-x-hidden">
        <div id="sidebar-groups" class="px-4 space-y-2 pb-4">
            <?php
            $rbac = new RBAC();
            $userPerfilId = isset($user) ? $user['perfil_id'] : 0;
            $routes = sidebar_sequence_for_profile((int) $userPerfilId);
            foreach ($routes as $route):
                if (!preg_match('/^[a-z0-9_]+\/[a-z0-9_]+$/', $route)) {
                    continue;
                }
                [$module, $page] = explode('/', $route, 2);
                if (!$rbac->hasPermissionForPage((int) $userPerfilId, $module, $page)) {
                    continue;
                }
                if (!sidebar_page_exists($route)) {
                    continue;
                }
                [$label, $icon] = sidebar_route_meta($route);
                $isActive = $currentPage === $route;
                ?>
                <a href="<?php echo $prefix; ?>index.php?page=<?php echo htmlspecialchars($route); ?>"
                    class="nav-item flex items-center <?php echo $isActive ? 'active' : ''; ?>"
                    data-sidebar-route="<?php echo htmlspecialchars($route); ?>">
                    <i data-lucide="<?php echo htmlspecialchars($icon); ?>" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($label); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <div class="sidebar-footer px-4 pb-6 pt-4 border-t mt-4">
        <button onclick="userLogout()"
            class="nav-item flex items-center w-full text-red-300 hover:text-red-100 hover:bg-red-500 hover:bg-opacity-20">
            <i data-lucide="log-out" class="w-5 h-5 mr-3"></i>
            <span class="font-medium">Sair</span>
        </button>
    </div>
</aside>

<script>
    const serverCurrentPage = <?php echo json_encode($currentPage ?? ''); ?>;

    function getCurrentPage() {
        return (window.LIDERGEST_CURRENT_PAGE || serverCurrentPage || '').toString();
    }

    function scrollSidebarToNode(node, behavior = 'smooth') {
        if (!node) return;
        try { node.scrollIntoView({ block: 'center', inline: 'nearest', behavior }); }
        catch (e) { node.scrollIntoView(); }
    }

    function scrollSidebarToActive(behavior = 'smooth') {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;
        const activeLink = sidebar.querySelector('a.nav-submenu.active, a.nav-item.active');
        if (activeLink) {
            scrollSidebarToNode(activeLink, behavior);
            return;
        }
        const currentPage = getCurrentPage();
        if (!currentPage) return;
        const module = currentPage.split('/')[0];
        scrollSidebarToNode(document.getElementById(`sidebar-group-${module}`), behavior);
    }

    function toggleSubmenu(menu) {
        const targetId = menu + '-submenu';
        const targetSubmenu = document.getElementById(targetId);
        const targetArrow = document.getElementById(menu + '-arrow');

        document.querySelectorAll('[id$="-submenu"]').forEach(submenu => {
            if (submenu.id !== targetId && !submenu.classList.contains('hidden')) {
                submenu.classList.add('hidden');
                const moduleName = submenu.id.replace('-submenu', '');
                const arrow = document.getElementById(moduleName + '-arrow');
                if (arrow) arrow.classList.remove('rotate-180');
            }
        });

        if (targetSubmenu && targetArrow) {
            targetSubmenu.classList.toggle('hidden');
            targetArrow.classList.toggle('rotate-180');
            if (!targetSubmenu.classList.contains('hidden') && typeof lucide !== 'undefined') {
                setTimeout(() => { lucide.createIcons(); }, 100);
            }
            scrollSidebarToNode(document.getElementById(`sidebar-group-${menu}`), 'smooth');
            if (!targetSubmenu.classList.contains('hidden')) {
                setTimeout(() => scrollSidebarToActive('smooth'), 60);
            }
        }
    }

    function isGroupedSidebarPage(page) {
        if (!page) return false;
        if (page === 'admin/permissoes' || page === 'cadastros/usuarios' || page.startsWith('dashboard/')) return false;
        const parts = page.split('/');
        return parts.length === 2 && !!document.getElementById(`${parts[0]}-submenu`);
    }

    function collapseAllSidebarGroups() {
        document.querySelectorAll('[id$="-submenu"]').forEach(s => {
            if (!s.classList.contains('hidden')) s.classList.add('hidden');
        });
        document.querySelectorAll('[id$="-arrow"]').forEach(a => a.classList.remove('rotate-180'));
    }

    function abrirSubmenuAtual() {
        const currentPage = getCurrentPage();
        if (!isGroupedSidebarPage(currentPage)) {
            collapseAllSidebarGroups();
            setTimeout(() => scrollSidebarToActive('auto'), 0);
            return;
        }
        const module = currentPage.split('/')[0];
        document.querySelectorAll('[id$="-submenu"]').forEach(submenu => {
            const isAtual = submenu.id === `${module}-submenu`;
            if (!isAtual && !submenu.classList.contains('hidden')) submenu.classList.add('hidden');
            if (isAtual && submenu.classList.contains('hidden')) submenu.classList.remove('hidden');
        });
        document.querySelectorAll('[id$="-arrow"]').forEach(arrow => {
            const isAtual = arrow.id === `${module}-arrow`;
            arrow.classList.toggle('rotate-180', isAtual);
        });
        setTimeout(() => scrollSidebarToActive('auto'), 0);
    }

    document.addEventListener('DOMContentLoaded', abrirSubmenuAtual);

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    async function userLogout() {
        if (!confirm('Deseja realmente sair do sistema?')) return;
        const baseUrl = typeof window.LIDERGEST_BASE_URL !== 'undefined' ?
            window.LIDERGEST_BASE_URL : '<?php echo $baseUrl; ?>';
        const url = `${baseUrl}/api/auth/logout.php`;

        try {
            await fetch(url, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
        } catch (error) { console.error('Erro:', error); }
        window.location.href = `${baseUrl}/index.php`;
    }
</script>
