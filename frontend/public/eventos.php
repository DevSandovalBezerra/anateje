<?php
require_once __DIR__ . '/_layout.php';
anateje_public_render_start(
    'eventos.php',
    'Eventos',
    'Agenda de eventos da ANATEJE com encontros tecnicos, webinars e foruns institucionais.'
);

$events = [
    ['15 MAR 2026', 'Encontro Nacional de Tecnicos', 'Painel sobre carreira, valorizacao profissional e estrategias coletivas.', 'cena5.jpg'],
    ['02 ABR 2026', 'Webinar Beneficios Ativos', 'Orientacoes praticas sobre ativacao de beneficios e uso do painel do associado.', 'curso.jpg'],
    ['28 ABR 2026', 'Forum de Comunicacao Sindical', 'Boas praticas de comunicacao institucional e mobilizacao da categoria.', 'cena6.jpg'],
    ['13 MAI 2026', 'Oficina de Gestao Associativa', 'Metodos para organizar pautas, dados e fluxo administrativo das entidades.', 'cena4.jpg'],
];
?>
<section class="sx-section pp-hero">
    <div class="px-container">
        <span class="sx-kicker"><i data-lucide="calendar"></i>Agenda ANATEJE</span>
        <h1 class="pp-hero__title">Eventos para conectar, capacitar e fortalecer a categoria.</h1>
        <p class="pp-hero__lead">
            Acompanhe encontros presenciais e online com foco em carreira, representatividade e operacao associativa.
        </p>
    </div>
</section>

<section class="sx-section sx-section--compact">
    <div class="px-container pp-grid pp-grid--2">
        <?php foreach ($events as $event): ?>
            <article class="px-card">
                <div class="px-card__media">
                    <img src="../../assets/images/<?php echo htmlspecialchars($event[3], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($event[1], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="px-card__body">
                    <span class="px-date-chip"><?php echo htmlspecialchars($event[0], ENT_QUOTES, 'UTF-8'); ?></span>
                    <h2 class="px-card__title"><?php echo htmlspecialchars($event[1], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="px-card__desc"><?php echo htmlspecialchars($event[2], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="pp-card-note">Inscricoes disponiveis para associados logados.</p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="sx-section sx-section--compact">
    <div class="px-container">
        <article class="px-card px-card__body">
            <h2 class="sx-header__title">Como participar</h2>
            <ul class="pp-list">
                <li>Faca login na area do associado.</li>
                <li>Acesse o modulo "Meus eventos".</li>
                <li>Escolha o evento e confirme inscricao.</li>
                <li>Acompanhe atualizacoes e eventuais alteracoes de agenda no painel.</li>
            </ul>
            <div class="sx-hero__actions">
                <a href="../../frontend/auth/login.html" class="px-btn px-btn--primary">Entrar para se inscrever</a>
                <a href="../../frontend/public/filiacao.php" class="px-btn px-btn--outline">Ainda nao sou associado</a>
            </div>
        </article>
    </div>
</section>

<?php anateje_public_render_end(); ?>

