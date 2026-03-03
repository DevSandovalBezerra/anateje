<div class="space-y-6">
    <section class="card-primary p-6">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-5">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Admin - Auditoria</h1>
                <p class="text-gray-600">Rastreie alteracoes sensiveis por modulo, usuario e periodo.</p>
            </div>
            <div class="flex gap-2">
                <button id="btnApply" class="btn-primary px-4 py-2">Filtrar</button>
                <button id="btnClear" class="btn-secondary px-4 py-2">Limpar</button>
                <button id="btnSaveFilters" class="btn-secondary px-4 py-2">Salvar filtros</button>
                <button id="btnLoadFilters" class="btn-secondary px-4 py-2">Usar salvos</button>
                <a id="btnExport" class="btn-secondary px-4 py-2" href="#">Exportar CSV</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="text-sm text-gray-600 block mb-1">Modulo</label>
                <input id="fModule" class="input-primary w-full" placeholder="Ex: admin.associados">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Acao</label>
                <input id="fOperation" class="input-primary w-full" placeholder="Ex: update, delete">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Entidade</label>
                <input id="fEntity" class="input-primary w-full" placeholder="Ex: member, event">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Usuario ID</label>
                <input id="fUserId" type="number" min="1" class="input-primary w-full" placeholder="ID">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Data inicial</label>
                <input id="fDateFrom" type="date" class="input-primary w-full">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Data final</label>
                <input id="fDateTo" type="date" class="input-primary w-full">
            </div>
            <div class="md:col-span-2">
                <label class="text-sm text-gray-600 block mb-1">Busca livre</label>
                <input id="fQ" class="input-primary w-full" placeholder="Modulo, acao, entidade, usuario, IP...">
            </div>
        </div>
    </section>

    <section class="card-primary p-6">
        <div class="flex items-center justify-between mb-4">
            <p id="auditMsg" class="text-sm text-gray-600"></p>
            <div class="text-sm text-gray-500">
                <span id="auditCounter"></span>
            </div>
        </div>

        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b border-gray-200">
                        <th class="py-2 pr-3">Data</th>
                        <th class="py-2 pr-3">Usuario</th>
                        <th class="py-2 pr-3">Modulo</th>
                        <th class="py-2 pr-3">Acao</th>
                        <th class="py-2 pr-3">Entidade</th>
                        <th class="py-2 pr-3">ID</th>
                        <th class="py-2 pr-3">IP</th>
                    </tr>
                </thead>
                <tbody id="auditRows"></tbody>
            </table>
        </div>

        <div class="flex items-center justify-between mt-4">
            <button id="btnPrev" class="btn-secondary px-3 py-1">Anterior</button>
            <span id="auditPage" class="text-sm text-gray-600"></span>
            <button id="btnNext" class="btn-secondary px-3 py-1">Proxima</button>
        </div>
    </section>
</div>

<script>
(() => {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => base + path;
    const FILTERS_MODULE_AUDIT_LIST = 'admin.auditoria.list';
    const perms = window.anatejePerms || null;
    const can = (code) => !perms || typeof perms.can !== 'function' ? true : perms.can(code);
    const deny = (code) => perms && typeof perms.denyMessage === 'function'
        ? perms.denyMessage(code)
        : 'Acesso negado para esta acao.';

    const state = {
        page: 1,
        perPage: 30,
        totalPages: 1,
        total: 0,
        filters: {
            module: '',
            operation: '',
            entity: '',
            user_id: '',
            date_from: '',
            date_to: '',
            q: '',
        }
    };

    const refs = {
        msg: document.getElementById('auditMsg'),
        counter: document.getElementById('auditCounter'),
        rows: document.getElementById('auditRows'),
        page: document.getElementById('auditPage'),
        prev: document.getElementById('btnPrev'),
        next: document.getElementById('btnNext'),
        apply: document.getElementById('btnApply'),
        clear: document.getElementById('btnClear'),
        saveFilters: document.getElementById('btnSaveFilters'),
        loadFilters: document.getElementById('btnLoadFilters'),
        export: document.getElementById('btnExport'),
        fModule: document.getElementById('fModule'),
        fOperation: document.getElementById('fOperation'),
        fEntity: document.getElementById('fEntity'),
        fUserId: document.getElementById('fUserId'),
        fDateFrom: document.getElementById('fDateFrom'),
        fDateTo: document.getElementById('fDateTo'),
        fQ: document.getElementById('fQ'),
    };

    function esc(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function setMsg(text, kind = 'info') {
        refs.msg.textContent = text || '';
        refs.msg.className = kind === 'err'
            ? 'text-sm text-red-600'
            : kind === 'ok'
                ? 'text-sm text-green-600'
                : 'text-sm text-gray-600';
    }

    function normalizeAuditFilters(raw) {
        const f = raw && typeof raw === 'object' ? raw : {};
        const userId = String(f.user_id || '').replace(/\D/g, '');
        return {
            module: String(f.module || '').trim().slice(0, 80),
            operation: String(f.operation || '').trim().slice(0, 80),
            entity: String(f.entity || '').trim().slice(0, 80),
            user_id: userId,
            date_from: String(f.date_from || '').trim().slice(0, 10),
            date_to: String(f.date_to || '').trim().slice(0, 10),
            q: String(f.q || '').trim().slice(0, 120),
        };
    }

    function readFiltersFromUI() {
        state.filters.module = refs.fModule.value.trim();
        state.filters.operation = refs.fOperation.value.trim();
        state.filters.entity = refs.fEntity.value.trim();
        state.filters.user_id = refs.fUserId.value.trim();
        state.filters.date_from = refs.fDateFrom.value || '';
        state.filters.date_to = refs.fDateTo.value || '';
        state.filters.q = refs.fQ.value.trim();
    }

    function writeFiltersToUI() {
        refs.fModule.value = state.filters.module || '';
        refs.fOperation.value = state.filters.operation || '';
        refs.fEntity.value = state.filters.entity || '';
        refs.fUserId.value = state.filters.user_id || '';
        refs.fDateFrom.value = state.filters.date_from || '';
        refs.fDateTo.value = state.filters.date_to || '';
        refs.fQ.value = state.filters.q || '';
    }

    async function fetchSavedFilters() {
        if (!can('admin.auditoria.view')) return;
        try {
            const params = new URLSearchParams();
            params.set('action', 'get');
            params.set('module', FILTERS_MODULE_AUDIT_LIST);
            params.set('key', 'default');
            const data = await window.anatejeApi(ep('/api/v1/filters.php?' + params.toString()));
            if (data && data.found && data.filters && typeof data.filters === 'object') {
                state.filters = normalizeAuditFilters(data.filters);
            } else {
                state.filters = normalizeAuditFilters({});
            }
        } catch (err) {
            state.filters = normalizeAuditFilters({});
        }
    }

    async function saveCurrentFilters() {
        if (!can('admin.auditoria.view')) {
            setMsg(deny('admin.auditoria.view'), 'err');
            return;
        }
        readFiltersFromUI();
        const payload = normalizeAuditFilters(state.filters);
        try {
            await window.anatejeApi(ep('/api/v1/filters.php?action=save'), {
                method: 'POST',
                body: {
                    module: FILTERS_MODULE_AUDIT_LIST,
                    key: 'default',
                    filters: payload
                }
            });
            setMsg('Filtros de auditoria salvos.', 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar filtros de auditoria', 'err');
        }
    }

    async function loadSavedFiltersAndRefresh() {
        await fetchSavedFilters();
        state.page = 1;
        writeFiltersToUI();
        await load();
        setMsg('Filtros salvos carregados.', 'ok');
    }

    function buildQuery(forExport = false) {
        const params = new URLSearchParams();
        params.set('action', forExport ? 'admin_export_csv' : 'admin_list');

        if (!forExport) {
            params.set('page', String(state.page));
            params.set('per_page', String(state.perPage));
        }

        Object.entries(state.filters).forEach(([key, value]) => {
            if (value !== null && value !== undefined && String(value).trim() !== '') {
                params.set(key, String(value).trim());
            }
        });

        return params;
    }

    function updateExportHref() {
        refs.export.href = ep('/api/v1/audit.php?' + buildQuery(true).toString());
    }

    function renderRows(rows) {
        if (!rows.length) {
            refs.rows.innerHTML = '<tr><td colspan="7" class="py-6 text-center text-gray-500">Nenhum registro encontrado.</td></tr>';
            return;
        }

        refs.rows.innerHTML = rows.map((row) => {
            const userLabel = row.user_nome
                ? `${esc(row.user_nome)}${row.user_email ? ` <span class="text-xs text-gray-500">(${esc(row.user_email)})</span>` : ''}`
                : (row.user_id ? `#${esc(row.user_id)}` : '-');
            return `
                <tr class="border-b border-gray-100">
                    <td class="py-2 pr-3">${esc(row.created_at)}</td>
                    <td class="py-2 pr-3">${userLabel}</td>
                    <td class="py-2 pr-3">${esc(row.modulo)}</td>
                    <td class="py-2 pr-3">${esc(row.acao)}</td>
                    <td class="py-2 pr-3">${esc(row.entidade || '')}</td>
                    <td class="py-2 pr-3">${esc(row.entidade_id || '')}</td>
                    <td class="py-2 pr-3">${esc(row.ip || '')}</td>
                </tr>
            `;
        }).join('');
    }

    async function load() {
        setMsg('Carregando auditoria...');
        refs.apply.disabled = true;
        refs.prev.disabled = true;
        refs.next.disabled = true;
        updateExportHref();

        try {
            const data = await window.anatejeApi(ep('/api/v1/audit.php?' + buildQuery(false).toString()));
            const logs = data.logs || [];
            const pagination = data.pagination || {};

            state.total = Number(pagination.total || 0);
            state.totalPages = Math.max(1, Number(pagination.total_pages || 1));
            state.page = Math.max(1, Number(pagination.page || state.page));

            renderRows(logs);
            refs.counter.textContent = `${state.total} registros`;
            refs.page.textContent = `Pagina ${state.page} de ${state.totalPages}`;
            refs.prev.disabled = state.page <= 1;
            refs.next.disabled = state.page >= state.totalPages;
            setMsg(logs.length ? '' : 'Nenhum resultado para os filtros atuais.', logs.length ? 'ok' : 'info');
        } catch (err) {
            refs.rows.innerHTML = '<tr><td colspan="7" class="py-6 text-center text-red-600">Falha ao carregar dados.</td></tr>';
            refs.counter.textContent = '';
            refs.page.textContent = '';
            setMsg(err.message || 'Falha ao carregar auditoria', 'err');
        } finally {
            refs.apply.disabled = false;
            updateExportHref();
        }
    }

    function applyFilters() {
        readFiltersFromUI();
        state.page = 1;
        load();
    }

    function clearFilters() {
        state.filters = {
            module: '',
            operation: '',
            entity: '',
            user_id: '',
            date_from: '',
            date_to: '',
            q: '',
        };
        state.page = 1;
        writeFiltersToUI();
        load();
    }

    refs.apply.addEventListener('click', applyFilters);
    refs.clear.addEventListener('click', clearFilters);
    refs.saveFilters.addEventListener('click', saveCurrentFilters);
    refs.loadFilters.addEventListener('click', () => {
        loadSavedFiltersAndRefresh();
    });
    refs.export.addEventListener('click', (ev) => {
        if (!can('admin.auditoria.export')) {
            ev.preventDefault();
            setMsg(deny('admin.auditoria.export'), 'err');
        }
    });
    refs.prev.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            load();
        }
    });
    refs.next.addEventListener('click', () => {
        if (state.page < state.totalPages) {
            state.page += 1;
            load();
        }
    });

    [refs.fModule, refs.fOperation, refs.fEntity, refs.fUserId, refs.fDateFrom, refs.fDateTo, refs.fQ]
        .forEach((el) => {
            el.addEventListener('keydown', (ev) => {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    applyFilters();
                }
            });
        });

    if (!can('admin.auditoria.export')) {
        refs.export.classList.add('hidden');
    }
    if (!can('admin.auditoria.view')) {
        refs.saveFilters.classList.add('hidden');
        refs.loadFilters.classList.add('hidden');
    }

    (async function bootstrap() {
        await fetchSavedFilters();
        writeFiltersToUI();
        await load();
    })();
})();
</script>
