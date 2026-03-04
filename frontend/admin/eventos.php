<?php
// Admin - Eventos

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
            'Admin - Eventos',
            'Cadastro, acesso por categoria, fila de espera e check-in.',
            [
                ['id' => 'novoEvento', 'label' => 'Novo Evento', 'class' => 'btn-primary px-4 py-2 text-sm'],
            ]
        );
        ?>

        <div class="mb-4 p-3 rounded border border-indigo-200 bg-indigo-50 flex flex-col md:flex-row md:items-center gap-2">
            <div class="text-xs text-gray-700" id="eventBulkMeta">Nenhum item selecionado</div>
            <div class="flex items-center gap-2 md:ml-auto">
                <select id="eventBulkStatus" class="input-primary text-sm">
                    <option value="published">Marcar como publicado</option>
                    <option value="draft">Marcar como rascunho</option>
                    <option value="archived">Marcar como arquivado</option>
                </select>
                <input id="eventBulkReason" class="input-primary text-sm" placeholder="Motivo (opcional)">
                <button id="eventBulkApply" class="btn-secondary px-3 py-2 text-xs">Aplicar em lote</button>
            </div>
        </div>

        <div class="overflow-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
                        <th class="py-2 pr-3"><input id="selectAllEventRows" type="checkbox"></th>
                        <th class="py-2 pr-3">ID</th>
                        <th class="py-2 pr-3">Titulo</th>
                        <th class="py-2 pr-3">Inicio</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Acesso</th>
                        <th class="py-2 pr-3">Vagas</th>
                        <th class="py-2 pr-3">Acoes</th>
                    </tr>
                </thead>
                <tbody id="eventRows"></tbody>
            </table>
        </div>

        <div id="regsBox" class="hidden mt-6 p-4 rounded-lg border border-blue-200 bg-blue-50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800">Inscricoes do Evento</h3>
                <div class="flex gap-2">
                    <button id="promoteWaitlist" class="btn-secondary px-3 py-1 text-xs">Promover fila</button>
                    <button id="exportRegsCsv" class="btn-secondary px-3 py-1 text-xs">Exportar CSV</button>
                    <button id="closeRegs" class="btn-secondary px-3 py-1 text-xs">Fechar</button>
                </div>
            </div>
            <div id="regsMeta" class="text-xs text-gray-700 mb-2"></div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3">
                <label>
                    <span class="text-xs text-gray-700">Status</span>
                    <select id="regs_status" class="input-primary w-full">
                        <option value="">Todos</option>
                        <option value="registered">registered</option>
                        <option value="waitlisted">waitlisted</option>
                        <option value="checked_in">checked_in</option>
                        <option value="canceled">canceled</option>
                    </select>
                </label>
                <label>
                    <span class="text-xs text-gray-700">Categoria</span>
                    <select id="regs_categoria" class="input-primary w-full">
                        <option value="">Todas</option>
                        <option value="PARCIAL">PARCIAL</option>
                        <option value="INTEGRAL">INTEGRAL</option>
                    </select>
                </label>
                <label class="md:col-span-2">
                    <span class="text-xs text-gray-700">Busca (nome/email/telefone)</span>
                    <div class="flex gap-2">
                        <input id="regs_q" class="input-primary w-full" placeholder="Buscar...">
                        <button id="regs_apply" type="button" class="btn-secondary px-3 py-1 text-xs">Aplicar</button>
                        <button id="regs_save_filter" type="button" class="btn-secondary px-3 py-1 text-xs">Salvar filtros</button>
                        <button id="regs_load_filter" type="button" class="btn-secondary px-3 py-1 text-xs">Usar salvos</button>
                    </div>
                </label>
            </div>
            <div class="max-h-72 overflow-auto border border-blue-200 rounded bg-white">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-gray-600 border-b">
                            <th class="py-2 px-2">Data</th>
                            <th class="py-2 px-2">Status</th>
                            <th class="py-2 px-2">Check-in</th>
                            <th class="py-2 px-2">Nome</th>
                            <th class="py-2 px-2">Email</th>
                            <th class="py-2 px-2">Categoria</th>
                            <th class="py-2 px-2">Acoes</th>
                        </tr>
                    </thead>
                    <tbody id="regsRows"></tbody>
                </table>
            </div>
            <div class="flex items-center justify-between mt-3">
                <div id="regsPageMeta" class="text-xs text-gray-700"></div>
                <div class="flex gap-2">
                    <button id="regsPrev" class="btn-secondary px-3 py-1 text-xs">Anterior</button>
                    <button id="regsNext" class="btn-secondary px-3 py-1 text-xs">Proxima</button>
                </div>
            </div>
        </div>

        <p id="eventMsg" class="text-sm mt-4"></p>
    </div>
</div>
<div id="eventModal" class="hidden fixed inset-0 z-50 admin-modal">
    <div class="absolute inset-0 admin-modal-overlay" data-close-event-modal="1"></div>
    <div class="relative flex min-h-screen items-center justify-center p-4">
        <div id="eventModalPanel" class="w-full rounded-lg border border-gray-200 bg-white shadow-2xl admin-modal-panel">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p id="eventModalMode" class="admin-modal-mode">Criacao</p>
                        <h3 id="eventModalTitle" class="admin-modal-title">Novo Evento</h3>
                        <p class="admin-modal-subtitle">Configure dados, regras de acesso e operacao do evento.</p>
                    </div>
                    <button id="closeEventModal" type="button" class="btn-secondary px-3 py-2 text-xs">Fechar</button>
                </div>
            </div>
            <form id="eventForm" class="space-y-4 p-6">
                <input id="event_id" type="hidden">
                <section class="admin-modal-section">
                    <h4 class="mb-3 text-sm font-semibold text-gray-800">Informacoes do Evento</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 admin-modal-grid">
                        <label>
                            <span class="text-sm font-medium text-gray-700">Titulo <span class="text-red-600">*</span></span>
                            <input id="event_titulo" class="input-primary w-full" required>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Local</span>
                            <input id="event_local" class="input-primary w-full">
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Inicio <span class="text-red-600">*</span></span>
                            <input id="event_inicio" type="datetime-local" class="input-primary w-full" required>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Fim</span>
                            <input id="event_fim" type="datetime-local" class="input-primary w-full">
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Vagas</span>
                            <input id="event_vagas" type="number" class="input-primary w-full">
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Status</span>
                            <select id="event_status" class="input-primary w-full">
                                <option value="draft">Rascunho</option>
                                <option value="published">Publicado</option>
                                <option value="archived">Arquivado</option>
                            </select>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Imagem URL</span>
                            <input id="event_imagem" class="input-primary w-full">
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Link Externo</span>
                            <input id="event_link" class="input-primary w-full">
                        </label>
                        <label class="md:col-span-2">
                            <span class="text-sm font-medium text-gray-700">Descricao</span>
                            <textarea id="event_descricao" class="input-primary w-full" rows="3"></textarea>
                        </label>
                    </div>
                </section>
                <section class="admin-modal-section">
                    <h4 class="mb-3 text-sm font-semibold text-gray-800">Acesso e Operacao</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 admin-modal-grid">
                        <label>
                            <span class="text-sm font-medium text-gray-700">Acesso por categoria</span>
                            <select id="event_access_scope" class="input-primary w-full">
                                <option value="ALL">Todos associados</option>
                                <option value="PARCIAL">Somente PARCIAL</option>
                                <option value="INTEGRAL">Somente INTEGRAL</option>
                            </select>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Max fila espera</span>
                            <input id="event_max_waitlist" type="number" class="input-primary w-full" placeholder="Vazio = ilimitado">
                        </label>
                        <label class="flex items-center gap-2 pt-6">
                            <input id="event_waitlist_enabled" type="checkbox" checked>
                            <span class="text-sm text-gray-700">Fila de espera ativa</span>
                        </label>
                        <label class="flex items-center gap-2 pt-6">
                            <input id="event_checkin_enabled" type="checkbox" checked>
                            <span class="text-sm text-gray-700">Check-in habilitado</span>
                        </label>
                    </div>
                </section>
                <div class="admin-modal-footer">
                    <p class="text-xs text-gray-500">Campos com <span class="text-red-600">*</span> sao obrigatorios.</p>
                    <div class="flex gap-2">
                        <button class="btn-secondary px-4 py-2 text-sm" type="button" id="cancelEvento">Cancelar</button>
                        <button id="eventSubmitBtn" class="btn-primary px-4 py-2 text-sm" type="submit">Salvar evento</button>
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
    const FILTERS_MODULE_EVENTS_REGS = 'admin.eventos.registrations';
    const perms = window.anatejePerms || null;
    const can = (code) => !perms || typeof perms.can !== 'function' ? true : perms.can(code);
    const deny = (code) => perms && typeof perms.denyMessage === 'function'
        ? perms.denyMessage(code)
        : 'Acesso negado para esta acao.';
    const canBulkEdit = can('admin.eventos.edit');
    const ui = window.anatejeUi || null;

    const rows = document.getElementById('eventRows');
    const msg = document.getElementById('eventMsg');
    const form = document.getElementById('eventForm');
    const eventModal = document.getElementById('eventModal');
    const eventModalPanel = document.getElementById('eventModalPanel');
    const eventModalTitle = document.getElementById('eventModalTitle');
    const eventModalMode = document.getElementById('eventModalMode');
    const eventSubmitBtn = document.getElementById('eventSubmitBtn');
    const eventBulkMeta = document.getElementById('eventBulkMeta');
    const eventBulkStatus = document.getElementById('eventBulkStatus');
    const eventBulkReason = document.getElementById('eventBulkReason');
    const eventBulkApply = document.getElementById('eventBulkApply');
    const selectAllEventRows = document.getElementById('selectAllEventRows');
    const regsBox = document.getElementById('regsBox');
    const regsRows = document.getElementById('regsRows');
    const regsMeta = document.getElementById('regsMeta');
    const exportRegsCsvBtn = document.getElementById('exportRegsCsv');
    const promoteWaitlistBtn = document.getElementById('promoteWaitlist');
    const regsStatus = document.getElementById('regs_status');
    const regsCategoria = document.getElementById('regs_categoria');
    const regsQ = document.getElementById('regs_q');
    const regsApply = document.getElementById('regs_apply');
    const regsSaveFilter = document.getElementById('regs_save_filter');
    const regsLoadFilter = document.getElementById('regs_load_filter');
    const regsPageMeta = document.getElementById('regsPageMeta');
    const regsPrev = document.getElementById('regsPrev');
    const regsNext = document.getElementById('regsNext');
    const el = (id) => document.getElementById(id);

    let cache = [];
    const selectedIds = new Set();
    let savedRegsFilters = { status: '', categoria: '', q: '' };
    let activeEventId = 0;
    let regsState = {
        status: '',
        categoria: '',
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
        eventBulkMeta.textContent = count > 0
            ? `${count} item(ns) selecionado(s)`
            : 'Nenhum item selecionado';
        eventBulkApply.disabled = count === 0 || !canBulkEdit;
    }

    function normalizeRegsFilterPayload(raw) {
        const f = raw && typeof raw === 'object' ? raw : {};
        return {
            status: typeof f.status === 'string' ? f.status : '',
            categoria: typeof f.categoria === 'string' ? f.categoria : '',
            q: typeof f.q === 'string' ? f.q.trim() : ''
        };
    }

    async function fetchSavedRegsFilters() {
        if (!can('admin.eventos.view')) return;
        try {
            const params = new URLSearchParams();
            params.set('action', 'get');
            params.set('module', FILTERS_MODULE_EVENTS_REGS);
            params.set('key', 'default');
            const data = await window.anatejeApi(ep('/api/v1/filters.php?' + params.toString()));
            if (data && data.found && data.filters && typeof data.filters === 'object') {
                savedRegsFilters = normalizeRegsFilterPayload(data.filters);
            } else {
                savedRegsFilters = { status: '', categoria: '', q: '' };
            }
        } catch (err) {
            savedRegsFilters = { status: '', categoria: '', q: '' };
        }
    }

    function dtToInput(v) {
        if (!v) return '';
        return String(v).replace(' ', 'T').slice(0, 16);
    }

    function dtLabel(v) {
        if (!v) return '-';
        const d = new Date(String(v).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return v;
        return d.toLocaleString('pt-BR');
    }

    function accessLabel(scope) {
        const s = String(scope || 'ALL').toUpperCase();
        if (s === 'PARCIAL') return 'PARCIAL';
        if (s === 'INTEGRAL') return 'INTEGRAL';
        return 'ALL';
    }

    function render() {
        if (!cache.length) {
            rows.innerHTML = '<tr><td colspan="8" class="py-3 text-gray-500">Nenhum evento cadastrado.</td></tr>';
            selectAllEventRows.checked = false;
            updateBulkMeta();
            return;
        }

        const pageIds = cache.map((ev) => parseInt(ev.id, 10)).filter((id) => id > 0);
        selectAllEventRows.checked = pageIds.length > 0 && pageIds.every((id) => selectedIds.has(id));

        rows.innerHTML = cache.map((ev) => {
            const vagas = ev.vagas == null ? 'Ilimitadas' : String(ev.vagas);
            const rest = ev.vagas_restantes == null ? '-' : String(ev.vagas_restantes);
            return `
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-3">
                    <input type="checkbox" data-row-select="1" data-id="${ev.id}" ${selectedIds.has(parseInt(ev.id, 10)) ? 'checked' : ''} ${canBulkEdit ? '' : 'disabled'}>
                </td>
                <td class="py-2 pr-3">${ev.id}</td>
                <td class="py-2 pr-3">${ev.titulo}</td>
                <td class="py-2 pr-3">${dtLabel(ev.inicio_em)}</td>
                <td class="py-2 pr-3">${ev.status}</td>
                <td class="py-2 pr-3">${accessLabel(ev.access_scope)}</td>
                <td class="py-2 pr-3">${vagas} (livres: ${rest})</td>
                <td class="py-2 pr-3">
                    <div class="flex gap-2 flex-wrap">
                        ${can('admin.eventos.edit') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="edit" data-id="${ev.id}">Editar</button>` : ''}
                        ${can('admin.eventos.view') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="regs" data-id="${ev.id}">Inscritos</button>` : ''}
                        ${can('admin.eventos.export') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="csv" data-id="${ev.id}">CSV</button>` : ''}
                        ${can('admin.eventos.delete') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="delete" data-id="${ev.id}">Excluir</button>` : ''}
                    </div>
                </td>
            </tr>
        `;
        }).join('');

        rows.querySelectorAll('input[data-row-select="1"]').forEach((cb) => cb.addEventListener('change', (ev) => {
            const id = parseInt(ev.currentTarget.getAttribute('data-id'), 10);
            if (!id) return;
            if (ev.currentTarget.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
            const ids = cache.map((x) => parseInt(x.id, 10)).filter((x) => x > 0);
            selectAllEventRows.checked = ids.length > 0 && ids.every((x) => selectedIds.has(x));
            updateBulkMeta();
        }));
        rows.querySelectorAll('button[data-act]').forEach((btn) => btn.addEventListener('click', onAction));
        updateBulkMeta();
    }

    function openForm(ev) {
        const isEdit = !!(ev && ev.id);
        if (eventModalTitle) eventModalTitle.textContent = isEdit ? 'Editar Evento' : 'Novo Evento';
        if (eventModalMode) eventModalMode.textContent = isEdit ? 'Edicao' : 'Criacao';
        if (eventSubmitBtn) eventSubmitBtn.textContent = isEdit ? 'Salvar alteracoes' : 'Salvar evento';
        eventModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        el('event_id').value = ev?.id || '';
        el('event_titulo').value = ev?.titulo || '';
        el('event_local').value = ev?.local || '';
        el('event_inicio').value = dtToInput(ev?.inicio_em);
        el('event_fim').value = dtToInput(ev?.fim_em);
        el('event_vagas').value = ev?.vagas ?? '';
        el('event_status').value = ev?.status || 'draft';
        el('event_access_scope').value = ev?.access_scope || 'ALL';
        el('event_waitlist_enabled').checked = parseInt(ev?.waitlist_enabled ?? 1, 10) === 1;
        el('event_checkin_enabled').checked = parseInt(ev?.checkin_enabled ?? 1, 10) === 1;
        el('event_max_waitlist').value = ev?.max_waitlist ?? '';
        el('event_imagem').value = ev?.imagem_url || '';
        el('event_link').value = ev?.link || '';
        el('event_descricao').value = ev?.descricao || '';
        setTimeout(() => {
            const t = el('event_titulo');
            if (t && typeof t.focus === 'function') {
                t.focus();
                t.select();
            }
        }, 10);
    }

    function closeForm() {
        eventModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        form.reset();
        if (ui && typeof ui.clearFieldErrors === 'function') {
            ui.clearFieldErrors(form);
        }
        el('event_id').value = '';
        el('event_access_scope').value = 'ALL';
        el('event_waitlist_enabled').checked = true;
        el('event_checkin_enabled').checked = true;
        if (eventModalTitle) eventModalTitle.textContent = 'Novo Evento';
        if (eventModalMode) eventModalMode.textContent = 'Criacao';
        if (eventSubmitBtn) {
            eventSubmitBtn.disabled = false;
            eventSubmitBtn.textContent = 'Salvar evento';
        }
    }

    function actionButtonsForRegistration(r) {
        const id = parseInt(r.id, 10);
        const status = String(r.status || '');
        const list = [];

        if (status === 'waitlisted' && can('admin.eventos.waitlist')) {
            list.push(`<button class="btn-secondary px-2 py-1 text-[11px]" data-r-act="promote" data-id="${id}">Promover</button>`);
        }
        if (status === 'registered' && can('admin.eventos.checkin')) {
            list.push(`<button class="btn-secondary px-2 py-1 text-[11px]" data-r-act="checkin" data-id="${id}" data-check="1">Check-in</button>`);
        }
        if (status === 'checked_in' && can('admin.eventos.checkin')) {
            list.push(`<button class="btn-secondary px-2 py-1 text-[11px]" data-r-act="checkin" data-id="${id}" data-check="0">Desfazer</button>`);
        }
        if (status !== 'canceled' && can('admin.eventos.waitlist')) {
            list.push(`<button class="btn-secondary px-2 py-1 text-[11px]" data-r-act="cancel" data-id="${id}">Cancelar</button>`);
        }

        return list.join('');
    }

    function renderRegistrations(data, eventRow) {
        const registrations = data.registrations || [];
        const pagination = data.pagination || {};
        regsBox.classList.remove('hidden');
        activeEventId = parseInt(eventRow?.id || 0, 10) || 0;

        regsState.status = (data.filters && typeof data.filters.status === 'string') ? data.filters.status : '';
        regsState.categoria = (data.filters && typeof data.filters.categoria === 'string') ? data.filters.categoria : '';
        regsState.q = (data.filters && typeof data.filters.q === 'string') ? data.filters.q : '';
        regsState.page = parseInt(pagination.page || regsState.page || 1, 10) || 1;
        regsState.perPage = parseInt(pagination.per_page || regsState.perPage || 20, 10) || 20;
        regsState.totalPages = parseInt(pagination.total_pages || 1, 10) || 1;

        regsStatus.value = regsState.status;
        regsCategoria.value = regsState.categoria;
        regsQ.value = regsState.q;

        const total = parseInt(pagination.total || 0, 10) || 0;
        regsMeta.textContent = `Evento #${eventRow?.id || '-'} - ${eventRow?.titulo || '-'} | Registros: ${total}`;

        if (!registrations.length) {
            regsRows.innerHTML = '<tr><td colspan="7" class="py-2 px-2 text-gray-500">Sem inscritos para este evento.</td></tr>';
        } else {
            regsRows.innerHTML = registrations.map((r) => `
                <tr class="border-b border-blue-100">
                    <td class="py-2 px-2">${dtLabel(r.created_at)}</td>
                    <td class="py-2 px-2">${r.status || '-'}</td>
                    <td class="py-2 px-2">${dtLabel(r.checked_in_at)}</td>
                    <td class="py-2 px-2">${r.nome || '-'}</td>
                    <td class="py-2 px-2">${r.email_funcional || '-'}</td>
                    <td class="py-2 px-2">${r.categoria || '-'}</td>
                    <td class="py-2 px-2">
                        <div class="flex gap-1 flex-wrap">${actionButtonsForRegistration(r)}</div>
                    </td>
                </tr>
            `).join('');
        }

        regsRows.querySelectorAll('button[data-r-act]').forEach((btn) => btn.addEventListener('click', onRegistrationAction));

        regsPageMeta.textContent = `Pagina ${regsState.page}/${regsState.totalPages}`;
        regsPrev.disabled = regsState.page <= 1;
        regsNext.disabled = regsState.page >= regsState.totalPages;
    }

    async function loadRegistrations(eventId, eventRowRef) {
        const params = new URLSearchParams();
        params.set('action', 'admin_registrations');
        params.set('id', String(eventId));
        params.set('page', String(regsState.page));
        params.set('per_page', String(regsState.perPage));
        if (regsState.status) params.set('status', regsState.status);
        if (regsState.categoria) params.set('categoria', regsState.categoria);
        if (regsState.q) params.set('q', regsState.q);

        const data = await window.anatejeApi(ep('/api/v1/events.php?' + params.toString()));
        renderRegistrations(data, eventRowRef || { id: eventId, titulo: '-' });
    }

    async function saveCurrentRegsFilters() {
        if (!can('admin.eventos.view')) {
            setMsg(deny('admin.eventos.view'), 'err');
            return;
        }
        const payload = normalizeRegsFilterPayload({
            status: regsStatus.value || '',
            categoria: regsCategoria.value || '',
            q: regsQ.value || ''
        });

        try {
            await window.anatejeApi(ep('/api/v1/filters.php?action=save'), {
                method: 'POST',
                body: {
                    module: FILTERS_MODULE_EVENTS_REGS,
                    key: 'default',
                    filters: payload
                }
            });
            savedRegsFilters = payload;
            setMsg('Filtros de inscricoes salvos.', 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar filtros de inscricoes', 'err');
        }
    }

    async function applySavedRegsFilters(forceReloadActiveEvent) {
        await fetchSavedRegsFilters();
        regsState.status = savedRegsFilters.status || '';
        regsState.categoria = savedRegsFilters.categoria || '';
        regsState.q = savedRegsFilters.q || '';
        regsState.page = 1;
        regsStatus.value = regsState.status;
        regsCategoria.value = regsState.categoria;
        regsQ.value = regsState.q;

        if (forceReloadActiveEvent && activeEventId > 0) {
            const ref = cache.find((x) => parseInt(x.id, 10) === activeEventId);
            await loadRegistrations(activeEventId, ref || { id: activeEventId, titulo: '-' });
        }
    }

    async function onRegistrationAction(e) {
        const btn = e.currentTarget;
        const act = btn.getAttribute('data-r-act');
        const registrationId = parseInt(btn.getAttribute('data-id'), 10);
        const eventRow = cache.find((x) => parseInt(x.id, 10) === activeEventId) || { id: activeEventId, titulo: '-' };

        try {
            if (act === 'checkin') {
                if (!can('admin.eventos.checkin')) {
                    setMsg(deny('admin.eventos.checkin'), 'err');
                    return;
                }
                const checked = parseInt(btn.getAttribute('data-check') || '0', 10) === 1 ? 1 : 0;
                await window.anatejeApi(ep('/api/v1/events.php?action=admin_checkin'), {
                    method: 'POST',
                    body: { registration_id: registrationId, checked }
                });
                setMsg('Check-in atualizado.', 'ok');
                await loadRegistrations(activeEventId, eventRow);
                return;
            }

            if (act === 'cancel') {
                if (!can('admin.eventos.waitlist')) {
                    setMsg(deny('admin.eventos.waitlist'), 'err');
                    return;
                }
                const confirmed = ui && typeof ui.confirmAction === 'function'
                    ? await ui.confirmAction({
                        title: 'Cancelar inscricao',
                        text: 'Deseja cancelar esta inscricao?',
                        confirmText: 'Cancelar inscricao',
                        icon: 'warning',
                        danger: true
                    })
                    : false;
                if (!confirmed) return;
                await window.anatejeApi(ep('/api/v1/events.php?action=admin_registration_status'), {
                    method: 'POST',
                    body: { registration_id: registrationId, status: 'canceled' }
                });
                setMsg('Inscricao cancelada.', 'ok');
                await loadRegistrations(activeEventId, eventRow);
                await load();
                return;
            }

            if (act === 'promote') {
                if (!can('admin.eventos.waitlist')) {
                    setMsg(deny('admin.eventos.waitlist'), 'err');
                    return;
                }
                await window.anatejeApi(ep('/api/v1/events.php?action=admin_registration_status'), {
                    method: 'POST',
                    body: { registration_id: registrationId, status: 'registered' }
                });
                setMsg('Inscricao promovida para registrada.', 'ok');
                await loadRegistrations(activeEventId, eventRow);
                await load();
                return;
            }
        } catch (err) {
            setMsg(err.message || 'Falha ao executar acao na inscricao', 'err');
        }
    }

    async function onAction(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const act = btn.getAttribute('data-act');
        const row = cache.find((x) => parseInt(x.id, 10) === id);

        if (act === 'edit') {
            if (!can('admin.eventos.edit')) {
                setMsg(deny('admin.eventos.edit'), 'err');
                return;
            }
            openForm(row);
            return;
        }

        if (act === 'delete') {
            if (!can('admin.eventos.delete')) {
                setMsg(deny('admin.eventos.delete'), 'err');
                return;
            }
            const confirmed = ui && typeof ui.confirmDelete === 'function'
                ? await ui.confirmDelete('este evento')
                : false;
            if (!confirmed) return;
            try {
                await window.anatejeApi(ep('/api/v1/events.php?action=admin_delete'), {
                    method: 'POST',
                    body: { id }
                });
                setMsg('Evento excluido.', 'ok');
                selectedIds.delete(id);
                selectAllEventRows.checked = false;
                await load();
            } catch (err) {
                setMsg(err.message || 'Falha ao excluir evento', 'err');
            }
            return;
        }

        if (act === 'regs') {
            if (!can('admin.eventos.view')) {
                setMsg(deny('admin.eventos.view'), 'err');
                return;
            }
            try {
                regsState = {
                    ...regsState,
                    status: savedRegsFilters.status || '',
                    categoria: savedRegsFilters.categoria || '',
                    q: savedRegsFilters.q || '',
                    page: 1
                };
                await loadRegistrations(id, row || { id, titulo: '-' });
            } catch (err) {
                setMsg(err.message || 'Falha ao carregar inscritos', 'err');
            }
            return;
        }

        if (act === 'csv') {
            if (!can('admin.eventos.export')) {
                setMsg(deny('admin.eventos.export'), 'err');
                return;
            }
            window.location.href = ep('/api/v1/events.php?action=admin_export_csv&id=' + id);
            return;
        }
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/events.php?action=admin_list'));
            cache = data.events || [];
            const allowedIds = new Set(cache.map((ev) => parseInt(ev.id, 10)).filter((id) => id > 0));
            Array.from(selectedIds.values()).forEach((id) => {
                if (!allowedIds.has(id)) {
                    selectedIds.delete(id);
                }
            });
            render();
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar eventos', 'err');
        }
    }

    document.getElementById('novoEvento').addEventListener('click', () => {
        if (!can('admin.eventos.create')) {
            setMsg(deny('admin.eventos.create'), 'err');
            return;
        }
        openForm(null);
    });
    document.getElementById('cancelEvento').addEventListener('click', closeForm);
    document.getElementById('closeEventModal').addEventListener('click', closeForm);
    eventModal.addEventListener('click', (e) => {
        if (e.target && e.target.getAttribute('data-close-event-modal') === '1') {
            closeForm();
        }
    });
    if (eventModalPanel) {
        eventModalPanel.addEventListener('click', (e) => e.stopPropagation());
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !eventModal.classList.contains('hidden')) {
            closeForm();
        }
    });
    document.getElementById('closeRegs').addEventListener('click', () => {
        regsBox.classList.add('hidden');
        activeEventId = 0;
        regsRows.innerHTML = '';
        regsPageMeta.textContent = '';
    });
    selectAllEventRows.addEventListener('change', (e) => {
        if (!canBulkEdit) {
            e.currentTarget.checked = false;
            return;
        }
        const checked = !!e.currentTarget.checked;
        cache.forEach((ev) => {
            const id = parseInt(ev.id, 10);
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
    eventBulkApply.addEventListener('click', async () => {
        if (!can('admin.eventos.edit')) {
            setMsg(deny('admin.eventos.edit'), 'err');
            return;
        }
        const ids = Array.from(selectedIds.values()).filter((id) => Number.isInteger(id) && id > 0);
        if (!ids.length) {
            setMsg('Selecione ao menos um evento para acao em lote.', 'err');
            return;
        }
        const status = eventBulkStatus.value || 'published';
        const reason = (eventBulkReason.value || '').trim();
        const confirmed = ui && typeof ui.confirmAction === 'function'
            ? await ui.confirmAction({
                title: 'Confirmar lote',
                text: `Aplicar status ${status} para ${ids.length} evento(s)?`,
                confirmText: 'Aplicar'
            })
            : false;
        if (!confirmed) return;

        try {
            const data = await window.anatejeApi(ep('/api/v1/events.php?action=admin_bulk_status'), {
                method: 'POST',
                body: { ids, status, reason }
            });
            setMsg(
                `Lote concluido. Atualizados: ${data.updated || 0}, sem alteracao: ${data.unchanged || 0}, nao encontrados: ${data.not_found || 0}.`,
                'ok'
            );
            ids.forEach((id) => selectedIds.delete(id));
            selectAllEventRows.checked = false;
            updateBulkMeta();
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao aplicar acao em lote nos eventos', 'err');
        }
    });
    exportRegsCsvBtn.addEventListener('click', () => {
        if (!can('admin.eventos.export')) {
            setMsg(deny('admin.eventos.export'), 'err');
            return;
        }
        if (!activeEventId) return;
        const params = new URLSearchParams();
        params.set('action', 'admin_export_csv');
        params.set('id', String(activeEventId));
        if (regsState.status) params.set('status', regsState.status);
        if (regsState.categoria) params.set('categoria', regsState.categoria);
        if (regsState.q) params.set('q', regsState.q);
        window.location.href = ep('/api/v1/events.php?' + params.toString());
    });
    promoteWaitlistBtn.addEventListener('click', async () => {
        if (!can('admin.eventos.waitlist')) {
            setMsg(deny('admin.eventos.waitlist'), 'err');
            return;
        }
        if (!activeEventId) return;
        try {
            await window.anatejeApi(ep('/api/v1/events.php?action=admin_promote_waitlist'), {
                method: 'POST',
                body: { event_id: activeEventId }
            });
            setMsg('Fila de espera promovida.', 'ok');
            const ref = cache.find((x) => parseInt(x.id, 10) === activeEventId);
            await loadRegistrations(activeEventId, ref || { id: activeEventId, titulo: '-' });
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao promover fila', 'err');
        }
    });

    regsApply.addEventListener('click', async () => {
        if (!activeEventId) return;
        regsState.status = regsStatus.value || '';
        regsState.categoria = regsCategoria.value || '';
        regsState.q = (regsQ.value || '').trim();
        regsState.page = 1;
        await loadRegistrations(activeEventId, cache.find((x) => parseInt(x.id, 10) === activeEventId));
    });
    regsSaveFilter.addEventListener('click', saveCurrentRegsFilters);
    regsLoadFilter.addEventListener('click', async () => {
        await applySavedRegsFilters(true);
        if (!activeEventId) {
            setMsg('Filtros salvos carregados.', 'ok');
        }
    });

    regsPrev.addEventListener('click', async () => {
        if (!activeEventId || regsState.page <= 1) return;
        regsState.page -= 1;
        await loadRegistrations(activeEventId, cache.find((x) => parseInt(x.id, 10) === activeEventId));
    });

    regsNext.addEventListener('click', async () => {
        if (!activeEventId || regsState.page >= regsState.totalPages) return;
        regsState.page += 1;
        await loadRegistrations(activeEventId, cache.find((x) => parseInt(x.id, 10) === activeEventId));
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (ui && typeof ui.clearFieldErrors === 'function') {
            ui.clearFieldErrors(form);
        }
        const wasEdit = !!(el('event_id').value && parseInt(el('event_id').value, 10) > 0);
        if (eventSubmitBtn) {
            eventSubmitBtn.disabled = true;
            eventSubmitBtn.textContent = 'Salvando...';
        }

        try {
            const body = {
                id: el('event_id').value ? parseInt(el('event_id').value, 10) : 0,
                titulo: el('event_titulo').value,
                descricao: el('event_descricao').value,
                local: el('event_local').value,
                inicio_em: el('event_inicio').value,
                fim_em: el('event_fim').value,
                vagas: el('event_vagas').value,
                status: el('event_status').value,
                access_scope: el('event_access_scope').value,
                waitlist_enabled: el('event_waitlist_enabled').checked ? 1 : 0,
                checkin_enabled: el('event_checkin_enabled').checked ? 1 : 0,
                max_waitlist: el('event_max_waitlist').value,
                imagem_url: el('event_imagem').value,
                link: el('event_link').value
            };

            const code = body.id > 0 ? 'admin.eventos.edit' : 'admin.eventos.create';
            if (!can(code)) {
                setMsg(deny(code), 'err');
                return;
            }

            await window.anatejeApi(ep('/api/v1/events.php?action=admin_save'), { method: 'POST', body });
            setMsg('Evento salvo com sucesso.', 'ok');
            closeForm();
            await load();
        } catch (err) {
            if (ui && typeof ui.applyValidationError === 'function') {
                ui.applyValidationError(form, err, [
                    { pattern: /titulo/i, field: 'event_titulo' },
                    { pattern: /inicio/i, field: 'event_inicio' },
                    { pattern: /fim/i, field: 'event_fim' },
                    { pattern: /vagas/i, field: 'event_vagas' },
                    { pattern: /status/i, field: 'event_status' }
                ]);
            }
            setMsg(err.message || 'Falha ao salvar evento', 'err');
        } finally {
            if (eventSubmitBtn) {
                eventSubmitBtn.disabled = false;
                eventSubmitBtn.textContent = wasEdit ? 'Salvar alteracoes' : 'Salvar evento';
            }
        }
    });

    if (!can('admin.eventos.create')) {
        document.getElementById('novoEvento').classList.add('hidden');
    }
    if (!can('admin.eventos.waitlist')) {
        promoteWaitlistBtn.classList.add('hidden');
    }
    if (!can('admin.eventos.export')) {
        exportRegsCsvBtn.classList.add('hidden');
    }
    if (!can('admin.eventos.view')) {
        regsSaveFilter.classList.add('hidden');
        regsLoadFilter.classList.add('hidden');
    }
    if (!canBulkEdit) {
        selectAllEventRows.disabled = true;
        eventBulkStatus.disabled = true;
        eventBulkReason.disabled = true;
        eventBulkApply.disabled = true;
    }

    (async function bootstrap() {
        await applySavedRegsFilters(false);
        updateBulkMeta();
        await load();
    })();
})();
</script>
