<?php
// Meu Perfil

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
                <h1 class="text-2xl font-bold text-gray-800">Meu Perfil</h1>
                <p class="text-gray-600">Atualize seus dados cadastrais e endereco.</p>
            </div>
            <div class="text-sm text-gray-500">Campos com * sao obrigatorios</div>
        </div>

        <form id="perfilForm" class="space-y-6">
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Dados do Associado</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Nome *</span>
                        <input id="nome" class="input-primary w-full" required>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">CPF *</span>
                        <input id="cpf" class="input-primary w-full" maxlength="14" required>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Lotacao</span>
                        <input id="lotacao" class="input-primary w-full">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Cargo</span>
                        <input id="cargo" class="input-primary w-full">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Data de Filiacao</span>
                        <input id="data_filiacao" type="date" class="input-primary w-full">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Categoria</span>
                        <select id="categoria" class="input-primary w-full">
                            <option value="PARCIAL">Parcial (0,5%)</option>
                            <option value="INTEGRAL">Integral (1%)</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Status</span>
                        <select id="status" class="input-primary w-full">
                            <option value="ATIVO">Ativo</option>
                            <option value="INATIVO">Inativo</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Contribuicao Mensal (R$)</span>
                        <input id="contribuicao_mensal" type="number" step="0.01" class="input-primary w-full">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Matricula</span>
                        <input id="matricula" class="input-primary w-full">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Telefone</span>
                        <input id="telefone" class="input-primary w-full" maxlength="15">
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-sm font-medium text-gray-700">Email Funcional</span>
                        <input id="email_funcional" type="email" class="input-primary w-full">
                    </label>
                </div>
            </section>

            <section>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-gray-800">Endereco</h2>
                    <button type="button" id="buscarCep" class="btn-secondary px-4 py-2 text-sm">Buscar CEP</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">CEP</span>
                        <input id="cep" class="input-primary w-full" maxlength="9">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Logradouro</span>
                        <input id="logradouro" class="input-primary w-full">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Numero</span>
                        <input id="numero" class="input-primary w-full">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Complemento</span>
                        <input id="complemento" class="input-primary w-full">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Bairro</span>
                        <input id="bairro" class="input-primary w-full">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Cidade</span>
                        <input id="cidade" class="input-primary w-full">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">UF</span>
                        <input id="uf" class="input-primary w-full" maxlength="2">
                    </label>
                </div>
            </section>

            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <button id="salvarPerfil" class="btn-primary px-5 py-2" type="submit">Salvar Perfil</button>
                <p id="perfilMsg" class="text-sm"></p>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;

    const form = document.getElementById('perfilForm');
    const msg = document.getElementById('perfilMsg');

    const el = (id) => document.getElementById(id);
    const ids = ['nome','cpf','lotacao','cargo','data_filiacao','categoria','status','contribuicao_mensal','matricula','telefone','email_funcional','cep','logradouro','numero','complemento','bairro','cidade','uf'];

    function setMsg(text, type) {
        msg.textContent = text || '';
        msg.className = type === 'ok' ? 'text-sm text-green-600' : 'text-sm text-red-600';
    }

    function fill(member, address) {
        const m = member || {};
        const a = address || {};

        el('nome').value = m.nome || '';
        el('cpf').value = m.cpf ? window.anatejeMask.formatCpf(m.cpf) : '';
        el('lotacao').value = m.lotacao || '';
        el('cargo').value = m.cargo || '';
        el('data_filiacao').value = m.data_filiacao || '';
        el('categoria').value = m.categoria || 'PARCIAL';
        el('status').value = m.status || 'ATIVO';
        el('contribuicao_mensal').value = m.contribuicao_mensal || '';
        el('matricula').value = m.matricula || '';
        el('telefone').value = m.telefone ? window.anatejeMask.formatPhone(m.telefone) : '';
        el('email_funcional').value = m.email_funcional || '';

        el('cep').value = a.cep ? window.anatejeMask.formatCep(a.cep) : '';
        el('logradouro').value = a.logradouro || '';
        el('numero').value = a.numero || '';
        el('complemento').value = a.complemento || '';
        el('bairro').value = a.bairro || '';
        el('cidade').value = a.cidade || '';
        el('uf').value = a.uf || '';
    }

    async function loadPerfil() {
        try {
            const data = await window.anatejeApi(ep('/api/v1/members.php?action=get'));
            fill(data.member, data.address);
        } catch (err) {
            setMsg(err.message || 'Falha ao carregar perfil', 'err');
        }
    }

    async function buscarCep() {
        const cep = window.anatejeMask.onlyDigits(el('cep').value);
        if (cep.length !== 8) {
            setMsg('Informe um CEP valido com 8 digitos.', 'err');
            return;
        }

        try {
            setMsg('', 'ok');
            const data = await window.anatejeApi(ep('/api/v1/utils.php?action=viacep&cep=' + cep));
            el('logradouro').value = data.logradouro || '';
            el('bairro').value = data.bairro || '';
            el('cidade').value = data.cidade || '';
            el('uf').value = data.uf || '';
            setMsg('Endereco preenchido pelo ViaCEP.', 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao consultar CEP', 'err');
        }
    }

    async function salvarPerfil(event) {
        event.preventDefault();
        setMsg('', 'ok');

        const body = {
            nome: el('nome').value,
            cpf: window.anatejeMask.onlyDigits(el('cpf').value),
            lotacao: el('lotacao').value,
            cargo: el('cargo').value,
            data_filiacao: el('data_filiacao').value,
            categoria: el('categoria').value,
            status: el('status').value,
            contribuicao_mensal: el('contribuicao_mensal').value,
            matricula: el('matricula').value,
            telefone: window.anatejeMask.onlyDigits(el('telefone').value),
            email_funcional: el('email_funcional').value,
            address: {
                cep: window.anatejeMask.onlyDigits(el('cep').value),
                logradouro: el('logradouro').value,
                numero: el('numero').value,
                complemento: el('complemento').value,
                bairro: el('bairro').value,
                cidade: el('cidade').value,
                uf: el('uf').value
            }
        };

        try {
            await window.anatejeApi(ep('/api/v1/members.php?action=update'), { method: 'POST', body });
            setMsg('Perfil salvo com sucesso.', 'ok');
        } catch (err) {
            setMsg(err.message || 'Falha ao salvar perfil', 'err');
        }
    }

    el('buscarCep').addEventListener('click', buscarCep);
    form.addEventListener('submit', salvarPerfil);

    el('cpf').addEventListener('input', (e) => {
        e.target.value = window.anatejeMask.formatCpf(e.target.value);
    });
    el('telefone').addEventListener('input', (e) => {
        e.target.value = window.anatejeMask.formatPhone(e.target.value);
    });
    el('cep').addEventListener('input', (e) => {
        e.target.value = window.anatejeMask.formatCep(e.target.value);
    });
    el('uf').addEventListener('input', (e) => {
        e.target.value = (e.target.value || '').toUpperCase().slice(0, 2);
    });

    loadPerfil();
})();
</script>
