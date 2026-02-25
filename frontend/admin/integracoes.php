<?php
// Admin - Integracoes

if (!defined('TEMPLATE_ROUTED')) {
    header('Location: ../../index.php');
    exit;
}

$basePrefix = isset($prefix) ? $prefix : '/';
?>
<div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Admin - Integracoes</h1>
            <p class="text-gray-600">Configure Mailchimp e WhatsApp para campanhas externas.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="p-4 rounded-lg border border-gray-200 bg-gray-50 space-y-3">
                <h2 class="text-lg font-semibold text-gray-800">Mailchimp</h2>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input id="mc_enabled" type="checkbox"> Integracao habilitada
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">API Key</span>
                    <input id="mc_api_key" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Endpoint</span>
                    <input id="mc_endpoint" class="input-primary w-full" placeholder="https://usX.api.mailchimp.com/3.0">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Sender/From</span>
                    <input id="mc_sender" class="input-primary w-full" placeholder="contato@dominio.com">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Config JSON (opcional)</span>
                    <textarea id="mc_config" class="input-primary w-full" rows="4" placeholder='{"list_id":"..."}'></textarea>
                </label>
                <div class="flex gap-2">
                    <button id="mc_save" class="btn-primary px-4 py-2 text-sm">Salvar</button>
                    <button id="mc_test" class="btn-secondary px-4 py-2 text-sm">Testar</button>
                </div>
            </section>

            <section class="p-4 rounded-lg border border-gray-200 bg-gray-50 space-y-3">
                <h2 class="text-lg font-semibold text-gray-800">WhatsApp</h2>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input id="wa_enabled" type="checkbox"> Integracao habilitada
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">API Key</span>
                    <input id="wa_api_key" class="input-primary w-full">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Endpoint</span>
                    <input id="wa_endpoint" class="input-primary w-full" placeholder="https://api.whatsapp-provider.com">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Sender/Numero</span>
                    <input id="wa_sender" class="input-primary w-full" placeholder="5511999999999">
                </label>
                <label>
                    <span class="text-sm font-medium text-gray-700">Config JSON (opcional)</span>
                    <textarea id="wa_config" class="input-primary w-full" rows="4" placeholder='{"instance":"..."}'></textarea>
                </label>
                <div class="flex gap-2">
                    <button id="wa_save" class="btn-primary px-4 py-2 text-sm">Salvar</button>
                    <button id="wa_test" class="btn-secondary px-4 py-2 text-sm">Testar</button>
                </div>
            </section>
        </div>

        <p id="integMsg" class="text-sm mt-4"></p>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;

    const msg = document.getElementById('integMsg');

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm mt-4 text-green-600' : 'text-sm mt-4 text-red-600';
    }

    function parseConfig(text) {
        const raw = String(text || '').trim();
        if (!raw) return {};
        try {
            const json = JSON.parse(raw);
            return typeof json === 'object' && json !== null ? json : {};
        } catch {
            throw new Error('Config JSON invalido');
        }
    }

    function fill(prefix, data) {
        document.getElementById(prefix + '_enabled').checked = parseInt(data.enabled || 0, 10) === 1;
        document.getElementById(prefix + '_api_key').value = data.api_key || '';
        document.getElementById(prefix + '_endpoint').value = data.endpoint || '';
        document.getElementById(prefix + '_sender').value = data.sender || '';
        document.getElementById(prefix + '_config').value = JSON.stringify(data.config || {}, null, 2);
    }

    function payload(provider, prefix) {
        return {
            provider,
            enabled: document.getElementById(prefix + '_enabled').checked ? 1 : 0,
            api_key: document.getElementById(prefix + '_api_key').value,
            endpoint: document.getElementById(prefix + '_endpoint').value,
            sender: document.getElementById(prefix + '_sender').value,
            config: parseConfig(document.getElementById(prefix + '_config').value)
        };
    }

    async function load() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/integrations.php?action=admin_get'));
            const list = data.providers || [];
            const map = {};
            list.forEach((p) => { map[p.provider] = p; });

            fill('mc', map.MAILCHIMP || {});
            fill('wa', map.WHATSAPP || {});
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar integracoes', 'err');
        }
    }

    async function save(provider, prefix) {
        try {
            const body = payload(provider, prefix);
            await window.anatejeApi(ep('/api/v1/integrations.php?action=admin_save'), {
                method: 'POST',
                body
            });
            setMsg(`${provider} salvo com sucesso.`, 'ok');
            await load();
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar integracao', 'err');
        }
    }

    async function test(provider) {
        try {
            const data = await window.anatejeApi(ep('/api/v1/integrations.php?action=admin_test'), {
                method: 'POST',
                body: { provider }
            });
            setMsg(data.message || `Teste de ${provider} realizado com sucesso.`, 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao testar integracao', 'err');
        }
    }

    document.getElementById('mc_save').addEventListener('click', () => save('MAILCHIMP', 'mc'));
    document.getElementById('wa_save').addEventListener('click', () => save('WHATSAPP', 'wa'));
    document.getElementById('mc_test').addEventListener('click', () => test('MAILCHIMP'));
    document.getElementById('wa_test').addEventListener('click', () => test('WHATSAPP'));

    load();
})();
</script>
