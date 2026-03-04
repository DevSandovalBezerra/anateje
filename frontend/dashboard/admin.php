<?php
// Dashboard Admin

if (!defined('TEMPLATE_ROUTED')) {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../includes/dashboard_components.php';

$basePrefix = isset($prefix) ? $prefix : '/';
dashboard_components_styles();
?>
<div id="dashboard-admin" class="dashboard-content p-6 space-y-6">
    <section class="dash-surface p-6">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold dash-title">Dashboard Administrativo</h1>
                <p class="dash-muted">KPIs operacionais para acompanhamento de associados, eventos, comunicados e campanhas.</p>
            </div>
            <div>
                <div class="flex flex-wrap items-end gap-2">
                    <label>
                        <span class="text-xs dash-muted block mb-1">De</span>
                        <input id="dashDateFrom" type="date" class="input-primary w-full">
                    </label>
                    <label>
                        <span class="text-xs dash-muted block mb-1">Ate</span>
                        <input id="dashDateTo" type="date" class="input-primary w-full">
                    </label>
                    <button id="adminDashApply" class="btn-secondary dash-focus px-4 py-2 text-sm" type="button" aria-label="Aplicar periodo do dashboard">
                        Aplicar periodo
                    </button>
                    <button id="adminDashClear" class="btn-secondary dash-focus px-4 py-2 text-sm" type="button" aria-label="Limpar periodo do dashboard">
                        Limpar
                    </button>
                    <button id="adminDashReload" class="btn-secondary dash-focus px-4 py-2 text-sm" type="button" aria-label="Atualizar dados do dashboard">
                        Atualizar dados
                    </button>
                    <button id="adminDashExportSummary" class="btn-secondary dash-focus px-4 py-2 text-sm" type="button" aria-label="Exportar resumo do dashboard">
                        Exportar resumo CSV
                    </button>
                    <button id="adminDashExportEvents" class="btn-secondary dash-focus px-4 py-2 text-sm" type="button" aria-label="Exportar eventos do dashboard">
                        Exportar eventos CSV
                    </button>
                    <button id="adminDashExportCampaigns" class="btn-secondary dash-focus px-4 py-2 text-sm" type="button" aria-label="Exportar campanhas do dashboard">
                        Exportar campanhas CSV
                    </button>
                    <button id="adminDashExportMembers" class="btn-secondary dash-focus px-4 py-2 text-sm" type="button" aria-label="Exportar associados do dashboard">
                        Exportar associados CSV
                    </button>
                </div>
            </div>
        </div>

        <p id="adminDashMsg" class="text-sm mb-4" role="status" aria-live="polite"></p>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <?php dashboard_kpi_card('Associados ativos', 'kpiActiveMembers', 'kpiInactiveMembers', 'Inativos: 0'); ?>
            <?php dashboard_kpi_card('Eventos publicados (futuros)', 'kpiUpcomingEvents', 'kpiDraftEvents', 'Rascunhos: 0'); ?>
            <?php dashboard_kpi_card('Comunicados publicados', 'kpiComunicados', 'kpiComunicadosMeta', 'Posts do tipo COMUNICADO'); ?>
            <?php dashboard_kpi_card('Campanhas', 'kpiCampaigns', 'kpiCampaignsProcessing', 'Em processamento: 0'); ?>
        </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 dash-surface p-6">
            <?php dashboard_section_header('Proximos eventos', $basePrefix . 'index.php?page=admin/eventos', 'Abrir modulo de eventos'); ?>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left dash-muted border-b border-gray-200">
                            <th class="py-2 pr-3" scope="col">Evento</th>
                            <th class="py-2 pr-3" scope="col">Inicio</th>
                            <th class="py-2 pr-3" scope="col">Status</th>
                            <th class="py-2 pr-3" scope="col">Inscritos</th>
                        </tr>
                    </thead>
                    <tbody id="adminEventsRows" aria-live="polite"></tbody>
                </table>
            </div>
        </div>

        <div class="dash-surface p-6">
            <h2 class="text-lg font-semibold dash-title mb-4">Atalhos de gestao</h2>
            <div class="grid grid-cols-1 gap-2">
                <?php dashboard_quick_link($basePrefix . 'index.php?page=dashboard/financeiro', 'Financeiro'); ?>
                <?php dashboard_quick_link($basePrefix . 'index.php?page=admin/associados', 'Associados'); ?>
                <?php dashboard_quick_link($basePrefix . 'index.php?page=admin/beneficios', 'Beneficios'); ?>
                <?php dashboard_quick_link($basePrefix . 'index.php?page=admin/eventos', 'Eventos'); ?>
                <?php dashboard_quick_link($basePrefix . 'index.php?page=admin/comunicados', 'Comunicados'); ?>
                <?php dashboard_quick_link($basePrefix . 'index.php?page=admin/campanhas', 'Campanhas'); ?>
                <?php dashboard_quick_link($basePrefix . 'index.php?page=admin/integracoes', 'Integracoes'); ?>
            </div>

            <h3 class="mt-6 text-sm font-semibold dash-title">Associados por categoria</h3>
            <ul id="membersByCategory" class="mt-2 space-y-2 text-sm dash-muted" aria-live="polite"></ul>
        </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="dash-surface p-6">
            <?php dashboard_section_header('Campanhas recentes', $basePrefix . 'index.php?page=admin/campanhas', 'Ver modulo'); ?>
            <div id="recentCampaignsList" class="space-y-3" aria-live="polite"></div>
        </div>

        <div class="dash-surface p-6">
            <?php dashboard_section_header('Associados recentes', $basePrefix . 'index.php?page=admin/associados', 'Ver modulo'); ?>
            <div id="recentMembersList" class="space-y-3" aria-live="polite"></div>
        </div>
    </section>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const endpoint = (path) => `${base}${path}`;

    const msg = document.getElementById('adminDashMsg');
    const rowsEvents = document.getElementById('adminEventsRows');
    const membersByCategory = document.getElementById('membersByCategory');
    const recentCampaignsList = document.getElementById('recentCampaignsList');
    const recentMembersList = document.getElementById('recentMembersList');

    const kpiActiveMembers = document.getElementById('kpiActiveMembers');
    const kpiInactiveMembers = document.getElementById('kpiInactiveMembers');
    const kpiUpcomingEvents = document.getElementById('kpiUpcomingEvents');
    const kpiDraftEvents = document.getElementById('kpiDraftEvents');
    const kpiComunicados = document.getElementById('kpiComunicados');
    const kpiCampaigns = document.getElementById('kpiCampaigns');
    const kpiCampaignsProcessing = document.getElementById('kpiCampaignsProcessing');
    const dashDateFrom = document.getElementById('dashDateFrom');
    const dashDateTo = document.getElementById('dashDateTo');
    const dashApply = document.getElementById('adminDashApply');
    const dashClear = document.getElementById('adminDashClear');
    const dashReload = document.getElementById('adminDashReload');
    const dashExportSummary = document.getElementById('adminDashExportSummary');
    const dashExportEvents = document.getElementById('adminDashExportEvents');
    const dashExportCampaigns = document.getElementById('adminDashExportCampaigns');
    const dashExportMembers = document.getElementById('adminDashExportMembers');

    const state = {
        date_from: '',
        date_to: ''
    };

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('pt-BR').format(Number(value || 0));
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

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mb-4 text-green-900' : 'text-sm mb-4 text-red-900';
    }

    function normalizeDate(value) {
        const v = String(value || '').trim();
        return /^\d{4}-\d{2}-\d{2}$/.test(v) ? v : '';
    }

    function readFiltersFromUi() {
        state.date_from = normalizeDate(dashDateFrom.value || '');
        state.date_to = normalizeDate(dashDateTo.value || '');
        if (state.date_from && state.date_to && state.date_from > state.date_to) {
            const tmp = state.date_from;
            state.date_from = state.date_to;
            state.date_to = tmp;
        }
    }

    function writeFiltersToUi() {
        dashDateFrom.value = state.date_from || '';
        dashDateTo.value = state.date_to || '';
    }

    function buildSummaryUrl() {
        const params = new URLSearchParams();
        params.set('action', 'admin_summary');
        if (state.date_from) params.set('date_from', state.date_from);
        if (state.date_to) params.set('date_to', state.date_to);
        return endpoint('/api/v1/dashboard.php?' + params.toString());
    }

    function buildExportUrl(module) {
        const params = new URLSearchParams();
        params.set('action', 'admin_export_csv');
        params.set('module', module);
        if (state.date_from) params.set('date_from', state.date_from);
        if (state.date_to) params.set('date_to', state.date_to);
        return endpoint('/api/v1/dashboard.php?' + params.toString());
    }

    function renderMembersByCategory(map) {
        const parcial = Number((map && map.PARCIAL) || 0);
        const integral = Number((map && map.INTEGRAL) || 0);
        membersByCategory.innerHTML = `
            <li class="dash-surface px-3 py-2">PARCIAL: ${formatNumber(parcial)}</li>
            <li class="dash-surface px-3 py-2">INTEGRAL: ${formatNumber(integral)}</li>
        `;
    }

    function renderEvents(list) {
        if (!Array.isArray(list) || list.length === 0) {
            rowsEvents.innerHTML = '<tr><td colspan="4" class="py-3"><div class="dash-empty text-sm">Sem eventos futuros cadastrados.</div></td></tr>';
            return;
        }

        rowsEvents.innerHTML = list.map((ev) => `
            <tr class="border-b border-gray-200">
                <td class="py-2 pr-3">${escapeHtml(ev.titulo)}</td>
                <td class="py-2 pr-3">${escapeHtml(formatDateTime(ev.inicio_em))}</td>
                <td class="py-2 pr-3">${escapeHtml(ev.status || '-')}</td>
                <td class="py-2 pr-3">${formatNumber(ev.inscritos || 0)}</td>
            </tr>
        `).join('');
    }

    function renderCampaigns(list) {
        if (!Array.isArray(list) || list.length === 0) {
            recentCampaignsList.innerHTML = '<p class="dash-empty text-sm">Nenhuma campanha registrada.</p>';
            return;
        }

        recentCampaignsList.innerHTML = list.map((item) => `
            <article class="dash-surface p-3">
                <h3 class="text-sm font-semibold dash-title">${escapeHtml(item.titulo)}</h3>
                <p class="text-xs dash-muted mt-1">Canal: ${escapeHtml(item.canal || '-')} | Status: ${escapeHtml(item.status || '-')}</p>
                <p class="text-xs dash-muted">Logs: ${formatNumber(item.total_logs || 0)} | Criada em ${escapeHtml(formatDateTime(item.created_at))}</p>
            </article>
        `).join('');
    }

    function renderRecentMembers(list) {
        if (!Array.isArray(list) || list.length === 0) {
            recentMembersList.innerHTML = '<p class="dash-empty text-sm">Nenhum associado cadastrado.</p>';
            return;
        }

        recentMembersList.innerHTML = list.map((item) => `
            <article class="dash-surface p-3">
                <h3 class="text-sm font-semibold dash-title">${escapeHtml(item.nome)}</h3>
                <p class="text-xs dash-muted mt-1">Categoria: ${escapeHtml(item.categoria || '-')} | Status: ${escapeHtml(item.status || '-')}</p>
                <p class="text-xs dash-muted">Contato: ${escapeHtml(item.email_funcional || item.telefone || '-')}</p>
            </article>
        `).join('');
    }

    async function loadDashboard() {
        try {
            readFiltersFromUi();
            const data = await window.anatejeApi(buildSummaryUrl());
            const counts = data.counts || {};

            kpiActiveMembers.textContent = formatNumber(counts.active_members || 0);
            kpiInactiveMembers.textContent = `Inativos: ${formatNumber(counts.inactive_members || 0)}`;
            kpiUpcomingEvents.textContent = formatNumber(counts.upcoming_published_events || 0);
            kpiDraftEvents.textContent = `Rascunhos: ${formatNumber(counts.draft_events || 0)}`;
            kpiComunicados.textContent = formatNumber(counts.published_comunicados || 0);
            kpiCampaigns.textContent = formatNumber(counts.total_campaigns || 0);
            kpiCampaignsProcessing.textContent = `Em processamento: ${formatNumber(counts.processing_campaigns || 0)}`;

            renderMembersByCategory(data.members_by_category || {});
            renderEvents(data.upcoming_events || []);
            renderCampaigns(data.recent_campaigns || []);
            renderRecentMembers(data.recent_members || []);

            const filters = data.filters || {};
            state.date_from = normalizeDate(filters.date_from || state.date_from);
            state.date_to = normalizeDate(filters.date_to || state.date_to);
            writeFiltersToUi();

            const periodText = (state.date_from || state.date_to)
                ? ` Periodo: ${state.date_from || 'inicio'} ate ${state.date_to || 'hoje'}.`
                : '';
            setMsg('Dashboard atualizado com sucesso.' + periodText, 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar dashboard administrativo.', 'err');
        }
    }

    dashApply.addEventListener('click', () => {
        state.date_from = normalizeDate(dashDateFrom.value || '');
        state.date_to = normalizeDate(dashDateTo.value || '');
        if (state.date_from && state.date_to && state.date_from > state.date_to) {
            const tmp = state.date_from;
            state.date_from = state.date_to;
            state.date_to = tmp;
            writeFiltersToUi();
        }
        loadDashboard();
    });
    dashClear.addEventListener('click', () => {
        state.date_from = '';
        state.date_to = '';
        writeFiltersToUi();
        loadDashboard();
    });
    dashReload.addEventListener('click', loadDashboard);
    dashExportSummary.addEventListener('click', () => {
        readFiltersFromUi();
        window.location.href = buildExportUrl('summary');
    });
    dashExportEvents.addEventListener('click', () => {
        readFiltersFromUi();
        window.location.href = buildExportUrl('events');
    });
    dashExportCampaigns.addEventListener('click', () => {
        readFiltersFromUi();
        window.location.href = buildExportUrl('campaigns');
    });
    dashExportMembers.addEventListener('click', () => {
        readFiltersFromUi();
        window.location.href = buildExportUrl('members');
    });

    [dashDateFrom, dashDateTo].forEach((el) => {
        el.addEventListener('keydown', (ev) => {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                dashApply.click();
            }
        });
    });
    loadDashboard();
})();
</script>
