<?php
// Comunicados

if (!defined('TEMPLATE_ROUTED')) {
    header('Location: ../../index.php');
    exit;
}

$basePrefix = isset($prefix) ? $prefix : '/';
?>
<div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Comunicados</h1>
            <p class="text-gray-600">Atualizacoes oficiais da associacao para os membros.</p>
        </div>

        <div id="comunicadosList" class="space-y-3"></div>
        <div id="comunicadoDetalhe" class="hidden mt-6 p-4 rounded-lg border border-amber-200 bg-amber-50"></div>
        <p id="comunicadosMsg" class="text-sm mt-4"></p>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;

    const list = document.getElementById('comunicadosList');
    const detail = document.getElementById('comunicadoDetalhe');
    const msg = document.getElementById('comunicadosMsg');

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function dateLabel(v) {
        if (!v) return '-';
        const d = new Date(v.replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return v;
        return d.toLocaleString('pt-BR');
    }

    function render(items) {
        if (!items.length) {
            list.innerHTML = '<div class="text-sm text-gray-600">Nenhum comunicado publicado.</div>';
            return;
        }

        list.innerHTML = items.map((p) => `
            <article class="p-4 rounded-lg border border-gray-200 bg-gray-50">
                <div class="text-xs text-gray-500">${dateLabel(p.publicado_em || p.created_at)}</div>
                <h3 class="text-base font-semibold text-gray-800 mt-1">${p.titulo}</h3>
                <button class="btn-secondary px-3 py-1 text-xs mt-2" data-id="${p.id}">Ler comunicado</button>
            </article>
        `).join('');

        list.querySelectorAll('button[data-id]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                await openDetail(parseInt(btn.getAttribute('data-id'), 10));
            });
        });
    }

    async function openDetail(id) {
        try {
            const data = await window.anatejeApi(ep('/api/v1/posts.php?action=detail&id=' + id));
            const post = data.post || {};

            detail.classList.remove('hidden');
            detail.innerHTML = `
                <h2 class="text-lg font-semibold text-gray-800">${post.titulo || ''}</h2>
                <div class="text-xs text-gray-500 mt-1">${dateLabel(post.publicado_em || post.created_at)}</div>
                <div class="text-sm text-gray-700 mt-3 whitespace-pre-line">${(post.conteudo || 'Sem conteudo cadastrado.').replace(/</g, '&lt;')}</div>
            `;
            setMsg('', 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar comunicado', 'err');
        }
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/posts.php?action=list&type=COMUNICADO'));
            render(data.posts || []);
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar comunicados', 'err');
        }
    }

    load();
})();
</script>
