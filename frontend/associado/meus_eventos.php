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
            <p class="text-gray-600">Inscreva-se, acompanhe fila de espera e status de check-in.</p>
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
    let cache = [];

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function dateLabel(v) {
        if (!v) return '-';
        const d = new Date(String(v).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return v;
        return d.toLocaleString('pt-BR');
    }

    function accessLabel(scope) {
        const s = String(scope || 'ALL').toUpperCase();
        if (s === 'PARCIAL') return 'Somente PARCIAL';
        if (s === 'INTEGRAL') return 'Somente INTEGRAL';
        return 'Todos associados';
    }

    function statusChip(status) {
        const s = String(status || '').toLowerCase();
        if (s === 'checked_in') return '<span class="text-xs px-2 py-1 rounded bg-green-100 text-green-700">Check-in confirmado</span>';
        if (s === 'registered') return '<span class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-700">Inscrito</span>';
        if (s === 'waitlisted') return '<span class="text-xs px-2 py-1 rounded bg-amber-100 text-amber-700">Fila de espera</span>';
        if (s === 'canceled') return '<span class="text-xs px-2 py-1 rounded bg-gray-200 text-gray-700">Cancelado</span>';
        return '<span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-600">Sem inscricao</span>';
    }

    function canRegister(ev) {
        const st = String(ev.my_registration_status || '').toLowerCase();
        return st === '' || st === 'canceled' || st === 'waitlisted';
    }

    function canCancel(ev) {
        const st = String(ev.my_registration_status || '').toLowerCase();
        return st === 'registered' || st === 'waitlisted' || st === 'checked_in';
    }

    function render(items) {
        if (!items.length) {
            list.innerHTML = '<div class="text-sm text-gray-600">Nenhum evento publicado no momento.</div>';
            return;
        }

        list.innerHTML = items.map((ev) => {
            const status = String(ev.my_registration_status || '');
            const vagas = ev.vagas == null ? 'Ilimitadas' : String(ev.vagas);
            const livres = ev.vagas_restantes == null ? '-' : String(ev.vagas_restantes);
            return `
            <article class="p-4 rounded-lg border border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-base font-semibold text-gray-800">${ev.titulo}</h3>
                    ${statusChip(status)}
                </div>
                <p class="text-sm text-gray-600 mt-1">${ev.local || 'Local a definir'}</p>
                <p class="text-sm text-gray-600">Inicio: ${dateLabel(ev.inicio_em)}</p>
                ${ev.fim_em ? `<p class="text-sm text-gray-600">Fim: ${dateLabel(ev.fim_em)}</p>` : ''}
                <p class="text-xs text-gray-500 mt-1">Acesso: ${accessLabel(ev.access_scope)} | Vagas: ${vagas} | Livres: ${livres}</p>
                ${ev.my_checked_in_at ? `<p class="text-xs text-green-700 mt-1">Check-in em: ${dateLabel(ev.my_checked_in_at)}</p>` : ''}
                <div class="flex flex-wrap gap-2 mt-3">
                    <button class="btn-primary px-3 py-1 text-xs" data-act="register" data-id="${ev.id}" ${canRegister(ev) ? '' : 'disabled'}>Inscrever</button>
                    <button class="btn-secondary px-3 py-1 text-xs" data-act="cancel" data-id="${ev.id}" ${canCancel(ev) ? '' : 'disabled'}>Cancelar</button>
                    <button class="btn-secondary px-3 py-1 text-xs" data-act="detail" data-id="${ev.id}">Detalhes</button>
                </div>
            </article>
        `;
        }).join('');

        list.querySelectorAll('button[data-act]').forEach((btn) => {
            btn.addEventListener('click', onAction);
        });
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/events.php?action=list'));
            cache = data.events || [];
            render(cache);
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar eventos', 'err');
        }
    }

    async function onAction(e) {
        const btn = e.currentTarget;
        if (btn.disabled) return;
        const act = btn.getAttribute('data-act');
        const eventId = parseInt(btn.getAttribute('data-id'), 10);

        try {
            if (act === 'register') {
                const data = await window.anatejeApi(ep('/api/v1/events.php?action=register'), {
                    method: 'POST',
                    body: { event_id: eventId }
                });
                if (data.waitlisted) {
                    setMsg('Evento lotado. Voce entrou na fila de espera.', 'ok');
                } else {
                    setMsg('Inscricao realizada com sucesso.', 'ok');
                }
                await load();
                return;
            }

            if (act === 'cancel') {
                await window.anatejeApi(ep('/api/v1/events.php?action=cancel'), {
                    method: 'POST',
                    body: { event_id: eventId }
                });
                setMsg('Inscricao cancelada.', 'ok');
                await load();
                return;
            }

            if (act === 'detail') {
                const data = await window.anatejeApi(ep('/api/v1/events.php?action=detail&id=' + eventId));
                const ev = data.event || {};
                const reg = data.registration || {};
                const txt = [
                    ev.titulo || '',
                    '',
                    ev.descricao || 'Sem descricao cadastrada.',
                    '',
                    'Inicio: ' + dateLabel(ev.inicio_em),
                    ev.fim_em ? 'Fim: ' + dateLabel(ev.fim_em) : '',
                    'Acesso: ' + accessLabel(ev.access_scope),
                    'Minha inscricao: ' + (reg.status || 'sem inscricao')
                ].filter(Boolean).join('\n');
                if (window.anatejeUi && typeof window.anatejeUi.info === 'function') {
                    await window.anatejeUi.info('Detalhes do evento', txt);
                } else if (typeof window.SweetAlertConfig !== 'undefined' && typeof window.SweetAlertConfig.info === 'function') {
                    await window.SweetAlertConfig.info('Detalhes do evento', txt);
                }
            }
        } catch (err) {
            setMsg(err.message || 'Falha ao processar acao', 'err');
        }
    }

    load();
})();
</script>
