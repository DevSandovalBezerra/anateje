if (typeof window.contasBancariasData === 'undefined') {
    window.contasBancariasData = [];
    window.registroEditando = null;
}

contasBancariasData = window.contasBancariasData;
registroEditando = window.registroEditando;

// Função helper segura para obter URL da API
function getApiUrl(path) {
    // Verificar se window.getApiUrl existe e não é a própria função (evitar recursão)
    if (typeof window.getApiUrl === 'function' && window.getApiUrl !== getApiUrl) {
        return window.getApiUrl(path);
    }
    // Fallback: usar apiConfig diretamente
    if (typeof apiConfig !== 'undefined' && apiConfig && typeof apiConfig.getApiEndpoint === 'function') {
        return apiConfig.getApiEndpoint(path);
    }
    // Último fallback: caminho relativo
    return `../../api/${path}`;
}

function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor || 0);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function carregarContas() {
    try {
        const tipo = document.getElementById('selectFiltroTipo')?.value || '';
        const ativo = document.getElementById('selectFiltroAtivo')?.value || '';
        const buscar = document.getElementById('inputBuscar')?.value || '';
        
        const params = new URLSearchParams();
        if (tipo) params.append('tipo_conta', tipo);
        if (ativo) params.append('ativo', ativo);
        if (buscar) params.append('buscar', buscar);
        
        const url = getApiUrl(`financeiro/contas_bancarias.php?action=listar&${params.toString()}`);
        const response = await fetch(url, { credentials: 'include' });
        
        if (!response.ok) {
            if (response.status === 401) {
                if (typeof SweetAlertConfig !== 'undefined') {
                    SweetAlertConfig.warning('Sessão expirada', 'Você será redirecionado para fazer login.').then(() => {
                        window.location.href = '../auth/login.html';
                    });
                }
                return;
            }
            throw new Error(`Erro HTTP ${response.status}`);
        }
        
        const result = await response.json();
        if (result.success) {
            contasBancariasData = result.data || [];
            renderizarTabela();
        } else {
            throw new Error(result.message || 'Erro ao carregar contas');
        }
    } catch (error) {
        console.error('Erro ao carregar contas:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar contas: ' + error.message);
        }
    }
}

function renderizarTabela() {
    const tbody = document.getElementById('tbodyContas');
    if (!tbody) return;
    
    if (contasBancariasData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-secondary-dark-gray">
                    <i data-lucide="credit-card" class="w-8 h-8 mx-auto mb-2"></i>
                    <p>Nenhuma conta encontrada</p>
                </td>
            </tr>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }
    
    tbody.innerHTML = contasBancariasData.map(conta => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">${escapeHtml(conta.nome_conta || '')}</td>
            <td class="px-4 py-3">${escapeHtml(conta.banco || '')}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full ${
                    conta.tipo_conta === 'corrente' ? 'bg-blue-100 text-blue-800' :
                    conta.tipo_conta === 'investimento' ? 'bg-green-100 text-green-800' :
                    'bg-gray-100 text-gray-800'
                }">
                    ${escapeHtml(conta.tipo_conta || '')}
                </span>
            </td>
            <td class="px-4 py-3">${escapeHtml(conta.agencia || '-')}</td>
            <td class="px-4 py-3">${escapeHtml(conta.numero_conta || '-')}</td>
            <td class="px-4 py-3 font-semibold ${(conta.saldo_real || 0) >= 0 ? 'text-green-600' : 'text-red-600'}">
                ${formatarMoeda(conta.saldo_real || 0)}
            </td>
            <td class="px-4 py-3 font-semibold ${(conta.saldo_previsto || 0) >= 0 ? 'text-blue-600' : 'text-red-600'}">
                ${formatarMoeda(conta.saldo_previsto || 0)}
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full ${conta.ativo == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${conta.ativo == 1 ? 'Ativo' : 'Inativo'}
                </span>
            </td>
            <td class="px-4 py-3">
                <div class="flex gap-2">
                    <button onclick="editarConta(${conta.id})" class="text-green-600 hover:text-green-700" title="Editar">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                    </button>
                    <button onclick="excluirConta(${conta.id})" class="text-red-500 hover:text-red-600" title="Excluir">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function editarConta(id) {
    try {
        const url = getApiUrl(`financeiro/contas_bancarias.php?action=obter&id=${id}`);
        const response = await fetch(url, { credentials: 'include' });
        const result = await response.json();
        
        if (result.success) {
            registroEditando = result.data;
            abrirModal();
        } else {
            throw new Error(result.message || 'Erro ao carregar conta');
        }
    } catch (error) {
        console.error('Erro ao carregar conta:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar conta: ' + error.message);
        }
    }
}

async function excluirConta(id) {
    if (typeof SweetAlertConfig === 'undefined') {
        if (!confirm('Tem certeza que deseja excluir esta conta?')) return;
    } else {
        const confirmacao = await SweetAlertConfig.confirm(
            'Excluir Conta',
            'Tem certeza que deseja excluir esta conta? Esta ação não pode ser desfeita.',
            'Excluir',
            'Cancelar'
        );
        if (!confirmacao) return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);
        
        const url = getApiUrl('financeiro/contas_bancarias.php');
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.success('Sucesso', 'Conta excluída com sucesso!');
            }
            await carregarContas();
        } else {
            throw new Error(result.message || 'Erro ao excluir conta');
        }
    } catch (error) {
        console.error('Erro ao excluir conta:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao excluir conta: ' + error.message);
        }
    }
}

function abrirModal() {
    const conta = registroEditando || {};
    
    if (typeof SweetAlertConfig !== 'undefined' && SweetAlertConfig.html) {
        SweetAlertConfig.html({
            title: conta.id ? 'Editar Conta Bancária' : 'Nova Conta Bancária',
            html: `
                <form id="formConta" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nome da Conta *</label>
                        <input type="text" id="inputNomeConta" value="${escapeHtml(conta.nome_conta || '')}" class="input-primary w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Banco *</label>
                        <input type="text" id="inputBanco" value="${escapeHtml(conta.banco || '')}" class="input-primary w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tipo *</label>
                        <select id="selectTipo" class="input-primary w-full" required>
                            <option value="">Selecione...</option>
                            <option value="corrente" ${conta.tipo_conta === 'corrente' ? 'selected' : ''}>Corrente</option>
                            <option value="investimento" ${conta.tipo_conta === 'investimento' ? 'selected' : ''}>Investimento</option>
                            <option value="caixa" ${conta.tipo_conta === 'caixa' ? 'selected' : ''}>Caixa</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Agência</label>
                            <input type="text" id="inputAgencia" value="${escapeHtml(conta.agencia || '')}" class="input-primary w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Número da Conta</label>
                            <input type="text" id="inputNumeroConta" value="${escapeHtml(conta.numero_conta || '')}" class="input-primary w-full">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Saldo Inicial *</label>
                            <input type="number" id="inputSaldoInicial" value="${conta.saldo_inicial || 0}" step="0.01" class="input-primary w-full" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Data Saldo Inicial *</label>
                            <input type="date" id="inputDataSaldoInicial" value="${conta.data_saldo_inicial || ''}" class="input-primary w-full" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Observações</label>
                        <textarea id="textareaObservacoes" class="input-primary w-full" rows="2">${escapeHtml(conta.observacoes || '')}</textarea>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="checkAtivo" ${conta.ativo != 0 ? 'checked' : ''} class="mr-2">
                            <span>Ativo</span>
                        </label>
                    </div>
                </form>
            `,
            confirmButtonText: conta.id ? 'Atualizar' : 'Criar',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                await salvarConta(conta.id);
            }
        });
    }
}

async function salvarConta(id) {
    try {
        const formData = new FormData();
        formData.append('action', id ? 'atualizar' : 'criar');
        if (id) formData.append('id', id);
        formData.append('nome_conta', document.getElementById('inputNomeConta').value);
        formData.append('banco', document.getElementById('inputBanco').value);
        formData.append('tipo_conta', document.getElementById('selectTipo').value);
        formData.append('agencia', document.getElementById('inputAgencia').value);
        formData.append('numero_conta', document.getElementById('inputNumeroConta').value);
        formData.append('saldo_inicial', document.getElementById('inputSaldoInicial').value);
        formData.append('data_saldo_inicial', document.getElementById('inputDataSaldoInicial').value);
        formData.append('observacoes', document.getElementById('textareaObservacoes').value);
        formData.append('ativo', document.getElementById('checkAtivo').checked ? '1' : '0');
        
        const url = getApiUrl('financeiro/contas_bancarias.php');
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.success('Sucesso', id ? 'Conta atualizada com sucesso!' : 'Conta criada com sucesso!');
            }
            registroEditando = null;
            await carregarContas();
        } else {
            throw new Error(result.message || 'Erro ao salvar conta');
        }
    } catch (error) {
        console.error('Erro ao salvar conta:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao salvar conta: ' + error.message);
        }
    }
}

let inicializandoContasBancarias = false;
let contasBancariasInicializado = false;
const handlersContasBancarias = {
    btnNovaConta: null,
    btnFiltrar: null,
    inputBuscar: null
};

async function initContasBancarias() {
    if (inicializandoContasBancarias) return;
    
    inicializandoContasBancarias = true;
    
    setTimeout(async () => {
        const container = document.getElementById('tbodyContas') || document.querySelector('.cadastros-content');
        if (!container) {
            inicializandoContasBancarias = false;
            setTimeout(async () => {
                if (document.getElementById('tbodyContas') || document.querySelector('.cadastros-content')) {
                    await initContasBancarias();
                }
            }, 200);
            return;
        }

        const btnNovaConta = document.getElementById('btnNovaConta');
        if (btnNovaConta) {
            if (handlersContasBancarias.btnNovaConta) {
                btnNovaConta.removeEventListener('click', handlersContasBancarias.btnNovaConta);
            }
            handlersContasBancarias.btnNovaConta = () => {
                registroEditando = null;
                abrirModal();
            };
            btnNovaConta.addEventListener('click', handlersContasBancarias.btnNovaConta);
        }
        
        const btnFiltrar = document.getElementById('btnFiltrar');
        if (btnFiltrar) {
            if (handlersContasBancarias.btnFiltrar) {
                btnFiltrar.removeEventListener('click', handlersContasBancarias.btnFiltrar);
            }
            handlersContasBancarias.btnFiltrar = carregarContas;
            btnFiltrar.addEventListener('click', handlersContasBancarias.btnFiltrar);
        }
        
        const inputBuscar = document.getElementById('inputBuscar');
        if (inputBuscar) {
            if (handlersContasBancarias.inputBuscar) {
                inputBuscar.removeEventListener('keypress', handlersContasBancarias.inputBuscar);
            }
            handlersContasBancarias.inputBuscar = (e) => {
                if (e.key === 'Enter') carregarContas();
            };
            inputBuscar.addEventListener('keypress', handlersContasBancarias.inputBuscar);
        }
        
        await carregarContas();
        
        contasBancariasInicializado = true;
        inicializandoContasBancarias = false;
    }, 100);
}

document.addEventListener('DOMContentLoaded', () => {
    contasBancariasInicializado = false;
    initContasBancarias();
});

document.addEventListener('lidergest:page-ready', (event) => {
    if (event.detail && event.detail.page === 'financeiro/contas_bancarias') {
        contasBancariasInicializado = false;
        initContasBancarias();
    }
});



