<?php
// Meus Beneficios

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
                <h1 class="text-2xl font-bold text-gray-800">Meus Beneficios</h1>
                <p class="text-gray-600">Marque os beneficios que voce utiliza.</p>
            </div>
            <button id="salvarBeneficios" class="btn-primary px-5 py-2 text-sm">Salvar</button>
        </div>

        <div id="beneficiosList" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
        <p id="beneficiosMsg" class="text-sm mt-4"></p>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;

    const list = document.getElementById('beneficiosList');
    const msg = document.getElementById('beneficiosMsg');
    const saveBtn = document.getElementById('salvarBeneficios');

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function render(items) {
        if (!items.length) {
            list.innerHTML = '<div class="text-sm text-gray-600">Nenhum beneficio disponivel no momento.</div>';
            return;
        }

        list.innerHTML = items.map((b) => `
            <article class="p-4 rounded-lg border border-gray-200 bg-gray-50">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">${b.nome}</h3>
                        <p class="text-sm text-gray-600 mt-1">${b.descricao || ''}</p>
                        ${b.link ? `<a class="text-sm text-blue-600 hover:underline mt-2 inline-block" href="${b.link}" target="_blank" rel="noopener">Acessar beneficio</a>` : ''}
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" data-id="${b.id}" ${b.active_for_me ? 'checked' : ''}>
                        Ativo
                    </label>
                </div>
            </article>
        `).join('');
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/benefits.php?action=list'));
            render(data.benefits || []);
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar beneficios', 'err');
        }
    }

    async function save() {
        try {
            const ids = Array.from(document.querySelectorAll('input[type="checkbox"][data-id]:checked'))
                .map((el) => parseInt(el.getAttribute('data-id'), 10))
                .filter((id) => Number.isInteger(id) && id > 0);

            await window.anatejeApi(ep('/api/v1/benefits.php?action=set_member_benefits'), {
                method: 'POST',
                body: { benefit_ids: ids }
            });
            setMsg('Beneficios atualizados com sucesso.', 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar beneficios', 'err');
        }
    }

    saveBtn.addEventListener('click', save);
    load();
})();
</script>
