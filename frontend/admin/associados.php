<?php
// Admin - Associados

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
            'Admin - Associados',
            'Cadastro completo, status e operacao dos associados.',
            [
                ['id' => 'exportAssociados', 'label' => 'Exportar CSV', 'class' => 'btn-secondary px-4 py-2 text-sm'],
                ['id' => 'importAssociados', 'label' => 'Importar CSV', 'class' => 'btn-secondary px-4 py-2 text-sm'],
                ['id' => 'reloadAssociados', 'label' => 'Atualizar', 'class' => 'btn-secondary px-4 py-2 text-sm'],
                ['id' => 'novoAssociado', 'label' => 'Novo Associado', 'class' => 'btn-primary px-4 py-2 text-sm'],
            ],
            ['wrapper_class' => 'mb-4']
        );
        ?>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-5">
            <label class="md:col-span-2">
                <span class="text-sm font-medium text-gray-700">Busca</span>
                <input id="f_q" class="input-primary w-full" placeholder="Nome, CPF, registro associativo, email ou telefone">
            </label>
            <label>
                <span class="text-sm font-medium text-gray-700">Status</span>
                <select id="f_status" class="input-primary w-full">
                    <option value="">Todos</option>
                    <option value="ATIVO">ATIVO</option>
                    <option value="INATIVO">INATIVO</option>
                </select>
            </label>
            <label>
                <span class="text-sm font-medium text-gray-700">Categoria</span>
                <select id="f_categoria" class="input-primary w-full">
                    <option value="">Todas</option>
                    <option value="PARCIAL">PARCIAL</option>
                    <option value="INTEGRAL">INTEGRAL</option>
                </select>
            </label>
            <label>
                <span class="text-sm font-medium text-gray-700">UF</span>
                <input id="f_uf" class="input-primary w-full" maxlength="2" placeholder="AM">
            </label>
            <div class="flex items-end gap-2">
                <button id="applyFilters" class="btn-secondary px-4 py-2 text-sm">Aplicar</button>
                <button id="clearFilters" class="btn-secondary px-4 py-2 text-sm">Limpar</button>
                <button id="saveFilters" class="btn-secondary px-4 py-2 text-sm">Salvar filtros</button>
                <button id="loadFilters" class="btn-secondary px-4 py-2 text-sm">Usar salvos</button>
            </div>
        </div>

        <div class="mb-4 p-3 rounded border border-indigo-200 bg-indigo-50 flex flex-col md:flex-row md:items-center gap-2">
            <div class="text-xs text-gray-700" id="bulkMeta">Nenhum item selecionado</div>
            <div class="flex items-center gap-2 md:ml-auto">
                <select id="bulkStatus" class="input-primary text-sm">
                    <option value="ATIVO">Marcar como ATIVO</option>
                    <option value="INATIVO">Marcar como INATIVO</option>
                </select>
                <input id="bulkReason" class="input-primary text-sm" placeholder="Motivo (opcional)">
                <button id="bulkApply" class="btn-secondary px-3 py-2 text-xs">Aplicar em lote</button>
            </div>
        </div>

        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
                        <th class="py-2 pr-3"><input id="selectAllRows" type="checkbox"></th>
                        <th class="py-2 pr-3">ID</th>
                        <th class="py-2 pr-3">Nome</th>
                        <th class="py-2 pr-3">CPF</th>
                        <th class="py-2 pr-3">Categoria</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Contato</th>
                        <th class="py-2 pr-3">Acoes</th>
                    </tr>
                </thead>
                <tbody id="associadosRows"></tbody>
            </table>
        </div>

        <div class="flex items-center justify-between mt-4">
            <div id="associadosPageMeta" class="text-xs text-gray-700"></div>
            <div class="flex gap-2">
                <button id="associadosPrev" class="btn-secondary px-3 py-1 text-xs">Anterior</button>
                <button id="associadosNext" class="btn-secondary px-3 py-1 text-xs">Proxima</button>
            </div>
        </div>

        <div id="importBox" class="hidden mt-6 p-4 rounded-lg border border-indigo-200 bg-indigo-50 space-y-3">
            <div class="flex flex-col md:flex-row md:items-end gap-3">
                <label class="flex-1">
                    <span class="text-sm font-medium text-gray-700">Arquivo CSV</span>
                    <input id="importFile" type="file" accept=".csv,text/csv" class="input-primary w-full">
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input id="importUpsert" type="checkbox">
                    Atualizar existentes por CPF (upsert)
                </label>
                <div class="flex gap-2">
                    <button id="importPreview" type="button" class="btn-secondary px-4 py-2 text-sm">Preview</button>
                    <button id="importCommit" type="button" class="btn-primary px-4 py-2 text-sm">Importar</button>
                    <button id="importCancel" type="button" class="btn-secondary px-4 py-2 text-sm">Fechar</button>
                </div>
            </div>
            <p class="text-xs text-gray-600">
                Colunas obrigatorias: <code>nome</code>, <code>cpf</code>, <code>login_email</code>.
                Colunas opcionais: categoria, status, registro_associativo (matricula), telefone, email_funcional, cargo, lotacao,
                data_filiacao, contribuicao_mensal, cep, logradouro, numero, complemento, bairro, cidade, uf.
            </p>
            <div id="importSummary" class="text-xs text-gray-700"></div>
            <div class="max-h-60 overflow-auto border border-indigo-200 rounded bg-white">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-gray-600 border-b border-indigo-100">
                            <th class="py-2 px-2">Linha</th>
                            <th class="py-2 px-2">Nome</th>
                            <th class="py-2 px-2">CPF</th>
                            <th class="py-2 px-2">Email</th>
                            <th class="py-2 px-2">Status</th>
                            <th class="py-2 px-2">Mensagens</th>
                        </tr>
                    </thead>
                    <tbody id="importRows">
                        <tr><td colspan="6" class="py-2 px-2 text-gray-500">Selecione um arquivo para iniciar.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <form id="assocForm" class="hidden mt-6 p-4 rounded-lg border border-gray-200 bg-gray-50 space-y-4">
            <input id="assoc_id" type="hidden">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <label>
                    <span class="text-sm font-medium text-gray-700">Nome *</span>
                    <input id="assoc_nome" class="input-primary w-full" required>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">CPF *</span>
                    <input id="assoc_cpf" class="input-primary w-full" maxlength="14" required>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Registro Associativo</span>
                    <input id="assoc_matricula" class="input-primary w-full" maxlength="60">
                </label>

                <label>
                    <span class="text-sm font-medium text-gray-700">Email de acesso *</span>
                    <input id="assoc_login_email" type="email" class="input-primary w-full" required>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Nova senha</span>
                    <input id="assoc_nova_senha" type="text" class="input-primary w-full" placeholder="Opcional">
                </label>
                <label class="flex items-center gap-2 pt-6">
                    <input id="assoc_user_ativo" type="checkbox" checked>
                    <span class="text-sm text-gray-700">Usuario ativo</span>
                </label>

                <label>
                    <span class="text-sm font-medium text-gray-700">Categoria *</span>
                    <select id="assoc_categoria" class="input-primary w-full">
                        <option value="PARCIAL">PARCIAL</option>
                        <option value="INTEGRAL">INTEGRAL</option>
                    </select>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Status *</span>
                    <select id="assoc_status" class="input-primary w-full">
                        <option value="ATIVO">ATIVO</option>
                        <option value="INATIVO">INATIVO</option>
                    </select>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Data filiacao</span>
                    <input id="assoc_data_filiacao" type="date" class="input-primary w-full">
                </label>

                <label>
                    <span class="text-sm font-medium text-gray-700">Cargo</span>
                    <input id="assoc_cargo" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Lotacao</span>
                    <input id="assoc_lotacao" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Contribuicao mensal</span>
                    <input id="assoc_contribuicao" class="input-primary w-full" placeholder="0,00">
                </label>

                <label>
                    <span class="text-sm font-medium text-gray-700">Telefone</span>
                    <input id="assoc_telefone" class="input-primary w-full" maxlength="16">
                </label>
                <label class="md:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Email funcional</span>
                    <input id="assoc_email_funcional" type="email" class="input-primary w-full">
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <label>
                    <span class="text-sm font-medium text-gray-700">CEP</span>
                    <input id="assoc_cep" class="input-primary w-full" maxlength="9">
                </label>
                <label class="md:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Logradouro</span>
                    <input id="assoc_logradouro" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Numero</span>
                    <input id="assoc_numero" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Complemento</span>
                    <input id="assoc_complemento" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Bairro</span>
                    <input id="assoc_bairro" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Cidade</span>
                    <input id="assoc_cidade" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">UF</span>
                    <input id="assoc_uf" class="input-primary w-full" maxlength="2">
                </label>
            </div>

            <label class="block">
                <span class="text-sm font-medium text-gray-700">Motivo da alteracao de status</span>
                <input id="assoc_status_reason" class="input-primary w-full" placeholder="Opcional">
            </label>

            <div class="flex gap-2">
                <button class="btn-primary px-4 py-2 text-sm" type="submit">Salvar</button>
                <button class="btn-secondary px-4 py-2 text-sm" type="button" id="cancelAssoc">Cancelar</button>
            </div>
        </form>

        <div id="statusHistoryBox" class="hidden mt-6 p-4 rounded-lg border border-blue-200 bg-blue-50">
            <h3 class="text-sm font-semibold text-gray-800 mb-2">Historico de status</h3>
            <div id="statusHistoryList" class="text-xs text-gray-700 space-y-1"></div>
        </div>

        <p id="associadosMsg" class="text-sm mt-4"></p>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;
    const FILTERS_MODULE_MEMBERS_LIST = 'admin.associados.list';
    const perms = window.anatejePerms || null;
    const can = (code) => !perms || typeof perms.can !== 'function' ? true : perms.can(code);
    const deny = (code) => perms && typeof perms.denyMessage === 'function'
        ? perms.denyMessage(code)
        : 'Acesso negado para esta acao.';
    const canEditMembers = can('admin.associados.edit');
    const ui = window.anatejeUi || null;

    const rows = document.getElementById('associadosRows');
    const msg = document.getElementById('associadosMsg');
    const form = document.getElementById('assocForm');
    const pageMeta = document.getElementById('associadosPageMeta');
    const prevBtn = document.getElementById('associadosPrev');
    const nextBtn = document.getElementById('associadosNext');
    const historyBox = document.getElementById('statusHistoryBox');
    const historyList = document.getElementById('statusHistoryList');
    const importBox = document.getElementById('importBox');
    const importFile = document.getElementById('importFile');
    const importUpsert = document.getElementById('importUpsert');
    const importSummary = document.getElementById('importSummary');
    const importRows = document.getElementById('importRows');
    const bulkMeta = document.getElementById('bulkMeta');
    const bulkStatus = document.getElementById('bulkStatus');
    const bulkReason = document.getElementById('bulkReason');
    const bulkApplyBtn = document.getElementById('bulkApply');
    const selectAllRows = document.getElementById('selectAllRows');

    const el = (id) => document.getElementById(id);

    let cache = [];
    const selectedIds = new Set();
    let importCsvText = '';
    let importPreviewReady = false;
    let importPreviewUpsert = false;
    let state = {
        page: 1,
        perPage: 20,
        totalPages: 1,
        total: 0,
        q: '',
        status: '',
        categoria: '',
        uf: '',
        sort: 'id',
        order: 'desc'
    };
    let savedMembersFilters = { q: '', status: '', categoria: '', uf: '' };

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function maskCpf(cpf) {
        return window.anatejeMask.formatCpf(String(cpf || ''));
    }

    function fmtDate(v) {
        if (!v) return '-';
        try {
            const d = new Date(v);
            if (Number.isNaN(d.getTime())) return String(v);
            return d.toLocaleDateString('pt-BR');
        } catch (e) {
            return String(v || '-');
        }
    }

    function updateBulkMeta() {
        const count = selectedIds.size;
        bulkMeta.textContent = count > 0
            ? `${count} item(ns) selecionado(s)`
            : 'Nenhum item selecionado';
        bulkApplyBtn.disabled = count === 0 || !can('admin.associados.edit');
    }

    function render() {
        if (!cache.length) {
            rows.innerHTML = '<tr><td colspan="8" class="py-3 text-gray-500">Nenhum associado encontrado.</td></tr>';
            pageMeta.textContent = `Total: ${state.total} - Pagina ${state.page}/${state.totalPages}`;
            prevBtn.disabled = state.page <= 1;
            nextBtn.disabled = state.page >= state.totalPages;
            selectAllRows.checked = false;
            updateBulkMeta();
            return;
        }

        const pageIds = cache.map((m) => parseInt(m.id, 10)).filter((id) => id > 0);
        const allSelected = pageIds.length > 0 && pageIds.every((id) => selectedIds.has(id));
        selectAllRows.checked = allSelected;

        rows.innerHTML = cache.map((m) => `
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-3">
                    <input type="checkbox" data-row-select="1" data-id="${m.id}" ${selectedIds.has(parseInt(m.id, 10)) ? 'checked' : ''} ${canEditMembers ? '' : 'disabled'}>
                </td>
                <td class="py-2 pr-3">${m.id}</td>
                <td class="py-2 pr-3">
                    <div class="font-medium">${m.nome || '-'}</div>
                    <div class="text-xs text-gray-500">Reg.: ${m.matricula || '-'} | ${m.uf || '-'}</div>
                </td>
                <td class="py-2 pr-3">${maskCpf(m.cpf)}</td>
                <td class="py-2 pr-3">${m.categoria || '-'}</td>
                <td class="py-2 pr-3">${m.status || '-'}</td>
                <td class="py-2 pr-3">
                    <div>${m.login_email || '-'}</div>
                    <div class="text-xs text-gray-500">${m.telefone || m.email_funcional || '-'}</div>
                </td>
                <td class="py-2 pr-3">
                    <div class="flex flex-wrap gap-2">
                        ${can('admin.associados.edit') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="edit" data-id="${m.id}">Editar</button>` : ''}
                        ${can('admin.associados.edit') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="toggle" data-id="${m.id}" data-status="${m.status}">
                            ${m.status === 'ATIVO' ? 'Inativar' : 'Ativar'}
                        </button>` : ''}
                        ${can('admin.associados.delete') ? `<button class="btn-secondary px-2 py-1 text-xs" data-act="delete" data-id="${m.id}">Excluir</button>` : ''}
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
            const ids = cache.map((m) => parseInt(m.id, 10)).filter((x) => x > 0);
            selectAllRows.checked = ids.length > 0 && ids.every((x) => selectedIds.has(x));
            updateBulkMeta();
        }));
        rows.querySelectorAll('button[data-act]').forEach((btn) => btn.addEventListener('click', onAction));

        pageMeta.textContent = `Total: ${state.total} - Pagina ${state.page}/${state.totalPages}`;
        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= state.totalPages;
        updateBulkMeta();
    }

    function setImportSummary(text, type) {
        importSummary.textContent = text || '';
        importSummary.className = type === 'err'
            ? 'text-xs text-red-700'
            : type === 'ok'
                ? 'text-xs text-green-700'
                : 'text-xs text-gray-700';
    }

    function renderImportRows(rowsData) {
        const rows = Array.isArray(rowsData) ? rowsData : [];
        if (!rows.length) {
            importRows.innerHTML = '<tr><td colspan="6" class="py-2 px-2 text-gray-500">Sem linhas para exibir.</td></tr>';
            return;
        }

        importRows.innerHTML = rows.map((r) => {
            const status = String(r.status || '').toUpperCase();
            const statusClass = status.indexOf('READY') === 0
                ? 'text-green-700'
                : status === 'SKIP'
                    ? 'text-amber-700'
                    : 'text-red-700';
            const messages = Array.isArray(r.messages) ? r.messages.join(' | ') : '';
            return `
                <tr class="border-b border-indigo-100">
                    <td class="py-2 px-2">${r.line || '-'}</td>
                    <td class="py-2 px-2">${r.nome || '-'}</td>
                    <td class="py-2 px-2">${maskCpf(r.cpf || '')}</td>
                    <td class="py-2 px-2">${r.login_email || '-'}</td>
                    <td class="py-2 px-2 ${statusClass}">${status || '-'}</td>
                    <td class="py-2 px-2">${messages || '-'}</td>
                </tr>
            `;
        }).join('');
    }

    async function readImportFileText() {
        const file = importFile.files && importFile.files[0] ? importFile.files[0] : null;
        if (!file) {
            throw new Error('Selecione um arquivo CSV para importar.');
        }

        return await new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(String(reader.result || ''));
            reader.onerror = () => reject(new Error('Falha ao ler arquivo CSV.'));
            reader.readAsText(file, 'UTF-8');
        });
    }

    function clearForm() {
        form.reset();
        el('assoc_id').value = '';
        el('assoc_categoria').value = 'PARCIAL';
        el('assoc_status').value = 'ATIVO';
        el('assoc_user_ativo').checked = true;
        if (ui && typeof ui.clearFieldErrors === 'function') {
            ui.clearFieldErrors(form);
        }
        historyBox.classList.add('hidden');
        historyList.innerHTML = '';
    }

    function openForm() {
        form.classList.remove('hidden');
    }

    function closeForm() {
        form.classList.add('hidden');
        clearForm();
    }

    function fillForm(member, history) {
        openForm();
        el('assoc_id').value = member.id || '';
        el('assoc_nome').value = member.nome || '';
        el('assoc_cpf').value = maskCpf(member.cpf || '');
        el('assoc_matricula').value = member.matricula || '';
        el('assoc_login_email').value = member.login_email || '';
        el('assoc_nova_senha').value = '';
        el('assoc_user_ativo').checked = parseInt(member.user_ativo || 0, 10) === 1;
        el('assoc_categoria').value = member.categoria || 'PARCIAL';
        el('assoc_status').value = member.status || 'ATIVO';
        el('assoc_data_filiacao').value = member.data_filiacao || '';
        el('assoc_cargo').value = member.cargo || '';
        el('assoc_lotacao').value = member.lotacao || '';
        el('assoc_contribuicao').value = member.contribuicao_mensal || '';
        el('assoc_telefone').value = member.telefone ? window.anatejeMask.formatPhone(member.telefone) : '';
        el('assoc_email_funcional').value = member.email_funcional || '';

        const addr = member.address || {};
        el('assoc_cep').value = addr.cep ? window.anatejeMask.formatCep(addr.cep) : '';
        el('assoc_logradouro').value = addr.logradouro || '';
        el('assoc_numero').value = addr.numero || '';
        el('assoc_complemento').value = addr.complemento || '';
        el('assoc_bairro').value = addr.bairro || '';
        el('assoc_cidade').value = addr.cidade || '';
        el('assoc_uf').value = (addr.uf || '').toUpperCase();
        el('assoc_status_reason').value = '';

        if (!history || !history.length) {
            historyBox.classList.add('hidden');
            historyList.innerHTML = '';
            return;
        }

        historyBox.classList.remove('hidden');
        historyList.innerHTML = history.map((h) => `
            <div class="border-b border-blue-100 py-1">
                <span class="font-medium">${fmtDate(h.created_at)}</span> - ${h.old_status || '-'} -> ${h.new_status || '-'}
                <span class="text-gray-500">(${h.reason || 'sem motivo'})</span>
            </div>
        `).join('');
    }

    function readFiltersFromUi() {
        state.q = (el('f_q').value || '').trim();
        state.status = el('f_status').value || '';
        state.categoria = el('f_categoria').value || '';
        state.uf = (el('f_uf').value || '').trim().toUpperCase();
    }

    function applyFiltersToUi() {
        el('f_q').value = state.q;
        el('f_status').value = state.status;
        el('f_categoria').value = state.categoria;
        el('f_uf').value = state.uf;
    }

    function normalizeMembersFilters(raw) {
        const f = raw && typeof raw === 'object' ? raw : {};
        const status = typeof f.status === 'string' ? f.status : '';
        const categoria = typeof f.categoria === 'string' ? f.categoria : '';
        const uf = (typeof f.uf === 'string' ? f.uf : '').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 2);
        const q = typeof f.q === 'string' ? f.q.trim() : '';
        return {
            q,
            status: ['', 'ATIVO', 'INATIVO'].includes(status) ? status : '',
            categoria: ['', 'PARCIAL', 'INTEGRAL'].includes(categoria) ? categoria : '',
            uf
        };
    }

    async function fetchSavedMembersFilters() {
        if (!can('admin.associados.view')) return;
        try {
            const params = new URLSearchParams();
            params.set('action', 'get');
            params.set('module', FILTERS_MODULE_MEMBERS_LIST);
            params.set('key', 'default');
            const data = await window.anatejeApi(ep('/api/v1/filters.php?' + params.toString()));
            if (data && data.found && data.filters && typeof data.filters === 'object') {
                savedMembersFilters = normalizeMembersFilters(data.filters);
            } else {
                savedMembersFilters = { q: '', status: '', categoria: '', uf: '' };
            }
        } catch (err) {
            savedMembersFilters = { q: '', status: '', categoria: '', uf: '' };
        }
    }

    async function saveCurrentMembersFilters() {
        if (!can('admin.associados.view')) {
            setMsg(deny('admin.associados.view'), 'err');
            return;
        }
        readFiltersFromUi();
        const payload = normalizeMembersFilters({
            q: state.q,
            status: state.status,
            categoria: state.categoria,
            uf: state.uf
        });
        try {
            await window.anatejeApi(ep('/api/v1/filters.php?action=save'), {
                method: 'POST',
                body: {
                    module: FILTERS_MODULE_MEMBERS_LIST,
                    key: 'default',
                    filters: payload
                }
            });
            savedMembersFilters = payload;
            setMsg('Filtros de associados salvos.', 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar filtros de associados', 'err');
        }
    }

    async function applySavedMembersFilters() {
        await fetchSavedMembersFilters();
        state.q = savedMembersFilters.q || '';
        state.status = savedMembersFilters.status || '';
        state.categoria = savedMembersFilters.categoria || '';
        state.uf = savedMembersFilters.uf || '';
        applyFiltersToUi();
    }

    function buildQuery(extra = {}) {
        const params = new URLSearchParams();
        params.set('action', extra.action || 'admin_list');
        params.set('page', String(extra.page || state.page));
        params.set('per_page', String(state.perPage));
        params.set('sort', state.sort);
        params.set('order', state.order);
        if (state.q) params.set('q', state.q);
        if (state.status) params.set('status', state.status);
        if (state.categoria) params.set('categoria', state.categoria);
        if (state.uf) params.set('uf', state.uf);
        return params.toString();
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/members.php?' + buildQuery()));
            cache = data.members || [];
            const p = data.pagination || {};
            state.page = parseInt(p.page || state.page || 1, 10) || 1;
            state.perPage = parseInt(p.per_page || state.perPage || 20, 10) || 20;
            state.total = parseInt(p.total || 0, 10) || 0;
            state.totalPages = parseInt(p.total_pages || 1, 10) || 1;
            render();
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar associados', 'err');
        }
    }

    async function loadDetail(id) {
        const data = await window.anatejeApi(ep('/api/v1/members.php?action=admin_get&id=' + id));
        fillForm(data.member || {}, data.status_history || []);
    }

    async function onAction(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const act = btn.getAttribute('data-act');

        try {
            if (act === 'edit') {
                if (!can('admin.associados.edit')) {
                    setMsg(deny('admin.associados.edit'), 'err');
                    return;
                }
                await loadDetail(id);
                return;
            }

            if (act === 'toggle') {
                if (!can('admin.associados.edit')) {
                    setMsg(deny('admin.associados.edit'), 'err');
                    return;
                }
                const cur = btn.getAttribute('data-status');
                const next = cur === 'ATIVO' ? 'INATIVO' : 'ATIVO';
                const reason = prompt('Motivo da alteracao de status (opcional):', '') || '';
                await window.anatejeApi(ep('/api/v1/members.php?action=admin_save_status'), {
                    method: 'POST',
                    body: { id, status: next, reason }
                });
                setMsg('Status atualizado com sucesso.', 'ok');
                await load();
                return;
            }

            if (act === 'delete') {
                if (!can('admin.associados.delete')) {
                    setMsg(deny('admin.associados.delete'), 'err');
                    return;
                }
                const confirmed = ui && typeof ui.confirmDelete === 'function'
                    ? await ui.confirmDelete('este associado')
                    : confirm('Deseja excluir este associado?');
                if (!confirmed) return;
                await window.anatejeApi(ep('/api/v1/members.php?action=admin_delete'), {
                    method: 'POST',
                    body: { id }
                });
                setMsg('Associado excluido.', 'ok');
                await load();
                return;
            }
        } catch (err) {
            setMsg(err.message || 'Falha ao executar acao', 'err');
        }
    }

    async function lookupCep() {
        const digits = window.anatejeMask.onlyDigits(el('assoc_cep').value || '');
        if (digits.length !== 8) return;

        try {
            const resp = await fetch('https://viacep.com.br/ws/' + digits + '/json/');
            const data = await resp.json();
            if (data && !data.erro) {
                if (!el('assoc_logradouro').value) el('assoc_logradouro').value = data.logradouro || '';
                if (!el('assoc_bairro').value) el('assoc_bairro').value = data.bairro || '';
                if (!el('assoc_cidade').value) el('assoc_cidade').value = data.localidade || '';
                if (!el('assoc_uf').value) el('assoc_uf').value = (data.uf || '').toUpperCase();
            }
        } catch (e) {
            // sem bloqueio no fluxo principal
        }
    }

    function bindMasks() {
        el('assoc_cpf').addEventListener('input', (e) => {
            e.target.value = window.anatejeMask.formatCpf(e.target.value);
        });
        el('assoc_telefone').addEventListener('input', (e) => {
            e.target.value = window.anatejeMask.formatPhone(e.target.value);
        });
        el('assoc_cep').addEventListener('input', (e) => {
            e.target.value = window.anatejeMask.formatCep(e.target.value);
        });
        el('assoc_cep').addEventListener('blur', lookupCep);
    }

    function collectBody() {
        return {
            id: el('assoc_id').value ? parseInt(el('assoc_id').value, 10) : 0,
            nome: (el('assoc_nome').value || '').trim(),
            cpf: window.anatejeMask.onlyDigits(el('assoc_cpf').value || ''),
            matricula: (el('assoc_matricula').value || '').trim(),
            login_email: (el('assoc_login_email').value || '').trim().toLowerCase(),
            nova_senha: (el('assoc_nova_senha').value || '').trim(),
            user_ativo: el('assoc_user_ativo').checked ? 1 : 0,
            categoria: el('assoc_categoria').value || 'PARCIAL',
            status: el('assoc_status').value || 'ATIVO',
            data_filiacao: el('assoc_data_filiacao').value || '',
            cargo: (el('assoc_cargo').value || '').trim(),
            lotacao: (el('assoc_lotacao').value || '').trim(),
            contribuicao_mensal: (el('assoc_contribuicao').value || '').trim(),
            telefone: window.anatejeMask.onlyDigits(el('assoc_telefone').value || ''),
            email_funcional: (el('assoc_email_funcional').value || '').trim().toLowerCase(),
            status_reason: (el('assoc_status_reason').value || '').trim(),
            address: {
                cep: window.anatejeMask.onlyDigits(el('assoc_cep').value || ''),
                logradouro: (el('assoc_logradouro').value || '').trim(),
                numero: (el('assoc_numero').value || '').trim(),
                complemento: (el('assoc_complemento').value || '').trim(),
                bairro: (el('assoc_bairro').value || '').trim(),
                cidade: (el('assoc_cidade').value || '').trim(),
                uf: (el('assoc_uf').value || '').trim().toUpperCase()
            }
        };
    }

    document.getElementById('novoAssociado').addEventListener('click', () => {
        if (!can('admin.associados.create')) {
            setMsg(deny('admin.associados.create'), 'err');
            return;
        }
        clearForm();
        openForm();
    });

    document.getElementById('cancelAssoc').addEventListener('click', closeForm);

    document.getElementById('applyFilters').addEventListener('click', async () => {
        readFiltersFromUi();
        state.page = 1;
        await load();
    });

    document.getElementById('clearFilters').addEventListener('click', async () => {
        state.q = '';
        state.status = '';
        state.categoria = '';
        state.uf = '';
        state.page = 1;
        applyFiltersToUi();
        await load();
    });
    document.getElementById('saveFilters').addEventListener('click', saveCurrentMembersFilters);
    document.getElementById('loadFilters').addEventListener('click', async () => {
        await applySavedMembersFilters();
        state.page = 1;
        await load();
        setMsg('Filtros salvos carregados.', 'ok');
    });

    document.getElementById('reloadAssociados').addEventListener('click', load);

    document.getElementById('exportAssociados').addEventListener('click', () => {
        if (!can('admin.associados.export')) {
            setMsg(deny('admin.associados.export'), 'err');
            return;
        }
        readFiltersFromUi();
        const params = new URLSearchParams();
        params.set('action', 'admin_export_csv');
        if (state.q) params.set('q', state.q);
        if (state.status) params.set('status', state.status);
        if (state.categoria) params.set('categoria', state.categoria);
        if (state.uf) params.set('uf', state.uf);
        params.set('sort', state.sort);
        params.set('order', state.order);
        window.location.href = ep('/api/v1/members.php?' + params.toString());
    });

    document.getElementById('importAssociados').addEventListener('click', () => {
        if (!can('admin.associados.create')) {
            setMsg(deny('admin.associados.create'), 'err');
            return;
        }
        importBox.classList.remove('hidden');
        setImportSummary('', 'info');
    });

    document.getElementById('importCancel').addEventListener('click', () => {
        importBox.classList.add('hidden');
        importFile.value = '';
        importUpsert.checked = false;
        importCsvText = '';
        importPreviewReady = false;
        importPreviewUpsert = false;
        setImportSummary('', 'info');
        renderImportRows([]);
    });

    importFile.addEventListener('change', () => {
        importCsvText = '';
        importPreviewReady = false;
        importPreviewUpsert = !!importUpsert.checked;
        setImportSummary('Arquivo carregado. Clique em Preview para validar.', 'info');
    });

    importUpsert.addEventListener('change', () => {
        importPreviewReady = false;
        importPreviewUpsert = !!importUpsert.checked;
        setImportSummary('Modo alterado. Gere o preview novamente.', 'info');
    });

    document.getElementById('importPreview').addEventListener('click', async () => {
        if (!can('admin.associados.create')) {
            setMsg(deny('admin.associados.create'), 'err');
            return;
        }
        const upsert = !!importUpsert.checked;
        if (upsert && !can('admin.associados.edit')) {
            setMsg(deny('admin.associados.edit'), 'err');
            return;
        }
        try {
            importCsvText = await readImportFileText();
            const data = await window.anatejeApi(ep('/api/v1/members.php?action=admin_import_csv_preview'), {
                method: 'POST',
                body: { csv_text: importCsvText, upsert_existing: upsert ? 1 : 0 }
            });
            const s = data.summary || {};
            const modeLabel = upsert ? 'upsert' : 'create_only';
            setImportSummary(
                `Modo: ${modeLabel} | Total: ${s.total || 0} | Criar: ${s.ready_create || 0} | Atualizar: ${s.ready_update || 0} | Ignorados: ${s.skip || 0} | Invalidos: ${s.invalid || 0}`,
                'ok'
            );
            renderImportRows(data.rows || []);
            importPreviewReady = true;
            importPreviewUpsert = upsert;
        } catch (err) {
            importPreviewReady = false;
            setImportSummary(err.message || 'Falha no preview da importacao.', 'err');
            renderImportRows([]);
        }
    });

    document.getElementById('importCommit').addEventListener('click', async () => {
        if (!can('admin.associados.create')) {
            setMsg(deny('admin.associados.create'), 'err');
            return;
        }
        const upsert = !!importUpsert.checked;
        if (upsert && !can('admin.associados.edit')) {
            setMsg(deny('admin.associados.edit'), 'err');
            return;
        }
        try {
            if (!importCsvText) {
                importCsvText = await readImportFileText();
            }
            if (!importPreviewReady || importPreviewUpsert !== upsert) {
                setImportSummary('Execute o preview antes de importar.', 'err');
                return;
            }
            const confirmed = ui && typeof ui.confirmAction === 'function'
                ? await ui.confirmAction({
                    title: 'Confirmar importacao',
                    text: 'Confirma a importacao do CSV?',
                    confirmText: 'Importar'
                })
                : confirm('Confirma a importacao do CSV?');
            if (!confirmed) return;

            const data = await window.anatejeApi(ep('/api/v1/members.php?action=admin_import_csv_commit'), {
                method: 'POST',
                body: { csv_text: importCsvText, upsert_existing: upsert ? 1 : 0 }
            });

            const errCount = Array.isArray(data.errors) ? data.errors.length : 0;
            const modeLabel = (data.mode || (upsert ? 'upsert' : 'create_only'));
            setImportSummary(
                `Importacao (${modeLabel}) concluida. Criados: ${data.created || 0} | Atualizados: ${data.updated || 0} | Ignorados: ${data.skipped || 0} | Invalidos: ${data.invalid || 0} | Erros listados: ${errCount}`,
                ((data.created || 0) > 0 || (data.updated || 0) > 0) ? 'ok' : 'info'
            );

            const mappedErrors = (data.errors || []).map((e) => ({
                line: e.line,
                nome: '',
                cpf: '',
                login_email: '',
                status: 'ERROR',
                messages: e.messages || []
            }));
            renderImportRows(mappedErrors);
            importPreviewReady = false;

            await load();
        } catch (err) {
            setImportSummary(err.message || 'Falha na importacao CSV.', 'err');
        }
    });

    selectAllRows.addEventListener('change', (e) => {
        if (!canEditMembers) {
            e.currentTarget.checked = false;
            return;
        }
        const checked = !!e.currentTarget.checked;
        cache.forEach((m) => {
            const id = parseInt(m.id, 10);
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

    bulkApplyBtn.addEventListener('click', async () => {
        if (!can('admin.associados.edit')) {
            setMsg(deny('admin.associados.edit'), 'err');
            return;
        }
        const ids = Array.from(selectedIds.values()).filter((id) => Number.isInteger(id) && id > 0);
        if (!ids.length) {
            setMsg('Selecione ao menos um associado para acao em lote.', 'err');
            return;
        }
        const status = bulkStatus.value || 'ATIVO';
        const reason = (bulkReason.value || '').trim();
        const confirmed = ui && typeof ui.confirmAction === 'function'
            ? await ui.confirmAction({
                title: 'Confirmar lote',
                text: `Aplicar status ${status} para ${ids.length} associado(s)?`,
                confirmText: 'Aplicar'
            })
            : confirm(`Aplicar status ${status} para ${ids.length} associado(s)?`);
        if (!confirmed) return;

        try {
            const data = await window.anatejeApi(ep('/api/v1/members.php?action=admin_bulk_status'), {
                method: 'POST',
                body: { ids, status, reason }
            });
            setMsg(
                `Lote concluido. Atualizados: ${data.updated || 0}, sem alteracao: ${data.unchanged || 0}, nao encontrados: ${data.not_found || 0}.`,
                'ok'
            );
            ids.forEach((id) => selectedIds.delete(id));
            selectAllRows.checked = false;
            updateBulkMeta();
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao aplicar acao em lote', 'err');
        }
    });

    prevBtn.addEventListener('click', async () => {
        if (state.page <= 1) return;
        state.page -= 1;
        await load();
    });

    nextBtn.addEventListener('click', async () => {
        if (state.page >= state.totalPages) return;
        state.page += 1;
        await load();
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (ui && typeof ui.clearFieldErrors === 'function') {
            ui.clearFieldErrors(form);
        }
        try {
            const body = collectBody();
            const code = body.id > 0 ? 'admin.associados.edit' : 'admin.associados.create';
            if (!can(code)) {
                setMsg(deny(code), 'err');
                return;
            }
            const data = await window.anatejeApi(ep('/api/v1/members.php?action=admin_save'), {
                method: 'POST',
                body
            });

            let text = 'Associado salvo com sucesso.';
            if (data.temp_password) {
                text += ' Senha temporaria gerada: ' + data.temp_password;
            }
            setMsg(text, 'ok');
            closeForm();
            await load();
        } catch (err) {
            if (ui && typeof ui.applyValidationError === 'function') {
                ui.applyValidationError(form, err, [
                    { pattern: /nome/i, field: 'assoc_nome' },
                    { pattern: /cpf/i, field: 'assoc_cpf' },
                    { pattern: /registro associativo|matricula/i, field: 'assoc_matricula' },
                    { pattern: /email de acesso/i, field: 'assoc_login_email' },
                    { pattern: /email funcional/i, field: 'assoc_email_funcional' },
                    { pattern: /nova senha/i, field: 'assoc_nova_senha' },
                    { pattern: /data de filiacao/i, field: 'assoc_data_filiacao' },
                    { pattern: /contribuicao mensal/i, field: 'assoc_contribuicao' },
                    { pattern: /telefone/i, field: 'assoc_telefone' },
                    { pattern: /cep/i, field: 'assoc_cep' },
                    { pattern: /uf invalida/i, field: 'assoc_uf' },
                    { pattern: /email de administrador/i, field: 'assoc_login_email' }
                ]);
            }
            setMsg(err.message || 'Falha ao salvar associado', 'err');
        }
    });

    bindMasks();
    if (!can('admin.associados.create')) {
        document.getElementById('novoAssociado').classList.add('hidden');
        document.getElementById('importAssociados').classList.add('hidden');
    }
    if (!can('admin.associados.view')) {
        document.getElementById('saveFilters').classList.add('hidden');
        document.getElementById('loadFilters').classList.add('hidden');
    }
    if (!can('admin.associados.export')) {
        document.getElementById('exportAssociados').classList.add('hidden');
    }
    if (!canEditMembers) {
        selectAllRows.disabled = true;
        bulkStatus.disabled = true;
        bulkReason.disabled = true;
        bulkApplyBtn.disabled = true;
    }
    if (!can('admin.associados.edit')) {
        importUpsert.checked = false;
        importUpsert.disabled = true;
        importUpsert.title = 'Sem permissao de edicao para modo upsert';
    }
    (async function bootstrap() {
        await applySavedMembersFilters();
        await load();
    })();
})();
</script>
