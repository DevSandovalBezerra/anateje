<?php
// Admin - Associados

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
                <h1 class="text-2xl font-bold text-gray-800">Admin - Associados</h1>
                <p class="text-gray-600">Gerencie status e cadastros dos associados.</p>
            </div>
            <button id="reloadAssociados" class="btn-secondary px-4 py-2 text-sm">Atualizar</button>
        </div>

        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
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

        <p id="associadosMsg" class="text-sm mt-4"></p>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;

    const rows = document.getElementById('associadosRows');
    const msg = document.getElementById('associadosMsg');

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function maskCpf(cpf) {
        const c = String(cpf || '').replace(/\D+/g, '').slice(0, 11);
        return c.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    function render(list) {
        if (!list.length) {
            rows.innerHTML = '<tr><td colspan="7" class="py-3 text-gray-500">Nenhum associado encontrado.</td></tr>';
            return;
        }

        rows.innerHTML = list.map((m) => `
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-3">${m.id}</td>
                <td class="py-2 pr-3">${m.nome || ''}</td>
                <td class="py-2 pr-3">${maskCpf(m.cpf)}</td>
                <td class="py-2 pr-3">${m.categoria || '-'}</td>
                <td class="py-2 pr-3">${m.status || '-'}</td>
                <td class="py-2 pr-3">${m.email_funcional || m.telefone || '-'}</td>
                <td class="py-2 pr-3">
                    <div class="flex flex-wrap gap-2">
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="toggle" data-id="${m.id}" data-status="${m.status}">${m.status === 'ATIVO' ? 'Inativar' : 'Ativar'}</button>
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="delete" data-id="${m.id}">Excluir</button>
                    </div>
                </td>
            </tr>
        `).join('');

        rows.querySelectorAll('button[data-act]').forEach((btn) => btn.addEventListener('click', onAction));
    }

    async function onAction(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const act = btn.getAttribute('data-act');

        try {
            if (act === 'toggle') {
                const cur = btn.getAttribute('data-status');
                const next = cur === 'ATIVO' ? 'INATIVO' : 'ATIVO';
                await window.anatejeApi(ep('/api/v1/members.php?action=admin_save_status'), {
                    method: 'POST',
                    body: { id, status: next }
                });
                setMsg('Status atualizado com sucesso.', 'ok');
                await load();
                return;
            }

            if (act === 'delete') {
                if (!confirm('Deseja excluir este associado?')) return;
                await window.anatejeApi(ep('/api/v1/members.php?action=admin_delete&id=' + id));
                setMsg('Associado excluido.', 'ok');
                await load();
            }
        } catch (err) {
            setMsg(err.message || 'Falha ao executar acao', 'err');
        }
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/members.php?action=admin_list'));
            render(data.members || []);
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar associados', 'err');
        }
    }

    document.getElementById('reloadAssociados').addEventListener('click', load);
    load();
})();
</script>
