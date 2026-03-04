<?php
// Admin - Beneficios

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
            'Admin - Beneficios',
            'Cadastro, elegibilidade e organizacao dos beneficios.',
            [
                ['id' => 'novoBeneficio', 'label' => 'Novo Beneficio', 'class' => 'btn-primary px-4 py-2 text-sm'],
            ]
        );
        ?>

        <div class="mb-4 p-3 rounded border border-indigo-200 bg-indigo-50 flex flex-col md:flex-row md:items-center gap-2">
            <div class="text-xs text-gray-700" id="benefBulkMeta">Nenhum item selecionado</div>
            <div class="flex items-center gap-2 md:ml-auto">
                <select id="benefBulkStatus" class="input-primary text-sm">
                    <option value="active">Marcar como ativo</option>
                    <option value="inactive">Marcar como inativo</option>
                </select>
                <input id="benefBulkReason" class="input-primary text-sm" placeholder="Motivo (opcional)">
                <button id="benefBulkApply" class="btn-secondary px-3 py-2 text-xs">Aplicar em lote</button>
            </div>
        </div>

        <div class="overflow-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
                        <th class="py-2 pr-3"><input id="selectAllBenefRows" type="checkbox"></th>
                        <th class="py-2 pr-3">ID</th>
                        <th class="py-2 pr-3">Nome</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Elegibilidade</th>
                        <th class="py-2 pr-3">Ordem</th>
                        <th class="py-2 pr-3">Acoes</th>
                    </tr>
                </thead>
                <tbody id="benefRows"></tbody>
            </table>
        </div>

        <div class="mb-6 p-4 rounded border border-emerald-200 bg-emerald-50 space-y-3">
            <h3 class="text-sm font-semibold text-gray-800">Vincular Beneficio em Lote</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <label>
                    <span class="text-sm font-medium text-gray-700">Beneficio</span>
                    <select id="link_benefit_id" class="input-primary w-full"></select>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Acao</span>
                    <select id="link_mode" class="input-primary w-full">
                        <option value="assign">Vincular/Ativar</option>
                        <option value="remove">Remover/Inativar</option>
                    </select>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Categoria</span>
                    <select id="link_filter_categoria" class="input-primary w-full">
                        <option value="">Todas</option>
                        <option value="PARCIAL">PARCIAL</option>
                        <option value="INTEGRAL">INTEGRAL</option>
                    </select>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Status</span>
                    <select id="link_filter_status" class="input-primary w-full">
                        <option value="">Todos</option>
                        <option value="ATIVO">ATIVO</option>
                        <option value="INATIVO">INATIVO</option>
                    </select>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">UF</span>
                    <input id="link_filter_uf" maxlength="2" class="input-primary w-full" placeholder="AM">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Busca</span>
                    <input id="link_filter_q" class="input-primary w-full" placeholder="Nome, CPF, registro associativo...">
                </label>
            </div>
            <div class="flex gap-2 flex-wrap">
                <button id="link_preview" type="button" class="btn-secondary px-4 py-2 text-sm">Prever publico</button>
                <button id="link_apply" type="button" class="btn-primary px-4 py-2 text-sm">Aplicar em lote</button>
            </div>
            <div id="linkMsg" class="text-xs text-gray-700"></div>
        </div>

        <p id="benefMsg" class="text-sm mt-4"></p>
    </div>
</div>
<div id="benefModal" class="hidden fixed inset-0 z-50 admin-modal">
    <div class="absolute inset-0 admin-modal-overlay" data-close-benef-modal="1"></div>
    <div class="relative flex min-h-screen items-center justify-center p-4">
        <div id="benefModalPanel" class="w-full rounded-lg border border-gray-200 bg-white shadow-2xl admin-modal-panel">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p id="benefModalMode" class="admin-modal-mode">Criacao</p>
                        <h3 id="benefModalTitle" class="admin-modal-title">Novo Beneficio</h3>
                        <p class="admin-modal-subtitle">Preencha os campos para cadastrar ou atualizar um beneficio.</p>
                    </div>
                    <button id="closeBenefModal" type="button" class="btn-secondary px-3 py-2 text-xs" aria-label="Fechar modal">
                        Fechar
                    </button>
                </div>
            </div>
            <form id="benefForm" class="space-y-4 p-6">
                <input id="benef_id" type="hidden">

                <section class="admin-modal-section">
                    <h4 class="mb-3 text-sm font-semibold text-gray-800">Informacoes do Beneficio</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 admin-modal-grid">
                        <label>
                            <span class="text-sm font-medium text-gray-700">Nome <span class="text-red-600">*</span></span>
                            <input id="benef_nome" class="input-primary w-full" required>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Link</span>
                            <input id="benef_link" class="input-primary w-full" placeholder="https://...">
                        </label>
                        <label class="md:col-span-2">
                            <span class="text-sm font-medium text-gray-700">Descricao</span>
                            <textarea id="benef_descricao" class="input-primary w-full" rows="3" placeholder="Detalhes do beneficio"></textarea>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Status</span>
                            <select id="benef_status" class="input-primary w-full">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Ordem</span>
                            <input id="benef_sort" type="number" class="input-primary w-full" value="0" min="0">
                        </label>
                    </div>
                </section>

                <section class="admin-modal-section">
                    <h4 class="mb-3 text-sm font-semibold text-gray-800">Elegibilidade</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 admin-modal-grid">
                        <label>
                            <span class="text-sm font-medium text-gray-700">Categoria elegivel</span>
                            <select id="benef_eligibility_categoria" class="input-primary w-full">
                                <option value="ALL">ALL</option>
                                <option value="PARCIAL">PARCIAL</option>
                                <option value="INTEGRAL">INTEGRAL</option>
                            </select>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-gray-700">Status elegivel</span>
                            <select id="benef_eligibility_member_status" class="input-primary w-full">
                                <option value="ALL">ALL</option>
                                <option value="ATIVO">ATIVO</option>
                                <option value="INATIVO">INATIVO</option>
                            </select>
                        </label>
                    </div>
                </section>

                <div class="admin-modal-footer">
                    <p class="text-xs text-gray-500">Campos com <span class="text-red-600">*</span> sao obrigatorios.</p>
                    <div class="flex gap-2">
                        <button class="btn-secondary px-4 py-2 text-sm" type="button" id="cancelBenef">Cancelar</button>
                        <button id="benefSubmitBtn" class="btn-primary px-4 py-2 text-sm" type="submit">Salvar beneficio</button>
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
    const perms = window.anatejePerms || null;
    const can = (code) => !perms || typeof perms.can !== 'function' ? true : perms.can(code);
    const deny = (code) => perms && typeof perms.denyMessage === 'function'
        ? perms.denyMessage(code)
        : 'Acesso negado para esta acao.';
    const canBulkEdit = can('admin.beneficios.edit');
    const ui = window.anatejeUi || null;

    const rows = document.getElementById('benefRows');
    const msg = document.getElementById('benefMsg');
    const form = document.getElementById('benefForm');
    const benefModal = document.getElementById('benefModal');
    const benefModalPanel = document.getElementById('benefModalPanel');
    const benefModalTitle = document.getElementById('benefModalTitle');
    const benefModalMode = document.getElementById('benefModalMode');
    const benefSubmitBtn = document.getElementById('benefSubmitBtn');
    const benefBulkMeta = document.getElementById('benefBulkMeta');
    const benefBulkStatus = document.getElementById('benefBulkStatus');
    const benefBulkReason = document.getElementById('benefBulkReason');
    const benefBulkApply = document.getElementById('benefBulkApply');
    const selectAllBenefRows = document.getElementById('selectAllBenefRows');
    const linkMsg = document.getElementById('linkMsg');

    let cache = [];
    const selectedIds = new Set();

    const el = (id) => document.getElementById(id);

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function updateBulkMeta() {
        const count = selectedIds.size;
        benefBulkMeta.textContent = count > 0
            ? `${count} item(ns) selecionado(s)`
            : 'Nenhum item selecionado';
        benefBulkApply.disabled = count === 0 || !canBulkEdit;
    }

    function setLinkMsg(text, type) {
        linkMsg.textContent = text || '';
        linkMsg.className = type === 'err'
            ? 'text-xs text-red-700'
            : type === 'ok'
                ? 'text-xs text-green-700'
                : 'text-xs text-gray-700';
    }

    function normalizeUfInput(v) {
        return (v || '').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 2);
    }

    function collectMemberLinkPayload() {
        return {
            benefit_id: parseInt(el('link_benefit_id').value || '0', 10) || 0,
            mode: el('link_mode').value || 'assign',
            filters: {
                categoria: el('link_filter_categoria').value || '',
                status: el('link_filter_status').value || '',
                uf: normalizeUfInput(el('link_filter_uf').value || ''),
                q: (el('link_filter_q').value || '').trim()
            }
        };
    }

    function renderLinkBenefitOptions() {
        const select = el('link_benefit_id');
        const current = parseInt(select.value || '0', 10) || 0;
        const opts = ['<option value="0">Selecione...</option>'];
        cache.forEach((b) => {
            const st = String(b.status || '');
            opts.push(`<option value="${b.id}">${b.nome} (${st})</option>`);
        });
        select.innerHTML = opts.join('');
        if (current > 0 && cache.some((b) => parseInt(b.id, 10) === current)) {
            select.value = String(current);
        } else if (cache.length > 0) {
            select.value = String(cache[0].id);
        }
    }

    function eligibilityLabel(b) {
        return `${b.eligibility_categoria || 'ALL'} | ${b.eligibility_member_status || 'ALL'}`;
    }

    function render() {
        if (!cache.length) {
            rows.innerHTML = '<tr><td colspan="7" class="py-3 text-gray-500">Nenhum beneficio cadastrado.</td></tr>';
            selectAllBenefRows.checked = false;
            updateBulkMeta();
            return;
        }

        const pageIds = cache.map((b) => parseInt(b.id, 10)).filter((id) => id > 0);
        selectAllBenefRows.checked = pageIds.length > 0 && pageIds.every((id) => selectedIds.has(id));

        rows.innerHTML = cache.map((b) => `
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-3">
                    <input type="checkbox" data-row-select="1" data-id="${b.id}" ${selectedIds.has(parseInt(b.id, 10)) ? 'checked' : ''} ${canBulkEdit ? '' : 'disabled'}>
                </td>
                <td class="py-2 pr-3">${b.id}</td>
                <td class="py-2 pr-3">${b.nome}</td>
                <td class="py-2 pr-3">${b.status}</td>
                <td class="py-2 pr-3">${eligibilityLabel(b)}</td>
                <td class="py-2 pr-3">${b.sort_order}</td>
                <td class="py-2 pr-3">
                    <div class="flex gap-2">
                        ${can('admin.beneficios.edit') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="edit" data-id="${b.id}">Editar</button>` : ''}
                        ${can('admin.beneficios.delete') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="delete" data-id="${b.id}">Excluir</button>` : ''}
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
            selectAllBenefRows.checked = ids.length > 0 && ids.every((x) => selectedIds.has(x));
            updateBulkMeta();
        }));
        rows.querySelectorAll('button[data-act]').forEach((btn) => btn.addEventListener('click', onAction));
        updateBulkMeta();
    }

    function openForm(data) {
        const isEdit = !!(data && data.id);
        if (benefModalTitle) benefModalTitle.textContent = isEdit ? 'Editar Beneficio' : 'Novo Beneficio';
        if (benefModalMode) benefModalMode.textContent = isEdit ? 'Edicao' : 'Criacao';
        if (benefSubmitBtn) benefSubmitBtn.textContent = isEdit ? 'Salvar alteracoes' : 'Salvar beneficio';

        benefModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        el('benef_id').value = data?.id || '';
        el('benef_nome').value = data?.nome || '';
        el('benef_link').value = data?.link || '';
        el('benef_descricao').value = data?.descricao || '';
        el('benef_status').value = data?.status || 'active';
        el('benef_sort').value = data?.sort_order ?? 0;
        el('benef_eligibility_categoria').value = data?.eligibility_categoria || 'ALL';
        el('benef_eligibility_member_status').value = data?.eligibility_member_status || 'ALL';

        setTimeout(() => {
            const nome = el('benef_nome');
            if (nome && typeof nome.focus === 'function') {
                nome.focus();
                nome.select();
            }
        }, 10);
    }

    function closeForm() {
        benefModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        form.reset();
        if (ui && typeof ui.clearFieldErrors === 'function') {
            ui.clearFieldErrors(form);
        }
        el('benef_id').value = '';
        el('benef_eligibility_categoria').value = 'ALL';
        el('benef_eligibility_member_status').value = 'ALL';
        if (benefModalTitle) benefModalTitle.textContent = 'Novo Beneficio';
        if (benefModalMode) benefModalMode.textContent = 'Criacao';
        if (benefSubmitBtn) {
            benefSubmitBtn.disabled = false;
            benefSubmitBtn.textContent = 'Salvar beneficio';
        }
    }

    async function onAction(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const act = btn.getAttribute('data-act');

        if (act === 'edit') {
            if (!can('admin.beneficios.edit')) {
                setMsg(deny('admin.beneficios.edit'), 'err');
                return;
            }
            const row = cache.find((b) => parseInt(b.id, 10) === id);
            openForm(row);
            return;
        }

        if (act === 'delete') {
            if (!can('admin.beneficios.delete')) {
                setMsg(deny('admin.beneficios.delete'), 'err');
                return;
            }
            const confirmed = ui && typeof ui.confirmDelete === 'function'
                ? await ui.confirmDelete('este beneficio')
                : false;
            if (!confirmed) return;
            try {
                await window.anatejeApi(ep('/api/v1/benefits.php?action=admin_delete'), {
                    method: 'POST',
                    body: { id }
                });
                setMsg('Beneficio excluido.', 'ok');
                selectedIds.delete(id);
                selectAllBenefRows.checked = false;
                await load();
            } catch (err) {
                setMsg(err.message || 'Falha ao excluir beneficio', 'err');
            }
        }
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/benefits.php?action=admin_list'));
            cache = data.benefits || [];
            const allowedIds = new Set(cache.map((b) => parseInt(b.id, 10)).filter((id) => id > 0));
            Array.from(selectedIds.values()).forEach((id) => {
                if (!allowedIds.has(id)) {
                    selectedIds.delete(id);
                }
            });
            render();
            renderLinkBenefitOptions();
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar beneficios', 'err');
        }
    }

    document.getElementById('novoBeneficio').addEventListener('click', () => {
        if (!can('admin.beneficios.create')) {
            setMsg(deny('admin.beneficios.create'), 'err');
            return;
        }
        openForm(null);
    });
    document.getElementById('cancelBenef').addEventListener('click', closeForm);
    document.getElementById('closeBenefModal').addEventListener('click', closeForm);
    benefModal.addEventListener('click', (e) => {
        if (e.target && e.target.getAttribute('data-close-benef-modal') === '1') {
            closeForm();
        }
    });
    if (benefModalPanel) {
        benefModalPanel.addEventListener('click', (e) => e.stopPropagation());
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !benefModal.classList.contains('hidden')) {
            closeForm();
        }
    });
    el('link_filter_uf').addEventListener('input', (e) => {
        e.currentTarget.value = normalizeUfInput(e.currentTarget.value || '');
    });
    el('link_preview').addEventListener('click', async () => {
        if (!can('admin.beneficios.view')) {
            setMsg(deny('admin.beneficios.view'), 'err');
            return;
        }
        const body = collectMemberLinkPayload();
        if (body.benefit_id <= 0) {
            setLinkMsg('Selecione um beneficio para prever o publico.', 'err');
            return;
        }
        try {
            const data = await window.anatejeApi(ep('/api/v1/benefits.php?action=admin_member_link_preview'), {
                method: 'POST',
                body
            });
            const sample = Array.isArray(data.sample) ? data.sample : [];
            const sampleNames = sample.map((x) => x.nome || ('#' + x.id)).slice(0, 5).join(', ');
            const text = `Publico estimado: ${data.total || 0}.` + (sampleNames ? ` Ex.: ${sampleNames}.` : '');
            setLinkMsg(text, 'ok');
        } catch (err) {
            setLinkMsg(err.message || 'Falha ao prever publico para vinculo', 'err');
        }
    });
    el('link_apply').addEventListener('click', async () => {
        if (!can('admin.beneficios.edit')) {
            setMsg(deny('admin.beneficios.edit'), 'err');
            return;
        }
        const body = collectMemberLinkPayload();
        if (body.benefit_id <= 0) {
            setLinkMsg('Selecione um beneficio para aplicar o lote.', 'err');
            return;
        }
        const modeLabel = body.mode === 'remove' ? 'remover/inativar' : 'vincular/ativar';
        const confirmed = ui && typeof ui.confirmAction === 'function'
            ? await ui.confirmAction({
                title: 'Confirmar lote de vinculo',
                text: `Confirma ${modeLabel} o beneficio para os associados filtrados?`,
                confirmText: 'Aplicar'
            })
            : false;
        if (!confirmed) return;

        try {
            const data = await window.anatejeApi(ep('/api/v1/benefits.php?action=admin_member_link_apply'), {
                method: 'POST',
                body
            });
            setLinkMsg(
                `Lote aplicado. Publico: ${data.matched || 0}, novos: ${data.assigned || 0}, reativados: ${data.reactivated || 0}, removidos: ${data.removed || 0}, sem alteracao: ${data.unchanged || 0}.`,
                'ok'
            );
        } catch (err) {
            setLinkMsg(err.message || 'Falha ao aplicar vinculo em lote', 'err');
        }
    });
    selectAllBenefRows.addEventListener('change', (e) => {
        if (!canBulkEdit) {
            e.currentTarget.checked = false;
            return;
        }
        const checked = !!e.currentTarget.checked;
        cache.forEach((b) => {
            const id = parseInt(b.id, 10);
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
    benefBulkApply.addEventListener('click', async () => {
        if (!can('admin.beneficios.edit')) {
            setMsg(deny('admin.beneficios.edit'), 'err');
            return;
        }
        const ids = Array.from(selectedIds.values()).filter((id) => Number.isInteger(id) && id > 0);
        if (!ids.length) {
            setMsg('Selecione ao menos um beneficio para acao em lote.', 'err');
            return;
        }
        const status = benefBulkStatus.value || 'active';
        const reason = (benefBulkReason.value || '').trim();
        const confirmed = ui && typeof ui.confirmAction === 'function'
            ? await ui.confirmAction({
                title: 'Confirmar lote',
                text: `Aplicar status ${status} para ${ids.length} beneficio(s)?`,
                confirmText: 'Aplicar'
            })
            : false;
        if (!confirmed) return;

        try {
            const data = await window.anatejeApi(ep('/api/v1/benefits.php?action=admin_bulk_status'), {
                method: 'POST',
                body: { ids, status, reason }
            });
            setMsg(
                `Lote concluido. Atualizados: ${data.updated || 0}, sem alteracao: ${data.unchanged || 0}, nao encontrados: ${data.not_found || 0}.`,
                'ok'
            );
            ids.forEach((id) => selectedIds.delete(id));
            selectAllBenefRows.checked = false;
            updateBulkMeta();
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao aplicar acao em lote nos beneficios', 'err');
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (ui && typeof ui.clearFieldErrors === 'function') {
            ui.clearFieldErrors(form);
        }
        if (benefSubmitBtn) {
            benefSubmitBtn.disabled = true;
            benefSubmitBtn.textContent = 'Salvando...';
        }

        try {
            const body = {
                id: el('benef_id').value ? parseInt(el('benef_id').value, 10) : 0,
                nome: el('benef_nome').value,
                descricao: el('benef_descricao').value,
                link: el('benef_link').value,
                status: el('benef_status').value,
                sort_order: parseInt(el('benef_sort').value || '0', 10),
                eligibility_categoria: el('benef_eligibility_categoria').value,
                eligibility_member_status: el('benef_eligibility_member_status').value
            };

            const code = body.id > 0 ? 'admin.beneficios.edit' : 'admin.beneficios.create';
            if (!can(code)) {
                setMsg(deny(code), 'err');
                return;
            }

            await window.anatejeApi(ep('/api/v1/benefits.php?action=admin_save'), { method: 'POST', body });
            setMsg('Beneficio salvo com sucesso.', 'ok');
            closeForm();
            await load();
        } catch (err) {
            if (ui && typeof ui.applyValidationError === 'function') {
                ui.applyValidationError(form, err, [
                    { pattern: /nome/i, field: 'benef_nome' },
                    { pattern: /link/i, field: 'benef_link' },
                    { pattern: /descricao/i, field: 'benef_descricao' }
                ]);
            }
            setMsg(err.message || 'Falha ao salvar beneficio', 'err');
        } finally {
            if (benefSubmitBtn) {
                benefSubmitBtn.disabled = false;
                benefSubmitBtn.textContent = el('benef_id').value ? 'Salvar alteracoes' : 'Salvar beneficio';
            }
        }
    });

    if (!can('admin.beneficios.create')) {
        document.getElementById('novoBeneficio').classList.add('hidden');
    }
    if (!canBulkEdit) {
        selectAllBenefRows.disabled = true;
        benefBulkStatus.disabled = true;
        benefBulkReason.disabled = true;
        benefBulkApply.disabled = true;
        el('link_mode').disabled = true;
        el('link_filter_categoria').disabled = true;
        el('link_filter_status').disabled = true;
        el('link_filter_uf').disabled = true;
        el('link_filter_q').disabled = true;
        el('link_apply').disabled = true;
    }
    if (!can('admin.beneficios.view')) {
        el('link_preview').disabled = true;
    }

    updateBulkMeta();
    load();
})();
</script>
