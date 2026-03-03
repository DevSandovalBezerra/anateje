// Usar window para evitar redeclaração em navegação AJAX
if (typeof window.contasData === 'undefined') {
    window.contasData = [];
    window.contaEditando = null;
    window.unidadesData = [];
    window.isAdminGlobal = false;
    window.userUnidadeId = null;
}

// Criar referências locais sem redeclarar (atribuição sem declaração)
contasData = window.contasData;
contaEditando = window.contaEditando;
unidadesData = window.unidadesData;
isAdminGlobal = window.isAdminGlobal;
userUnidadeId = window.userUnidadeId;

function inicializarContexto() {
    const container = document.querySelector('.cadastros-content');
    if (!container) return;

    const perfilAttr = container.dataset.perfil;
    const unidadeAttr = container.dataset.unidade;

    isAdminGlobal = parseInt(perfilAttr ?? '0', 10) === 1;
    userUnidadeId = unidadeAttr ? parseInt(unidadeAttr, 10) : null;

    if (!isAdminGlobal) {
        const filtroUnidadeContainer = document.getElementById('filtroUnidadeContainer');
        if (filtroUnidadeContainer) filtroUnidadeContainer.style.display = 'none';
    }
}

async function carregarUnidades() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('cadastros/unidades.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/cadastros/unidades.php?action=listar`
                : `../../api/cadastros/unidades.php?action=listar`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            unidadesData = result.data || [];
            preencherSelectUnidades();
            preencherFiltroUnidades();
        }
    } catch (error) {
        console.error('Erro ao carregar unidades:', error);
    }
}

function preencherSelectUnidades() {
    const select = document.getElementById('selectUnidade');
    if (!select) return;
    
    select.innerHTML = '<option value="">Selecione uma unidade</option>';
    unidadesData.forEach(unidade => {
        if (unidade.ativo == 1) {
            const option = document.createElement('option');
            option.value = unidade.id;
            option.textContent = unidade.nome;
            select.appendChild(option);
        }
    });
}

function preencherFiltroUnidades() {
    const select = document.getElementById('selectFiltroUnidade');
    if (!select) return;
    
    select.innerHTML = '<option value="">Todas</option>';
    unidadesData.forEach(unidade => {
        if (unidade.ativo == 1) {
            const option = document.createElement('option');
            option.value = unidade.id;
            option.textContent = unidade.nome;
            select.appendChild(option);
        }
    });
}

async function carregarContas() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/contas_financeiras.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/contas_financeiras.php?action=listar`
                : `../../api/financeiro/contas_financeiras.php?action=listar`);
        
        const response = await fetch(url, { credentials: 'include' });

        if (!response.ok) {
            if (response.status === 401) {
                if (typeof SweetAlertConfig !== 'undefined') {
                    SweetAlertConfig.warning('Sessão expirada', 'Você será redirecionado para fazer login.').then(() => {
                        window.location.href = '../auth/login.html';
                    });
                } else {
                    alert('Sessão expirada');
                    window.location.href = '../auth/login.html';
                }
                return;
            }
            throw new Error(`Erro HTTP ${response.status}`);
        }

        const result = await response.json();
        
        if (result.success) {
            contasData = result.data || [];
            renderizarTabela();
        } else {
            throw new Error(result.message || 'Erro ao carregar contas');
        }
    } catch (error) {
        console.error('Erro ao carregar contas:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar contas: ' + error.message);
        } else {
            alert('Erro ao carregar contas: ' + error.message);
        }
    }
}

function renderizarTabela(filtro = '') {
    const tbody = document.getElementById('tbodyContas');
    if (!tbody) return;

    let contasFiltradas = contasData;
    
    if (filtro) {
        const filtroLower = filtro.toLowerCase();
        contasFiltradas = contasData.filter(c => 
            c.nome?.toLowerCase().includes(filtroLower) ||
            c.banco?.toLowerCase().includes(filtroLower)
        );
    }

    const filtroStatus = document.getElementById('selectFiltroStatus')?.value;
    if (filtroStatus) {
        contasFiltradas = contasFiltradas.filter(c => c.status === filtroStatus);
    }

    const filtroUnidade = document.getElementById('selectFiltroUnidade')?.value;
    if (filtroUnidade) {
        contasFiltradas = contasFiltradas.filter(c => c.unidade_id == filtroUnidade);
    }

    if (contasFiltradas.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-secondary-dark-gray">
                    Nenhuma conta encontrada
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = contasFiltradas.map(conta => {
        const tipoLabels = {
            'conta_corrente': 'Conta Corrente',
            'poupanca': 'Poupança',
            'carteira_digital': 'Carteira Digital',
            'caixa_fisica': 'Caixa Física'
        };
        const tipoLabel = tipoLabels[conta.tipo] || conta.tipo;
        const saldo = parseFloat(conta.saldo_atual || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const status = conta.status === 'ativa' ? 'Ativa' : 'Inativa';
        const statusClass = conta.status === 'ativa' ? 'text-green-600' : 'text-red-600';
        const unidadeNome = conta.unidade_nome || '-';
        const banco = conta.banco || '-';

        return `
            <tr>
                <td class="px-4 py-2">
                    <p class="font-medium text-secondary-black">${escapeHtml(conta.nome || '')}</p>
                </td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(tipoLabel)}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(banco)}</td>
                <td class="px-4 py-2 text-sm font-medium text-secondary-black">${saldo}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(unidadeNome)}</td>
                <td class="px-4 py-2 text-sm ${statusClass}">${status}</td>
                <td class="px-4 py-2">
                    <div class="flex space-x-2">
                        <button onclick="editarConta(${conta.id})" class="text-green-600 hover:text-green-700" title="Editar">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        <button onclick="excluirConta(${conta.id})" class="text-red-500 hover:text-red-600" title="Excluir">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    lucide.createIcons();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function criarConta() {
    contaEditando = null;
    if (isAdminGlobal) {
        await carregarUnidades();
    }
    abrirModal();
}

async function editarConta(id) {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl(`financeiro/contas_financeiras.php?action=obter&id=${id}`)
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/contas_financeiras.php?action=obter&id=${id}`
                : `../../api/financeiro/contas_financeiras.php?action=obter&id=${id}`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            contaEditando = result.data;
            if (isAdminGlobal) {
                await carregarUnidades();
            }
            abrirModal();
        } else {
            throw new Error(result.message || 'Erro ao carregar conta');
        }
    } catch (error) {
        console.error('Erro ao carregar conta:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar conta: ' + error.message);
        } else {
            alert('Erro ao carregar conta: ' + error.message);
        }
    }
}

function abrirModal() {
    const modal = document.getElementById('modalConta');
    if (!modal) {
        criarModal();
        return;
    }

    const form = document.getElementById('formConta');
    if (form) {
        form.reset();
    }

    setTimeout(() => {
        if (contaEditando) {
            document.getElementById('modalTitulo').textContent = 'Editar Conta';
            document.getElementById('inputNome').value = contaEditando.nome || '';
            document.getElementById('selectTipo').value = contaEditando.tipo || 'conta_corrente';
            document.getElementById('inputBanco').value = contaEditando.banco || '';
            document.getElementById('inputAgencia').value = contaEditando.agencia || '';
            document.getElementById('inputNumeroConta').value = contaEditando.numero_conta || '';
            document.getElementById('inputTitular').value = contaEditando.titular || '';
            document.getElementById('inputDocumento').value = contaEditando.documento || '';
            document.getElementById('inputSaldoInicial').value = contaEditando.saldo_inicial || '0';
            document.getElementById('inputDataSaldo').value = contaEditando.data_saldo || '';
            document.getElementById('selectStatus').value = contaEditando.status || 'ativa';
            document.getElementById('textareaObservacoes').value = contaEditando.observacoes || '';
            if (isAdminGlobal && document.getElementById('selectUnidade')) {
                document.getElementById('selectUnidade').value = contaEditando.unidade_id || '';
            }
        } else {
            document.getElementById('modalTitulo').textContent = 'Nova Conta';
        }
    }, 100);

    modal.classList.remove('hidden');
}

function fecharModal() {
    const modal = document.getElementById('modalConta');
    if (modal) {
        modal.classList.add('hidden');
    }
    contaEditando = null;
}

function criarModal() {
    const modalHTML = `
        <div id="modalConta" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h2 id="modalTitulo" class="text-xl font-bold text-secondary-black">Nova Conta</h2>
                    <button onclick="fecharModal()" class="text-secondary-dark-gray hover:text-secondary-black">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <form id="formConta" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Nome da Conta *</label>
                        <input type="text" id="inputNome" required class="input-primary w-full" placeholder="Ex: Conta Principal, Caixa Físico">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Tipo *</label>
                            <select id="selectTipo" required class="input-primary w-full">
                                <option value="conta_corrente">Conta Corrente</option>
                                <option value="poupanca">Poupança</option>
                                <option value="carteira_digital">Carteira Digital</option>
                                <option value="caixa_fisica">Caixa Física</option>
                            </select>
                        </div>
                        ${isAdminGlobal ? `
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Unidade *</label>
                            <select id="selectUnidade" required class="input-primary w-full">
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                        ` : ''}
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Banco</label>
                            <input type="text" id="inputBanco" class="input-primary w-full" placeholder="Ex: Banco do Brasil">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Agência</label>
                            <input type="text" id="inputAgencia" class="input-primary w-full" placeholder="0000-0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Número da Conta</label>
                            <input type="text" id="inputNumeroConta" class="input-primary w-full" placeholder="00000-0">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Titular</label>
                            <input type="text" id="inputTitular" class="input-primary w-full" placeholder="Nome do titular">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Documento</label>
                            <input type="text" id="inputDocumento" class="input-primary w-full" placeholder="CPF/CNPJ">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Saldo Inicial</label>
                            <input type="number" id="inputSaldoInicial" step="0.01" class="input-primary w-full" placeholder="0.00" value="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Data do Saldo</label>
                            <input type="date" id="inputDataSaldo" class="input-primary w-full">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Observações</label>
                        <textarea id="textareaObservacoes" rows="3" class="input-primary w-full" placeholder="Observações adicionais"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                        <select id="selectStatus" class="input-primary w-full">
                            <option value="ativa">Ativa</option>
                            <option value="inativa">Inativa</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" onclick="fecharModal()" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    lucide.createIcons();
    
    document.getElementById('formConta').addEventListener('submit', async (e) => {
        e.preventDefault();
        await salvarConta();
    });
}

async function salvarConta() {
    const nome = document.getElementById('inputNome').value.trim();
    const tipo = document.getElementById('selectTipo').value;
    const banco = document.getElementById('inputBanco').value.trim();
    const agencia = document.getElementById('inputAgencia').value.trim();
    const numeroConta = document.getElementById('inputNumeroConta').value.trim();
    const titular = document.getElementById('inputTitular').value.trim();
    const documento = document.getElementById('inputDocumento').value.trim();
    const saldoInicial = document.getElementById('inputSaldoInicial').value;
    const dataSaldo = document.getElementById('inputDataSaldo').value;
    const status = document.getElementById('selectStatus').value;
    const observacoes = document.getElementById('textareaObservacoes').value.trim();
    const unidadeId = isAdminGlobal ? document.getElementById('selectUnidade')?.value : null;

    if (!nome || !tipo) {
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.warning('Atenção', 'Nome e tipo são obrigatórios');
        } else {
            alert('Nome e tipo são obrigatórios');
        }
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', contaEditando ? 'atualizar' : 'criar');
        if (contaEditando) {
            formData.append('id', contaEditando.id);
        }
        formData.append('nome', nome);
        formData.append('tipo', tipo);
        if (banco) formData.append('banco', banco);
        if (agencia) formData.append('agencia', agencia);
        if (numeroConta) formData.append('numero_conta', numeroConta);
        if (titular) formData.append('titular', titular);
        if (documento) formData.append('documento', documento);
        formData.append('saldo_inicial', saldoInicial || '0');
        if (dataSaldo) formData.append('data_saldo', dataSaldo);
        formData.append('status', status);
        if (observacoes) formData.append('observacoes', observacoes);
        if (isAdminGlobal && unidadeId) {
            formData.append('unidade_id', unidadeId);
        }

        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/contas_financeiras.php')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/contas_financeiras.php`
                : `../../api/financeiro/contas_financeiras.php`);

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        if (!response.ok) {
            if (response.status === 401) {
                if (typeof SweetAlertConfig !== 'undefined') {
                    SweetAlertConfig.warning('Sessão expirada', 'Você será redirecionado para fazer login.').then(() => {
                        window.location.href = '../auth/login.html';
                    });
                } else {
                    alert('Sessão expirada');
                    window.location.href = '../auth/login.html';
                }
                return;
            }
            throw new Error(`Erro HTTP ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.success('Sucesso', result.message || 'Conta salva com sucesso');
            } else {
                alert(result.message || 'Conta salva com sucesso');
            }
            fecharModal();
            await carregarContas();
        } else {
            throw new Error(result.message || 'Erro ao salvar conta');
        }
    } catch (error) {
        console.error('Erro ao salvar conta:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao salvar conta: ' + error.message);
        } else {
            alert('Erro ao salvar conta: ' + error.message);
        }
    }
}

async function excluirConta(id) {
    try {
        const confirmacao = await SweetAlertConfig.confirm(
            'Confirmar Exclusão',
            'Tem certeza que deseja excluir esta conta? Esta ação não pode ser desfeita.',
            'Excluir',
            'Cancelar'
        );

        if (!confirmacao.isConfirmed) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);

        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/contas_financeiras.php')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/contas_financeiras.php`
                : `../../api/financeiro/contas_financeiras.php`);

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        if (!response.ok) {
            if (response.status === 401) {
                if (typeof SweetAlertConfig !== 'undefined') {
                    SweetAlertConfig.warning('Sessão expirada', 'Você será redirecionado para fazer login.').then(() => {
                        window.location.href = '../auth/login.html';
                    });
                } else {
                    alert('Sessão expirada');
                    window.location.href = '../auth/login.html';
                }
                return;
            }
            throw new Error(`Erro HTTP ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.success('Sucesso', result.message || 'Conta excluída com sucesso');
            } else {
                alert(result.message || 'Conta excluída com sucesso');
            }
            await carregarContas();
        } else {
            throw new Error(result.message || 'Erro ao excluir conta');
        }
    } catch (error) {
        console.error('Erro ao excluir conta:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao excluir conta: ' + error.message);
        } else {
            alert('Erro ao excluir conta: ' + error.message);
        }
    }
}

let inicializandoContasFinanceiras = false;
let contasFinanceirasInicializado = false;
const handlersContasFinanceiras = {
    btnNovaConta: null,
    inputBuscar: null,
    selectFiltroStatus: null,
    selectFiltroUnidade: null
};

async function initContasFinanceiras() {
    if (inicializandoContasFinanceiras) return;
    if (contasFinanceirasInicializado) return;
    
    inicializandoContasFinanceiras = true;
    
    setTimeout(async () => {
        const tbody = document.getElementById('tbodyContas');
        if (!tbody) {
            inicializandoContasFinanceiras = false;
            setTimeout(async () => {
                if (document.getElementById('tbodyContas')) {
                    await initContasFinanceiras();
                }
            }, 200);
            return;
        }

        inicializarContexto();
        if (isAdminGlobal) {
            await carregarUnidades();
        }
        await carregarContas();
        
        const btnNovaConta = document.getElementById('btnNovaConta');
        if (btnNovaConta && !handlersContasFinanceiras.btnNovaConta) {
            handlersContasFinanceiras.btnNovaConta = criarConta;
            btnNovaConta.addEventListener('click', handlersContasFinanceiras.btnNovaConta);
        }

        const inputBuscar = document.getElementById('inputBuscar');
        if (inputBuscar && !handlersContasFinanceiras.inputBuscar) {
            handlersContasFinanceiras.inputBuscar = (e) => {
                renderizarTabela(e.target.value);
            };
            inputBuscar.addEventListener('input', handlersContasFinanceiras.inputBuscar);
        }

        const selectFiltroStatus = document.getElementById('selectFiltroStatus');
        if (selectFiltroStatus && !handlersContasFinanceiras.selectFiltroStatus) {
            handlersContasFinanceiras.selectFiltroStatus = () => {
                const filtro = inputBuscar?.value || '';
                renderizarTabela(filtro);
            };
            selectFiltroStatus.addEventListener('change', handlersContasFinanceiras.selectFiltroStatus);
        }

        const selectFiltroUnidade = document.getElementById('selectFiltroUnidade');
        if (selectFiltroUnidade && !handlersContasFinanceiras.selectFiltroUnidade) {
            handlersContasFinanceiras.selectFiltroUnidade = () => {
                const filtro = inputBuscar?.value || '';
                renderizarTabela(filtro);
            };
            selectFiltroUnidade.addEventListener('change', handlersContasFinanceiras.selectFiltroUnidade);
        }
        
        contasFinanceirasInicializado = true;
        inicializandoContasFinanceiras = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initContasFinanceiras);
} else {
    initContasFinanceiras();
}

