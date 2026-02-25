<?php
$basePath = '../../';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANATEJE UI Kit - Premium Components</title>
    <meta name="description" content="Guia visual de componentes premium do portal ANATEJE.">
    <link rel="icon" href="<?php echo $basePath; ?>assets/images/favicon.ico" type="image/x-icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@500;600;700;800&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/tokens.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/components-premium.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/sections-premium.css">
    <script src="<?php echo $basePath; ?>assets/vendor/lucide/lucide.min.js"></script>
</head>
<body class="px-theme-shell">
    <header class="px-navbar">
        <div class="px-container px-navbar__inner">
            <a class="px-brand" href="#">
                <img class="px-brand__mark" src="<?php echo $basePath; ?>assets/images/logo.png" alt="Logo ANATEJE">
                <span class="px-brand__name">ANATEJE UI KIT</span>
            </a>
            <nav class="px-nav" aria-label="Styleguide">
                <a class="px-nav__link" aria-current="page" href="#buttons">Buttons</a>
                <a class="px-nav__link" href="#cards">Cards</a>
                <a class="px-nav__link" href="#sections">Sections</a>
                <a class="px-nav__link" href="#forms">Forms</a>
            </nav>
            <div class="px-actions">
                <a class="px-btn px-btn--ghost" href="<?php echo $basePath; ?>frontend/public/home.php">Home</a>
                <a class="px-btn px-btn--primary" href="<?php echo $basePath; ?>frontend/auth/login.html">Entrar</a>
            </div>
        </div>
    </header>

    <main>
        <section class="sx-section sx-hero">
            <div class="px-container sx-hero__grid">
                <div>
                    <span class="sx-kicker"><i data-lucide="sparkles"></i>Premium Component Library</span>
                    <h1 class="sx-hero__title">Componentes modulares para um portal elegante, moderno e consistente.</h1>
                    <p class="sx-hero__text">
                        Este styleguide concentra os padrões visuais do projeto: botões, cards, navegação e composições de seção.
                        A regra é construir telas por blocos reutilizáveis, não por estilos isolados.
                    </p>
                    <div class="sx-hero__actions">
                        <a class="px-btn px-btn--primary" href="#cards">Ver cards</a>
                        <a class="px-btn px-btn--outline" href="#sections">Ver seções</a>
                    </div>
                </div>
                <figure class="sx-stage">
                    <img src="<?php echo $basePath; ?>assets/images/cena2.jpg" alt="Composição visual do kit">
                    <figcaption class="sx-stage__caption">Linguagem institucional com direção visual editorial.</figcaption>
                </figure>
            </div>
        </section>

        <section class="sx-section sx-section--compact" id="buttons">
            <div class="px-container">
                <div class="sx-header">
                    <div>
                        <h2 class="sx-header__title">Buttons & Badges</h2>
                        <p class="sx-header__lead">Estados e variantes para ações primárias, secundárias e contextuais.</p>
                    </div>
                </div>
                <div class="px-card px-card--glass px-card__body">
                    <div class="sx-grid-2">
                        <div class="sx-stack">
                            <div class="px-actions">
                                <button class="px-btn px-btn--primary">Primary</button>
                                <button class="px-btn px-btn--outline">Outline</button>
                                <button class="px-btn px-btn--ghost">Ghost</button>
                                <button class="px-btn px-btn--danger">Danger</button>
                            </div>
                            <div class="px-actions">
                                <span class="px-badge">Default</span>
                                <span class="px-badge px-badge--gold">Gold Accent</span>
                                <span class="px-date-chip">15 MAR 2026</span>
                            </div>
                        </div>
                        <div class="px-card px-card--feature px-card__body">
                            <h3 class="px-card__title">Guideline</h3>
                            <p class="px-card__desc">Use `Primary` apenas para ação principal por bloco. Para ações secundárias, `Outline` ou `Ghost`.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sx-section sx-section--compact" id="cards">
            <div class="px-container">
                <div class="sx-header">
                    <div>
                        <h2 class="sx-header__title">Card Variants</h2>
                        <p class="sx-header__lead">Cartões para benefícios, eventos, conteúdos editoriais e destaques institucionais.</p>
                    </div>
                </div>
                <div class="sx-grid-3">
                    <article class="px-card">
                        <div class="px-card__media">
                            <img src="<?php echo $basePath; ?>assets/images/cena1.jpg" alt="Card padrão">
                        </div>
                        <div class="px-card__body">
                            <h3 class="px-card__title">Card Base</h3>
                            <p class="px-card__desc">Uso geral em listas de conteúdo, com mídia opcional no topo.</p>
                        </div>
                    </article>
                    <article class="px-card px-card--elevated">
                        <div class="px-card__media">
                            <img src="<?php echo $basePath; ?>assets/images/cena6.jpg" alt="Card elevado">
                        </div>
                        <div class="px-card__body">
                            <h3 class="px-card__title">Card Elevated</h3>
                            <p class="px-card__desc">Para blocos de alto destaque com hierarquia visual superior.</p>
                        </div>
                    </article>
                    <article class="px-card px-card--feature">
                        <div class="px-card__media">
                            <img src="<?php echo $basePath; ?>assets/images/curso.jpg" alt="Card feature">
                        </div>
                        <div class="px-card__body">
                            <h3 class="px-card__title">Card Feature</h3>
                            <p class="px-card__desc">Variação com camada de brilho contextual para storytelling de marca.</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="sx-section" id="sections">
            <div class="px-container">
                <div class="sx-header">
                    <div>
                        <h2 class="sx-header__title">Section Patterns</h2>
                        <p class="sx-header__lead">Composições prontas para páginas institucionais e de conversão.</p>
                    </div>
                </div>
                <div class="sx-split">
                    <div class="sx-stack">
                        <div class="px-card px-card__body">
                            <h3 class="px-card__title">Split Section</h3>
                            <p class="px-card__desc">Texto estruturado de um lado, mídia principal do outro. Ideal para "Sobre", "Missão" e blocos estratégicos.</p>
                        </div>
                        <div class="px-card px-card__body">
                            <h3 class="px-card__title">CTA Band</h3>
                            <p class="px-card__desc">Bloco horizontal de chamada para ação com headline + mídia + dupla de botões.</p>
                        </div>
                    </div>
                    <figure class="sx-stage">
                        <img src="<?php echo $basePath; ?>assets/images/cena7.jpg" alt="Section pattern">
                        <figcaption class="sx-stage__caption">Layout com contraste alto e foco em conversão.</figcaption>
                    </figure>
                </div>
                <div class="sx-band" style="margin-top: 0.95rem;">
                    <div class="sx-band__grid">
                        <div>
                            <h3 class="sx-band__title">Exemplo de CTA Premium</h3>
                            <p class="sx-band__text">Converta visitantes com uma narrativa objetiva e um bloco visual com credibilidade institucional.</p>
                            <div class="sx-hero__actions">
                                <a class="px-btn px-btn--primary" href="#">Quero me filiar</a>
                                <a class="px-btn px-btn--outline" href="#">Saiba mais</a>
                            </div>
                        </div>
                        <figure class="sx-band__media">
                            <img src="<?php echo $basePath; ?>assets/images/cena8.jpg" alt="Cta band media">
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        <section class="sx-section sx-section--compact" id="forms">
            <div class="px-container">
                <div class="sx-header">
                    <div>
                        <h2 class="sx-header__title">Form Controls</h2>
                        <p class="sx-header__lead">Inputs com foco em clareza, contraste e acessibilidade visual.</p>
                    </div>
                </div>
                <form class="px-card px-card__body sx-grid-2" action="#" method="post" onsubmit="return false;">
                    <label>
                        <span class="px-badge">Nome</span>
                        <input class="px-input" type="text" placeholder="Seu nome completo">
                    </label>
                    <label>
                        <span class="px-badge">Email funcional</span>
                        <input class="px-input" type="email" placeholder="nome@org.br">
                    </label>
                    <label>
                        <span class="px-badge">Categoria</span>
                        <select class="px-select">
                            <option>Parcial (0,5%)</option>
                            <option>Integral (1%)</option>
                        </select>
                    </label>
                    <label>
                        <span class="px-badge">Mensagem</span>
                        <textarea class="px-textarea" placeholder="Detalhe sua necessidade"></textarea>
                    </label>
                </form>
            </div>
        </section>
    </main>

    <script>
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    </script>
</body>
</html>
