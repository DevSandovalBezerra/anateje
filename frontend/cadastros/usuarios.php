<?php
// Cadastros - Usuarios

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
                <h1 class="text-2xl font-bold text-gray-800">Admin - Usuarios</h1>
                <p class="text-gray-600">Gerencie usuarios internos, tipos de funcionario e perfil de acesso.</p>
            </div>
            <div class="flex gap-2">
                <button id="userRefresh" class="btn-secondary px-4 py-2 text-sm">Atualizar</button>
                <button id="userNovoTop" class="btn-primary px-4 py-2 text-sm">Novo usuario</button>
            </div>
        </div>

        <div class="mb-4 p-4 rounded border border-gray-200 bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <label>
                    <span class="block text-sm font-medium text-gray-700 mb-1">Buscar</span>
                    <input id="f_q" class="input-primary w-full" placeholder="Nome ou email">
                </label>
                <label>
                    <span class="block text-sm font-medium text-gray-700 mb-1">Perfil</span>
                    <select id="f_perfil" class="input-primary w-full"></select>
                </label>
                <label>
                    <span class="block text-sm font-medium text-gray-700 mb-1">Status</span>
                    <select id="f_ativo" class="input-primary w-full">
                        <option value="">Todos</option>
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </label>
                <label>
                    <span class="block text-sm font-medium text-gray-700 mb-1">Tipo de usuario</span>
                    <select id="f_tipo_usuario" class="input-primary w-full">
                        <option value="">Todos</option>
                        <option value="ASSOCIADO">Associado</option>
                        <option value="FUNCIONARIO">Funcionario</option>
                        <option value="ADMIN">Admin</option>
                    </select>
                </label>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <button id="f_apply" class="btn-secondary px-4 py-2 text-sm">Aplicar filtros</button>
                <button id="f_clear" class="btn-secondary px-4 py-2 text-sm">Limpar</button>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <section class="xl:col-span-2">
                <div class="overflow-auto border border-gray-200 rounded bg-white">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-600 border-b">
                                <th class="py-2 px-2">ID</th>
                                <th class="py-2 px-2">Nome</th>
                                <th class="py-2 px-2">Email</th>
                                <th class="py-2 px-2">Perfil</th>
                                <th class="py-2 px-2">Tipo</th>
                                <th class="py-2 px-2">Status</th>
                                <th class="py-2 px-2">Acoes</th>
                            </tr>
                        </thead>
                        <tbody id="userRows"></tbody>
                    </table>
                </div>
            </section>

            <section class="xl:col-span-1">
                <form id="userForm" class="p-4 rounded-lg border border-gray-200 bg-gray-50 space-y-3">
                    <input type="hidden" id="user_id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                        <input id="user_nome" class="input-primary w-full" maxlength="150" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input id="user_email" type="email" class="input-primary w-full" maxlength="190" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                        <input id="user_senha" type="password" class="input-primary w-full" maxlength="120" placeholder="Obrigatoria no cadastro novo">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Perfil *</label>
                        <select id="user_perfil_id" class="input-primary w-full" required></select>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Tipo de usuario</span>
                            <select id="user_tipo_usuario" class="input-primary w-full">
                                <option value="ASSOCIADO">Associado</option>
                                <option value="FUNCIONARIO">Funcionario</option>
                                <option value="ADMIN">Admin</option>
                            </select>
                        </label>
                        <label>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Tipo funcionario</span>
                            <select id="user_tipo_funcionario" class="input-primary w-full">
                                <option value="">Nao se aplica</option>
                                <option value="CONTADOR">Contador</option>
                                <option value="ATENDENTE">Atendente</option>
                                <option value="FINANCEIRO">Financeiro</option>
                                <option value="COORDENACAO">Coordenacao</option>
                                <option value="SUPORTE">Suporte</option>
                                <option value="GESTOR">Gestor</option>
                                <option value="OUTRO">Outro</option>
                            </select>
                        </label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unidade ID (opcional)</label>
                        <input id="user_unidade_id" type="number" min="1" class="input-primary w-full" placeholder="Ex: 1">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input id="user_ativo" type="checkbox" checked>
                        Usuario ativo
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn-primary px-4 py-2 text-sm">Salvar</button>
                        <button type="button" id="userNovo" class="btn-secondary px-4 py-2 text-sm">Novo</button>
                        <button type="button" id="userDelete" class="btn-secondary px-4 py-2 text-sm">Excluir</button>
                    </div>
                </form>
            </section>
        </div>

        <p id="userMsg" class="text-sm mt-4"></p>
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
    const ui = window.anatejeUi || null;
    const code = 'cadastros.usuarios';

    const rows = document.getElementById('userRows');
    const msg = document.getElementById('userMsg');
    const form = document.getElementById('userForm');

    const el = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[ch]);

    let payload = { users: [], profiles: [] };
    let selectedUserId = 0;

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function statusBadge(ativo) {
        return Number(ativo) === 1
            ? '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700">Ativo</span>'
            : '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700">Inativo</span>';
    }

    function tipoLabel(user) {
        const tipo = String(user.tipo_usuario || '');
        const tf = String(user.tipo_funcionario || '');
        if (tipo === 'FUNCIONARIO' && tf) {
            return `${tipo} (${tf})`;
        }
        return tipo || '-';
    }

    function syncProfileOptions() {
        const options = ['<option value="">Todos</option>']
            .concat((payload.profiles || []).map((p) => `<option value="${Number(p.id)}">${esc(p.nome)}</option>`));
        el('f_perfil').innerHTML = options.join('');

        const selected = String(el('user_perfil_id').value || '');
        const formOptions = ['<option value="">Selecione...</option>']
            .concat((payload.profiles || []).map((p) => `<option value="${Number(p.id)}">${esc(p.nome)}</option>`));
        el('user_perfil_id').innerHTML = formOptions.join('');
        if (selected) {
            el('user_perfil_id').value = selected;
        }
    }

    function renderRows() {
        const list = payload.users || [];
        if (!list.length) {
            rows.innerHTML = '<tr><td colspan="7" class="py-3 px-2 text-gray-500">Nenhum usuario encontrado.</td></tr>';
            return;
        }

        rows.innerHTML = list.map((u) => {
            const activeClass = Number(u.id) === selectedUserId ? 'bg-indigo-50' : '';
            return `
                <tr class="border-b border-gray-100 ${activeClass}">
                    <td class="py-2 px-2">${Number(u.id)}</td>
                    <td class="py-2 px-2">${esc(u.nome)}</td>
                    <td class="py-2 px-2">${esc(u.email)}</td>
                    <td class="py-2 px-2">${esc(u.perfil_nome || '-')}</td>
                    <td class="py-2 px-2">${esc(tipoLabel(u))}</td>
                    <td class="py-2 px-2">${statusBadge(u.ativo)}</td>
                    <td class="py-2 px-2">
                        <div class="flex gap-2">
                            <button class="btn-secondary px-2 py-1 text-xs" data-act="edit" data-id="${Number(u.id)}">Editar</button>
                            <button class="btn-secondary px-2 py-1 text-xs" data-act="delete" data-id="${Number(u.id)}">Excluir</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        rows.querySelectorAll('button[data-act]').forEach((btn) => {
            btn.addEventListener('click', async (event) => {
                const action = String(event.currentTarget.getAttribute('data-act') || '');
                const id = Number(event.currentTarget.getAttribute('data-id') || '0');
                if (action === 'edit') {
                    selectUser(id);
                    return;
                }
                if (action === 'delete') {
                    selectUser(id);
                    await deleteUser();
                }
            });
        });
    }

    function syncTipoFuncionario() {
        const tipo = String(el('user_tipo_usuario').value || 'ASSOCIADO');
        const campo = el('user_tipo_funcionario');
        const enabled = tipo === 'FUNCIONARIO';
        campo.disabled = !enabled;
        if (!enabled) {
            campo.value = '';
        }
    }

    function clearForm() {
        selectedUserId = 0;
        el('user_id').value = '';
        el('user_nome').value = '';
        el('user_email').value = '';
        el('user_senha').value = '';
        el('user_perfil_id').value = '';
        el('user_tipo_usuario').value = 'ASSOCIADO';
        el('user_tipo_funcionario').value = '';
        el('user_unidade_id').value = '';
        el('user_ativo').checked = true;
        syncTipoFuncionario();
        renderRows();
    }

    function selectUser(id) {
        const u = (payload.users || []).find((x) => Number(x.id) === Number(id));
        if (!u) return;
        selectedUserId = Number(u.id);
        el('user_id').value = String(u.id);
        el('user_nome').value = String(u.nome || '');
        el('user_email').value = String(u.email || '');
        el('user_senha').value = '';
        el('user_perfil_id').value = String(u.perfil_id || '');
        el('user_tipo_usuario').value = String(u.tipo_usuario || 'ASSOCIADO');
        el('user_tipo_funcionario').value = String(u.tipo_funcionario || '');
        el('user_unidade_id').value = u.unidade_id ? String(u.unidade_id) : '';
        el('user_ativo').checked = Number(u.ativo) === 1;
        syncTipoFuncionario();
        renderRows();
    }

    function collectFilters() {
        return {
            q: String(el('f_q').value || '').trim(),
            perfil_id: String(el('f_perfil').value || ''),
            ativo: String(el('f_ativo').value || ''),
            tipo_usuario: String(el('f_tipo_usuario').value || '')
        };
    }

    async function load() {
        try {
            const filters = collectFilters();
            const qs = new URLSearchParams();
            Object.keys(filters).forEach((k) => {
                if (filters[k] !== '') qs.set(k, filters[k]);
            });

            const data = await window.anatejeApi(ep('/api/v1/users.php?action=admin_list&' + qs.toString()));
            payload.users = Array.isArray(data.users) ? data.users : [];
            payload.profiles = Array.isArray(data.profiles) ? data.profiles : [];

            const selectedFilterProfile = String(el('f_perfil').value || '');
            syncProfileOptions();
            if (selectedFilterProfile) {
                el('f_perfil').value = selectedFilterProfile;
            }

            renderRows();
            if (selectedUserId > 0) {
                const keep = payload.users.some((u) => Number(u.id) === selectedUserId);
                if (keep) {
                    selectUser(selectedUserId);
                } else {
                    clearForm();
                }
            }
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar usuarios', 'err');
        }
    }

    async function saveUser(event) {
        event.preventDefault();
        if (ui && typeof ui.clearFieldErrors === 'function') {
            ui.clearFieldErrors(form);
        }

        if (!can(code)) {
            setMsg(deny(code), 'err');
            return;
        }

        const body = {
            id: Number(el('user_id').value || '0'),
            nome: String(el('user_nome').value || '').trim(),
            email: String(el('user_email').value || '').trim(),
            senha: String(el('user_senha').value || ''),
            perfil_id: Number(el('user_perfil_id').value || '0'),
            tipo_usuario: String(el('user_tipo_usuario').value || 'ASSOCIADO'),
            tipo_funcionario: String(el('user_tipo_funcionario').value || ''),
            unidade_id: Number(el('user_unidade_id').value || '0'),
            ativo: el('user_ativo').checked ? 1 : 0
        };

        if (!body.nome) {
            setMsg('Informe o nome do usuario.', 'err');
            return;
        }
        if (!body.email) {
            setMsg('Informe o email do usuario.', 'err');
            return;
        }
        if (body.perfil_id <= 0) {
            setMsg('Selecione o perfil de acesso.', 'err');
            return;
        }

        try {
            const data = await window.anatejeApi(ep('/api/v1/users.php?action=admin_save'), {
                method: 'POST',
                body
            });
            selectedUserId = Number(data.id || 0);
            setMsg('Usuario salvo com sucesso.', 'ok');
            await load();
            if (selectedUserId > 0) {
                selectUser(selectedUserId);
            }
        } catch (err) {
            if (ui && typeof ui.applyValidationError === 'function') {
                ui.applyValidationError(form, err, [
                    { pattern: /nome/i, field: 'user_nome' },
                    { pattern: /email/i, field: 'user_email' },
                    { pattern: /senha/i, field: 'user_senha' },
                    { pattern: /perfil/i, field: 'user_perfil_id' },
                    { pattern: /funcionario/i, field: 'user_tipo_funcionario' }
                ]);
            }
            setMsg(err.message || 'Falha ao salvar usuario', 'err');
        }
    }

    async function deleteUser() {
        if (!can(code)) {
            setMsg(deny(code), 'err');
            return;
        }
        const id = Number(el('user_id').value || '0');
        if (id <= 0) {
            setMsg('Selecione um usuario para excluir.', 'err');
            return;
        }

        const confirmed = ui && typeof ui.confirmDelete === 'function'
            ? await ui.confirmDelete('este usuario')
            : false;
        if (!confirmed) return;

        try {
            const data = await window.anatejeApi(ep('/api/v1/users.php?action=admin_delete'), {
                method: 'POST',
                body: { id }
            });
            if (data && data.deactivated) {
                setMsg('Usuario vinculado a associado foi inativado.', 'ok');
            } else {
                setMsg('Usuario excluido com sucesso.', 'ok');
            }
            clearForm();
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao excluir usuario', 'err');
        }
    }

    el('f_apply').addEventListener('click', load);
    el('f_clear').addEventListener('click', () => {
        el('f_q').value = '';
        el('f_perfil').value = '';
        el('f_ativo').value = '';
        el('f_tipo_usuario').value = '';
        load();
    });
    el('userRefresh').addEventListener('click', load);
    el('userNovoTop').addEventListener('click', clearForm);
    el('userNovo').addEventListener('click', clearForm);
    el('userDelete').addEventListener('click', deleteUser);
    el('user_tipo_usuario').addEventListener('change', syncTipoFuncionario);
    form.addEventListener('submit', saveUser);

    if (!can(code)) {
        form.querySelectorAll('input,select,button').forEach((node) => {
            if (node.id === 'userRefresh') return;
            if (node.id === 'f_apply' || node.id === 'f_clear' || node.id === 'f_q' || node.id === 'f_perfil' || node.id === 'f_ativo' || node.id === 'f_tipo_usuario') {
                return;
            }
            node.setAttribute('disabled', 'disabled');
        });
    }

    syncTipoFuncionario();
    load();
})();
</script>
