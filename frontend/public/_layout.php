<?php

if (!function_exists('anateje_public_base_path')) {
    function anateje_public_base_path(): string
    {
        return '../../';
    }
}

if (!function_exists('anateje_public_nav_items')) {
    function anateje_public_nav_items(): array
    {
        return [
            ['home.php', 'Home'],
            ['sobre.php', 'Sobre'],
            ['beneficios.php', 'Beneficios'],
            ['eventos.php', 'Eventos'],
            ['blog.php', 'Blog'],
            ['contato.php', 'Contato'],
            ['filiacao.php', 'Filiacao'],
        ];
    }
}

if (!function_exists('anateje_public_nav_link')) {
    function anateje_public_nav_link(string $currentPage, string $file, string $label): string
    {
        $basePath = anateje_public_base_path();
        $active = $currentPage === $file ? ' aria-current="page"' : '';
        $href = $basePath . 'frontend/public/' . $file;
        return '<a class="px-nav__link" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $active . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }
}

if (!function_exists('anateje_public_render_start')) {
    function anateje_public_render_start(string $currentPage, string $title, string $description): void
    {
        $basePath = anateje_public_base_path();
        $fullTitle = $title . ' - ANATEJE';
        ?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" href="<?php echo $basePath; ?>assets/images/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@500;600;700;800&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/tokens.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/components-premium.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/sections-premium.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/public-pages.css">
    <script src="<?php echo $basePath; ?>assets/vendor/lucide/lucide.min.js"></script>
</head>
<body class="px-theme-shell pp-shell">
    <header class="px-navbar">
        <div class="px-container px-navbar__inner">
            <a class="px-brand" href="<?php echo $basePath; ?>frontend/public/home.php">
                <img class="px-brand__mark" src="<?php echo $basePath; ?>assets/images/logo.png" alt="Logo ANATEJE">
                <span class="px-brand__name">ANATEJE</span>
            </a>
            <nav class="px-nav" aria-label="Principal">
                <?php foreach (anateje_public_nav_items() as $item): ?>
                    <?php echo anateje_public_nav_link($currentPage, $item[0], $item[1]); ?>
                <?php endforeach; ?>
            </nav>
            <div class="px-actions">
                <button type="button" class="pp-mobile-toggle" id="pp-mobile-toggle" aria-label="Abrir menu">
                    <i data-lucide="menu"></i>
                </button>
                <a href="<?php echo $basePath; ?>frontend/auth/login.html" class="px-btn px-btn--outline">Entrar</a>
                <a href="<?php echo $basePath; ?>frontend/public/filiacao.php" class="px-btn px-btn--primary">Associe-se</a>
            </div>
            <nav class="pp-mobile-panel" id="pp-mobile-panel" aria-label="Principal movel">
                <?php foreach (anateje_public_nav_items() as $item): ?>
                    <a href="<?php echo $basePath; ?>frontend/public/<?php echo htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>
    <main>
        <?php
    }
}

if (!function_exists('anateje_public_render_end')) {
    function anateje_public_render_end(): void
    {
        $basePath = anateje_public_base_path();
        ?>
    </main>
    <footer class="sx-section sx-section--tight pp-footer">
        <div class="px-container">
            <hr class="px-divider">
            <div class="pp-footer__grid">
                <div>
                    <strong class="pp-footer__brand">ANATEJE</strong>
                    <div>Associacao Nacional dos Tecnicos do Judiciario Estadual</div>
                </div>
                <div>
                    <div>contato@anateje.org.br</div>
                    <div>Brasil</div>
                </div>
            </div>
        </div>
    </footer>
    <script>
        (function () {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }

            const toggle = document.getElementById('pp-mobile-toggle');
            const panel = document.getElementById('pp-mobile-panel');
            if (toggle && panel) {
                toggle.addEventListener('click', function () {
                    panel.classList.toggle('is-open');
                });

                panel.querySelectorAll('a').forEach(function (link) {
                    link.addEventListener('click', function () {
                        panel.classList.remove('is-open');
                    });
                });
            }
        })();
    </script>
</body>
</html>
        <?php
    }
}

