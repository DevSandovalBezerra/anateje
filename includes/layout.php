<?php
// LiderGest - Layout Wrapper
// Sistema de Gestão Pedagógico-Financeira Líder School

if (!isset($pageTitle) || !isset($user) || !isset($menu) || !isset($currentPage)) {
    die('Variáveis necessárias não definidas para o layout');
}

require_once __DIR__ . '/base_path.php';
$baseUrl = lidergest_base_url();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - ANATEJE</title>
    <link rel="icon" href="<?php echo $baseUrl; ?>/assets/images/favicon.ico" type="image/x-icon">
    <script>
        (function () {
            try {
                const saved = localStorage.getItem('lidergest_theme');
                const themes = ['light', 'dark-blue', 'monokai'];
                if (saved && themes.includes(saved)) {
                    document.documentElement.setAttribute('data-theme', saved);
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/main-compiled.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/tokens.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/colors.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/themes.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/forms-dark-theme.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/modal-lancamentos.css">
    <style>
        :root {
            --sidebar-width: 18rem;
        }

        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }

        @media (min-width: 1024px) {
            #sidebar {
                width: var(--sidebar-width);
            }

            .lg\:ml-64 {
                margin-left: var(--sidebar-width);
            }
        }

        .nav-item {
            @apply flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 transition-colors;
        }

        .nav-item.active {
            @apply bg-purple-100 text-purple-700;
        }

        .nav-submenu {
            @apply block px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors;
        }

        .nav-submenu.active {
            @apply bg-purple-50 text-purple-700 font-medium;
        }

        .page-loading {
            position: relative;
        }

        .page-loading::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(2px);
            z-index: 10;
        }

        .page-loading-spinner {
            position: absolute;
            top: 2rem;
            right: 2rem;
            z-index: 11;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            border: 3px solid #ddd6fe;
            border-top-color: #7c3aed;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
    <script src="<?php echo $baseUrl; ?>/assets/vendor/lucide/lucide.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/vendor/chartjs/chart.umd.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/js/sweetalert-config.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/js/api-config.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/js/theme-manager.js"></script>
</head>

<body style="background: var(--bg-primary); color: var(--text-primary);">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="lg:ml-64">
        <header class="header-primary">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center space-x-4">
                    <button onclick="toggleSidebar()" class="lg:hidden" style="color: var(--text-secondary);"
                        onmouseover="this.style.color = 'var(--text-primary)'"
                        onmouseout="this.style.color = 'var(--text-secondary)'">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold" style="color: var(--text-primary);">
                            <?php echo htmlspecialchars($pageTitle); ?></h1>
                        <p style="color: var(--text-secondary);">Area administrativa</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="theme-toggle"
                        class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        style="background: var(--bg-secondary); color: var(--text-primary);"
                        onmouseover="this.style.backgroundColor = 'var(--bg-hover)'"
                        onmouseout="this.style.backgroundColor = 'var(--bg-secondary)'">
                        <i data-lucide="sun" class="w-5 h-5" style="color: var(--text-secondary);"></i>
                        <span class="theme-text text-sm font-medium" style="color: var(--text-primary);">Claro</span>
                    </button>
                    <div class="text-right">
                        <p class="text-sm text-secondary-dark-gray" style="color: var(--text-secondary);">
                            <?php echo htmlspecialchars($user['nome']); ?></p>
                        <p class="text-xs text-secondary-dark-gray" style="color: var(--text-light);">
                            <?php echo htmlspecialchars($user['perfil_nome'] ?? ''); ?></p>
                    </div>
                    <div class="w-8 h-8 bg-primary-blue rounded-full flex items-center justify-center text-white text-sm font-medium"
                        style="background: var(--primary-purple);">
                        <?php echo strtoupper(substr($user['nome'], 0, 2)); ?>
                    </div>
                </div>
            </div>
        </header>

        <main id="page-wrapper" class="p-6 space-y-6 min-h-screen">
            <?php if (isset($pageContent)): ?>
                <?php echo $pageContent; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Definir baseUrl global para uso em JavaScript
        window.LIDERGEST_BASE_URL = '<?php echo $baseUrl; ?>';
        window.LIDERGEST_CURRENT_PAGE = <?php echo json_encode($currentPage); ?>;
        window.LIDERGEST_PAGE_TITLE = <?php echo json_encode($pageTitle); ?>;

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth >= 1024) {
                return;
            }
            if (sidebar) {
                sidebar.classList.toggle('-translate-x-full');
            }
        }

        window.addEventListener('resize', function () {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove('-translate-x-full');
                } else {
                    sidebar.classList.add('-translate-x-full');
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            if (sidebar && window.innerWidth >= 1024) {
                sidebar.classList.remove('-translate-x-full');
            }
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });
    </script>
    <script src="<?php echo $baseUrl; ?>/assets/js/navigation.js"></script>
</body>

</html>
