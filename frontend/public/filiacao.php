<?php
require_once __DIR__ . '/_layout.php';
anateje_public_render_start(
    'filiacao.php',
    'Filiacao',
    'Pre-cadastro de filiacao ANATEJE para entrada na area de membros e ativacao do fluxo associativo.'
);
?>
<section class="sx-section pp-hero">
    <div class="px-container">
        <span class="sx-kicker"><i data-lucide="user-plus"></i>Quero me filiar</span>
        <h1 class="pp-hero__title">Inicie seu pre-cadastro e entre no fluxo de associacao.</h1>
        <p class="pp-hero__lead">
            Preencha seus dados iniciais para abertura do processo de filiacao. Depois voce podera completar o perfil e acompanhar tudo no painel.
        </p>
    </div>
</section>

<section class="sx-section sx-section--compact pp-filiacao-stage">
    <div class="px-container sx-split pp-filiacao-split">
        <article class="px-card px-card__body pp-filiacao-card pp-filiacao-card--form">
            <div class="pp-crud-head">
                <h2 class="pp-crud-title">Formulario de pre-cadastro</h2>
                <p class="pp-crud-subtitle">Preencha os campos para iniciar sua filiacao.</p>
            </div>
            <form id="filiacaoForm" class="pp-form pp-form--crud" novalidate>
                <section class="pp-crud-section">
                    <h4 class="pp-crud-section__title">Informacoes da Filiacao</h4>
                    <div class="pp-crud-grid">
                        <label class="pp-crud-field" for="filiacao_nome">
                            <span class="pp-crud-label">Nome completo <span class="pp-required">*</span></span>
                            <input id="filiacao_nome" class="px-input pp-crud-input" maxlength="150" autocomplete="name" required>
                        </label>
                        <label class="pp-crud-field" for="filiacao_email">
                            <span class="pp-crud-label">Email <span class="pp-required">*</span></span>
                            <input id="filiacao_email" type="email" class="px-input pp-crud-input" maxlength="190" autocomplete="email" required>
                        </label>
                        <label class="pp-crud-field" for="filiacao_telefone">
                            <span class="pp-crud-label">Telefone <span class="pp-required">*</span></span>
                            <input id="filiacao_telefone" class="px-input pp-crud-input" maxlength="20" autocomplete="tel" required>
                        </label>
                        <label class="pp-crud-field" for="filiacao_uf">
                            <span class="pp-crud-label">UF <span class="pp-required">*</span></span>
                            <input id="filiacao_uf" class="px-input pp-crud-input" maxlength="2" autocomplete="address-level1" required>
                        </label>
                        <label class="pp-crud-field" for="filiacao_categoria">
                            <span class="pp-crud-label">Categoria de interesse</span>
                            <select id="filiacao_categoria" class="px-select pp-crud-input">
                                <option value="PARCIAL">Parcial (0,5%)</option>
                                <option value="INTEGRAL">Integral (1%)</option>
                            </select>
                        </label>
                        <label class="pp-crud-field" for="filiacao_origem">
                            <span class="pp-crud-label">Como conheceu a ANATEJE?</span>
                            <input id="filiacao_origem" class="px-input pp-crud-input" maxlength="120" placeholder="Indicacao, evento, redes, etc.">
                        </label>
                        <label class="pp-crud-field pp-crud-field--full pp-consent">
                            <input id="filiacao_consentimento" type="checkbox" required>
                            Autorizo o contato da ANATEJE para continuidade da filiacao.
                        </label>
                    </div>
                </section>
                <div class="pp-form__actions">
                    <button type="submit" class="px-btn px-btn--primary">Enviar pre-cadastro</button>
                    <a href="../../frontend/auth/login.html" class="px-btn px-btn--outline">Ja tenho acesso</a>
                </div>
                <p id="filiacaoMsg" class="pp-form__msg"></p>
            </form>
        </article>
        <aside class="px-card px-card__body pp-filiacao-card pp-filiacao-card--help">
            <h2 class="sx-header__title">Proximo passo</h2>
            <ul class="pp-list">
                <li>Validacao inicial do pre-cadastro pela equipe ANATEJE.</li>
                <li>Acesso ao login para completar dados de perfil.</li>
                <li>Acompanhamento de beneficios, eventos e comunicados no painel.</li>
            </ul>
            <p class="pp-muted">Para duvidas, use tambem o canal de contato publico.</p>
            <div class="sx-hero__actions">
                <a href="../../frontend/public/contato.php" class="px-btn px-btn--ghost">Falar com atendimento</a>
            </div>
        </aside>
    </div>
</section>

<script>
    (function () {
        const form = document.getElementById('filiacaoForm');
        const msg = document.getElementById('filiacaoMsg');
        const uf = document.getElementById('filiacao_uf');

        function setMessage(text, type) {
            msg.textContent = text || '';
            msg.className = 'pp-form__msg ' + (type === 'ok' ? 'pp-form__msg--ok' : 'pp-form__msg--err');
        }

        function getBasePath() {
            const pathname = window.location.pathname || '';
            const idx = pathname.indexOf('/frontend/');
            if (idx <= 0) return '';
            return pathname.substring(0, idx);
        }

        uf.addEventListener('input', function (event) {
            event.target.value = (event.target.value || '').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 2);
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            setMessage('', 'ok');

            const consent = document.getElementById('filiacao_consentimento').checked;
            const payload = {
                nome: document.getElementById('filiacao_nome').value.trim(),
                email: document.getElementById('filiacao_email').value.trim(),
                telefone: document.getElementById('filiacao_telefone').value.trim(),
                uf: document.getElementById('filiacao_uf').value.trim().toUpperCase(),
                categoria: document.getElementById('filiacao_categoria').value,
                origem: document.getElementById('filiacao_origem').value.trim(),
                consentimento: consent ? 1 : 0
            };

            if (!payload.nome || !payload.email || !payload.telefone || payload.uf.length !== 2) {
                setMessage('Preencha nome, email, telefone e UF validos.', 'err');
                return;
            }

            if (!consent) {
                setMessage('Voce precisa autorizar o contato para continuar.', 'err');
                return;
            }

            const base = getBasePath();
            const url = (base ? base : '') + '/api/v1/public.php?action=lead_capture';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await response.json().catch(function () { return null; });
                if (!response.ok || !json || json.ok !== true) {
                    throw new Error((json && json.error && json.error.message) || 'Falha ao enviar pre-cadastro');
                }

                form.reset();
                setMessage('Pre-cadastro enviado. Nossa equipe entrara em contato.', 'ok');
            } catch (err) {
                setMessage(err.message || 'Falha ao enviar pre-cadastro.', 'err');
            }
        });
    })();
</script>

<?php anateje_public_render_end(); ?>
