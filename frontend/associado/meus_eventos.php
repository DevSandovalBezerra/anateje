<?php
// Meus Eventos

if (!defined('TEMPLATE_ROUTED')) {
    header('Location: ../../index.php');
    exit;
}

$basePrefix = isset($prefix) ? $prefix : '/';
?>
<div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Meus Eventos</h1>
            <p class="text-gray-600">Inscreva-se e acompanhe sua participacao nos eventos.</p>
        </div>

        <div id="eventosList" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
        <p id="eventosMsg" class="text-sm mt-4"></p>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;

    const list = document.getElementById('eventosList');
    const msg = document.getElementById('eventosMsg');

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
            list.innerHTML = '<div class="text-sm text-gray-600">Nenhum evento publicado no momento.</div>';
            return;
        }

        list.innerHTML = items.map((ev) => `
            <article class="p-4 rounded-lg border border-gray-200 bg-gray-50">
                <h3 class="text-base font-semibold text-gray-800">${ev.titulo}</h3>
                <p class="text-sm text-gray-600 mt-1">${ev.local || 'Local a definir'}</p>
                <p class="text-sm text-gray-600">Inicio: ${dateLabel(ev.inicio_em)}</p>
                ${ev.fim_em ? `<p class="text-sm text-gray-600">Fim: ${dateLabel(ev.fim_em)}</p>` : ''}
                <div class="flex flex-wrap gap-2 mt-3">
                    <button class="btn-primary px-3 py-1 text-xs" data-act="register" data-id="${ev.id}">Inscrever</button>
                    <button class="btn-secondary px-3 py-1 text-xs" data-act="cancel" data-id="${ev.id}">Cancelar</button>
                    <button class="btn-secondary px-3 py-1 text-xs" data-act="detail" data-id="${ev.id}">Detalhes</button>
                </div>
            </article>
        `).join('');

        list.querySelectorAll('button[data-act]').forEach((btn) => {
            btn.addEventListener('click', onAction);
        });
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/events.php?action=list'));
            render(data.events || []);
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar eventos', 'err');
        }
    }

    async function onAction(e) {
        const btn = e.currentTarget;
        const act = btn.getAttribute('data-act');
        const eventId = parseInt(btn.getAttribute('data-id'), 10);

        try {
            if (act === 'register') {
                await window.anatejeApi(ep('/api/v1/events.php?action=register'), {
                    method: 'POST',
                    body: { event_id: eventId }
                });
                setMsg('Inscricao realizada com sucesso.', 'ok');
                return;
            }

            if (act === 'cancel') {
                await window.anatejeApi(ep('/api/v1/events.php?action=cancel'), {
                    method: 'POST',
                    body: { event_id: eventId }
                });
                setMsg('Inscricao cancelada.', 'ok');
                return;
            }

            if (act === 'detail') {
                const data = await window.anatejeApi(ep('/api/v1/events.php?action=detail&id=' + eventId));
                const ev = data.event || {};
                const txt = [
                    ev.titulo || '',
                    '',
                    ev.descricao || 'Sem descricao cadastrada.',
                    '',
                    'Inicio: ' + dateLabel(ev.inicio_em),
                    ev.fim_em ? 'Fim: ' + dateLabel(ev.fim_em) : ''
                ].filter(Boolean).join('\n');
                alert(txt);
            }
        } catch (err) {
            setMsg(err.message || 'Falha ao processar acao', 'err');
        }
    }

    load();
})();
</script>
