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
?>
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-64 sidebar-primary shadow-lg sidebar-transition transform -translate-x-full lg:translate-x-0">
    <div class="flex items-center justify-center h-16 bg-gradient-primary">
        <a href="<?php echo $prefix; ?>index.php"
            class="flex items-center space-x-2 hover:opacity-90 transition-opacity">
            <img src="<?php echo $baseUrl; ?>/assets/images/logo.png" alt="Logo ANATEJE"
                class="w-8 h-8 rounded-full object-cover border border-white/25">
            <span class="text-white font-bold text-lg">ANATEJE</span>
        </a>
    </div>

    <nav id="sidebar-scroll" class="mt-8 flex-1 overflow-y-auto overflow-x-hidden">
        <div id="sidebar-groups" class="px-4 space-y-2 pb-4">
            <?php if (isset($user) && $user['perfil_id'] == 1): ?>
                <?php if (sidebar_page_exists('home')): ?>
                    <a href="<?php echo $prefix; ?>index.php?page=home"
                        class="nav-item flex items-center <?php echo ($currentPage === "home") ? 'active' : ''; ?>">
                        <i data-lucide="home" class="w-5 h-5 mr-3"></i>
                        <span class="font-medium">Inicio</span>
                    </a>
                <?php endif; ?>

                <?php if (sidebar_page_exists('admin/permissoes')): ?>
                    <a href="<?php echo $prefix; ?>index.php?page=admin/permissoes"
                        class="nav-item flex items-center <?php echo ($currentPage === "admin/permissoes") ? 'active' : ''; ?>">
                        <i data-lucide="shield" class="w-5 h-5 mr-3"></i>
                        <span class="font-medium">Permissoes</span>
                    </a>
                <?php endif; ?>

                <?php if (sidebar_page_exists('cadastros/usuarios')): ?>
                    <a href="<?php echo $prefix; ?>index.php?page=cadastros/usuarios"
                        class="nav-item flex items-center <?php echo ($currentPage === "cadastros/usuarios") ? 'active' : ''; ?>">
                        <i data-lucide="users" class="w-5 h-5 mr-3"></i>
                        <span class="font-medium">Usuarios</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            $rbac = new RBAC();
            $userPerfilId = isset($user) ? $user['perfil_id'] : 0;

            foreach ($menu as $module => $moduleData):
                if ($module === 'dashboard'):
                    foreach ($moduleData['pages'] as $page):
                        if (!$rbac->hasPermissionForPage($userPerfilId, $module, $page))
                            continue;
                        $dashboardRoute = "dashboard/{$page}";
                        if (!sidebar_page_exists($dashboardRoute))
                            continue;
                        ?>
                        <a href="<?php echo $prefix; ?>index.php?page=dashboard/<?php echo htmlspecialchars($page); ?>"
                            class="nav-item flex items-center <?php echo ($currentPage === "dashboard/{$page}") ? 'active' : ''; ?>">
                            <i data-lucide="<?php echo htmlspecialchars($moduleData['icon']); ?>" class="w-5 h-5 mr-3"></i>
                            <span class="font-medium">Dashboard <?php echo ucfirst($page); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else:
                    $temPermissaoModulo = false;
                    $paginasComPermissao = [];

                    foreach ($moduleData['pages'] as $page) {
                        $routeName = "{$module}/{$page}";
                        if ($rbac->hasPermissionForPage($userPerfilId, $module, $page) && sidebar_page_exists($routeName)) {
                            $temPermissaoModulo = true;
                            $paginasComPermissao[] = $page;
                        }
                    }

                    if (!$temPermissaoModulo)
                        continue;
                    $isCurrentModule = ($currentModule === $module);
                    ?>
                    <div class="space-y-1" id="sidebar-group-<?php echo htmlspecialchars($module); ?>">
                        <button onclick="toggleSubmenu('<?php echo $module; ?>')"
                            class="nav-item flex items-center justify-between w-full">
                            <div class="flex items-center">
                                <i data-lucide="<?php echo htmlspecialchars($moduleData['icon']); ?>" class="w-5 h-5 mr-3"></i>
                                <span><?php echo htmlspecialchars($moduleData['name']); ?></span>
                            </div>
                            <i data-lucide="chevron-down"
                                class="w-4 h-4 transform transition-transform <?php echo $isCurrentModule ? 'rotate-180' : ''; ?>"
                                id="<?php echo $module; ?>-arrow"></i>
                        </button>
                        <div id="<?php echo $module; ?>-submenu"
                            class="<?php echo $isCurrentModule ? '' : 'hidden '; ?>ml-4 space-y-1">
                            <?php foreach ($paginasComPermissao as $page): ?>
                                <?php
                                $pageName = ucfirst(str_replace('_', ' ', $page));
                                $isActive = ($currentPage === "{$module}/{$page}");
                                ?>
                                <a href="<?php echo $prefix; ?>index.php?page=<?php echo $module; ?>/<?php echo htmlspecialchars($page); ?>"
                                    class="nav-submenu block <?php echo $isActive ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($pageName); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>

    <div class="px-4 pb-6 pt-4 border-t border-white border-opacity-10 mt-4">
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
