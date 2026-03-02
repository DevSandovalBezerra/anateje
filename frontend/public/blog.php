<?php
require_once __DIR__ . '/_layout.php';
anateje_public_render_start(
    'blog.php',
    'Blog e Noticias',
    'Noticias institucionais, analises e comunicados publicos da ANATEJE.'
);

$posts = [
    ['Comunicado institucional', 'Atualizacao de pautas nacionais da carreira tecnica para o primeiro semestre de 2026.', '12 FEV 2026'],
    ['Guia rapido', 'Como organizar documentos e dados para atualizar o perfil de associado no portal.', '29 JAN 2026'],
    ['Boletim de eventos', 'Calendario preliminar de encontros, webinars e foruns da ANATEJE.', '15 JAN 2026'],
    ['Analise juridica', 'Pontos de atencao em alteracoes normativas com impacto em tecnicos do Judiciario.', '04 JAN 2026'],
];
?>
<section class="sx-section pp-hero">
    <div class="px-container">
        <span class="sx-kicker"><i data-lucide="newspaper"></i>Conteudo institucional</span>
        <h1 class="pp-hero__title">Noticias, orientacoes e contexto sobre as pautas da categoria.</h1>
        <p class="pp-hero__lead">
            Esta area consolida informacoes publicas da ANATEJE. Comunicados exclusivos para associados seguem no painel logado.
        </p>
    </div>
</section>

<section class="sx-section sx-section--compact">
    <div class="px-container pp-grid pp-grid--2">
        <?php foreach ($posts as $post): ?>
            <article class="px-card px-card__body">
                <span class="px-date-chip"><?php echo htmlspecialchars($post[2], ENT_QUOTES, 'UTF-8'); ?></span>
                <h2 class="px-card__title"><?php echo htmlspecialchars($post[0], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="px-card__desc"><?php echo htmlspecialchars($post[1], ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="pp-card-note">Publicacao demonstrativa para o MVP. O modulo editorial completo segue em evolucao.</p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="sx-section sx-section--compact">
    <div class="px-container">
        <article class="px-card px-card__body">
            <h2 class="sx-header__title">Quer receber comunicados no painel?</h2>
            <p class="px-card__desc">Associe-se e acompanhe comunicados segmentados por status, categoria e beneficios ativos.</p>
            <div class="sx-hero__actions">
                <a href="../../frontend/public/filiacao.php" class="px-btn px-btn--primary">Fazer filiacao</a>
                <a href="../../frontend/auth/login.html" class="px-btn px-btn--outline">Acessar area do associado</a>
            </div>
        </article>
    </div>
</section>

<?php anateje_public_render_end(); ?>

