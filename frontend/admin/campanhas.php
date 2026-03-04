<?php
// Admin - Campanhas

if (!defined('TEMPLATE_ROUTED')) {
    header('Location: ../../index.php');
    exit;
}

$basePrefix = isset($prefix) ? $prefix : '/';
require_once __DIR__ . '/../../includes/admin_components.php';
?>
<div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <?php
        admin_render_toolbar(
            'Admin - Campanhas',
            'Disparos segmentados por categoria, status, UF e beneficio ativo.',
            [
                ['id' => 'novaCampanha', 'label' => 'Nova Campanha', 'class' => 'btn-primary px-4 py-2 text-sm'],
            ]
        );
        ?>

        <div class="mb-4 p-3 rounded border border-indigo-200 bg-indigo-50 flex flex-col md:flex-row md:items-center gap-2">
            <div class="text-xs text-gray-700" id="campBulkMeta">Nenhum item selecionado</div>
            <div class="flex items-center gap-2 md:ml-auto">
                <select id="campBulkStatus" class="input-primary text-sm">
                    <option value="queued">Marcar como na fila</option>
                    <option value="draft">Marcar como rascunho</option>
                    <option value="done">Marcar como concluida</option>
                    <option value="failed">Marcar como falhou</option>
                </select>
                <input id="campBulkReason" class="input-primary text-sm" placeholder="Motivo (opcional)">
                <button id="campBulkApply" class="btn-secondary px-3 py-2 text-xs">Aplicar em lote</button>
            </div>
        </div>

        <div class="overflow-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
                        <th class="py-2 pr-3"><input id="selectAllCampRows" type="checkbox"></th>
                        <th class="py-2 pr-3">ID</th>
                        <th class="py-2 pr-3">Titulo</th>
                        <th class="py-2 pr-3">Canal</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Logs</th>
                        <th class="py-2 pr-3">Acoes</th>
                    </tr>
                </thead>
                <tbody id="campRows"></tbody>
            </table>
        </div>

        <div id="campLogsBox" class="hidden mt-6 p-4 rounded-lg border border-amber-200 bg-amber-50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800">Logs da Campanha</h3>
                <div class="flex gap-2">
                    <button id="exportLogsCsv" class="btn-secondary px-3 py-1 text-xs">Exportar CSV</button>
                    <button id="closeLogs" class="btn-secondary px-3 py-1 text-xs">Fechar</button>
                </div>
            </div>
            <div id="campLogsMeta" class="text-xs text-gray-700 mb-2"></div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3">
                <label>
                    <span class="text-xs text-gray-700">Rodada</span>
                    <select id="camp_logs_run" class="input-primary w-full"></select>
                </label>
                <label>
                    <span class="text-xs text-gray-700">Status</span>
                    <select id="camp_logs_status" class="input-primary w-full">
                        <option value="">Todos</option>
                        <option value="queued">queued</option>
                        <option value="sent">sent</option>
                        <option value="failed">failed</option>
                        <option value="skipped">skipped</option>
                    </select>
                </label>
                <label class="md:col-span-2">
                    <span class="text-xs text-gray-700">Busca (destino/nome/email/telefone)</span>
                    <div class="flex gap-2">
                        <input id="camp_logs_q" class="input-primary w-full" placeholder="Buscar...">
                        <button id="camp_logs_apply" type="button" class="btn-secondary px-3 py-1 text-xs">Aplicar</button>
                        <button id="camp_logs_save_filter" type="button" class="btn-secondary px-3 py-1 text-xs">Salvar filtros</button>
                        <button id="camp_logs_load_filter" type="button" class="btn-secondary px-3 py-1 text-xs">Usar salvos</button>
                    </div>
                </label>
            </div>
            <div class="max-h-64 overflow-auto border border-amber-200 rounded bg-white">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-gray-600 border-b">
                            <th class="py-2 px-2">Run</th>
                            <th class="py-2 px-2">Data</th>
                            <th class="py-2 px-2">Canal</th>
                            <th class="py-2 px-2">Destino</th>
                            <th class="py-2 px-2">Status</th>
                            <th class="py-2 px-2">Erro</th>
                        </tr>
                    </thead>
                    <tbody id="campLogsRows"></tbody>
                </table>
            </div>
            <div class="flex items-center justify-between mt-3">
                <div id="campLogsPageMeta" class="text-xs text-gray-700"></div>
                <div class="flex gap-2">
                    <button id="campLogsPrev" class="btn-secondary px-3 py-1 text-xs">Anterior</button>
                    <button id="campLogsNext" class="btn-secondary px-3 py-1 text-xs">Proxima</button>
                </div>
            </div>
        </div>

        <p id="campMsg" class="text-sm mt-4"></p>
    </div>
</div>
<div id="campModal" class="hidden fixed inset-0 z-50 admin-modal">
    <div class="absolute inset-0 admin-modal-overlay" data-close-camp-modal="1"></div>
    <div class="relative flex min-h-screen items-center justify-center p-4">
        <div id="campModalPanel" class="w-full rounded-lg border border-gray-200 bg-white shadow-2xl admin-modal-panel">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p id="campModalMode" class="admin-modal-mode">Criacao</p>
                        <h3 id="campModalTitle" class="admin-modal-title">Nova Campanha</h3>
                        <p class="admin-modal-subtitle">Configure mensagem, canal e segmentacao de envio.</p>
                    </div>
                    <button id="closeCampModal" type="button" class="btn-secondary px-3 py-2 text-xs">Fechar</button>
                </div>
            </div>
            <form id="campForm" class="space-y-4 p-6">
                <input id="camp_id" type="hidden">
                <section class="admin-modal-section">
                    <h4 class="mb-3 text-sm font-semibold text-gray-800">Mensagem</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 admin-modal-grid">
                        <label>
                            <span class="text-sm font-medium text-gray-700">Titulo <span class="text-red-600">*</span></span>
                            <input id="camp_titulo" class="input-primary w-full" required>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Canal</span>
                            <select id="camp_canal" class="input-primary w-full">
                                <option value="INAPP">INAPP</option>
                                <option value="EMAIL">EMAIL</option>
                                <option value="WHATSAPP">WHATSAPP</option>
                            </select>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Status</span>
                            <select id="camp_status" class="input-primary w-full">
                                <option value="draft">Rascunho</option>
                                <option value="queued">Na fila</option>
                                <option value="processing">Processando</option>
                                <option value="done">Concluida</option>
                                <option value="failed">Falhou</option>
                            </select>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Assunto</span>
                            <input id="camp_assunto" class="input-primary w-full">
                        </label>
                        <label class="md:col-span-2">
                            <span class="text-sm font-medium text-gray-700">Mensagem</span>
                            <textarea id="camp_mensagem" class="input-primary w-full" rows="4"></textarea>
                        </label>
                    </div>
                </section>
                <section class="admin-modal-section">
                    <h4 class="mb-3 text-sm font-semibold text-gray-800">Filtro de Segmentacao</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 admin-modal-grid">
                        <label>
                            <span class="text-sm text-gray-700">Categoria</span>
                            <select id="camp_f_categoria" class="input-primary w-full">
                                <option value="">Todas</option>
                                <option value="PARCIAL">PARCIAL</option>
                                <option value="INTEGRAL">INTEGRAL</option>
                            </select>
                        </label>
                        <label>
                            <span class="text-sm text-gray-700">Status</span>
                            <select id="camp_f_status" class="input-primary w-full">
                                <option value="">Todos</option>
                                <option value="ATIVO">ATIVO</option>
                                <option value="INATIVO">INATIVO</option>
                            </select>
                        </label>
                        <label>
                            <span class="text-sm text-gray-700">UF</span>
                            <input id="camp_f_uf" maxlength="2" class="input-primary w-full" placeholder="Ex: SP">
                        </label>
                        <label>
                            <span class="text-sm text-gray-700">ID Beneficio</span>
                            <input id="camp_f_benefit" type="number" class="input-primary w-full" min="0" placeholder="0 = todos">
                        </label>
                    </div>
                </section>
                <div class="admin-modal-footer">
                    <p id="campPreviewMsg" class="text-xs text-gray-700"></p>
                    <div class="flex flex-wrap gap-2">
                        <button class="btn-secondary px-4 py-2 text-sm" type="button" id="cancelCamp">Cancelar</button>
                        <button class="btn-secondary px-4 py-2 text-sm" type="button" id="previewCampanha">Simular publico</button>
                        <button id="campSubmitBtn" class="btn-primary px-4 py-2 text-sm" type="submit">Salvar campanha</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;
    const FILTERS_MODULE_CAMPAIGNS_LOGS = 'admin.campanhas.logs';
    const perms = window.anatejePerms || null;
    const can = (code) => !perms || typeof perms.can !== 'function' ? true : perms.can(code);
    const deny = (code) => perms && typeof perms.denyMessage === 'function'
        ? perms.denyMessage(code)
        : 'Acesso negado para esta acao.';
    const canBulkEdit = can('admin.campanhas.edit');
    const ui = window.anatejeUi || null;

    const rows = document.getElementById('campRows');
    const msg = document.getElementById('campMsg');
    const form = document.getElementById('campForm');
    const campModal = document.getElementById('campModal');
    const campModalPanel = document.getElementById('campModalPanel');
    const campModalTitle = document.getElementById('campModalTitle');
    const campModalMode = document.getElementById('campModalMode');
    const campSubmitBtn = document.getElementById('campSubmitBtn');
    const campBulkMeta = document.getElementById('campBulkMeta');
    const campBulkStatus = document.getElementById('campBulkStatus');
    const campBulkReason = document.getElementById('campBulkReason');
    const campBulkApply = document.getElementById('campBulkApply');
    const selectAllCampRows = document.getElementById('selectAllCampRows');
    const logsBox = document.getElementById('campLogsBox');
    const logsRows = document.getElementById('campLogsRows');
    const logsMeta = document.getElementById('campLogsMeta');
    const exportLogsCsvBtn = document.getElementById('exportLogsCsv');
    const logsRun = document.getElementById('camp_logs_run');
    const logsStatus = document.getElementById('camp_logs_status');
    const logsQ = document.getElementById('camp_logs_q');
    const logsApply = document.getElementById('camp_logs_apply');
    const logsSaveFilter = document.getElementById('camp_logs_save_filter');
    const logsLoadFilter = document.getElementById('camp_logs_load_filter');
    const logsPageMeta = document.getElementById('campLogsPageMeta');
    const logsPrev = document.getElementById('campLogsPrev');
    const logsNext = document.getElementById('campLogsNext');
    const previewMsg = document.getElementById('campPreviewMsg');

    const el = (id) => document.getElementById(id);
    let cache = [];
    const selectedIds = new Set();
    let savedLogsFilters = { run_id: 0, status: '', q: '' };
    let activeCampaignId = 0;
    let logsState = {
        runId: 0,
        status: '',
        q: '',
        page: 1,
        perPage: 20,
        totalPages: 1
    };

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function updateBulkMeta() {
        const count = selectedIds.size;
        campBulkMeta.textContent = count > 0
            ? `${count} item(ns) selecionado(s)`
            : 'Nenhum item selecionado';
        campBulkApply.disabled = count === 0 || !canBulkEdit;
    }

    function normalizeLogsFilterPayload(raw) {
        const f = raw && typeof raw === 'object' ? raw : {};
        const allowed = ['', 'queued', 'sent', 'failed', 'skipped'];
        const status = typeof f.status === 'string' ? f.status : '';
        const q = typeof f.q === 'string' ? f.q.trim() : '';
        const runIdRaw = parseInt(f.run_id || f.runId || 0, 10) || 0;
        return {
            run_id: runIdRaw > 0 ? runIdRaw : 0,
            status: allowed.includes(status) ? status : '',
            q: q
        };
    }

    async function fetchSavedLogsFilters() {
        if (!can('admin.campanhas.view')) return;
        try {
            const params = new URLSearchParams();
            params.set('action', 'get');
            params.set('module', FILTERS_MODULE_CAMPAIGNS_LOGS);
            params.set('key', 'default');
            const data = await window.anatejeApi(ep('/api/v1/filters.php?' + params.toString()));
            if (data && data.found && data.filters && typeof data.filters === 'object') {
                savedLogsFilters = normalizeLogsFilterPayload(data.filters);
            } else {
                savedLogsFilters = { run_id: 0, status: '', q: '' };
            }
        } catch (err) {
            savedLogsFilters = { run_id: 0, status: '', q: '' };
        }
    }

    function dtLabel(v) {
        if (!v) return '-';
        const d = new Date(String(v).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return v;
        return d.toLocaleString('pt-BR');
    }

    function renderTable() {
        if (!cache.length) {
            rows.innerHTML = '<tr><td colspan="7" class="py-3 text-gray-500">Nenhuma campanha cadastrada.</td></tr>';
            selectAllCampRows.checked = false;
            updateBulkMeta();
            return;
        }

        const pageIds = cache.map((c) => parseInt(c.id, 10)).filter((id) => id > 0);
        selectAllCampRows.checked = pageIds.length > 0 && pageIds.every((id) => selectedIds.has(id));

        rows.innerHTML = cache.map((c) => `
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-3">
                    <input type="checkbox" data-row-select="1" data-id="${c.id}" ${selectedIds.has(parseInt(c.id, 10)) ? 'checked' : ''} ${canBulkEdit ? '' : 'disabled'}>
                </td>
                <td class="py-2 pr-3">${c.id}</td>
                <td class="py-2 pr-3">${c.titulo}</td>
                <td class="py-2 pr-3">${c.canal}</td>
                <td class="py-2 pr-3">${c.status}</td>
                <td class="py-2 pr-3">T:${c.total_logs || 0} / S:${c.sent_logs || 0} / Q:${c.queued_logs || 0} / K:${c.skipped_logs || 0}</td>
                <td class="py-2 pr-3">
                    <div class="flex flex-wrap gap-2">
                        ${can('admin.campanhas.edit') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="edit" data-id="${c.id}">Editar</button>` : ''}
                        ${can('admin.campanhas.run') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="run" data-id="${c.id}">Executar</button>` : ''}
                        ${can('admin.campanhas.view') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="logs" data-id="${c.id}">Logs</button>` : ''}
                        ${can('admin.campanhas.export') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="csv" data-id="${c.id}">CSV</button>` : ''}
                        ${can('admin.campanhas.delete') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="delete" data-id="${c.id}">Excluir</button>` : ''}
                    </div>
                </td>
            </tr>
        `).join('');

        rows.querySelectorAll('input[data-row-select="1"]').forEach((cb) => cb.addEventListener('change', (ev) => {
            const id = parseInt(ev.currentTarget.getAttribute('data-id'), 10);
            if (!id) return;
            if (ev.currentTarget.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
            const ids = cache.map((x) => parseInt(x.id, 10)).filter((x) => x > 0);
            selectAllCampRows.checked = ids.length > 0 && ids.every((x) => selectedIds.has(x));
            updateBulkMeta();
        }));
        rows.querySelectorAll('button[data-act]').forEach((btn) => btn.addEventListener('click', onAction));
        updateBulkMeta();
    }

    function openForm(c) {
        const isEdit = !!(c && c.id);
        if (campModalTitle) campModalTitle.textContent = isEdit ? 'Editar Campanha' : 'Nova Campanha';
        if (campModalMode) campModalMode.textContent = isEdit ? 'Edicao' : 'Criacao';
        if (campSubmitBtn) campSubmitBtn.textContent = isEdit ? 'Salvar alteracoes' : 'Salvar campanha';
        campModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        previewMsg.textContent = '';
        el('camp_id').value = c?.id || '';
        el('camp_titulo').value = c?.titulo || '';
        el('camp_canal').value = c?.canal || 'INAPP';
        el('camp_status').value = c?.status || 'draft';
        el('camp_assunto').value = c?.payload?.assunto || '';
        el('camp_mensagem').value = c?.payload?.mensagem || '';
        el('camp_f_categoria').value = c?.filtro?.categoria || '';
        el('camp_f_status').value = c?.filtro?.status || '';
        el('camp_f_uf').value = c?.filtro?.uf || '';
        el('camp_f_benefit').value = c?.filtro?.benefit_id || '';
        setTimeout(() => {
            const t = el('camp_titulo');
            if (t && typeof t.focus === 'function') {
                t.focus();
                t.select();
            }
        }, 10);
    }

    function closeForm() {
        campModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        form.reset();
        if (ui && typeof ui.clearFieldErrors === 'function') {
            ui.clearFieldErrors(form);
        }
        el('camp_id').value = '';
        previewMsg.textContent = '';
        if (campModalTitle) campModalTitle.textContent = 'Nova Campanha';
        if (campModalMode) campModalMode.textContent = 'Criacao';
        if (campSubmitBtn) {
            campSubmitBtn.disabled = false;
            campSubmitBtn.textContent = 'Salvar campanha';
        }
    }

    function runLabel(run) {
        const started = dtLabel(run.started_at || '');
        return `#${run.id} - ${run.status} (${started})`;
    }

    function renderRunOptions(runs, selectedRunId) {
        const opts = ['<option value="0">Todas as rodadas</option>'];
        runs.forEach((r) => {
            const selected = parseInt(selectedRunId, 10) === parseInt(r.id, 10) ? 'selected' : '';
            opts.push(`<option value="${r.id}" ${selected}>${runLabel(r)}</option>`);
        });
        logsRun.innerHTML = opts.join('');
    }

    function renderLogs(data, campaign) {
        const logs = data.logs || [];
        const pagination = data.pagination || {};
        const runs = data.runs || [];

        logsBox.classList.remove('hidden');
        logsMeta.textContent = `Campanha #${campaign.id} - ${campaign.titulo} (${campaign.canal})`;
        activeCampaignId = parseInt(campaign.id || 0, 10) || 0;

        logsState.runId = parseInt(data.selected_run_id || 0, 10) || 0;
        logsState.status = (data.filters && typeof data.filters.status === 'string') ? data.filters.status : '';
        logsState.q = (data.filters && typeof data.filters.q === 'string') ? data.filters.q : '';
        logsState.page = parseInt(pagination.page || logsState.page || 1, 10) || 1;
        logsState.perPage = parseInt(pagination.per_page || logsState.perPage || 20, 10) || 20;
        logsState.totalPages = parseInt(pagination.total_pages || 1, 10) || 1;

        logsStatus.value = logsState.status;
        logsQ.value = logsState.q;
        renderRunOptions(runs, logsState.runId);

        if (!logs.length) {
            logsRows.innerHTML = '<tr><td colspan="6" class="py-2 px-2 text-gray-500">Sem logs para os filtros atuais.</td></tr>';
        } else {
            logsRows.innerHTML = logs.map((l) => `
                <tr class="border-b border-amber-100">
                    <td class="py-2 px-2">${l.run_id || '-'}</td>
                    <td class="py-2 px-2">${dtLabel(l.created_at)}</td>
                    <td class="py-2 px-2">${l.canal}</td>
                    <td class="py-2 px-2">${l.destino}</td>
                    <td class="py-2 px-2">${l.status}</td>
                    <td class="py-2 px-2">${l.erro || '-'}</td>
                </tr>
            `).join('');
        }

        const total = parseInt(pagination.total || 0, 10) || 0;
        logsPageMeta.textContent = `Pagina ${logsState.page}/${logsState.totalPages} - ${total} registro(s)`;
        logsPrev.disabled = logsState.page <= 1;
        logsNext.disabled = logsState.page >= logsState.totalPages;
    }

    async function loadLogs(campaignId, campaignRef) {
        const params = new URLSearchParams();
        params.set('action', 'admin_get');
        params.set('id', String(campaignId));
        params.set('page', String(logsState.page));
        params.set('per_page', String(logsState.perPage));
        if (logsState.runId > 0) {
            params.set('run_id', String(logsState.runId));
        }
        if (logsState.status) {
            params.set('status', logsState.status);
        }
        if (logsState.q) {
            params.set('q', logsState.q);
        }

        const data = await window.anatejeApi(ep('/api/v1/campaigns.php?' + params.toString()));
        renderLogs(data, data.campaign || campaignRef || { id: campaignId, titulo: '-', canal: '-' });
    }

    async function saveCurrentLogsFilters() {
        if (!can('admin.campanhas.view')) {
            setMsg(deny('admin.campanhas.view'), 'err');
            return;
        }
        const payload = normalizeLogsFilterPayload({
            run_id: parseInt(logsRun.value || '0', 10) || 0,
            status: logsStatus.value || '',
            q: logsQ.value || ''
        });

        try {
            await window.anatejeApi(ep('/api/v1/filters.php?action=save'), {
                method: 'POST',
                body: {
                    module: FILTERS_MODULE_CAMPAIGNS_LOGS,
                    key: 'default',
                    filters: payload
                }
            });
            savedLogsFilters = payload;
            setMsg('Filtros de logs salvos.', 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar filtros de logs', 'err');
        }
    }

    async function applySavedLogsFilters(forceReloadActiveCampaign) {
        await fetchSavedLogsFilters();
        logsState.runId = savedLogsFilters.run_id || 0;
        logsState.status = savedLogsFilters.status || '';
        logsState.q = savedLogsFilters.q || '';
        logsState.page = 1;
        logsStatus.value = logsState.status;
        logsQ.value = logsState.q;
        if (logsRun.options.length > 0) {
            logsRun.value = String(logsState.runId || 0);
        }

        if (forceReloadActiveCampaign && activeCampaignId > 0) {
            const ref = cache.find((x) => parseInt(x.id, 10) === activeCampaignId);
            await loadLogs(activeCampaignId, ref || { id: activeCampaignId, titulo: '-', canal: '-' });
        }
    }

    async function onAction(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const act = btn.getAttribute('data-act');

        const row = cache.find((x) => parseInt(x.id, 10) === id);

        try {
            if (act === 'edit') {
                if (!can('admin.campanhas.edit')) {
                    setMsg(deny('admin.campanhas.edit'), 'err');
                    return;
                }
                openForm(row || null);
                return;
            }

            if (act === 'delete') {
                if (!can('admin.campanhas.delete')) {
                    setMsg(deny('admin.campanhas.delete'), 'err');
                    return;
                }
                const confirmedDelete = ui && typeof ui.confirmDelete === 'function'
                    ? await ui.confirmDelete('esta campanha')
                    : false;
                if (!confirmedDelete) return;
                await window.anatejeApi(ep('/api/v1/campaigns.php?action=admin_delete'), {
                    method: 'POST',
                    body: { id }
                });
                setMsg('Campanha excluida.', 'ok');
                selectedIds.delete(id);
                selectAllCampRows.checked = false;
                await load();
                return;
            }

            if (act === 'run') {
                if (!can('admin.campanhas.run')) {
                    setMsg(deny('admin.campanhas.run'), 'err');
                    return;
                }
                const confirmedRun = ui && typeof ui.confirmAction === 'function'
                    ? await ui.confirmAction({
                        title: 'Executar campanha',
                        text: 'Deseja executar esta campanha agora?',
                        confirmText: 'Executar'
                    })
                    : false;
                if (!confirmedRun) return;
                const data = await window.anatejeApi(ep('/api/v1/campaigns.php?action=admin_run'), {
                    method: 'POST',
                    body: { id }
                });
                const run = data.run || {};
                setMsg(`Campanha executada. Total: ${run.total || 0}, queued: ${run.queued || 0}, sent: ${run.sent || 0}, skipped: ${run.skipped || 0}.`, 'ok');
                await load();
                return;
            }

            if (act === 'logs') {
                if (!can('admin.campanhas.view')) {
                    setMsg(deny('admin.campanhas.view'), 'err');
                    return;
                }
                logsState = {
                    ...logsState,
                    runId: savedLogsFilters.run_id || 0,
                    status: savedLogsFilters.status || '',
                    q: savedLogsFilters.q || '',
                    page: 1
                };
                await loadLogs(id, row || { id, titulo: '-', canal: '-' });
                return;
            }

            if (act === 'csv') {
                if (!can('admin.campanhas.export')) {
                    setMsg(deny('admin.campanhas.export'), 'err');
                    return;
                }
                window.location.href = ep('/api/v1/campaigns.php?action=export_logs_csv&id=' + id);
                return;
            }
        } catch (err) {
            setMsg(err.message || 'Falha ao executar acao', 'err');
        }
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/campaigns.php?action=admin_list'));
            cache = data.campaigns || [];
            const allowedIds = new Set(cache.map((c) => parseInt(c.id, 10)).filter((id) => id > 0));
            Array.from(selectedIds.values()).forEach((id) => {
                if (!allowedIds.has(id)) {
                    selectedIds.delete(id);
                }
            });
            renderTable();
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar campanhas', 'err');
        }
    }

    document.getElementById('novaCampanha').addEventListener('click', () => {
        if (!can('admin.campanhas.create')) {
            setMsg(deny('admin.campanhas.create'), 'err');
            return;
        }
        openForm(null);
    });
    document.getElementById('cancelCamp').addEventListener('click', closeForm);
    document.getElementById('closeCampModal').addEventListener('click', closeForm);
    campModal.addEventListener('click', (e) => {
        if (e.target && e.target.getAttribute('data-close-camp-modal') === '1') {
            closeForm();
        }
    });
    if (campModalPanel) {
        campModalPanel.addEventListener('click', (e) => e.stopPropagation());
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !campModal.classList.contains('hidden')) {
            closeForm();
        }
    });
    selectAllCampRows.addEventListener('change', (e) => {
        if (!canBulkEdit) {
            e.currentTarget.checked = false;
            return;
        }
        const checked = !!e.currentTarget.checked;
        cache.forEach((c) => {
            const id = parseInt(c.id, 10);
            if (!id) return;
            if (checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
        });
        rows.querySelectorAll('input[data-row-select="1"]').forEach((cb) => {
            cb.checked = checked;
        });
        updateBulkMeta();
    });
    campBulkApply.addEventListener('click', async () => {
        if (!can('admin.campanhas.edit')) {
            setMsg(deny('admin.campanhas.edit'), 'err');
            return;
        }
        const ids = Array.from(selectedIds.values()).filter((id) => Number.isInteger(id) && id > 0);
        if (!ids.length) {
            setMsg('Selecione ao menos uma campanha para acao em lote.', 'err');
            return;
        }
        const status = campBulkStatus.value || 'queued';
        const reason = (campBulkReason.value || '').trim();
        const confirmed = ui && typeof ui.confirmAction === 'function'
            ? await ui.confirmAction({
                title: 'Confirmar lote',
                text: `Aplicar status ${status} para ${ids.length} campanha(s)?`,
                confirmText: 'Aplicar'
            })
            : false;
        if (!confirmed) return;

        try {
            const data = await window.anatejeApi(ep('/api/v1/campaigns.php?action=admin_bulk_status'), {
                method: 'POST',
                body: { ids, status, reason }
            });
            setMsg(
                `Lote concluido. Atualizados: ${data.updated || 0}, sem alteracao: ${data.unchanged || 0}, nao encontrados: ${data.not_found || 0}.`,
                'ok'
            );
            ids.forEach((id) => selectedIds.delete(id));
            selectAllCampRows.checked = false;
            updateBulkMeta();
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao aplicar acao em lote nas campanhas', 'err');
        }
    });
    document.getElementById('previewCampanha').addEventListener('click', async () => {
        if (!can('admin.campanhas.view')) {
            setMsg(deny('admin.campanhas.view'), 'err');
            return;
        }
        try {
            const body = {
                canal: el('camp_canal').value,
                filtro: {
                    categoria: el('camp_f_categoria').value,
                    status: el('camp_f_status').value,
                    uf: el('camp_f_uf').value,
                    benefit_id: parseInt(el('camp_f_benefit').value || '0', 10) || 0
                }
            };
            const data = await window.anatejeApi(ep('/api/v1/campaigns.php?action=admin_preview'), { method: 'POST', body });
            const p = data.preview || {};
            previewMsg.textContent = `Simulacao: total ${p.total || 0}, aptos ${p.ready || 0}, sem contato ${p.missing_contact || 0}.`;
            setMsg('', 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao simular publico', 'err');
        }
    });
    document.getElementById('closeLogs').addEventListener('click', () => {
        logsBox.classList.add('hidden');
        activeCampaignId = 0;
        logsRows.innerHTML = '';
        logsPageMeta.textContent = '';
    });
    exportLogsCsvBtn.addEventListener('click', () => {
        if (!can('admin.campanhas.export')) {
            setMsg(deny('admin.campanhas.export'), 'err');
            return;
        }
        if (!activeCampaignId) return;
        const params = new URLSearchParams();
        params.set('action', 'export_logs_csv');
        params.set('id', String(activeCampaignId));
        if (logsState.runId > 0) {
            params.set('run_id', String(logsState.runId));
        }
        if (logsState.status) {
            params.set('status', logsState.status);
        }
        if (logsState.q) {
            params.set('q', logsState.q);
        }
        window.location.href = ep('/api/v1/campaigns.php?' + params.toString());
    });

    logsApply.addEventListener('click', async () => {
        if (!activeCampaignId) return;
        logsState.runId = parseInt(logsRun.value || '0', 10) || 0;
        logsState.status = logsStatus.value || '';
        logsState.q = (logsQ.value || '').trim();
        logsState.page = 1;
        await loadLogs(activeCampaignId, cache.find((x) => parseInt(x.id, 10) === activeCampaignId));
    });
    logsSaveFilter.addEventListener('click', saveCurrentLogsFilters);
    logsLoadFilter.addEventListener('click', async () => {
        await applySavedLogsFilters(true);
        if (!activeCampaignId) {
            setMsg('Filtros salvos carregados.', 'ok');
        }
    });

    logsRun.addEventListener('change', async () => {
        if (!activeCampaignId) return;
        logsState.runId = parseInt(logsRun.value || '0', 10) || 0;
        logsState.page = 1;
        await loadLogs(activeCampaignId, cache.find((x) => parseInt(x.id, 10) === activeCampaignId));
    });

    logsPrev.addEventListener('click', async () => {
        if (!activeCampaignId || logsState.page <= 1) return;
        logsState.page -= 1;
        await loadLogs(activeCampaignId, cache.find((x) => parseInt(x.id, 10) === activeCampaignId));
    });

    logsNext.addEventListener('click', async () => {
        if (!activeCampaignId || logsState.page >= logsState.totalPages) return;
        logsState.page += 1;
        await loadLogs(activeCampaignId, cache.find((x) => parseInt(x.id, 10) === activeCampaignId));
    });

    el('camp_f_uf').addEventListener('input', (e) => {
        e.target.value = (e.target.value || '').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 2);
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (ui && typeof ui.clearFieldErrors === 'function') {
            ui.clearFieldErrors(form);
        }
        const wasEdit = !!(el('camp_id').value && parseInt(el('camp_id').value, 10) > 0);
        if (campSubmitBtn) {
            campSubmitBtn.disabled = true;
            campSubmitBtn.textContent = 'Salvando...';
        }

        try {
            const body = {
                id: el('camp_id').value ? parseInt(el('camp_id').value, 10) : 0,
                canal: el('camp_canal').value,
                titulo: el('camp_titulo').value,
                status: el('camp_status').value,
                payload: {
                    assunto: el('camp_assunto').value,
                    mensagem: el('camp_mensagem').value
                },
                filtro: {
                    categoria: el('camp_f_categoria').value,
                    status: el('camp_f_status').value,
                    uf: el('camp_f_uf').value,
                    benefit_id: parseInt(el('camp_f_benefit').value || '0', 10) || 0
                }
            };

            const code = body.id > 0 ? 'admin.campanhas.edit' : 'admin.campanhas.create';
            if (!can(code)) {
                setMsg(deny(code), 'err');
                return;
            }

            await window.anatejeApi(ep('/api/v1/campaigns.php?action=admin_save'), { method: 'POST', body });
            setMsg('Campanha salva com sucesso.', 'ok');
            closeForm();
            await load();
        } catch (err) {
            if (ui && typeof ui.applyValidationError === 'function') {
                ui.applyValidationError(form, err, [
                    { pattern: /titulo/i, field: 'camp_titulo' },
                    { pattern: /canal/i, field: 'camp_canal' },
                    { pattern: /status/i, field: 'camp_status' }
                ]);
            }
            setMsg(err.message || 'Falha ao salvar campanha', 'err');
        } finally {
            if (campSubmitBtn) {
                campSubmitBtn.disabled = false;
                campSubmitBtn.textContent = wasEdit ? 'Salvar alteracoes' : 'Salvar campanha';
            }
        }
    });

    if (!can('admin.campanhas.create')) {
        document.getElementById('novaCampanha').classList.add('hidden');
    }
    if (!can('admin.campanhas.view')) {
        document.getElementById('previewCampanha').classList.add('hidden');
        logsSaveFilter.classList.add('hidden');
        logsLoadFilter.classList.add('hidden');
    }
    if (!can('admin.campanhas.export')) {
        exportLogsCsvBtn.classList.add('hidden');
    }
    if (!canBulkEdit) {
        selectAllCampRows.disabled = true;
        campBulkStatus.disabled = true;
        campBulkReason.disabled = true;
        campBulkApply.disabled = true;
    }

    (async function bootstrap() {
        await applySavedLogsFilters(false);
        updateBulkMeta();
        await load();
    })();
})();
</script>
