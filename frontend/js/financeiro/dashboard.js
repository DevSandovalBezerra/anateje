if (typeof window.dashboardData === 'undefined') {
    window.dashboardData = null;
    window.contasBancariasData = [];
    window.chartCategorias = null;
    window.chartCentrosCusto = null;
}

dashboardData = window.dashboardData;
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

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function obterPeriodo() {
    const periodo = document.getElementById('selectPeriodo')?.value || 'mes';
    const hoje = new Date();
    let inicio, fim;
    
    switch (periodo) {
        case 'hoje':
            inicio = fim = hoje.toISOString().split('T')[0];
            break;
        case 'semana':
            const inicioSemana = new Date(hoje);
            inicioSemana.setDate(hoje.getDate() - hoje.getDay());
            inicio = inicioSemana.toISOString().split('T')[0];
            fim = hoje.toISOString().split('T')[0];
            break;
        case 'mes':
            inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1).toISOString().split('T')[0];
            fim = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0).toISOString().split('T')[0];
            break;
        case 'trimestre':
            const trimestre = Math.floor(hoje.getMonth() / 3);
            inicio = new Date(hoje.getFullYear(), trimestre * 3, 1).toISOString().split('T')[0];
            fim = new Date(hoje.getFullYear(), (trimestre + 1) * 3, 0).toISOString().split('T')[0];
            break;
        case 'ano':
            inicio = new Date(hoje.getFullYear(), 0, 1).toISOString().split('T')[0];
            fim = new Date(hoje.getFullYear(), 11, 31).toISOString().split('T')[0];
            break;
        case 'custom':
            inicio = document.getElementById('inputDataInicio')?.value || '';
            fim = document.getElementById('inputDataFim')?.value || '';
            break;
    }
    
    return { inicio, fim };
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

async function carregarDashboard() {
    try {
        const periodo = obterPeriodo();
        const conta = document.getElementById('selectConta')?.value || '';
        
        const params = new URLSearchParams();
        if (periodo.inicio) params.append('data_inicio', periodo.inicio);
        if (periodo.fim) params.append('data_fim', periodo.fim);
        if (conta) params.append('conta_bancaria_id', conta);
        
        const url = getApiUrl(`financeiro/dashboard.php?action=indicadores&${params.toString()}`);
        const response = await fetch(url, { credentials: 'include' });
        
        if (!response.ok) {
            throw new Error(`Erro HTTP ${response.status}`);
        }
        
        const result = await response.json();
        if (result.success) {
            dashboardData = result.data;
            atualizarIndicadores();
            atualizarTabelaSaldos();
            await carregarGraficos();
        } else {
            throw new Error(result.message || 'Erro ao carregar dashboard');
        }
    } catch (error) {
        console.error('Erro ao carregar dashboard:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar dashboard: ' + error.message);
        }
    }
}

function atualizarIndicadores() {
    if (!dashboardData) return;
    
    const statEntradas = document.getElementById('statEntradasRealizado');
    const statSaidas = document.getElementById('statSaidasRealizado');
    const statSaldoAtual = document.getElementById('statSaldoAtual');
    const statSaldoPrevisto = document.getElementById('statSaldoPrevisto');
    
    if (statEntradas) statEntradas.textContent = formatarMoeda(dashboardData.total_entradas_realizado || 0);
    if (statSaidas) statSaidas.textContent = formatarMoeda(dashboardData.total_saidas_realizado || 0);
    if (statSaldoAtual) statSaldoAtual.textContent = formatarMoeda(dashboardData.saldo_atual || 0);
    if (statSaldoPrevisto) statSaldoPrevisto.textContent = formatarMoeda(dashboardData.saldo_previsto || 0);
}

function atualizarTabelaSaldos() {
    if (!dashboardData || !dashboardData.saldos_contas) return;
    
    const tbody = document.getElementById('tbodySaldos');
    if (!tbody) return;
    
    if (dashboardData.saldos_contas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-secondary-dark-gray">Nenhuma conta encontrada</td></tr>';
        return;
    }
    
    tbody.innerHTML = dashboardData.saldos_contas.map(conta => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">${escapeHtml(conta.nome_conta || '')}</td>
            <td class="px-4 py-3">${formatarMoeda(conta.saldo_inicial || 0)}</td>
            <td class="px-4 py-3 text-green-600">${formatarMoeda(conta.entradas || 0)}</td>
            <td class="px-4 py-3 text-red-600">${formatarMoeda(conta.saidas || 0)}</td>
            <td class="px-4 py-3 font-semibold ${(conta.saldo_real || 0) >= 0 ? 'text-green-600' : 'text-red-600'}">
                ${formatarMoeda(conta.saldo_real || 0)}
            </td>
        </tr>
    `).join('');
}

async function carregarGraficos() {
    try {
        const periodo = obterPeriodo();
        const params = new URLSearchParams();
        if (periodo.inicio) params.append('data_inicio', periodo.inicio);
        if (periodo.fim) params.append('data_fim', periodo.fim);
        
        const [categorias, centros] = await Promise.all([
            fetch(getApiUrl(`financeiro/dashboard.php?action=por_categoria&${params.toString()}`), { credentials: 'include' }).then(r => r.json()),
            fetch(getApiUrl(`financeiro/dashboard.php?action=por_centro_custo&${params.toString()}`), { credentials: 'include' }).then(r => r.json())
        ]);
        
        if (categorias.success) {
            atualizarGraficoCategorias(categorias.data || []);
        }
        
        if (centros.success) {
            atualizarGraficoCentrosCusto(centros.data || []);
        }
    } catch (error) {
        console.error('Erro ao carregar gráficos:', error);
    }
}

function atualizarGraficoCategorias(dados) {
    const ctx = document.getElementById('chartCategorias');
    if (!ctx) return;
    
    const labels = dados.map(d => d.categoria_nome || 'Sem categoria');
    const receitas = dados.map(d => parseFloat(d.receitas_realizado || 0));
    const despesas = dados.map(d => parseFloat(d.despesas_realizado || 0));
    
    const colors = getThemeColors();
    
    if (window.chartCategorias) {
        window.chartCategorias.destroy();
    }
    
    window.chartCategorias = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Receitas',
                    data: receitas,
                    backgroundColor: colors.success || 'rgba(16, 185, 129, 0.5)',
                    borderColor: colors.success || 'rgba(16, 185, 129, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Despesas',
                    data: despesas,
                    backgroundColor: colors.error || 'rgba(239, 68, 68, 0.5)',
                    borderColor: colors.error || 'rgba(239, 68, 68, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    if (typeof registerChart === 'function') {
        registerChart(window.chartCategorias);
    }
}

function atualizarGraficoCentrosCusto(dados) {
    const ctx = document.getElementById('chartCentrosCusto');
    if (!ctx) return;
    
    const labels = dados.map(d => d.centro_custo_nome || 'Sem centro');
    const receitas = dados.map(d => parseFloat(d.receitas_realizado || 0));
    const despesas = dados.map(d => parseFloat(d.despesas_realizado || 0));
    
    const colors = getThemeColors();
    
    if (window.chartCentrosCusto) {
        window.chartCentrosCusto.destroy();
    }
    
    window.chartCentrosCusto = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Receitas',
                    data: receitas,
                    backgroundColor: colors.success || 'rgba(16, 185, 129, 0.5)',
                    borderColor: colors.success || 'rgba(16, 185, 129, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Despesas',
                    data: despesas,
                    backgroundColor: colors.error || 'rgba(239, 68, 68, 0.5)',
                    borderColor: colors.error || 'rgba(239, 68, 68, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    if (typeof registerChart === 'function') {
        registerChart(window.chartCentrosCusto);
    }
}

function getThemeColors() {
    if (typeof window.getThemeColors === 'function') {
        return window.getThemeColors();
    }
    return {
        success: 'rgba(16, 185, 129, 0.5)',
        error: 'rgba(239, 68, 68, 0.5)',
        primary: 'rgba(139, 92, 246, 0.5)'
    };
}

let inicializandoDashboard = false;
let dashboardInicializado = false;
const handlersDashboard = {
    selectPeriodo: null,
    btnAtualizar: null
};

async function initDashboard() {
    if (inicializandoDashboard) return;
    if (dashboardInicializado) return;
    
    inicializandoDashboard = true;
    
    setTimeout(async () => {
        const container = document.getElementById('tbodySaldos') || document.querySelector('.cadastros-content');
        if (!container) {
            inicializandoDashboard = false;
            setTimeout(async () => {
                if (document.getElementById('tbodySaldos') || document.querySelector('.cadastros-content')) {
                    await initDashboard();
                }
            }, 200);
            return;
        }

        const selectPeriodo = document.getElementById('selectPeriodo');
        if (selectPeriodo && !handlersDashboard.selectPeriodo) {
            handlersDashboard.selectPeriodo = function() {
                const isCustom = this.value === 'custom';
                const divDataInicio = document.getElementById('divDataInicio');
                const divDataFim = document.getElementById('divDataFim');
                if (divDataInicio) divDataInicio.style.display = isCustom ? 'block' : 'none';
                if (divDataFim) divDataFim.style.display = isCustom ? 'block' : 'none';
            };
            selectPeriodo.addEventListener('change', handlersDashboard.selectPeriodo);
        }
        
        const btnAtualizar = document.getElementById('btnAtualizar');
        if (btnAtualizar && !handlersDashboard.btnAtualizar) {
            handlersDashboard.btnAtualizar = carregarDashboard;
            btnAtualizar.addEventListener('click', handlersDashboard.btnAtualizar);
        }
        
        await carregarContas();
        await carregarDashboard();
        
        dashboardInicializado = true;
        inicializandoDashboard = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}

