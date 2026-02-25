<?php
// Admin - Campanhas

if (!defined('TEMPLATE_ROUTED')) {
    header('Location: ../../index.php');
    exit;
}

$basePrefix = isset($prefix) ? $prefix : '/';
?>
<div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Admin - Campanhas</h1>
                <p class="text-gray-600">Disparos segmentados por categoria, status, UF e beneficio ativo.</p>
            </div>
            <button id="novaCampanha" class="btn-primary px-4 py-2 text-sm">Nova Campanha</button>
        </div>

        <div class="overflow-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
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

        <form id="campForm" class="hidden p-4 rounded-lg border border-gray-200 bg-gray-50 space-y-3">
            <input id="camp_id" type="hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label>
                    <span class="text-sm font-medium text-gray-700">Titulo *</span>
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

            <div class="pt-2 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Filtro de Segmentacao</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
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
            </div>

            <div class="flex flex-wrap gap-2">
                <button class="btn-primary px-4 py-2 text-sm" type="submit">Salvar Campanha</button>
                <button class="btn-secondary px-4 py-2 text-sm" type="button" id="cancelCamp">Cancelar</button>
            </div>
        </form>

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

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;

    const rows = document.getElementById('campRows');
    const msg = document.getElementById('campMsg');
    const form = document.getElementById('campForm');
    const logsBox = document.getElementById('campLogsBox');
    const logsRows = document.getElementById('campLogsRows');
    const logsMeta = document.getElementById('campLogsMeta');
    const exportLogsCsvBtn = document.getElementById('exportLogsCsv');
    const logsRun = document.getElementById('camp_logs_run');
    const logsStatus = document.getElementById('camp_logs_status');
    const logsQ = document.getElementById('camp_logs_q');
    const logsApply = document.getElementById('camp_logs_apply');
    const logsPageMeta = document.getElementById('campLogsPageMeta');
    const logsPrev = document.getElementById('campLogsPrev');
    const logsNext = document.getElementById('campLogsNext');

    const el = (id) => document.getElementById(id);
    let cache = [];
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

    function dtLabel(v) {
        if (!v) return '-';
        const d = new Date(String(v).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return v;
        return d.toLocaleString('pt-BR');
    }

    function renderTable() {
        if (!cache.length) {
            rows.innerHTML = '<tr><td colspan="6" class="py-3 text-gray-500">Nenhuma campanha cadastrada.</td></tr>';
            return;
        }

        rows.innerHTML = cache.map((c) => `
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-3">${c.id}</td>
                <td class="py-2 pr-3">${c.titulo}</td>
                <td class="py-2 pr-3">${c.canal}</td>
                <td class="py-2 pr-3">${c.status}</td>
                <td class="py-2 pr-3">T:${c.total_logs || 0} / S:${c.sent_logs || 0} / Q:${c.queued_logs || 0} / K:${c.skipped_logs || 0}</td>
                <td class="py-2 pr-3">
                    <div class="flex flex-wrap gap-2">
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="edit" data-id="${c.id}">Editar</button>
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="run" data-id="${c.id}">Executar</button>
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="logs" data-id="${c.id}">Logs</button>
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="csv" data-id="${c.id}">CSV</button>
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="delete" data-id="${c.id}">Excluir</button>
                    </div>
                </td>
            </tr>
        `).join('');

        rows.querySelectorAll('button[data-act]').forEach((btn) => btn.addEventListener('click', onAction));
    }

    function openForm(c) {
        form.classList.remove('hidden');
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
    }

    function closeForm() {
        form.classList.add('hidden');
        form.reset();
        el('camp_id').value = '';
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

    async function onAction(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const act = btn.getAttribute('data-act');

        const row = cache.find((x) => parseInt(x.id, 10) === id);

        try {
            if (act === 'edit') {
                openForm(row || null);
                return;
            }

            if (act === 'delete') {
                if (!confirm('Deseja excluir esta campanha?')) return;
                await window.anatejeApi(ep('/api/v1/campaigns.php?action=admin_delete&id=' + id));
                setMsg('Campanha excluida.', 'ok');
                await load();
                return;
            }

            if (act === 'run') {
                if (!confirm('Executar esta campanha agora?')) return;
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
                logsState = { ...logsState, runId: 0, status: '', q: '', page: 1 };
                await loadLogs(id, row || { id, titulo: '-', canal: '-' });
                return;
            }

            if (act === 'csv') {
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
            renderTable();
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar campanhas', 'err');
        }
    }

    document.getElementById('novaCampanha').addEventListener('click', () => openForm(null));
    document.getElementById('cancelCamp').addEventListener('click', closeForm);
    document.getElementById('closeLogs').addEventListener('click', () => {
        logsBox.classList.add('hidden');
        activeCampaignId = 0;
        logsRows.innerHTML = '';
        logsPageMeta.textContent = '';
    });
    exportLogsCsvBtn.addEventListener('click', () => {
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

            await window.anatejeApi(ep('/api/v1/campaigns.php?action=admin_save'), { method: 'POST', body });
            setMsg('Campanha salva com sucesso.', 'ok');
            closeForm();
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar campanha', 'err');
        }
    });

    load();
})();
</script>
