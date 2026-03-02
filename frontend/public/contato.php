<?php
require_once __DIR__ . '/_layout.php';
anateje_public_render_start(
    'contato.php',
    'Contato',
    'Canal de contato da ANATEJE para duvidas institucionais, suporte e orientacoes.'
);
?>
<section class="sx-section pp-hero">
    <div class="px-container">
        <span class="sx-kicker"><i data-lucide="mail"></i>Fale com a ANATEJE</span>
        <h1 class="pp-hero__title">Envie sua mensagem para atendimento institucional.</h1>
        <p class="pp-hero__lead">
            Nosso time recebe demandas de filiacao, suporte ao associado, orientacoes sobre beneficios e informacoes gerais.
        </p>
    </div>
</section>

<section class="sx-section sx-section--compact">
    <div class="px-container sx-split">
        <article class="px-card px-card__body">
            <h2 class="sx-header__title">Canal direto</h2>
            <p class="px-card__desc">Preencha o formulario e retornaremos pelo melhor contato informado.</p>
            <form id="contatoForm" class="pp-form" novalidate>
                <div class="pp-form__grid">
                    <div class="pp-form__group">
                        <label for="contato_nome">Nome</label>
                        <input id="contato_nome" class="px-input" maxlength="140" required>
                    </div>
                    <div class="pp-form__group">
                        <label for="contato_email">Email</label>
                        <input id="contato_email" type="email" class="px-input" maxlength="180" required>
                    </div>
                    <div class="pp-form__group">
                        <label for="contato_telefone">Telefone</label>
                        <input id="contato_telefone" class="px-input" maxlength="20">
                    </div>
                    <div class="pp-form__group">
                        <label for="contato_assunto">Assunto</label>
                        <input id="contato_assunto" class="px-input" maxlength="160" required>
                    </div>
                    <div class="pp-form__group pp-form__group--full">
                        <label for="contato_mensagem">Mensagem</label>
                        <textarea id="contato_mensagem" class="px-textarea" maxlength="2000" required></textarea>
                    </div>
                </div>
                <div class="pp-form__actions">
                    <button type="submit" class="px-btn px-btn--primary">Enviar mensagem</button>
                </div>
                <p id="contatoMsg" class="pp-form__msg"></p>
            </form>
        </article>
        <aside class="px-card px-card__body">
            <h2 class="sx-header__title">Informacoes gerais</h2>
            <ul class="pp-list">
                <li>Email institucional: contato@anateje.org.br</li>
                <li>Atendimento em dias uteis, horario comercial.</li>
                <li>Demandas de associado tambem podem ser abertas pelo painel logado.</li>
            </ul>
            <div class="sx-hero__actions">
                <a href="../../frontend/auth/login.html" class="px-btn px-btn--outline">Entrar na area do associado</a>
            </div>
        </aside>
    </div>
</section>

<script>
    (function () {
        const form = document.getElementById('contatoForm');
        const msg = document.getElementById('contatoMsg');

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

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            setMessage('', 'ok');

            const payload = {
                nome: document.getElementById('contato_nome').value.trim(),
                email: document.getElementById('contato_email').value.trim(),
                telefone: document.getElementById('contato_telefone').value.trim(),
                assunto: document.getElementById('contato_assunto').value.trim(),
                mensagem: document.getElementById('contato_mensagem').value.trim()
            };

            if (!payload.nome || !payload.email || !payload.assunto || !payload.mensagem) {
                setMessage('Preencha os campos obrigatorios.', 'err');
                return;
            }

            const base = getBasePath();
            const url = (base ? base : '') + '/api/v1/public.php?action=contact';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await response.json().catch(function () { return null; });
                if (!response.ok || !json || json.ok !== true) {
                    throw new Error((json && json.error && json.error.message) || 'Falha ao enviar mensagem');
                }

                form.reset();
                setMessage('Mensagem enviada com sucesso. Retornaremos em breve.', 'ok');
            } catch (err) {
                setMessage(err.message || 'Falha ao enviar mensagem.', 'err');
            }
        });
    })();
</script>

<?php anateje_public_render_end(); ?>

