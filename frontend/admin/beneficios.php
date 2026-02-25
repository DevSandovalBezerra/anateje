<?php
// Admin - Beneficios

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
                <h1 class="text-2xl font-bold text-gray-800">Admin - Beneficios</h1>
                <p class="text-gray-600">Cadastro e organizacao dos beneficios.</p>
            </div>
            <button id="novoBeneficio" class="btn-primary px-4 py-2 text-sm">Novo Beneficio</button>
        </div>

        <div class="overflow-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
                        <th class="py-2 pr-3">ID</th>
                        <th class="py-2 pr-3">Nome</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Ordem</th>
                        <th class="py-2 pr-3">Acoes</th>
                    </tr>
                </thead>
                <tbody id="benefRows"></tbody>
            </table>
        </div>

        <form id="benefForm" class="hidden p-4 rounded-lg border border-gray-200 bg-gray-50 space-y-3">
            <input id="benef_id" type="hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label>
                    <span class="text-sm font-medium text-gray-700">Nome *</span>
                    <input id="benef_nome" class="input-primary w-full" required>
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Link</span>
                    <input id="benef_link" class="input-primary w-full">
                </label>
                <label class="md:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Descricao</span>
                    <textarea id="benef_descricao" class="input-primary w-full" rows="3"></textarea>
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
                    <input id="benef_sort" type="number" class="input-primary w-full" value="0">
                </label>
            </div>
            <div class="flex gap-2">
                <button class="btn-primary px-4 py-2 text-sm" type="submit">Salvar</button>
                <button class="btn-secondary px-4 py-2 text-sm" type="button" id="cancelBenef">Cancelar</button>
            </div>
        </form>

        <p id="benefMsg" class="text-sm mt-4"></p>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;

    const rows = document.getElementById('benefRows');
    const msg = document.getElementById('benefMsg');
    const form = document.getElementById('benefForm');

    let cache = [];

    const el = (id) => document.getElementById(id);

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function render() {
        if (!cache.length) {
            rows.innerHTML = '<tr><td colspan="5" class="py-3 text-gray-500">Nenhum beneficio cadastrado.</td></tr>';
            return;
        }

        rows.innerHTML = cache.map((b) => `
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-3">${b.id}</td>
                <td class="py-2 pr-3">${b.nome}</td>
                <td class="py-2 pr-3">${b.status}</td>
                <td class="py-2 pr-3">${b.sort_order}</td>
                <td class="py-2 pr-3">
                    <div class="flex gap-2">
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="edit" data-id="${b.id}">Editar</button>
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="delete" data-id="${b.id}">Excluir</button>
                    </div>
                </td>
            </tr>
        `).join('');

        rows.querySelectorAll('button[data-act]').forEach((btn) => btn.addEventListener('click', onAction));
    }

    function openForm(data) {
        form.classList.remove('hidden');
        el('benef_id').value = data?.id || '';
        el('benef_nome').value = data?.nome || '';
        el('benef_link').value = data?.link || '';
        el('benef_descricao').value = data?.descricao || '';
        el('benef_status').value = data?.status || 'active';
        el('benef_sort').value = data?.sort_order ?? 0;
    }

    function closeForm() {
        form.classList.add('hidden');
        form.reset();
        el('benef_id').value = '';
    }

    async function onAction(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const act = btn.getAttribute('data-act');

        if (act === 'edit') {
            const row = cache.find((b) => parseInt(b.id, 10) === id);
            openForm(row);
            return;
        }

        if (act === 'delete') {
            if (!confirm('Deseja excluir este beneficio?')) return;
            try {
                await window.anatejeApi(ep('/api/v1/benefits.php?action=admin_delete&id=' + id));
                setMsg('Beneficio excluido.', 'ok');
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
            render();
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar beneficios', 'err');
        }
    }

    document.getElementById('novoBeneficio').addEventListener('click', () => openForm(null));
    document.getElementById('cancelBenef').addEventListener('click', closeForm);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        try {
            const body = {
                id: el('benef_id').value ? parseInt(el('benef_id').value, 10) : 0,
                nome: el('benef_nome').value,
                descricao: el('benef_descricao').value,
                link: el('benef_link').value,
                status: el('benef_status').value,
                sort_order: parseInt(el('benef_sort').value || '0', 10)
            };

            await window.anatejeApi(ep('/api/v1/benefits.php?action=admin_save'), { method: 'POST', body });
            setMsg('Beneficio salvo com sucesso.', 'ok');
            closeForm();
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar beneficio', 'err');
        }
    });

    load();
})();
</script>
