<?php
$basePath = '../../';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANATEJE - Associacao Nacional dos Tecnicos do Judiciario Estadual</title>
    <meta name="description" content="Portal institucional da ANATEJE: representatividade, beneficios, eventos e area de membros para filiacao e acompanhamento.">
    <link rel="icon" href="<?php echo $basePath; ?>assets/images/favicon.ico" type="image/x-icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@500;600;700;800&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/tokens.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/components-premium.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/sections-premium.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/home-premium.css">
    <script src="<?php echo $basePath; ?>assets/vendor/lucide/lucide.min.js"></script>
</head>
<body class="px-theme-shell home-full">
    <header class="px-navbar">
        <div class="px-container px-navbar__inner">
            <a class="px-brand" href="#topo">
                <img class="px-brand__mark" src="<?php echo $basePath; ?>assets/images/logo.png" alt="Logo ANATEJE">
                <span class="px-brand__name">ANATEJE</span>
            </a>

            <nav class="px-nav" aria-label="Principal">
                <a class="px-nav__link" href="<?php echo $basePath; ?>frontend/public/sobre.php">Sobre</a>
                <a class="px-nav__link" href="<?php echo $basePath; ?>frontend/public/beneficios.php">Beneficios</a>
                <a class="px-nav__link" href="<?php echo $basePath; ?>frontend/public/eventos.php">Eventos</a>
                <a class="px-nav__link" href="<?php echo $basePath; ?>frontend/public/blog.php">Blog</a>
                <a class="px-nav__link" href="<?php echo $basePath; ?>frontend/public/contato.php">Contato</a>
            </nav>

            <div class="px-actions">
                <button type="button" class="home-mobile-toggle" id="home-mobile-toggle" aria-label="Abrir menu">
                    <i data-lucide="menu"></i>
                </button>
                <a href="<?php echo $basePath; ?>frontend/auth/login.html" class="px-btn px-btn--outline">Entrar</a>
                <a href="<?php echo $basePath; ?>frontend/public/filiacao.php" class="px-btn px-btn--primary">Associe-se</a>
            </div>

            <nav class="home-mobile-panel" id="home-mobile-panel" aria-label="Principal móvel">
                <a href="<?php echo $basePath; ?>frontend/public/sobre.php">Sobre</a>
                <a href="<?php echo $basePath; ?>frontend/public/beneficios.php">Beneficios</a>
                <a href="<?php echo $basePath; ?>frontend/public/eventos.php">Eventos</a>
                <a href="<?php echo $basePath; ?>frontend/public/blog.php">Blog</a>
                <a href="<?php echo $basePath; ?>frontend/public/contato.php">Contato</a>
            </nav>
        </div>
    </header>

    <main id="topo">
        <section class="sx-section sx-hero">
            <span class="sx-orb sx-orb--left"></span>
            <span class="sx-orb sx-orb--right"></span>
            <div class="px-container sx-hero__grid">
                <div>
                    <span class="sx-kicker">
                        <i data-lucide="shield-check"></i>
                        Representatividade nacional
                    </span>
                    <h1 class="sx-hero__title home-marquee" data-marquee>Fortalecendo os tecnicos do Judiciario Estadual em todo o Brasil.</h1>
                    <p class="sx-hero__text">
                        A ANATEJE conecta representatividade, beneficios reais e comunicacao ativa para apoiar o servidor
                        tecnico em sua carreira e no dia a dia.
                    </p>
                    <div class="sx-hero__actions">
                        <a href="<?php echo $basePath; ?>frontend/public/filiacao.php" class="px-btn px-btn--primary">Quero me filiar</a>
                        <a href="<?php echo $basePath; ?>frontend/public/beneficios.php" class="px-btn px-btn--outline">Ver beneficios</a>
                    </div>
                    <div class="home-metrics">
                        <article class="px-card px-card__body">
                            <strong>08</strong>
                            <span>beneficios iniciais</span>
                        </article>
                        <article class="px-card px-card__body">
                            <strong>03</strong>
                            <span>eventos em destaque</span>
                        </article>
                        <article class="px-card px-card__body">
                            <strong>01</strong>
                            <span>area do associado</span>
                        </article>
                    </div>
                </div>
                <figure class="sx-stage">
                    <img src="<?php echo $basePath; ?>assets/images/cena2.jpg" alt="Equipe institucional da associacao">
                    <figcaption class="sx-stage__caption">Uniao institucional com foco no associado.</figcaption>
                </figure>
            </div>
        </section>

        <section class="sx-section" id="sobre">
            <div class="px-container sx-split">
                <div>
                    <div class="sx-header">
                        <div>
                            <h2 class="sx-header__title">Por que ANATEJE</h2>
                            <p class="sx-header__lead">
                                Atuamos para valorizar o servidor tecnico do poder judiciario estadual, com foco em representatividade,
                                servicos e fortalecimento institucional da categoria.
                            </p>
                        </div>
                    </div>
                    <div class="sx-grid-3">
                        <article class="px-card px-card__body">
                            <span class="px-badge px-badge--gold"><i data-lucide="users"></i>Representatividade</span>
                            <p class="px-card__desc">Defesa organizada dos interesses da categoria em pauta nacional.</p>
                        </article>
                        <article class="px-card px-card__body">
                            <span class="px-badge px-badge--gold"><i data-lucide="handshake"></i>Rede de apoio</span>
                            <p class="px-card__desc">Parcerias e servicos que reduzem custos e ampliam oportunidades.</p>
                        </article>
                        <article class="px-card px-card__body">
                            <span class="px-badge px-badge--gold"><i data-lucide="megaphone"></i>Comunicacao ativa</span>
                            <p class="px-card__desc">Informacao clara para acompanhar noticias, campanhas e eventos.</p>
                        </article>
                    </div>
                </div>
                <figure class="sx-stage">
                    <img src="<?php echo $basePath; ?>assets/images/cena6.jpg" alt="Reuniao institucional em plenaria">
                    <figcaption class="sx-stage__caption">Atuacao coletiva com foco em resultado institucional.</figcaption>
                </figure>
            </div>
        </section>

        <section class="sx-section" id="beneficios">
            <div class="px-container">
                <div class="sx-header">
                    <div>
                        <h2 class="sx-header__title">Beneficios em destaque</h2>
                        <p class="sx-header__lead">Modelo editorial: menos foto repetida, mais foco em informacao e valor percebido.</p>
                    </div>
                </div>

                <div class="sx-split">
                    <figure class="sx-stage">
                        <img src="<?php echo $basePath; ?>assets/images/cena1.jpg" alt="Atendimento institucional aos associados">
                        <figcaption class="sx-stage__caption">Beneficios reais para fortalecer o tecnico do Judiciario Estadual.</figcaption>
                    </figure>
                    <div class="home-benefits-list">
                        <article class="px-card px-card__body home-benefit-item"><i data-lucide="scale"></i><div><h3>Assessoria Juridica</h3><p>Suporte tecnico-juridico para demandas da categoria.</p></div></article>
                        <article class="px-card px-card__body home-benefit-item"><i data-lucide="stethoscope"></i><div><h3>Telemedicina Byteclin</h3><p>Acesso agil a atendimento medico remoto.</p></div></article>
                        <article class="px-card px-card__body home-benefit-item"><i data-lucide="heart-pulse"></i><div><h3>Ambulatej</h3><p>Rede de atendimento com parceiros locais.</p></div></article>
                        <article class="px-card px-card__body home-benefit-item"><i data-lucide="graduation-cap"></i><div><h3>Mestrado Cesara</h3><p>Oportunidade de formacao avancada em Direito.</p></div></article>
                        <article class="px-card px-card__body home-benefit-item"><i data-lucide="badge-percent"></i><div><h3>Byte Club Descontos</h3><p>Rede credenciada de servicos e consumo.</p></div></article>
                        <article class="px-card px-card__body home-benefit-item"><i data-lucide="dumbbell"></i><div><h3>Wellhub/Gympass</h3><p>Descontos para atividades fisicas e bem-estar.</p></div></article>
                        <article class="px-card px-card__body home-benefit-item"><i data-lucide="building-2"></i><div><h3>Instituto ITES</h3><p>Parcerias para capacitacao e desenvolvimento.</p></div></article>
                        <article class="px-card px-card__body home-benefit-item"><i data-lucide="smartphone"></i><div><h3>TIM Telefonia</h3><p>Condicoes diferenciadas de plano para associados.</p></div></article>
                    </div>
                </div>
            </div>
        </section>

        <section class="sx-section" id="eventos">
            <div class="px-container">
                <div class="sx-header">
                    <div>
                        <h2 class="sx-header__title">Proximos eventos</h2>
                        <p class="sx-header__lead">Agenda institucional com encontros, oficinas e atualizacoes da associacao.</p>
                    </div>
                </div>
                <div class="home-carousel" id="eventos-carousel" aria-label="Carrossel de eventos">
                    <div class="home-carousel__viewport">
                        <div class="home-carousel__track">
                            <article class="px-card home-carousel__slide">
                                <div class="px-card__media"><img src="<?php echo $basePath; ?>assets/images/cena5.jpg" alt="Encontro nacional"></div>
                                <div class="px-card__body">
                                    <span class="px-date-chip">15 MAR 2026</span>
                                    <h3 class="px-card__title">Encontro Nacional de Tecnicos</h3>
                                    <p class="px-card__desc">Painel sobre carreira, desafios e estrategias coletivas da categoria.</p>
                                </div>
                            </article>
                            <article class="px-card home-carousel__slide">
                                <div class="px-card__media"><img src="<?php echo $basePath; ?>assets/images/curso.jpg" alt="Webinar de beneficios"></div>
                                <div class="px-card__body">
                                    <span class="px-date-chip">02 ABR 2026</span>
                                    <h3 class="px-card__title">Webinar Beneficios Ativos</h3>
                                    <p class="px-card__desc">Apresentacao dos beneficios e fluxo de ativacao na area do associado.</p>
                                </div>
                            </article>
                            <article class="px-card home-carousel__slide">
                                <div class="px-card__media"><img src="<?php echo $basePath; ?>assets/images/cena6.jpg" alt="Forum de comunicacao"></div>
                                <div class="px-card__body">
                                    <span class="px-date-chip">28 ABR 2026</span>
                                    <h3 class="px-card__title">Forum de Comunicacao Sindical</h3>
                                    <p class="px-card__desc">Boas praticas de comunicacao e mobilizacao para entidades representativas.</p>
                                </div>
                            </article>
                        </div>
                    </div>
                    <div class="home-carousel__controls" aria-label="Controles do carrossel">
                        <button type="button" class="home-carousel__btn home-carousel__btn--prev" data-carousel-prev aria-label="Evento anterior">
                            <i data-lucide="chevron-left"></i>
                        </button>
                        <div class="home-carousel__dots" data-carousel-dots aria-label="Indicadores de slide"></div>
                        <button type="button" class="home-carousel__btn home-carousel__btn--next" data-carousel-next aria-label="Proximo evento">
                            <i data-lucide="chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="sx-band" id="filiacao" style="margin-top: 0.95rem;">
                    <div class="sx-band__grid">
                        <div>
                            <h3 class="sx-band__title">Pronto para se associar?</h3>
                            <p class="sx-band__text">Complete sua filiacao e acompanhe beneficios, eventos e comunicados em um unico painel.</p>
                            <div class="sx-hero__actions">
                                <a href="<?php echo $basePath; ?>frontend/public/filiacao.php" class="px-btn px-btn--primary">Iniciar filiacao</a>
                                <a href="<?php echo $basePath; ?>frontend/auth/login.html" class="px-btn px-btn--outline">Ja sou associado</a>
                            </div>
                        </div>
                        <figure class="sx-band__media">
                            <img src="<?php echo $basePath; ?>assets/images/cena7.jpg" alt="Integracao e associacao">
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        <section class="sx-section sx-section--compact" id="faq">
            <div class="px-container sx-split">
                <div>
                    <div class="sx-header">
                        <div>
                            <h2 class="sx-header__title">Perguntas frequentes</h2>
                        </div>
                    </div>
                    <div class="home-faq">
                        <details>
                            <summary>Quais documentos sao necessarios para filiacao?</summary>
                            <p>Dados pessoais, CPF, matricula institucional e informacoes funcionais para validacao cadastral.</p>
                        </details>
                        <details>
                            <summary>Como funciona a contribuicao mensal?</summary>
                            <p>O associado escolhe entre categoria Parcial (0,5%) e Integral (1%), conforme regras da associacao.</p>
                        </details>
                        <details>
                            <summary>Onde acompanho eventos e comunicados?</summary>
                            <p>Na area de membros, com painel dedicado para comunicados, agenda e inscricoes em eventos.</p>
                        </details>
                    </div>
                </div>
                <aside class="px-card px-card--glass px-card__body home-faq-aside">
                    <img src="<?php echo $basePath; ?>assets/images/logo_branco.jpg" alt="Identidade visual ANATEJE">
                    <h3 class="px-card__title">Atendimento humano com suporte digital</h3>
                    <p class="px-card__desc">O associado acompanha status, eventos, beneficios e comunicados em um fluxo simples e direto.</p>
                </aside>
            </div>
        </section>
    </main>

    <footer class="sx-section sx-section--tight" id="contato">
        <div class="px-container">
            <hr class="px-divider">
            <div class="home-footer">
                <div>
                    <strong class="home-footer-brand">ANATEJE</strong>
                    <div>Associacao Nacional dos Tecnicos do Judiciario Estadual</div>
                </div>
                <div>contato@anateje.org.br</div>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            document.body.classList.add('is-enhanced');

            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }

            const toggle = document.getElementById('home-mobile-toggle');
            const panel = document.getElementById('home-mobile-panel');
            if (toggle && panel) {
                toggle.addEventListener('click', function () {
                    panel.classList.toggle('is-open');
                });

                panel.querySelectorAll('a').forEach((link) => {
                    link.addEventListener('click', function () {
                        panel.classList.remove('is-open');
                    });
                });
            }

            const marquee = document.querySelector('[data-marquee]');
            if (marquee && !prefersReducedMotion) {
                const sourceText = marquee.textContent.trim();
                const words = sourceText.split(' ').filter(Boolean);
                marquee.textContent = '';
                marquee.setAttribute('aria-label', sourceText);
                marquee.setAttribute('role', 'text');

                let charIndex = 0;
                words.forEach(function (word, wordIndex) {
                    const wordElement = document.createElement('span');
                    wordElement.className = 'home-marquee__word';
                    wordElement.setAttribute('aria-hidden', 'true');

                    Array.from(word).forEach(function (char) {
                        const charElement = document.createElement('span');
                        charElement.className = 'home-marquee__char';
                        charElement.textContent = char;
                        charElement.setAttribute('aria-hidden', 'true');
                        charElement.style.setProperty('--char-index', charIndex);
                        wordElement.appendChild(charElement);
                        charIndex += 1;
                    });

                    marquee.appendChild(wordElement);
                    if (wordIndex !== words.length - 1) {
                        const space = document.createTextNode(' ');
                        marquee.appendChild(space);
                    }
                });

                window.requestAnimationFrame(function () {
                    marquee.classList.add('is-mounted');
                });
            }

            const revealTargets = Array.from(document.querySelectorAll(
                'main .sx-header, main .sx-stage, main .px-card, main .home-benefit-item, main .sx-band, main .home-faq details'
            ));

            revealTargets.forEach(function (target, index) {
                target.classList.add('home-reveal');
                target.style.setProperty('--reveal-delay', ((index % 7) * 120) + 'ms');
            });

            if (!prefersReducedMotion && 'IntersectionObserver' in window) {
                const revealObserver = new IntersectionObserver(function (entries, observer) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) {
                            return;
                        }
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    });
                }, { threshold: 0.14, rootMargin: '0px 0px -8% 0px' });

                revealTargets.forEach(function (target) {
                    revealObserver.observe(target);
                });
            } else {
                revealTargets.forEach(function (target) {
                    target.classList.add('is-visible');
                });
            }

            const carousel = document.getElementById('eventos-carousel');
            if (!carousel) {
                return;
            }

            const track = carousel.querySelector('.home-carousel__track');
            const slides = Array.from(carousel.querySelectorAll('.home-carousel__slide'));
            const dotsContainer = carousel.querySelector('[data-carousel-dots]');
            const prevButton = carousel.querySelector('[data-carousel-prev]');
            const nextButton = carousel.querySelector('[data-carousel-next]');
            if (!track || slides.length === 0) {
                return;
            }

            let currentIndex = 0;
            let timerId = null;
            const dots = [];

            function updateCarousel(nextIndex) {
                currentIndex = (nextIndex + slides.length) % slides.length;
                track.style.transform = 'translate3d(-' + (currentIndex * 100) + '%, 0, 0)';

                slides.forEach(function (slide, index) {
                    slide.classList.toggle('is-active', index === currentIndex);
                });

                dots.forEach(function (dot, index) {
                    const isCurrent = index === currentIndex;
                    dot.classList.toggle('is-active', isCurrent);
                    dot.setAttribute('aria-current', isCurrent ? 'true' : 'false');
                });
            }

            function stopAutoPlay() {
                if (!timerId) {
                    return;
                }
                clearInterval(timerId);
                timerId = null;
            }

            function startAutoPlay() {
                if (prefersReducedMotion || slides.length < 2) {
                    return;
                }
                stopAutoPlay();
                timerId = setInterval(function () {
                    updateCarousel(currentIndex + 1);
                }, 5000);
            }

            if (dotsContainer) {
                slides.forEach(function (slide, index) {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = 'home-carousel__dot';
                    dot.setAttribute('aria-label', 'Ir para evento ' + (index + 1));
                    dot.addEventListener('click', function () {
                        updateCarousel(index);
                        startAutoPlay();
                    });
                    dotsContainer.appendChild(dot);
                    dots.push(dot);
                });
            }

            if (prevButton) {
                prevButton.addEventListener('click', function () {
                    updateCarousel(currentIndex - 1);
                    startAutoPlay();
                });
            }

            if (nextButton) {
                nextButton.addEventListener('click', function () {
                    updateCarousel(currentIndex + 1);
                    startAutoPlay();
                });
            }

            carousel.addEventListener('mouseenter', stopAutoPlay);
            carousel.addEventListener('mouseleave', startAutoPlay);
            carousel.addEventListener('focusin', stopAutoPlay);
            carousel.addEventListener('focusout', startAutoPlay);
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    stopAutoPlay();
                    return;
                }
                startAutoPlay();
            });

            updateCarousel(0);
            startAutoPlay();
        })();
    </script>
</body>
</html>
