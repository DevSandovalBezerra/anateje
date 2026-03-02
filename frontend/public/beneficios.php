<?php
require_once __DIR__ . '/_layout.php';
anateje_public_render_start(
    'beneficios.php',
    'Beneficios',
    'Catalogo de beneficios da ANATEJE para associados: juridico, saude, educacao e convenios.'
);

$benefits = [
    ['scale', 'Assessoria Juridica', 'Suporte tecnico-juridico em pautas da categoria e orientacoes institucionais.'],
    ['stethoscope', 'Telemedicina Byteclin', 'Atendimento medico remoto com acesso rapido e cobertura nacional.'],
    ['heart-pulse', 'Ambulatej', 'Rede de parceiros para consultas, exames e servicos de saude.'],
    ['graduation-cap', 'Mestrado Cesara', 'Acesso a condicoes especiais em formacao avancada.'],
    ['badge-percent', 'Byte Club Descontos', 'Rede de descontos para servicos e consumo cotidiano.'],
    ['dumbbell', 'Wellhub / Gympass', 'Beneficios para atividade fisica, bem-estar e qualidade de vida.'],
    ['building-2', 'Instituto ITES', 'Parcerias para desenvolvimento profissional e educacao continuada.'],
    ['smartphone', 'TIM Telefonia', 'Planos de telefonia com condicoes especiais para associados.'],
];
?>
<section class="sx-section pp-hero">
    <div class="px-container">
        <span class="sx-kicker"><i data-lucide="gift"></i>Beneficios do associado</span>
        <h1 class="pp-hero__title">Vantagens concretas para apoiar sua carreira e rotina.</h1>
        <p class="pp-hero__lead">
            O associado ANATEJE acessa beneficios em saude, qualificacao, suporte juridico e convenios com condicoes diferenciadas.
        </p>
    </div>
</section>

<section class="sx-section sx-section--compact">
    <div class="px-container pp-grid pp-grid--2">
        <?php foreach ($benefits as $item): ?>
            <article class="px-card px-card__body">
                <span class="px-badge px-badge--gold"><i data-lucide="<?php echo htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8'); ?>"></i><?php echo htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8'); ?></span>
                <p class="px-card__desc"><?php echo htmlspecialchars($item[2], ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="sx-section sx-section--compact">
    <div class="px-container">
        <div class="sx-band">
            <div class="sx-band__grid">
                <div>
                    <h2 class="sx-band__title">Quer ativar seus beneficios?</h2>
                    <p class="sx-band__text">Complete sua filiacao e acesse o painel para acompanhar status e servicos disponiveis.</p>
                    <div class="sx-hero__actions">
                        <a href="../../frontend/public/filiacao.php" class="px-btn px-btn--primary">Quero me filiar</a>
                        <a href="../../frontend/auth/login.html" class="px-btn px-btn--outline">Ja sou associado</a>
                    </div>
                </div>
                <figure class="sx-band__media">
                    <img src="../../assets/images/cena7.jpg" alt="Associado com acesso a beneficios">
                </figure>
            </div>
        </div>
    </div>
</section>

<?php anateje_public_render_end(); ?>

