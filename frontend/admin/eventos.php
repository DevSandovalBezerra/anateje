<?php
// Admin - Eventos

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
                <h1 class="text-2xl font-bold text-gray-800">Admin - Eventos</h1>
                <p class="text-gray-600">Cadastro e publicacao de eventos.</p>
            </div>
            <button id="novoEvento" class="btn-primary px-4 py-2 text-sm">Novo Evento</button>
        </div>

        <div class="overflow-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
                        <th class="py-2 pr-3">ID</th>
                        <th class="py-2 pr-3">Titulo</th>
                        <th class="py-2 pr-3">Inicio</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Vagas</th>
                        <th class="py-2 pr-3">Acoes</th>
                    </tr>
                </thead>
                <tbody id="eventRows"></tbody>
            </table>
        </div>

        <form id="eventForm" class="hidden p-4 rounded-lg border border-gray-200 bg-gray-50 space-y-3">
            <input id="event_id" type="hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label>
                    <span class="text-sm font-medium text-gray-700">Titulo *</span>
                    <input id="event_titulo" class="input-primary w-full" required>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Local</span>
                    <input id="event_local" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Inicio *</span>
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
            <div class="flex gap-2">
                <button class="btn-primary px-4 py-2 text-sm" type="submit">Salvar</button>
                <button class="btn-secondary px-4 py-2 text-sm" type="button" id="cancelEvento">Cancelar</button>
            </div>
        </form>

        <div id="regsBox" class="hidden mt-6 p-4 rounded-lg border border-blue-200 bg-blue-50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800">Inscritos no Evento</h3>
                <div class="flex gap-2">
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
                    </div>
                </label>
            </div>
            <div class="max-h-64 overflow-auto border border-blue-200 rounded bg-white">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-gray-600 border-b">
                            <th class="py-2 px-2">Data</th>
                            <th class="py-2 px-2">Status</th>
                            <th class="py-2 px-2">Nome</th>
                            <th class="py-2 px-2">Email</th>
                            <th class="py-2 px-2">Telefone</th>
                            <th class="py-2 px-2">Categoria</th>
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

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;

    const rows = document.getElementById('eventRows');
    const msg = document.getElementById('eventMsg');
    const form = document.getElementById('eventForm');
    const regsBox = document.getElementById('regsBox');
    const regsRows = document.getElementById('regsRows');
    const regsMeta = document.getElementById('regsMeta');
    const exportRegsCsvBtn = document.getElementById('exportRegsCsv');
    const regsStatus = document.getElementById('regs_status');
    const regsCategoria = document.getElementById('regs_categoria');
    const regsQ = document.getElementById('regs_q');
    const regsApply = document.getElementById('regs_apply');
    const regsPageMeta = document.getElementById('regsPageMeta');
    const regsPrev = document.getElementById('regsPrev');
    const regsNext = document.getElementById('regsNext');
    const el = (id) => document.getElementById(id);

    let cache = [];
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

    function render() {
        if (!cache.length) {
            rows.innerHTML = '<tr><td colspan="6" class="py-3 text-gray-500">Nenhum evento cadastrado.</td></tr>';
            return;
        }

        rows.innerHTML = cache.map((ev) => `
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-3">${ev.id}</td>
                <td class="py-2 pr-3">${ev.titulo}</td>
                <td class="py-2 pr-3">${dtLabel(ev.inicio_em)}</td>
                <td class="py-2 pr-3">${ev.status}</td>
                <td class="py-2 pr-3">${ev.vagas ?? '-'}</td>
                <td class="py-2 pr-3">
                    <div class="flex gap-2">
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="edit" data-id="${ev.id}">Editar</button>
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="regs" data-id="${ev.id}">Inscritos</button>
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="csv" data-id="${ev.id}">CSV</button>
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="delete" data-id="${ev.id}">Excluir</button>
                    </div>
                </td>
            </tr>
        `).join('');

        rows.querySelectorAll('button[data-act]').forEach((btn) => btn.addEventListener('click', onAction));
    }

    function openForm(ev) {
        form.classList.remove('hidden');
        el('event_id').value = ev?.id || '';
        el('event_titulo').value = ev?.titulo || '';
        el('event_local').value = ev?.local || '';
        el('event_inicio').value = dtToInput(ev?.inicio_em);
        el('event_fim').value = dtToInput(ev?.fim_em);
        el('event_vagas').value = ev?.vagas ?? '';
        el('event_status').value = ev?.status || 'draft';
        el('event_imagem').value = ev?.imagem_url || '';
        el('event_link').value = ev?.link || '';
        el('event_descricao').value = ev?.descricao || '';
    }

    function closeForm() {
        form.classList.add('hidden');
        form.reset();
        el('event_id').value = '';
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
            regsRows.innerHTML = '<tr><td colspan="6" class="py-2 px-2 text-gray-500">Sem inscritos para este evento.</td></tr>';
        } else {
            regsRows.innerHTML = registrations.map((r) => `
                <tr class="border-b border-blue-100">
                    <td class="py-2 px-2">${dtLabel(r.created_at)}</td>
                    <td class="py-2 px-2">${r.status || '-'}</td>
                    <td class="py-2 px-2">${r.nome || '-'}</td>
                    <td class="py-2 px-2">${r.email_funcional || '-'}</td>
                    <td class="py-2 px-2">${r.telefone || '-'}</td>
                    <td class="py-2 px-2">${r.categoria || '-'}</td>
                </tr>
            `).join('');
        }

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
        if (regsState.status) {
            params.set('status', regsState.status);
        }
        if (regsState.categoria) {
            params.set('categoria', regsState.categoria);
        }
        if (regsState.q) {
            params.set('q', regsState.q);
        }

        const data = await window.anatejeApi(ep('/api/v1/events.php?' + params.toString()));
        renderRegistrations(data, eventRowRef || { id: eventId, titulo: '-' });
    }

    async function onAction(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const act = btn.getAttribute('data-act');
        const row = cache.find((x) => parseInt(x.id, 10) === id);

        if (act === 'edit') {
            openForm(row);
            return;
        }

        if (act === 'delete') {
            if (!confirm('Deseja excluir este evento?')) return;
            try {
                await window.anatejeApi(ep('/api/v1/events.php?action=admin_delete&id=' + id));
                setMsg('Evento excluido.', 'ok');
                await load();
            } catch (err) {
                setMsg(err.message || 'Falha ao excluir evento', 'err');
            }
            return;
        }

        if (act === 'regs') {
            try {
                regsState = { ...regsState, status: '', categoria: '', q: '', page: 1 };
                await loadRegistrations(id, row || { id, titulo: '-' });
            } catch (err) {
                setMsg(err.message || 'Falha ao carregar inscritos', 'err');
            }
            return;
        }

        if (act === 'csv') {
            window.location.href = ep('/api/v1/events.php?action=admin_export_csv&id=' + id);
            return;
        }
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/events.php?action=admin_list'));
            cache = data.events || [];
            render();
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar eventos', 'err');
        }
    }

    document.getElementById('novoEvento').addEventListener('click', () => openForm(null));
    document.getElementById('cancelEvento').addEventListener('click', closeForm);
    document.getElementById('closeRegs').addEventListener('click', () => {
        regsBox.classList.add('hidden');
        activeEventId = 0;
        regsRows.innerHTML = '';
        regsPageMeta.textContent = '';
    });
    exportRegsCsvBtn.addEventListener('click', () => {
        if (!activeEventId) return;
        const params = new URLSearchParams();
        params.set('action', 'admin_export_csv');
        params.set('id', String(activeEventId));
        if (regsState.status) {
            params.set('status', regsState.status);
        }
        if (regsState.categoria) {
            params.set('categoria', regsState.categoria);
        }
        if (regsState.q) {
            params.set('q', regsState.q);
        }
        window.location.href = ep('/api/v1/events.php?' + params.toString());
    });

    regsApply.addEventListener('click', async () => {
        if (!activeEventId) return;
        regsState.status = regsStatus.value || '';
        regsState.categoria = regsCategoria.value || '';
        regsState.q = (regsQ.value || '').trim();
        regsState.page = 1;
        await loadRegistrations(activeEventId, cache.find((x) => parseInt(x.id, 10) === activeEventId));
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
                imagem_url: el('event_imagem').value,
                link: el('event_link').value
            };

            await window.anatejeApi(ep('/api/v1/events.php?action=admin_save'), { method: 'POST', body });
            setMsg('Evento salvo com sucesso.', 'ok');
            closeForm();
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar evento', 'err');
        }
    });

    load();
})();
</script>
