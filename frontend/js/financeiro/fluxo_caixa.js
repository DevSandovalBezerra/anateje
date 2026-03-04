// Modo debug: localStorage lidergest_debug_fluxo=1 ou URL ?debug=fluxo
window.__fluxoCaixaDebug = (typeof localStorage !== 'undefined' && localStorage.getItem('lidergest_debug_fluxo') === '1') ||
    (typeof URLSearchParams !== 'undefined' && new URLSearchParams(window.location.search).get('debug') === 'fluxo');
if (window.__fluxoCaixaDebug) {
    window.__fluxoCaixaRunCount = (window.__fluxoCaixaRunCount || 0) + 1;
    console.log('[FluxoCaixa] script executado', 'runCount=', window.__fluxoCaixaRunCount);
}

if (typeof window.fluxoCaixaData === 'undefined') {
    window.fluxoCaixaData = [];
    window.contasBancariasData = [];
    window.chartFluxo = null;
}

fluxoCaixaData = window.fluxoCaixaData;
contasBancariasData = window.contasBancariasData;

// Função helper segura para obter URL da API
function getApiUrl(path) {
    var branch = 'fallback';
    if (typeof window.getApiUrl === 'function' && window.getApiUrl !== getApiUrl) {
        branch = 'window.getApiUrl';
        if (window.__fluxoCaixaDebug) {
            console.log('[FluxoCaixa] getApiUrl("' + (path || '').substring(0, 60) + '...") -> ' + branch);
        }
        return window.getApiUrl(path);
    }
    if (typeof apiConfig !== 'undefined' && apiConfig && typeof apiConfig.getApiEndpoint === 'function') {
        branch = 'apiConfig';
        if (window.__fluxoCaixaDebug) {
            console.log('[FluxoCaixa] getApiUrl("' + (path || '').substring(0, 60) + '...") -> ' + branch);
        }
        return apiConfig.getApiEndpoint(path);
    }
    if (window.__fluxoCaixaDebug) {
        console.log('[FluxoCaixa] getApiUrl("' + (path || '').substring(0, 60) + '...") -> ' + branch);
    }
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
        const url = getApiUrl('financeiro/contas_bancarias.php?action=listar&ativo=1');
        const response = await fetch(url, { credentials: 'include' });
        const result = await response.json();
        
        if (result.success) {
            contasBancariasData = result.data || [];
            const select = document.getElementById('selectConta');
            if (select) {
                select.innerHTML = '<option value="">Todas</option>';
                contasBancariasData.forEach(conta => {
                    const option = document.createElement('option');
                    option.value = conta.id;
                    option.textContent = conta.nome_conta;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Erro ao carregar contas:', error);
    }
}

async function carregarFluxoCaixa() {
    if (window.__fluxoCaixaDebug) {
        console.log('[FluxoCaixa] carregarFluxoCaixa chamado');
    }
    try {
        const tipo = document.getElementById('selectTipo')?.value || 'mensal';
        const conta = document.getElementById('selectConta')?.value || '';
        const dataInicio = document.getElementById('inputDataInicio')?.value || '';
        const dataFim = document.getElementById('inputDataFim')?.value || '';
        
        const params = new URLSearchParams();
        params.append('tipo', tipo);
        if (conta) params.append('conta_bancaria_id', conta);
        if (dataInicio) params.append('data_inicio', dataInicio);
        if (dataFim) params.append('data_fim', dataFim);
        
        const url = getApiUrl(`financeiro/fluxo_caixa.php?action=obter&${params.toString()}`);
        const response = await fetch(url, { credentials: 'include' });
        
        if (!response.ok) {
            throw new Error(`Erro HTTP ${response.status}`);
        }
        
        const result = await response.json();
        if (result.success) {
            fluxoCaixaData = result.data || [];
            if (window.__fluxoCaixaDebug) {
                console.log('[FluxoCaixa] API success', 'data.length=', Array.isArray(result.data) ? result.data.length : 'não é array');
            }
            if (window.__fluxoCaixaDebug) {
                console.log('[FluxoCaixa] antes renderizarTabela');
            }
            renderizarTabela();
            if (window.__fluxoCaixaDebug) {
                console.log('[FluxoCaixa] antes atualizarGrafico', 'fluxoCaixaData.length=', fluxoCaixaData.length);
            }
            atualizarGrafico();
        } else {
            throw new Error(result.message || 'Erro ao carregar fluxo de caixa');
        }
    } catch (error) {
        console.error('Erro ao carregar fluxo de caixa:', error);
        if (window.__fluxoCaixaDebug && error && error.stack) {
            console.error('[FluxoCaixa] stack:', error.stack);
        }
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar fluxo de caixa: ' + error.message);
        }
    }
}

function renderizarTabela() {
    const tbody = document.getElementById('tbodyFluxo');
    if (!tbody) return;
    
    if (fluxoCaixaData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-secondary-dark-gray">
                    <i data-lucide="trending-down" class="w-8 h-8 mx-auto mb-2"></i>
                    <p>Nenhum dado encontrado</p>
                </td>
            </tr>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }
    
    tbody.innerHTML = fluxoCaixaData.map(periodo => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">${escapeHtml(periodo.periodo || '')}</td>
            <td class="px-4 py-3 text-green-600">${formatarMoeda(periodo.entradas_realizado || 0)}</td>
            <td class="px-4 py-3 text-red-600">${formatarMoeda(periodo.saidas_realizado || 0)}</td>
            <td class="px-4 py-3 text-green-500">${formatarMoeda(periodo.entradas_previsto || 0)}</td>
            <td class="px-4 py-3 text-red-500">${formatarMoeda(periodo.saidas_previsto || 0)}</td>
            <td class="px-4 py-3 font-semibold ${(periodo.saldo_periodo_realizado || 0) >= 0 ? 'text-green-600' : 'text-red-600'}">
                ${formatarMoeda(periodo.saldo_periodo_realizado || 0)}
            </td>
            <td class="px-4 py-3 font-semibold ${(periodo.saldo_acumulado || 0) >= 0 ? 'text-blue-600' : 'text-red-600'}">
                ${formatarMoeda(periodo.saldo_acumulado || 0)}
            </td>
        </tr>
    `).join('');
    
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function atualizarGrafico() {
    const ctx = document.getElementById('chartFluxoCaixa');
    if (!ctx) return;
    try {
        const labels = fluxoCaixaData.map(p => p.periodo || '');
        const entradasRealizado = fluxoCaixaData.map(p => parseFloat(p.entradas_realizado || 0));
        const saidasRealizado = fluxoCaixaData.map(p => parseFloat(p.saidas_realizado || 0));
        const saldoAcumulado = fluxoCaixaData.map(p => parseFloat(p.saldo_acumulado || 0));
        
        const colors = getThemeColors();
        
        if (window.chartFluxo) {
            if (window.__fluxoCaixaDebug) {
                console.log('[FluxoCaixa] chartFluxo.destroy()');
            }
            window.chartFluxo.destroy();
        }
        
        if (window.__fluxoCaixaDebug) {
            console.log('[FluxoCaixa] new Chart(...) chamado');
        }
        window.chartFluxo = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Entradas Realizado',
                        data: entradasRealizado,
                        borderColor: colors.success || 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Saídas Realizado',
                        data: saidasRealizado,
                        borderColor: colors.error || 'rgba(239, 68, 68, 1)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Saldo Acumulado',
                        data: saldoAcumulado,
                        borderColor: colors.primary || 'rgba(139, 92, 246, 1)',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
        
        if (typeof registerChart === 'function') {
            registerChart(window.chartFluxo);
        }
    } catch (err) {
        console.error('[FluxoCaixa] erro em atualizarGrafico:', err);
        if (window.__fluxoCaixaDebug && err && err.stack) {
            console.error('[FluxoCaixa] stack atualizarGrafico:', err.stack);
        }
    }
}

function getThemeColors() {
    if (typeof window.getThemeColors === 'function') {
        return window.getThemeColors();
    }
    return {
        success: 'rgba(16, 185, 129, 1)',
        error: 'rgba(239, 68, 68, 1)',
        primary: 'rgba(139, 92, 246, 1)'
    };
}

// var permite reexecução segura quando o script é carregado novamente (navegação AJAX)
var inicializandoFluxoCaixa = false;
var fluxoCaixaInicializado = false;
// Permitir re-init quando o script é re-executado (ex.: navegação AJAX para a mesma página)
if (typeof window.__fluxoCaixaListenerAttached !== 'undefined' && window.__fluxoCaixaListenerAttached) {
    fluxoCaixaInicializado = false;
}
const handlersFluxoCaixa = {
    btnAtualizar: null
};

async function initFluxoCaixa() {
    if (window.__fluxoCaixaDebug) {
        console.log('[FluxoCaixa] initFluxoCaixa chamado', 'inicializandoFluxoCaixa=', inicializandoFluxoCaixa, 'fluxoCaixaInicializado=', fluxoCaixaInicializado);
    }
    if (inicializandoFluxoCaixa) {
        if (window.__fluxoCaixaDebug) {
            console.log('[FluxoCaixa] initFluxoCaixa early return (flag)');
        }
        return;
    }
    if (fluxoCaixaInicializado) {
        if (window.__fluxoCaixaDebug) {
            console.log('[FluxoCaixa] initFluxoCaixa early return (flag)');
        }
        return;
    }
    
    inicializandoFluxoCaixa = true;
    
    setTimeout(async () => {
        if (window.__fluxoCaixaDebug) {
            console.log('[FluxoCaixa] initFluxoCaixa setTimeout callback');
        }
        try {
            const container = document.getElementById('tbodyFluxo') || document.querySelector('.cadastros-content');
            if (!container) {
                if (window.__fluxoCaixaDebug) {
                    console.log('[FluxoCaixa] container não encontrado, reagendando');
                }
                inicializandoFluxoCaixa = false;
                setTimeout(async () => {
                    if (document.getElementById('tbodyFluxo') || document.querySelector('.cadastros-content')) {
                        await initFluxoCaixa();
                    }
                }, 200);
                return;
            }

            const hoje = new Date();
            const primeiroDiaMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
            const ultimoDiaMes = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);
            
            const inputInicio = document.getElementById('inputDataInicio');
            const inputFim = document.getElementById('inputDataFim');
            
            if (inputInicio) inputInicio.value = primeiroDiaMes.toISOString().split('T')[0];
            if (inputFim) inputFim.value = ultimoDiaMes.toISOString().split('T')[0];
            
            const btnAtualizar = document.getElementById('btnAtualizar');
            if (btnAtualizar && !handlersFluxoCaixa.btnAtualizar) {
                handlersFluxoCaixa.btnAtualizar = carregarFluxoCaixa;
                btnAtualizar.addEventListener('click', handlersFluxoCaixa.btnAtualizar);
            }
            
            if (window.__fluxoCaixaDebug) {
                console.log('[FluxoCaixa] antes carregarContas');
            }
            await carregarContas();
            if (window.__fluxoCaixaDebug) {
                console.log('[FluxoCaixa] depois carregarContas');
            }
            await carregarFluxoCaixa();
            
            fluxoCaixaInicializado = true;
            inicializandoFluxoCaixa = false;
        } catch (err) {
            inicializandoFluxoCaixa = false;
            console.error('[FluxoCaixa] erro no init callback:', err);
            if (window.__fluxoCaixaDebug && err && err.stack) {
                console.error('[FluxoCaixa] stack init:', err.stack);
            }
            throw err;
        }
    }, 100);
}

if (window.__fluxoCaixaListenerAttached) {
    if (document.readyState !== 'loading') {
        initFluxoCaixa();
    }
} else {
    window.__fluxoCaixaListenerAttached = true;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFluxoCaixa);
    } else {
        initFluxoCaixa();
    }
}



