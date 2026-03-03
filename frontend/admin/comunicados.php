<?php
// Admin - Comunicados

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
            'Admin - Comunicados',
            'Criacao, agendamento e segmentacao de comunicados.',
            [
                ['id' => 'exportPosts', 'label' => 'Exportar CSV', 'class' => 'btn-secondary px-4 py-2 text-sm'],
                ['id' => 'novoPost', 'label' => 'Novo Comunicado', 'class' => 'btn-primary px-4 py-2 text-sm'],
            ]
        );
        ?>

        <div class="mb-4 p-3 rounded border border-indigo-200 bg-indigo-50 flex flex-col md:flex-row md:items-center gap-2">
            <div class="text-xs text-gray-700" id="postBulkMeta">Nenhum item selecionado</div>
            <div class="flex items-center gap-2 md:ml-auto">
                <select id="postBulkStatus" class="input-primary text-sm">
                    <option value="published">Marcar como publicado</option>
                    <option value="draft">Marcar como rascunho</option>
                    <option value="archived">Marcar como arquivado</option>
                </select>
                <input id="postBulkReason" class="input-primary text-sm" placeholder="Motivo (opcional)">
                <button id="postBulkApply" class="btn-secondary px-3 py-2 text-xs">Aplicar em lote</button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4">
            <label class="md:col-span-2">
                <span class="text-xs text-gray-700">Busca (titulo/segmento)</span>
                <input id="post_filter_q" class="input-primary w-full" placeholder="Buscar...">
            </label>
            <label>
                <span class="text-xs text-gray-700">Status</span>
                <select id="post_filter_status" class="input-primary w-full">
                    <option value="">Todos</option>
                    <option value="draft">draft</option>
                    <option value="published">published</option>
                    <option value="scheduled">scheduled</option>
                    <option value="archived">archived</option>
                </select>
            </label>
            <div class="flex items-end gap-2 flex-wrap">
                <button id="post_filter_apply" type="button" class="btn-secondary px-3 py-2 text-xs">Aplicar</button>
                <button id="post_filter_clear" type="button" class="btn-secondary px-3 py-2 text-xs">Limpar</button>
                <button id="post_filter_save" type="button" class="btn-secondary px-3 py-2 text-xs">Salvar filtros</button>
                <button id="post_filter_load" type="button" class="btn-secondary px-3 py-2 text-xs">Usar salvos</button>
            </div>
        </div>

        <div class="overflow-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
                        <th class="py-2 pr-3"><input id="selectAllPostRows" type="checkbox"></th>
                        <th class="py-2 pr-3">ID</th>
                        <th class="py-2 pr-3">Titulo</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Agendamento</th>
                        <th class="py-2 pr-3">Segmento</th>
                        <th class="py-2 pr-3">Acoes</th>
                    </tr>
                </thead>
                <tbody id="postRows"></tbody>
            </table>
        </div>

        <form id="postForm" class="hidden p-4 rounded-lg border border-gray-200 bg-gray-50 space-y-3">
            <input id="post_id" type="hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="md:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Titulo *</span>
                    <input id="post_titulo" class="input-primary w-full" required>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Slug (opcional)</span>
                    <input id="post_slug" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Status</span>
                    <select id="post_status" class="input-primary w-full">
                        <option value="draft">Rascunho</option>
                        <option value="published">Publicado</option>
                        <option value="archived">Arquivado</option>
                    </select>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Publicado em</span>
                    <input id="post_publicado" type="datetime-local" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Agendar para</span>
                    <input id="post_scheduled_for" type="datetime-local" class="input-primary w-full">
                </label>

                <label>
                    <span class="text-sm font-medium text-gray-700">Categoria alvo</span>
                    <select id="post_target_categoria" class="input-primary w-full">
                        <option value="ALL">ALL</option>
                        <option value="PARCIAL">PARCIAL</option>
                        <option value="INTEGRAL">INTEGRAL</option>
                    </select>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Status associado</span>
                    <select id="post_target_status" class="input-primary w-full">
                        <option value="ALL">ALL</option>
                        <option value="ATIVO">ATIVO</option>
                        <option value="INATIVO">INATIVO</option>
                    </select>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">UF</span>
                    <input id="post_target_uf" class="input-primary w-full" maxlength="2">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Lotacao</span>
                    <input id="post_target_lotacao" class="input-primary w-full">
                </label>

                <label class="md:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Conteudo</span>
                    <textarea id="post_conteudo" class="input-primary w-full" rows="6"></textarea>
                </label>
            </div>
            <div class="flex gap-2">
                <button class="btn-primary px-4 py-2 text-sm" type="submit">Salvar</button>
                <button class="btn-secondary px-4 py-2 text-sm" type="button" id="previewAudience">Prever publico</button>
                <button class="btn-secondary px-4 py-2 text-sm" type="button" id="cancelPost">Cancelar</button>
            </div>
            <p id="audienceMsg" class="text-xs text-gray-600"></p>
        </form>

        <p id="postMsg" class="text-sm mt-4"></p>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;
    const FILTERS_MODULE_POSTS_LIST = 'admin.comunicados.list';
    const perms = window.anatejePerms || null;
    const can = (code) => !perms || typeof perms.can !== 'function' ? true : perms.can(code);
    const deny = (code) => perms && typeof perms.denyMessage === 'function'
        ? perms.denyMessage(code)
        : 'Acesso negado para esta acao.';
    const canBulkEdit = can('admin.comunicados.edit');
    const ui = window.anatejeUi || null;

    const rows = document.getElementById('postRows');
    const msg = document.getElementById('postMsg');
    const audienceMsg = document.getElementById('audienceMsg');
    const form = document.getElementById('postForm');
    const postBulkMeta = document.getElementById('postBulkMeta');
    const postBulkStatus = document.getElementById('postBulkStatus');
    const postBulkReason = document.getElementById('postBulkReason');
    const postBulkApply = document.getElementById('postBulkApply');
    const selectAllPostRows = document.getElementById('selectAllPostRows');
    const postFilterQ = document.getElementById('post_filter_q');
    const postFilterStatus = document.getElementById('post_filter_status');
    const postFilterApply = document.getElementById('post_filter_apply');
    const postFilterClear = document.getElementById('post_filter_clear');
    const postFilterSave = document.getElementById('post_filter_save');
    const postFilterLoad = document.getElementById('post_filter_load');
    const el = (id) => document.getElementById(id);

    let cache = [];
    const selectedIds = new Set();
    let listFilters = { q: '', status: '' };
    let savedListFilters = { q: '', status: '' };

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function updateBulkMeta() {
        const count = selectedIds.size;
        postBulkMeta.textContent = count > 0
            ? `${count} item(ns) selecionado(s)`
            : 'Nenhum item selecionado';
        postBulkApply.disabled = count === 0 || !canBulkEdit;
    }

    function normalizePostListFilters(raw) {
        const f = raw && typeof raw === 'object' ? raw : {};
        const status = typeof f.status === 'string' ? f.status : '';
        const q = typeof f.q === 'string' ? f.q.trim() : '';
        const allowed = ['', 'draft', 'published', 'scheduled', 'archived'];
        return {
            status: allowed.includes(status) ? status : '',
            q: q
        };
    }

    function applyListFiltersToUi() {
        postFilterStatus.value = listFilters.status || '';
        postFilterQ.value = listFilters.q || '';
    }

    async function fetchSavedListFilters() {
        if (!can('admin.comunicados.view')) return;
        try {
            const params = new URLSearchParams();
            params.set('action', 'get');
            params.set('module', FILTERS_MODULE_POSTS_LIST);
            params.set('key', 'default');
            const data = await window.anatejeApi(ep('/api/v1/filters.php?' + params.toString()));
            if (data && data.found && data.filters && typeof data.filters === 'object') {
                savedListFilters = normalizePostListFilters(data.filters);
            } else {
                savedListFilters = { q: '', status: '' };
            }
        } catch (err) {
            savedListFilters = { q: '', status: '' };
        }
    }

    async function applySavedListFilters() {
        await fetchSavedListFilters();
        listFilters = { ...savedListFilters };
        applyListFiltersToUi();
        render();
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

    function segmentLabel(p) {
        const c = p.target_categoria || 'ALL';
        const s = p.target_status || 'ALL';
        const uf = (p.target_uf || '').trim();
        const lot = (p.target_lotacao || '').trim();
        return [c, s, uf || 'UF:*', lot || 'Lot:*'].join(' | ');
    }

    function currentStatusLabel(p) {
        const st = String(p.status || '');
        if (st !== 'published') return st;
        if (p.scheduled_for) {
            const at = new Date(String(p.scheduled_for).replace(' ', 'T'));
            if (!Number.isNaN(at.getTime()) && at.getTime() > Date.now()) {
                return 'scheduled';
            }
        }
        return st;
    }

    function render() {
        const baseList = cache.filter((p) => p.tipo === 'COMUNICADO');
        const q = (listFilters.q || '').toLowerCase();
        const list = baseList.filter((p) => {
            if (listFilters.status) {
                if (listFilters.status === 'scheduled') {
                    const sch = p.scheduled_for ? new Date(String(p.scheduled_for).replace(' ', 'T')) : null;
                    if (!sch || Number.isNaN(sch.getTime()) || sch.getTime() <= Date.now()) {
                        return false;
                    }
                    if (String(p.status || '') !== 'published') {
                        return false;
                    }
                } else if (String(p.status || '') !== listFilters.status) {
                    return false;
                }
            }

            if (q) {
                const text = `${p.titulo || ''} ${segmentLabel(p)}`.toLowerCase();
                if (text.indexOf(q) === -1) {
                    return false;
                }
            }
            return true;
        });

        if (!list.length) {
            rows.innerHTML = '<tr><td colspan="7" class="py-3 text-gray-500">Nenhum comunicado cadastrado.</td></tr>';
            selectAllPostRows.checked = false;
            updateBulkMeta();
            return;
        }

        const pageIds = list.map((p) => parseInt(p.id, 10)).filter((id) => id > 0);
        selectAllPostRows.checked = pageIds.length > 0 && pageIds.every((id) => selectedIds.has(id));

        rows.innerHTML = list.map((p) => `
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-3">
                    <input type="checkbox" data-row-select="1" data-id="${p.id}" ${selectedIds.has(parseInt(p.id, 10)) ? 'checked' : ''} ${canBulkEdit ? '' : 'disabled'}>
                </td>
                <td class="py-2 pr-3">${p.id}</td>
                <td class="py-2 pr-3">${p.titulo}</td>
                <td class="py-2 pr-3">${currentStatusLabel(p)}</td>
                <td class="py-2 pr-3">${dtLabel(p.scheduled_for || p.publicado_em)}</td>
                <td class="py-2 pr-3">${segmentLabel(p)}</td>
                <td class="py-2 pr-3">
                    <div class="flex gap-2">
                        ${can('admin.comunicados.edit') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="edit" data-id="${p.id}">Editar</button>` : ''}
                        ${can('admin.comunicados.delete') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="delete" data-id="${p.id}">Excluir</button>` : ''}
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
            const ids = list.map((p) => parseInt(p.id, 10)).filter((x) => x > 0);
            selectAllPostRows.checked = ids.length > 0 && ids.every((x) => selectedIds.has(x));
            updateBulkMeta();
        }));
        rows.querySelectorAll('button[data-act]').forEach((btn) => btn.addEventListener('click', onAction));
        updateBulkMeta();
    }

    function openForm(post) {
        form.classList.remove('hidden');
        audienceMsg.textContent = '';
        el('post_id').value = post?.id || '';
        el('post_titulo').value = post?.titulo || '';
        el('post_slug').value = post?.slug || '';
        el('post_status').value = post?.status || 'draft';
        el('post_publicado').value = dtToInput(post?.publicado_em);
        el('post_scheduled_for').value = dtToInput(post?.scheduled_for);
        el('post_target_categoria').value = post?.target_categoria || 'ALL';
        el('post_target_status').value = post?.target_status || 'ALL';
        el('post_target_uf').value = (post?.target_uf || '').toUpperCase();
        el('post_target_lotacao').value = post?.target_lotacao || '';
        el('post_conteudo').value = post?.conteudo || '';
    }

    function closeForm() {
        form.classList.add('hidden');
        form.reset();
        if (ui && typeof ui.clearFieldErrors === 'function') {
            ui.clearFieldErrors(form);
        }
        el('post_id').value = '';
        el('post_target_categoria').value = 'ALL';
        el('post_target_status').value = 'ALL';
        audienceMsg.textContent = '';
    }

    async function onAction(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const act = btn.getAttribute('data-act');

        if (act === 'edit') {
            if (!can('admin.comunicados.edit')) {
                setMsg(deny('admin.comunicados.edit'), 'err');
                return;
            }
            try {
                const data = await window.anatejeApi(ep('/api/v1/posts.php?action=admin_get&id=' + id));
                openForm(data.post || cache.find((x) => parseInt(x.id, 10) === id));
                if (typeof data.audience_estimate !== 'undefined' && data.audience_estimate !== null) {
                    audienceMsg.textContent = `Publico estimado: ${data.audience_estimate}`;
                }
            } catch (err) {
                setMsg(err.message || 'Falha ao carregar comunicado para edicao', 'err');
            }
            return;
        }

        if (act === 'delete') {
            if (!can('admin.comunicados.delete')) {
                setMsg(deny('admin.comunicados.delete'), 'err');
                return;
            }
            const confirmed = ui && typeof ui.confirmDelete === 'function'
                ? await ui.confirmDelete('este comunicado')
                : confirm('Deseja excluir este comunicado?');
            if (!confirmed) return;
            try {
                await window.anatejeApi(ep('/api/v1/posts.php?action=admin_delete'), {
                    method: 'POST',
                    body: { id }
                });
                setMsg('Comunicado excluido.', 'ok');
                selectedIds.delete(id);
                selectAllPostRows.checked = false;
                await load();
            } catch (err) {
                setMsg(err.message || 'Falha ao excluir comunicado', 'err');
            }
        }
    }

    function collectBody() {
        return {
            id: el('post_id').value ? parseInt(el('post_id').value, 10) : 0,
            tipo: 'COMUNICADO',
            titulo: el('post_titulo').value,
            slug: el('post_slug').value,
            conteudo: el('post_conteudo').value,
            status: el('post_status').value,
            publicado_em: el('post_publicado').value,
            scheduled_for: el('post_scheduled_for').value,
            target_categoria: el('post_target_categoria').value,
            target_status: el('post_target_status').value,
            target_uf: (el('post_target_uf').value || '').trim().toUpperCase(),
            target_lotacao: (el('post_target_lotacao').value || '').trim()
        };
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/posts.php?action=admin_list'));
            cache = data.posts || [];
            const allowedIds = new Set(
                cache
                    .filter((p) => String(p.tipo || '').toUpperCase() === 'COMUNICADO')
                    .map((p) => parseInt(p.id, 10))
                    .filter((id) => id > 0)
            );
            Array.from(selectedIds.values()).forEach((id) => {
                if (!allowedIds.has(id)) {
                    selectedIds.delete(id);
                }
            });
            render();
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar comunicados', 'err');
        }
    }

    async function saveCurrentListFilters() {
        if (!can('admin.comunicados.view')) {
            setMsg(deny('admin.comunicados.view'), 'err');
            return;
        }
        const payload = normalizePostListFilters({
            q: postFilterQ.value || '',
            status: postFilterStatus.value || ''
        });

        try {
            await window.anatejeApi(ep('/api/v1/filters.php?action=save'), {
                method: 'POST',
                body: {
                    module: FILTERS_MODULE_POSTS_LIST,
                    key: 'default',
                    filters: payload
                }
            });
            savedListFilters = payload;
            listFilters = { ...payload };
            applyListFiltersToUi();
            render();
            setMsg('Filtros de comunicados salvos.', 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar filtros de comunicados', 'err');
        }
    }

    document.getElementById('novoPost').addEventListener('click', () => {
        if (!can('admin.comunicados.create')) {
            setMsg(deny('admin.comunicados.create'), 'err');
            return;
        }
        openForm(null);
    });
    document.getElementById('exportPosts').addEventListener('click', () => {
        if (!can('admin.comunicados.export')) {
            setMsg(deny('admin.comunicados.export'), 'err');
            return;
        }
        const params = new URLSearchParams();
        params.set('action', 'admin_export_csv');
        params.set('type', 'COMUNICADO');
        if (listFilters.status) {
            params.set('status', listFilters.status);
        }
        if (listFilters.q) {
            params.set('q', listFilters.q);
        }
        window.location.href = ep('/api/v1/posts.php?' + params.toString());
    });
    document.getElementById('cancelPost').addEventListener('click', closeForm);
    postFilterApply.addEventListener('click', () => {
        listFilters = normalizePostListFilters({
            q: postFilterQ.value || '',
            status: postFilterStatus.value || ''
        });
        render();
    });
    postFilterClear.addEventListener('click', () => {
        listFilters = { q: '', status: '' };
        applyListFiltersToUi();
        render();
    });
    postFilterSave.addEventListener('click', saveCurrentListFilters);
    postFilterLoad.addEventListener('click', async () => {
        await applySavedListFilters();
        setMsg('Filtros salvos carregados.', 'ok');
    });
    selectAllPostRows.addEventListener('change', (e) => {
        if (!canBulkEdit) {
            e.currentTarget.checked = false;
            return;
        }
        const checked = !!e.currentTarget.checked;
        cache.filter((p) => String(p.tipo || '').toUpperCase() === 'COMUNICADO').forEach((p) => {
            const id = parseInt(p.id, 10);
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
    postBulkApply.addEventListener('click', async () => {
        if (!can('admin.comunicados.edit')) {
            setMsg(deny('admin.comunicados.edit'), 'err');
            return;
        }
        const ids = Array.from(selectedIds.values()).filter((id) => Number.isInteger(id) && id > 0);
        if (!ids.length) {
            setMsg('Selecione ao menos um comunicado para acao em lote.', 'err');
            return;
        }
        const status = postBulkStatus.value || 'published';
        if (status === 'published' && !can('admin.comunicados.publish')) {
            setMsg(deny('admin.comunicados.publish'), 'err');
            return;
        }
        const reason = (postBulkReason.value || '').trim();
        const confirmed = ui && typeof ui.confirmAction === 'function'
            ? await ui.confirmAction({
                title: 'Confirmar lote',
                text: `Aplicar status ${status} para ${ids.length} comunicado(s)?`,
                confirmText: 'Aplicar'
            })
            : confirm(`Aplicar status ${status} para ${ids.length} comunicado(s)?`);
        if (!confirmed) return;

        try {
            const data = await window.anatejeApi(ep('/api/v1/posts.php?action=admin_bulk_status'), {
                method: 'POST',
                body: { ids, status, reason, tipo: 'COMUNICADO' }
            });
            setMsg(
                `Lote concluido. Atualizados: ${data.updated || 0}, sem alteracao: ${data.unchanged || 0}, nao encontrados: ${data.not_found || 0}, ignorados por tipo: ${data.ignored_type || 0}.`,
                'ok'
            );
            ids.forEach((id) => selectedIds.delete(id));
            selectAllPostRows.checked = false;
            updateBulkMeta();
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao aplicar acao em lote nos comunicados', 'err');
        }
    });
    document.getElementById('previewAudience').addEventListener('click', async () => {
        if (!can('admin.comunicados.view')) {
            setMsg(deny('admin.comunicados.view'), 'err');
            return;
        }
        try {
            const data = await window.anatejeApi(ep('/api/v1/posts.php?action=admin_preview_audience'), {
                method: 'POST',
                body: collectBody()
            });
            audienceMsg.textContent = `Publico estimado: ${data.audience_estimate ?? 0}`;
        } catch (err) {
            setMsg(err.message || 'Falha ao calcular publico', 'err');
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (ui && typeof ui.clearFieldErrors === 'function') {
            ui.clearFieldErrors(form);
        }

        try {
            const body = collectBody();
            const code = body.id > 0 ? 'admin.comunicados.edit' : 'admin.comunicados.create';
            if (!can(code)) {
                setMsg(deny(code), 'err');
                return;
            }
            if (String(body.status || '').toLowerCase() === 'published' && !can('admin.comunicados.publish')) {
                setMsg(deny('admin.comunicados.publish'), 'err');
                return;
            }
            const data = await window.anatejeApi(ep('/api/v1/posts.php?action=admin_save'), { method: 'POST', body });
            let text = 'Comunicado salvo com sucesso.';
            if (typeof data.audience_estimate !== 'undefined' && data.audience_estimate !== null) {
                text += ` Publico estimado: ${data.audience_estimate}.`;
            }
            setMsg(text, 'ok');
            closeForm();
            await load();
        } catch (err) {
            if (ui && typeof ui.applyValidationError === 'function') {
                ui.applyValidationError(form, err, [
                    { pattern: /titulo/i, field: 'post_titulo' },
                    { pattern: /slug/i, field: 'post_slug' },
                    { pattern: /agendamento/i, field: 'post_scheduled_for' },
                    { pattern: /uf de segmentacao/i, field: 'post_target_uf' },
                    { pattern: /lotacao de segmentacao/i, field: 'post_target_lotacao' }
                ]);
            }
            setMsg(err.message || 'Falha ao salvar comunicado', 'err');
        }
    });

    if (!can('admin.comunicados.create')) {
        document.getElementById('novoPost').classList.add('hidden');
    }
    if (!can('admin.comunicados.export')) {
        document.getElementById('exportPosts').classList.add('hidden');
    }
    if (!can('admin.comunicados.view')) {
        document.getElementById('previewAudience').classList.add('hidden');
        postFilterSave.classList.add('hidden');
        postFilterLoad.classList.add('hidden');
    }
    if (!can('admin.comunicados.publish')) {
        const st = document.getElementById('post_status');
        const opt = st.querySelector('option[value="published"]');
        if (opt) {
            opt.disabled = true;
        }
    }
    if (!canBulkEdit) {
        selectAllPostRows.disabled = true;
        postBulkStatus.disabled = true;
        postBulkReason.disabled = true;
        postBulkApply.disabled = true;
    }

    (async function bootstrap() {
        await applySavedListFilters();
        updateBulkMeta();
        await load();
    })();
})();
</script>
