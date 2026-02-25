<?php
// Admin - Comunicados

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
                <h1 class="text-2xl font-bold text-gray-800">Admin - Comunicados</h1>
                <p class="text-gray-600">Criacao e publicacao de comunicados para os associados.</p>
            </div>
            <button id="novoPost" class="btn-primary px-4 py-2 text-sm">Novo Comunicado</button>
        </div>

        <div class="overflow-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
                        <th class="py-2 pr-3">ID</th>
                        <th class="py-2 pr-3">Titulo</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Publicado em</th>
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
                    <span class="text-sm font-medium text-gray-700">Data Publicacao</span>
                    <input id="post_publicado" type="datetime-local" class="input-primary w-full">
                </label>
                <label class="md:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Conteudo</span>
                    <textarea id="post_conteudo" class="input-primary w-full" rows="5"></textarea>
                </label>
            </div>
            <div class="flex gap-2">
                <button class="btn-primary px-4 py-2 text-sm" type="submit">Salvar</button>
                <button class="btn-secondary px-4 py-2 text-sm" type="button" id="cancelPost">Cancelar</button>
            </div>
        </form>

        <p id="postMsg" class="text-sm mt-4"></p>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;

    const rows = document.getElementById('postRows');
    const msg = document.getElementById('postMsg');
    const form = document.getElementById('postForm');
    const el = (id) => document.getElementById(id);

    let cache = [];

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
        const list = cache.filter((p) => p.tipo === 'COMUNICADO');

        if (!list.length) {
            rows.innerHTML = '<tr><td colspan="5" class="py-3 text-gray-500">Nenhum comunicado cadastrado.</td></tr>';
            return;
        }

        rows.innerHTML = list.map((p) => `
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-3">${p.id}</td>
                <td class="py-2 pr-3">${p.titulo}</td>
                <td class="py-2 pr-3">${p.status}</td>
                <td class="py-2 pr-3">${dtLabel(p.publicado_em)}</td>
                <td class="py-2 pr-3">
                    <div class="flex gap-2">
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="edit" data-id="${p.id}">Editar</button>
                        <button class="btn-secondary px-2 py-1 text-xs" data-act="delete" data-id="${p.id}">Excluir</button>
                    </div>
                </td>
            </tr>
        `).join('');

        rows.querySelectorAll('button[data-act]').forEach((btn) => btn.addEventListener('click', onAction));
    }

    function openForm(post) {
        form.classList.remove('hidden');
        el('post_id').value = post?.id || '';
        el('post_titulo').value = post?.titulo || '';
        el('post_slug').value = post?.slug || '';
        el('post_status').value = post?.status || 'draft';
        el('post_publicado').value = dtToInput(post?.publicado_em);
        el('post_conteudo').value = post?.conteudo || '';
    }

    function closeForm() {
        form.classList.add('hidden');
        form.reset();
        el('post_id').value = '';
    }

    async function onAction(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const act = btn.getAttribute('data-act');

        if (act === 'edit') {
            try {
                const data = await window.anatejeApi(ep('/api/v1/posts.php?action=admin_get&id=' + id));
                openForm(data.post || cache.find((x) => parseInt(x.id, 10) === id));
            } catch (err) {
                setMsg(err.message || 'Falha ao carregar comunicado para edicao', 'err');
            }
            return;
        }

        if (act === 'delete') {
            if (!confirm('Deseja excluir este comunicado?')) return;
            try {
                await window.anatejeApi(ep('/api/v1/posts.php?action=admin_delete&id=' + id));
                setMsg('Comunicado excluido.', 'ok');
                await load();
            } catch (err) {
                setMsg(err.message || 'Falha ao excluir comunicado', 'err');
            }
        }
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/posts.php?action=admin_list'));
            cache = data.posts || [];
            render();
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar comunicados', 'err');
        }
    }

    document.getElementById('novoPost').addEventListener('click', () => openForm(null));
    document.getElementById('cancelPost').addEventListener('click', closeForm);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        try {
            const body = {
                id: el('post_id').value ? parseInt(el('post_id').value, 10) : 0,
                tipo: 'COMUNICADO',
                titulo: el('post_titulo').value,
                slug: el('post_slug').value,
                conteudo: el('post_conteudo').value,
                status: el('post_status').value,
                publicado_em: el('post_publicado').value
            };

            await window.anatejeApi(ep('/api/v1/posts.php?action=admin_save'), { method: 'POST', body });
            setMsg('Comunicado salvo com sucesso.', 'ok');
            closeForm();
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar comunicado', 'err');
        }
    });

    load();
})();
</script>
