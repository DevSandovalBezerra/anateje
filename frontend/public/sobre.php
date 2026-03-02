<?php
require_once __DIR__ . '/_layout.php';
anateje_public_render_start(
    'sobre.php',
    'Sobre',
    'Conheca a ANATEJE, nossa atuacao institucional e compromisso com os tecnicos do Judiciario Estadual.'
);
?>
<section class="sx-section pp-hero">
    <div class="px-container">
        <span class="sx-kicker"><i data-lucide="shield-check"></i>Institucional</span>
        <h1 class="pp-hero__title">A ANATEJE representa tecnicos do Judiciario Estadual com foco em resultado coletivo.</h1>
        <p class="pp-hero__lead">
            Nossa atuacao combina representatividade, servicos ao associado e comunicacao ativa para fortalecer a categoria
            em pautas nacionais e regionais.
        </p>
    </div>
</section>

<section class="sx-section sx-section--compact">
    <div class="px-container pp-grid pp-grid--3">
        <article class="px-card px-card__body">
            <span class="px-badge px-badge--gold"><i data-lucide="target"></i>Missao</span>
            <p class="px-card__desc">Defender os interesses dos tecnicos do Judiciario Estadual com transparencia, unidade e estrategia.</p>
        </article>
        <article class="px-card px-card__body">
            <span class="px-badge px-badge--gold"><i data-lucide="eye"></i>Visao</span>
            <p class="px-card__desc">Ser referencia nacional em representatividade associativa para a carreira tecnica do Judiciario.</p>
        </article>
        <article class="px-card px-card__body">
            <span class="px-badge px-badge--gold"><i data-lucide="heart-handshake"></i>Valores</span>
            <p class="px-card__desc">Etica, compromisso institucional, colaboracao entre estados e foco no associado.</p>
        </article>
    </div>
</section>

<section class="sx-section sx-section--compact">
    <div class="px-container sx-split">
        <article class="px-card px-card__body">
            <h2 class="sx-header__title">Como atuamos</h2>
            <ul class="pp-list">
                <li>Incidencia institucional em temas de carreira e condicoes de trabalho.</li>
                <li>Comunicacao direta com associados por painel, comunicados e campanhas.</li>
                <li>Rede de beneficios para saude, qualificacao e economia no dia a dia.</li>
                <li>Eventos de capacitacao, integracao e debate tecnico-juridico.</li>
            </ul>
        </article>
        <figure class="sx-stage">
            <img src="../../assets/images/cena6.jpg" alt="Reuniao institucional da ANATEJE">
            <figcaption class="sx-stage__caption">Articulacao nacional para fortalecer a categoria tecnica.</figcaption>
        </figure>
    </div>
</section>

<?php anateje_public_render_end(); ?>

