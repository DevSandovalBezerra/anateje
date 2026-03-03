<?php
// Dashboard do Associado

if (!defined('TEMPLATE_ROUTED')) {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../includes/dashboard_components.php';

$basePrefix = isset($prefix) ? $prefix : '/';
dashboard_components_styles();
?>
<div id="dashboard-user" class="dashboard-content p-6 space-y-6">
    <section class="dash-surface p-6">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold dash-title">Dashboard do Associado</h1>
                <p class="dash-muted">Visao geral da sua filiacao, beneficios, eventos e comunicados.</p>
            </div>
            <div class="text-sm dash-muted">Usuario: <?php echo htmlspecialchars($user['nome'] ?? ''); ?></div>
        </div>

        <div id="dashboardUserAlert" class="hidden mb-5 p-3 rounded border text-sm" role="status" aria-live="polite"></div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <?php dashboard_kpi_card('Status da filiacao', 'kpiStatus', 'kpiCategoria', 'Categoria: --', '--'); ?>
            <?php dashboard_kpi_card('Beneficios ativos', 'kpiBeneficios', 'kpiBeneficiosMeta', 'Vinculados ao seu cadastro'); ?>
            <?php dashboard_kpi_card('Eventos em que voce esta inscrito', 'kpiEventosInscritos', 'kpiEventosInscritosMeta', 'Apenas eventos futuros'); ?>
            <?php dashboard_kpi_card('Comunicados recentes', 'kpiComunicados', 'kpiComunicadosMeta', 'Ultimos avisos publicados'); ?>
        </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 dash-surface p-6">
            <?php dashboard_section_header('Proximos eventos', $basePrefix . 'index.php?page=associado/meus_eventos', 'Ir para meus eventos'); ?>
            <div id="nextEventsList" class="space-y-3" aria-live="polite"></div>
        </div>

        <div class="dash-surface p-6">
            <h2 class="text-lg font-semibold dash-title mb-4">Acoes rapidas</h2>
            <div class="grid grid-cols-1 gap-2">
                <?php dashboard_quick_link($basePrefix . 'index.php?page=associado/perfil', 'Atualizar perfil'); ?>
                <?php dashboard_quick_link($basePrefix . 'index.php?page=associado/meus_beneficios', 'Meus beneficios'); ?>
                <?php dashboard_quick_link($basePrefix . 'index.php?page=associado/meus_eventos', 'Meus eventos'); ?>
                <?php dashboard_quick_link($basePrefix . 'index.php?page=associado/comunicados', 'Comunicados'); ?>
            </div>
            <h3 class="mt-6 text-sm font-semibold dash-title">Beneficios ativos</h3>
            <ul id="activeBenefitsList" class="mt-2 space-y-2 text-sm dash-muted" aria-live="polite"></ul>
        </div>
    </section>

    <section class="dash-surface p-6">
        <?php dashboard_section_header('Comunicados recentes', $basePrefix . 'index.php?page=associado/comunicados', 'Ver todos'); ?>
        <div id="comunicadosList" class="space-y-3" aria-live="polite"></div>
    </section>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const endpoint = (path) => `${base}${path}`;

    const elAlert = document.getElementById('dashboardUserAlert');
    const elStatus = document.getElementById('kpiStatus');
    const elCategoria = document.getElementById('kpiCategoria');
    const elBeneficios = document.getElementById('kpiBeneficios');
    const elEventos = document.getElementById('kpiEventosInscritos');
    const elComunicados = document.getElementById('kpiComunicados');
    const elEventsList = document.getElementById('nextEventsList');
    const elBenefitsList = document.getElementById('activeBenefitsList');
    const elComunicadosList = document.getElementById('comunicadosList');

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDateTime(value) {
        if (!value) {
            return '-';
        }
        const dt = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(dt.getTime())) {
            return String(value);
        }
        return dt.toLocaleString('pt-BR');
    }

    function setAlert(message, type) {
        if (!message) {
            elAlert.className = 'hidden mb-5 p-3 rounded border text-sm';
            elAlert.textContent = '';
            return;
        }
        if (type === 'ok') {
            elAlert.className = 'mb-5 p-3 rounded border border-green-300 bg-green-50 text-green-900 text-sm';
        } else {
            elAlert.className = 'mb-5 p-3 rounded border border-red-300 bg-red-50 text-red-900 text-sm';
        }
        elAlert.textContent = message;
    }

    function renderEvents(events) {
        if (!Array.isArray(events) || events.length === 0) {
            elEventsList.innerHTML = '<p class="dash-empty text-sm">Nenhum evento publicado para os proximos dias.</p>';
            return;
        }

        elEventsList.innerHTML = events.map((ev) => {
            const status = ev.registration_status === 'registered'
                ? '<span class="text-xs px-2 py-1 rounded bg-green-100 text-green-800">Inscrito</span>'
                : '<span class="text-xs px-2 py-1 rounded bg-gray-200 text-gray-800">Nao inscrito</span>';

            return `
                <article class="dash-surface p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold dash-title">${escapeHtml(ev.titulo)}</h3>
                            <p class="text-xs dash-muted mt-1">${escapeHtml(ev.local || 'Local a definir')}</p>
                            <p class="text-xs dash-muted mt-1">Inicio: ${escapeHtml(formatDateTime(ev.inicio_em))}</p>
                        </div>
                        ${status}
                    </div>
                </article>
            `;
        }).join('');
    }

    function renderBenefits(list) {
        if (!Array.isArray(list) || list.length === 0) {
            elBenefitsList.innerHTML = '<li class="dash-empty text-sm">Nenhum beneficio ativo no momento.</li>';
            return;
        }

        elBenefitsList.innerHTML = list.map((item) => `
            <li class="dash-surface px-3 py-2">${escapeHtml(item.nome)}</li>
        `).join('');
    }

    function renderComunicados(list) {
        if (!Array.isArray(list) || list.length === 0) {
            elComunicadosList.innerHTML = '<p class="dash-empty text-sm">Ainda nao ha comunicados publicados.</p>';
            return;
        }

        elComunicadosList.innerHTML = list.map((post) => `
            <article class="dash-surface p-4">
                <h3 class="text-sm font-semibold dash-title">${escapeHtml(post.titulo)}</h3>
                <p class="text-xs dash-muted mt-1">Publicado em ${escapeHtml(formatDateTime(post.publicado_em || post.created_at))}</p>
            </article>
        `).join('');
    }

    async function loadDashboard() {
        try {
            const data = await window.anatejeApi(endpoint('/api/v1/dashboard.php?action=member_summary'));
            const member = data.member || null;
            const counts = data.counts || {};

            if (!member) {
                elStatus.textContent = 'Perfil incompleto';
                elCategoria.textContent = 'Categoria: -';
                setAlert('Seu cadastro de associado ainda nao foi concluido. Atualize seu perfil para liberar recursos.', 'err');
            } else {
                elStatus.textContent = member.status || '-';
                elCategoria.textContent = `Categoria: ${member.categoria || '-'}`;
                if (Array.isArray(data.missing_fields) && data.missing_fields.length > 0) {
                    setAlert('Perfil parcialmente preenchido. Revise os campos obrigatorios em Atualizar perfil.', 'err');
                } else {
                    setAlert('Dados carregados com sucesso.', 'ok');
                }
            }

            elBeneficios.textContent = String(counts.active_benefits || 0);
            elEventos.textContent = String(counts.registered_upcoming_events || 0);
            elComunicados.textContent = String((data.comunicados || []).length);

            renderEvents(data.next_events || []);
            renderBenefits(data.active_benefits_list || []);
            renderComunicados(data.comunicados || []);
        } catch (err) {
            setAlert(err.message || 'Falha ao carregar dashboard do associado.', 'err');
        }
    }

    loadDashboard();
})();
</script>
