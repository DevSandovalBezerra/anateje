if (typeof window.transferenciasData === 'undefined') {
    window.transferenciasData = [];
    window.contasBancariasData = [];
}

transferenciasData = window.transferenciasData;
contasBancariasData = window.contasBancariasData;

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

function formatarData(data) {
    if (!data) return '-';
    return new Date(data + 'T00:00:00').toLocaleDateString('pt-BR');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function carregarContas() {
    try {
        const url = getApiUrl('financeiro/contas_bancarias.php?action=listar&ativo=1');
        const response = await fetch(url, { credentials: 'include' });
        const result = await response.json();
        
        if (result.success) {
            contasBancariasData = result.data || [];
            preencherSelect('selectFiltroOrigem', contasBancariasData, 'nome_conta', 'id', true);
            preencherSelect('selectFiltroDestino', contasBancariasData, 'nome_conta', 'id', true);
        }
    } catch (error) {
        console.error('Erro ao carregar contas:', error);
    }
}

function preencherSelect(id, dados, campoTexto, campoValor, incluirTodos = false) {
    const select = document.getElementById(id);
    if (!select) return;
    
    select.innerHTML = '';
    if (incluirTodos) {
        select.innerHTML = '<option value="">Todas</option>';
    }
    
    dados.forEach(item => {
        const option = document.createElement('option');
        option.value = item[campoValor];
        option.textContent = item[campoTexto];
        select.appendChild(option);
    });
}

async function carregarTransferencias() {
    try {
        const origem = document.getElementById('selectFiltroOrigem')?.value || '';
        const destino = document.getElementById('selectFiltroDestino')?.value || '';
        const dataInicio = document.getElementById('inputDataInicio')?.value || '';
        const dataFim = document.getElementById('inputDataFim')?.value || '';
        
        const params = new URLSearchParams();
        if (origem) params.append('conta_origem_id', origem);
        if (destino) params.append('conta_destino_id', destino);
        if (dataInicio) params.append('data_inicio', dataInicio);
        if (dataFim) params.append('data_fim', dataFim);
        
        const url = getApiUrl(`financeiro/transferencias.php?action=listar&${params.toString()}`);
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
            transferenciasData = result.data || [];
            renderizarTabela();
        } else {
            throw new Error(result.message || 'Erro ao carregar transferências');
        }
    } catch (error) {
        console.error('Erro ao carregar transferências:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar transferências: ' + error.message);
        }
    }
}

function renderizarTabela() {
    const tbody = document.getElementById('tbodyTransferencias');
    if (!tbody) return;
    
    if (transferenciasData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-secondary-dark-gray">
                    <i data-lucide="arrow-left-right" class="w-8 h-8 mx-auto mb-2"></i>
                    <p>Nenhuma transferência encontrada</p>
                </td>
            </tr>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }
    
    tbody.innerHTML = transferenciasData.map(transf => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3">${formatarData(transf.data_transferencia)}</td>
            <td class="px-4 py-3 font-medium">${escapeHtml(transf.titulo || '')}</td>
            <td class="px-4 py-3">${escapeHtml(transf.conta_origem_nome || '')}</td>
            <td class="px-4 py-3">${escapeHtml(transf.conta_destino_nome || '')}</td>
            <td class="px-4 py-3 font-semibold text-blue-600">${formatarMoeda(transf.valor || 0)}</td>
            <td class="px-4 py-3">
                <button onclick="excluirTransferencia(${transf.id})" class="text-red-500 hover:text-red-600" title="Excluir">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function excluirTransferencia(id) {
    if (typeof SweetAlertConfig === 'undefined') {
        if (!confirm('Tem certeza que deseja excluir esta transferência?')) return;
    } else {
        const confirmacao = await SweetAlertConfig.confirm(
            'Excluir Transferência',
            'Tem certeza que deseja excluir esta transferência? Os lançamentos relacionados também serão excluídos.',
            'Excluir',
            'Cancelar'
        );
        if (!confirmacao) return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);
        
        const url = getApiUrl('financeiro/transferencias.php');
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.success('Sucesso', 'Transferência excluída com sucesso!');
            }
            await carregarTransferencias();
        } else {
            throw new Error(result.message || 'Erro ao excluir transferência');
        }
    } catch (error) {
        console.error('Erro ao excluir transferência:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao excluir transferência: ' + error.message);
        }
    }
}

function abrirModal() {
    if (typeof SweetAlertConfig !== 'undefined') {
        SweetAlertConfig.info('Em desenvolvimento', 'O formulário de transferência será implementado em breve.');
    }
}

let inicializandoTransferencias = false;
let transferenciasInicializado = false;
const handlersTransferencias = {
    btnNovaTransferencia: null,
    btnFiltrar: null
};

async function initTransferencias() {
    if (inicializandoTransferencias) return;
    if (transferenciasInicializado) return;
    
    inicializandoTransferencias = true;
    
    setTimeout(async () => {
        const container = document.getElementById('tbodyTransferencias') || document.querySelector('.cadastros-content');
        if (!container) {
            inicializandoTransferencias = false;
            setTimeout(async () => {
                if (document.getElementById('tbodyTransferencias') || document.querySelector('.cadastros-content')) {
                    await initTransferencias();
                }
            }, 200);
            return;
        }

        const btnNovaTransferencia = document.getElementById('btnNovaTransferencia');
        if (btnNovaTransferencia && !handlersTransferencias.btnNovaTransferencia) {
            handlersTransferencias.btnNovaTransferencia = abrirModal;
            btnNovaTransferencia.addEventListener('click', handlersTransferencias.btnNovaTransferencia);
        }
        
        const btnFiltrar = document.getElementById('btnFiltrar');
        if (btnFiltrar && !handlersTransferencias.btnFiltrar) {
            handlersTransferencias.btnFiltrar = carregarTransferencias;
            btnFiltrar.addEventListener('click', handlersTransferencias.btnFiltrar);
        }
        
        await carregarContas();
        await carregarTransferencias();
        
        transferenciasInicializado = true;
        inicializandoTransferencias = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTransferencias);
} else {
    initTransferencias();
}

