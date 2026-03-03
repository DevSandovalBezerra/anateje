if (typeof window.pagamentosData === 'undefined') {
    window.pagamentosData = [];
    window.estatisticasData = null;
}

pagamentosData = window.pagamentosData;
estatisticasData = window.estatisticasData;

function nomeAssociado(item) {
    return item?.associado_nome || item?.aluno_nome || '';
}

async function carregarPagamentos() {
    try {
        const status = document.getElementById('selectFiltroStatus')?.value || '';
        const formaPagamento = document.getElementById('selectFiltroFormaPagamento')?.value || '';
        const dataInicio = document.getElementById('inputDataInicio')?.value || '';
        const dataFim = document.getElementById('inputDataFim')?.value || '';
        const buscar = document.getElementById('inputBuscar')?.value || '';
        
        let url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/pagamentos.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/pagamentos.php?action=listar`
                : `../../api/financeiro/pagamentos.php?action=listar`);
        
        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (formaPagamento) params.append('forma_pagamento', formaPagamento);
        if (dataInicio) params.append('data_pagamento_inicio', dataInicio);
        if (dataFim) params.append('data_pagamento_fim', dataFim);
        
        if (params.toString()) {
            url += '&' + params.toString();
        }
        
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
            pagamentosData = result.data || [];
            
            if (buscar) {
                const buscarLower = buscar.toLowerCase();
                pagamentosData = pagamentosData.filter(p => 
                    nomeAssociado(p)?.toLowerCase().includes(buscarLower) ||
                    p.numero_pagamento?.toLowerCase().includes(buscarLower)
                );
            }
            
            renderizarTabela();
            await carregarEstatisticas();
        } else {
            throw new Error(result.message || 'Erro ao carregar pagamentos');
        }
    } catch (error) {
        console.error('Erro ao carregar pagamentos:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar pagamentos: ' + error.message);
        }
    }
}

async function carregarEstatisticas() {
    try {
        const dataInicio = document.getElementById('inputDataInicio')?.value || null;
        const dataFim = document.getElementById('inputDataFim')?.value || null;
        
        let url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/pagamentos.php?action=estatisticas')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/pagamentos.php?action=estatisticas`
                : `../../api/financeiro/pagamentos.php?action=estatisticas`);
        
        const params = new URLSearchParams();
        if (dataInicio) params.append('data_inicio', dataInicio);
        if (dataFim) params.append('data_fim', dataFim);
        
        if (params.toString()) {
            url += '&' + params.toString();
        }
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        
        if (result.success) {
            estatisticasData = result.data;
            atualizarEstatisticas();
        }
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
    }
}

function atualizarEstatisticas() {
    if (!estatisticasData) return;
    
    const statTotal = document.getElementById('statTotalPagamentos');
    const statValorTotal = document.getElementById('statValorTotal');
    const statConfirmados = document.getElementById('statConfirmados');
    const statPendentes = document.getElementById('statPendentes');
    
    if (statTotal) statTotal.textContent = estatisticasData.total_pagamentos || 0;
    if (statValorTotal) {
        const valor = parseFloat(estatisticasData.valor_total_recebido || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        statValorTotal.textContent = valor;
    }
    if (statConfirmados) statConfirmados.textContent = estatisticasData.pagamentos_confirmados || 0;
    if (statPendentes) statPendentes.textContent = estatisticasData.pagamentos_pendentes || 0;
}

function renderizarTabela() {
    const tbody = document.getElementById('tbodyPagamentos');
    if (!tbody) return;

    if (pagamentosData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-secondary-dark-gray">
                    Nenhum pagamento encontrado
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = pagamentosData.map(pag => {
        const valor = parseFloat(pag.valor_pago || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const dataPagamento = pag.data_pagamento ? new Date(pag.data_pagamento + 'T00:00:00').toLocaleDateString('pt-BR') : '-';
        
        let statusLabel = '';
        if (pag.status === 'confirmado') {
            statusLabel = '<span class="px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800">Confirmado</span>';
        } else if (pag.status === 'pendente') {
            statusLabel = '<span class="px-2 py-1 text-xs font-medium rounded bg-yellow-100 text-yellow-800">Pendente</span>';
        } else {
            statusLabel = '<span class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800">Cancelado</span>';
        }
        
        const formaLabels = {
            'dinheiro': 'Dinheiro',
            'pix': 'PIX',
            'cartao': 'Cartão',
            'transferencia': 'Transferência'
        };
        const formaLabel = formaLabels[pag.forma_pagamento] || pag.forma_pagamento;

        return `
            <tr>
                <td class="px-4 py-2">
                    <p class="font-medium text-secondary-black">${escapeHtml(pag.numero_pagamento || '')}</p>
                </td>
                <td class="px-4 py-2">
                    <p class="font-medium text-secondary-black">${escapeHtml(nomeAssociado(pag))}</p>
                </td>
                <td class="px-4 py-2 text-sm font-medium text-secondary-black">${valor}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${dataPagamento}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${formaLabel}</td>
                <td class="px-4 py-2">${statusLabel}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">
                    ${pag.numero_cobranca ? `<a href="index.php?page=financeiro/cobrancas" class="text-primary-blue hover:underline">${escapeHtml(pag.numero_cobranca)}</a>` : '-'}
                </td>
                <td class="px-4 py-2">
                    <div class="flex space-x-2">
                        <button onclick="verDetalhes(${pag.id})" class="text-purple-600 hover:text-purple-700" title="Ver Detalhes">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        ${pag.status === 'pendente' ? `
                            <button onclick="cancelarPagamento(${pag.id})" class="text-red-500 hover:text-red-600" title="Cancelar">
                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                            </button>
                        ` : ''}
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

async function verDetalhes(id) {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl(`financeiro/pagamentos.php?action=obter&id=${id}`)
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/pagamentos.php?action=obter&id=${id}`
                : `../../api/financeiro/pagamentos.php?action=obter&id=${id}`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            const pag = result.data;
            const valor = parseFloat(pag.valor_pago || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            const dataPagamento = pag.data_pagamento ? new Date(pag.data_pagamento + 'T00:00:00').toLocaleDateString('pt-BR') : '-';
            
            const detalhes = `
                <div class="space-y-3">
                    <div><strong>Número:</strong> ${escapeHtml(pag.numero_pagamento || '')}</div>
                    <div><strong>Associado:</strong> ${escapeHtml(nomeAssociado(pag))}</div>
                    <div><strong>Valor:</strong> ${valor}</div>
                    <div><strong>Data:</strong> ${dataPagamento}</div>
                    <div><strong>Forma:</strong> ${escapeHtml(pag.forma_pagamento || '')}</div>
                    <div><strong>Status:</strong> ${escapeHtml(pag.status || '')}</div>
                    ${pag.numero_cobranca ? `<div><strong>Cobrança:</strong> ${escapeHtml(pag.numero_cobranca)}</div>` : ''}
                    ${pag.observacoes ? `<div><strong>Observações:</strong> ${escapeHtml(pag.observacoes)}</div>` : ''}
                </div>
            `;
            
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.info('Detalhes do Pagamento', detalhes);
            }
        } else {
            throw new Error(result.message || 'Erro ao carregar detalhes');
        }
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar detalhes: ' + error.message);
        }
    }
}

async function cancelarPagamento(id) {
    try {
        const confirmacao = await SweetAlertConfig.confirm(
            'Confirmar Cancelamento',
            'Tem certeza que deseja cancelar este pagamento? Esta ação pode afetar o status da cobrança relacionada.',
            'Cancelar Pagamento',
            'Voltar'
        );

        if (!confirmacao.isConfirmed) {
            return;
        }

        let motivo = '';
        if (typeof SweetAlertConfig !== 'undefined' && SweetAlertConfig.prompt) {
            const resultado = await SweetAlertConfig.prompt(
                'Motivo do Cancelamento',
                'Informe o motivo do cancelamento:',
                'Motivo obrigatório'
            );
            if (!resultado.isConfirmed || !resultado.value) {
                return;
            }
            motivo = resultado.value;
        } else {
            motivo = prompt('Informe o motivo do cancelamento:');
            if (!motivo || motivo.trim() === '') {
                return;
            }
        }

        const formData = new FormData();
        formData.append('action', 'cancelar');
        formData.append('id', id);
        formData.append('motivo', motivo);

        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/pagamentos.php')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/pagamentos.php`
                : `../../api/financeiro/pagamentos.php`);

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
                }
                return;
            }
            throw new Error(`Erro HTTP ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.success('Sucesso', result.message || 'Pagamento cancelado com sucesso');
            }
            await carregarPagamentos();
        } else {
            throw new Error(result.message || 'Erro ao cancelar pagamento');
        }
    } catch (error) {
        console.error('Erro ao cancelar pagamento:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao cancelar pagamento: ' + error.message);
        }
    }
}

let inicializandoPagamentos = false;
let pagamentosInicializado = false;
const handlersPagamentos = {
    inputDataInicio: null,
    inputDataFim: null,
    selectFiltroStatus: null,
    selectFiltroFormaPagamento: null,
    inputBuscar: null,
    timeoutBuscar: null
};

async function initPagamentos() {
    if (inicializandoPagamentos) return;
    if (pagamentosInicializado) return;
    
    inicializandoPagamentos = true;
    
    setTimeout(async () => {
        const container = document.getElementById('tbodyPagamentos') || document.querySelector('.cadastros-content');
        if (!container) {
            inicializandoPagamentos = false;
            setTimeout(async () => {
                if (document.getElementById('tbodyPagamentos') || document.querySelector('.cadastros-content')) {
                    await initPagamentos();
                }
            }, 200);
            return;
        }

        const hoje = new Date();
        const primeiroDiaMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
        const ultimoDiaMes = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);
        
        const inputDataInicio = document.getElementById('inputDataInicio');
        const inputDataFim = document.getElementById('inputDataFim');
        
        if (inputDataInicio && !handlersPagamentos.inputDataInicio) {
            inputDataInicio.value = primeiroDiaMes.toISOString().split('T')[0];
            handlersPagamentos.inputDataInicio = () => carregarPagamentos();
            inputDataInicio.addEventListener('change', handlersPagamentos.inputDataInicio);
        }
        
        if (inputDataFim && !handlersPagamentos.inputDataFim) {
            inputDataFim.value = ultimoDiaMes.toISOString().split('T')[0];
            handlersPagamentos.inputDataFim = () => carregarPagamentos();
            inputDataFim.addEventListener('change', handlersPagamentos.inputDataFim);
        }

        const selectFiltroStatus = document.getElementById('selectFiltroStatus');
        if (selectFiltroStatus && !handlersPagamentos.selectFiltroStatus) {
            handlersPagamentos.selectFiltroStatus = () => carregarPagamentos();
            selectFiltroStatus.addEventListener('change', handlersPagamentos.selectFiltroStatus);
        }

        const selectFiltroFormaPagamento = document.getElementById('selectFiltroFormaPagamento');
        if (selectFiltroFormaPagamento && !handlersPagamentos.selectFiltroFormaPagamento) {
            handlersPagamentos.selectFiltroFormaPagamento = () => carregarPagamentos();
            selectFiltroFormaPagamento.addEventListener('change', handlersPagamentos.selectFiltroFormaPagamento);
        }

        const inputBuscar = document.getElementById('inputBuscar');
        if (inputBuscar && !handlersPagamentos.inputBuscar) {
            handlersPagamentos.inputBuscar = () => {
                if (handlersPagamentos.timeoutBuscar) clearTimeout(handlersPagamentos.timeoutBuscar);
                handlersPagamentos.timeoutBuscar = setTimeout(() => carregarPagamentos(), 500);
            };
            inputBuscar.addEventListener('input', handlersPagamentos.inputBuscar);
        }

        await carregarPagamentos();
        
        pagamentosInicializado = true;
        inicializandoPagamentos = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPagamentos);
} else {
    initPagamentos();
}

