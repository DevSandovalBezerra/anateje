<?php
// Admin - Permissoes

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
                <h1 class="text-2xl font-bold text-gray-800">Admin - Permissoes</h1>
                <p class="text-gray-600">Gerencie perfis de acesso e permissoes por modulo.</p>
            </div>
            <button id="permReload" class="btn-secondary px-4 py-2 text-sm">Atualizar</button>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <section class="xl:col-span-1">
                <div class="p-4 rounded-lg border border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-800 mb-3">Perfis</h2>
                    <div class="overflow-auto max-h-80 border border-gray-200 rounded bg-white">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-600 border-b">
                                    <th class="py-2 px-2">ID</th>
                                    <th class="py-2 px-2">Nome</th>
                                    <th class="py-2 px-2">Usuarios</th>
                                </tr>
                            </thead>
                            <tbody id="perfilRows"></tbody>
                        </table>
                    </div>
                </div>

                <form id="perfilForm" class="mt-4 p-4 rounded-lg border border-gray-200 bg-gray-50 space-y-3">
                    <input type="hidden" id="perfil_id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome do perfil *</label>
                        <input id="perfil_nome" class="input-primary w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descricao</label>
                        <input id="perfil_descricao" class="input-primary w-full">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input id="perfil_ativo" type="checkbox" checked>
                        Perfil ativo
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn-primary px-4 py-2 text-sm">Salvar perfil</button>
                        <button type="button" id="perfilNovo" class="btn-secondary px-4 py-2 text-sm">Novo</button>
                        <button type="button" id="perfilDelete" class="btn-secondary px-4 py-2 text-sm">Excluir</button>
                    </div>
                </form>
            </section>

            <section class="xl:col-span-2">
                <div class="p-4 rounded-lg border border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-semibold text-gray-800">Permissoes do perfil selecionado</h2>
                        <button id="salvarPermissoes" class="btn-primary px-4 py-2 text-sm">Salvar permissoes</button>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Selecione um perfil para editar as permissoes.</p>
                    <div id="permissionsGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                </div>
            </section>
        </div>

        <p id="permMsg" class="text-sm mt-4"></p>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;

    const perfilRows = document.getElementById('perfilRows');
    const permissionsGrid = document.getElementById('permissionsGrid');
    const msg = document.getElementById('permMsg');

    const el = (id) => document.getElementById(id);

    let payload = {
        profiles: [],
        permissions: [],
        profile_permissions: {}
    };
    let selectedProfileId = 0;

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function groupedPermissions() {
        const groups = {};
        (payload.permissions || []).forEach((perm) => {
            const mod = perm.modulo || 'geral';
            if (!groups[mod]) groups[mod] = [];
            groups[mod].push(perm);
        });
        return groups;
    }

    function currentPermissionIds() {
        const raw = payload.profile_permissions && payload.profile_permissions[selectedProfileId];
        return Array.isArray(raw) ? raw.map((x) => parseInt(x, 10)) : [];
    }

    function renderProfiles() {
        const list = payload.profiles || [];
        if (!list.length) {
            perfilRows.innerHTML = '<tr><td colspan="3" class="py-2 px-2 text-gray-500">Sem perfis cadastrados.</td></tr>';
            return;
        }

        perfilRows.innerHTML = list.map((p) => {
            const activeClass = parseInt(p.id, 10) === selectedProfileId ? 'bg-indigo-50' : '';
            return `
                <tr class="border-b border-gray-100 cursor-pointer ${activeClass}" data-perfil-id="${p.id}">
                    <td class="py-2 px-2">${p.id}</td>
                    <td class="py-2 px-2">${p.nome}</td>
                    <td class="py-2 px-2">${p.users_count || 0}</td>
                </tr>
            `;
        }).join('');

        perfilRows.querySelectorAll('tr[data-perfil-id]').forEach((row) => {
            row.addEventListener('click', () => {
                const id = parseInt(row.getAttribute('data-perfil-id'), 10);
                selectProfile(id);
            });
        });
    }

    function renderPermissions() {
        if (!selectedProfileId) {
            permissionsGrid.innerHTML = '<div class="text-sm text-gray-500">Selecione um perfil para configurar permissoes.</div>';
            return;
        }

        const selected = new Set(currentPermissionIds());
        const groups = groupedPermissions();
        const groupKeys = Object.keys(groups);
        if (!groupKeys.length) {
            permissionsGrid.innerHTML = '<div class="text-sm text-gray-500">Nenhuma permissao cadastrada.</div>';
            return;
        }

        permissionsGrid.innerHTML = groupKeys.map((mod) => {
            const list = groups[mod];
            const items = list.map((perm) => {
                const checked = selected.has(parseInt(perm.id, 10)) ? 'checked' : '';
                return `
                    <label class="flex items-center gap-2 py-1 text-sm text-gray-700">
                        <input type="checkbox" data-perm-id="${perm.id}" ${checked}>
                        <span>${perm.nome} <span class="text-gray-400">(${perm.codigo})</span></span>
                    </label>
                `;
            }).join('');

            return `
                <div class="p-3 border border-gray-200 rounded bg-white">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">${mod}</h3>
                    ${items}
                </div>
            `;
        }).join('');
    }

    function selectProfile(id) {
        const profile = (payload.profiles || []).find((p) => parseInt(p.id, 10) === parseInt(id, 10));
        if (!profile) return;

        selectedProfileId = parseInt(profile.id, 10);
        el('perfil_id').value = profile.id;
        el('perfil_nome').value = profile.nome || '';
        el('perfil_descricao').value = profile.descricao || '';
        el('perfil_ativo').checked = parseInt(profile.ativo, 10) === 1;

        renderProfiles();
        renderPermissions();
    }

    function clearFormForNew() {
        selectedProfileId = 0;
        el('perfil_id').value = '';
        el('perfil_nome').value = '';
        el('perfil_descricao').value = '';
        el('perfil_ativo').checked = true;
        renderProfiles();
        renderPermissions();
    }

    async function load() {
        try {
            payload = await window.anatejeApi(ep('/api/v1/permissions.php?action=admin_list'));
            if (!selectedProfileId && payload.profiles && payload.profiles.length) {
                selectedProfileId = parseInt(payload.profiles[0].id, 10);
            }

            renderProfiles();
            if (selectedProfileId) {
                selectProfile(selectedProfileId);
            } else {
                renderPermissions();
            }
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar permissoes', 'err');
        }
    }

    async function saveProfile(event) {
        event.preventDefault();
        setMsg('', 'ok');

        const body = {
            id: el('perfil_id').value ? parseInt(el('perfil_id').value, 10) : 0,
            nome: el('perfil_nome').value.trim(),
            descricao: el('perfil_descricao').value.trim(),
            ativo: el('perfil_ativo').checked ? 1 : 0
        };

        if (!body.nome) {
            setMsg('Informe o nome do perfil.', 'err');
            return;
        }

        try {
            const data = await window.anatejeApi(ep('/api/v1/permissions.php?action=admin_profile_save'), {
                method: 'POST',
                body
            });
            selectedProfileId = parseInt(data.id || body.id || 0, 10);
            setMsg('Perfil salvo com sucesso.', 'ok');
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar perfil', 'err');
        }
    }

    async function deleteProfile() {
        const id = el('perfil_id').value ? parseInt(el('perfil_id').value, 10) : 0;
        if (!id) {
            setMsg('Selecione um perfil para excluir.', 'err');
            return;
        }
        if (!confirm('Deseja excluir este perfil?')) {
            return;
        }

        try {
            await window.anatejeApi(ep('/api/v1/permissions.php?action=admin_profile_delete'), {
                method: 'POST',
                body: { id }
            });
            setMsg('Perfil excluido.', 'ok');
            selectedProfileId = 0;
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao excluir perfil', 'err');
        }
    }

    async function saveProfilePermissions() {
        if (!selectedProfileId) {
            setMsg('Selecione um perfil para salvar permissoes.', 'err');
            return;
        }

        const ids = Array.from(permissionsGrid.querySelectorAll('input[type="checkbox"][data-perm-id]'))
            .filter((elc) => elc.checked)
            .map((elc) => parseInt(elc.getAttribute('data-perm-id'), 10))
            .filter((n) => n > 0);

        try {
            await window.anatejeApi(ep('/api/v1/permissions.php?action=admin_profile_permissions_save'), {
                method: 'POST',
                body: {
                    profile_id: selectedProfileId,
                    permission_ids: ids
                }
            });
            setMsg('Permissoes salvas com sucesso.', 'ok');
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar permissoes', 'err');
        }
    }

    el('perfilForm').addEventListener('submit', saveProfile);
    el('perfilNovo').addEventListener('click', clearFormForNew);
    el('perfilDelete').addEventListener('click', deleteProfile);
    el('salvarPermissoes').addEventListener('click', saveProfilePermissions);
    el('permReload').addEventListener('click', load);

    load();
})();
</script>

