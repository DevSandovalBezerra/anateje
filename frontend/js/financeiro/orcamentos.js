// Usar window para evitar redeclaração em navegação AJAX
if (typeof window.orcamentosData === 'undefined') {
    window.orcamentosData = [];
    window.orcamentoEditando = null;
    window.unidadesData = [];
    window.centrosCustosData = [];
    window.categoriasData = [];
    window.isAdminGlobal = false;
    window.userUnidadeId = null;
}

// Criar referências locais sem redeclarar (atribuição sem declaração)
orcamentosData = window.orcamentosData;
orcamentoEditando = window.orcamentoEditando;
unidadesData = window.unidadesData;
centrosCustosData = window.centrosCustosData;
categoriasData = window.categoriasData;
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

async function carregarCentrosCustos() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/centros_custos.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/centros_custos.php?action=listar`
                : `../../api/financeiro/centros_custos.php?action=listar`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            centrosCustosData = result.data || [];
            preencherSelectCentrosCustos();
            preencherFiltroCentrosCustos();
        }
    } catch (error) {
        console.error('Erro ao carregar centros de custo:', error);
    }
}

async function carregarCategorias() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/categorias_financeiras.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/categorias_financeiras.php?action=listar`
                : `../../api/financeiro/categorias_financeiras.php?action=listar`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            categoriasData = result.data || [];
            preencherSelectCategorias();
            preencherFiltroCategorias();
        }
    } catch (error) {
        console.error('Erro ao carregar categorias:', error);
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

function preencherSelectCentrosCustos() {
    const select = document.getElementById('selectCentroCusto');
    if (!select) return;
    
    select.innerHTML = '<option value="">Selecione um centro de custo</option>';
    centrosCustosData.forEach(centro => {
        if (centro.status === 'ativo') {
            const option = document.createElement('option');
            option.value = centro.id;
            option.textContent = centro.nome;
            select.appendChild(option);
        }
    });
}

function preencherFiltroCentrosCustos() {
    const select = document.getElementById('selectFiltroCentroCusto');
    if (!select) return;
    
    select.innerHTML = '<option value="">Todos</option>';
    centrosCustosData.forEach(centro => {
        if (centro.status === 'ativo') {
            const option = document.createElement('option');
            option.value = centro.id;
            option.textContent = centro.nome;
            select.appendChild(option);
        }
    });
}

function preencherSelectCategorias() {
    const select = document.getElementById('selectCategoria');
    if (!select) return;
    
    select.innerHTML = '<option value="">Selecione uma categoria</option>';
    categoriasData.forEach(categoria => {
        if (categoria.ativo == 1) {
            const option = document.createElement('option');
            option.value = categoria.id;
            option.textContent = categoria.nome + ' (' + (categoria.tipo === 'receita' ? 'Receita' : 'Despesa') + ')';
            select.appendChild(option);
        }
    });
}

function preencherFiltroCategorias() {
    const select = document.getElementById('selectFiltroCategoria');
    if (!select) return;
    
    select.innerHTML = '<option value="">Todas</option>';
    categoriasData.forEach(categoria => {
        if (categoria.ativo == 1) {
            const option = document.createElement('option');
            option.value = categoria.id;
            option.textContent = categoria.nome;
            select.appendChild(option);
        }
    });
}

async function carregarOrcamentos() {
    try {
        const ano = document.getElementById('inputFiltroAno')?.value || '';
        const mes = document.getElementById('selectFiltroMes')?.value || '';
        const unidadeId = document.getElementById('selectFiltroUnidade')?.value || '';
        const centroCustoId = document.getElementById('selectFiltroCentroCusto')?.value || '';
        const categoriaId = document.getElementById('selectFiltroCategoria')?.value || '';
        
        let url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/orcamentos.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/orcamentos.php?action=listar`
                : `../../api/financeiro/orcamentos.php?action=listar`);
        
        const params = new URLSearchParams();
        if (ano) params.append('ano', ano);
        if (mes) params.append('mes', mes);
        if (unidadeId) params.append('unidade_id', unidadeId);
        if (centroCustoId) params.append('centro_custo_id', centroCustoId);
        if (categoriaId) params.append('categoria_id', categoriaId);
        
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
            orcamentosData = result.data || [];
            renderizarTabela();
        } else {
            throw new Error(result.message || 'Erro ao carregar orçamentos');
        }
    } catch (error) {
        console.error('Erro ao carregar orçamentos:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar orçamentos: ' + error.message);
        }
    }
}

function renderizarTabela() {
    const tbody = document.getElementById('tbodyOrcamentos');
    if (!tbody) return;

    if (orcamentosData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-secondary-dark-gray">
                    Nenhum orçamento encontrado
                </td>
            </tr>
        `;
        return;
    }

    const meses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    tbody.innerHTML = orcamentosData.map(orcamento => {
        const periodo = `${meses[orcamento.mes] || orcamento.mes}/${orcamento.ano}`;
        const valorOrcado = parseFloat(orcamento.valor_orcado || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const valorRevisado = orcamento.valor_revisado ? parseFloat(orcamento.valor_revisado).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '-';
        const unidadeNome = orcamento.unidade_nome || '-';
        const centroNome = orcamento.centro_nome || '-';
        const categoriaNome = orcamento.categoria_nome || '-';

        return `
            <tr>
                <td class="px-4 py-2">
                    <p class="font-medium text-secondary-black">${escapeHtml(periodo)}</p>
                </td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(centroNome)}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(categoriaNome)}</td>
                <td class="px-4 py-2 text-sm font-medium text-secondary-black">${valorOrcado}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${valorRevisado}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(unidadeNome)}</td>
                <td class="px-4 py-2">
                    <div class="flex space-x-2">
                        <button onclick="editarOrcamento(${orcamento.id})" class="text-green-600 hover:text-green-700" title="Editar">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        <button onclick="excluirOrcamento(${orcamento.id})" class="text-red-500 hover:text-red-600" title="Excluir">
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

async function criarOrcamento() {
    orcamentoEditando = null;
    if (isAdminGlobal) {
        await Promise.all([carregarUnidades(), carregarCentrosCustos(), carregarCategorias()]);
    } else {
        await Promise.all([carregarCentrosCustos(), carregarCategorias()]);
    }
    abrirModal();
}

async function editarOrcamento(id) {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl(`financeiro/orcamentos.php?action=obter&id=${id}`)
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/orcamentos.php?action=obter&id=${id}`
                : `../../api/financeiro/orcamentos.php?action=obter&id=${id}`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            orcamentoEditando = result.data;
            if (isAdminGlobal) {
                await Promise.all([carregarUnidades(), carregarCentrosCustos(), carregarCategorias()]);
            } else {
                await Promise.all([carregarCentrosCustos(), carregarCategorias()]);
            }
            abrirModal();
        } else {
            throw new Error(result.message || 'Erro ao carregar orçamento');
        }
    } catch (error) {
        console.error('Erro ao carregar orçamento:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar orçamento: ' + error.message);
        }
    }
}

function abrirModal() {
    const modal = document.getElementById('modalOrcamento');
    if (!modal) {
        criarModal();
        return;
    }

    const form = document.getElementById('formOrcamento');
    if (form) {
        form.reset();
    }

    setTimeout(() => {
        if (orcamentoEditando) {
            document.getElementById('modalTitulo').textContent = 'Editar Orçamento';
            document.getElementById('inputAno').value = orcamentoEditando.ano || new Date().getFullYear();
            document.getElementById('selectMes').value = orcamentoEditando.mes || '';
            document.getElementById('selectCentroCusto').value = orcamentoEditando.centro_custo_id || '';
            document.getElementById('selectCategoria').value = orcamentoEditando.categoria_id || '';
            document.getElementById('inputValorOrcado').value = orcamentoEditando.valor_orcado || '0';
            document.getElementById('inputValorRevisado').value = orcamentoEditando.valor_revisado || '';
            document.getElementById('textareaObservacoes').value = orcamentoEditando.observacoes || '';
        } else {
            document.getElementById('modalTitulo').textContent = 'Novo Orçamento';
            document.getElementById('inputAno').value = new Date().getFullYear();
        }
    }, 100);

    modal.classList.remove('hidden');
}

function fecharModal() {
    const modal = document.getElementById('modalOrcamento');
    if (modal) {
        modal.classList.add('hidden');
    }
    orcamentoEditando = null;
}

function criarModal() {
    const modalHTML = `
        <div id="modalOrcamento" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h2 id="modalTitulo" class="text-xl font-bold text-secondary-black">Novo Orçamento</h2>
                    <button onclick="fecharModal()" class="text-secondary-dark-gray hover:text-secondary-black">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <form id="formOrcamento" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Ano *</label>
                            <input type="number" id="inputAno" required class="input-primary w-full" min="2020" max="2100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Mês *</label>
                            <select id="selectMes" required class="input-primary w-full">
                                <option value="">Selecione</option>
                                <option value="1">Janeiro</option>
                                <option value="2">Fevereiro</option>
                                <option value="3">Março</option>
                                <option value="4">Abril</option>
                                <option value="5">Maio</option>
                                <option value="6">Junho</option>
                                <option value="7">Julho</option>
                                <option value="8">Agosto</option>
                                <option value="9">Setembro</option>
                                <option value="10">Outubro</option>
                                <option value="11">Novembro</option>
                                <option value="12">Dezembro</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Centro de Custo *</label>
                            <select id="selectCentroCusto" required class="input-primary w-full">
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Categoria *</label>
                            <select id="selectCategoria" required class="input-primary w-full">
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Valor Orçado *</label>
                            <input type="number" id="inputValorOrcado" step="0.01" required class="input-primary w-full" placeholder="0.00" min="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Valor Revisado</label>
                            <input type="number" id="inputValorRevisado" step="0.01" class="input-primary w-full" placeholder="0.00" min="0">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Observações</label>
                        <textarea id="textareaObservacoes" rows="3" class="input-primary w-full" placeholder="Observações adicionais"></textarea>
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
    
    document.getElementById('formOrcamento').addEventListener('submit', async (e) => {
        e.preventDefault();
        await salvarOrcamento();
    });
}

async function salvarOrcamento() {
    const ano = document.getElementById('inputAno').value;
    const mes = document.getElementById('selectMes').value;
    const centroCustoId = document.getElementById('selectCentroCusto').value;
    const categoriaId = document.getElementById('selectCategoria').value;
    const valorOrcado = document.getElementById('inputValorOrcado').value;
    const valorRevisado = document.getElementById('inputValorRevisado').value;
    const observacoes = document.getElementById('textareaObservacoes').value.trim();

    if (!ano || !mes || !centroCustoId || !categoriaId || !valorOrcado) {
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.warning('Atenção', 'Todos os campos obrigatórios devem ser preenchidos');
        }
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', orcamentoEditando ? 'atualizar' : 'criar');
        if (orcamentoEditando) {
            formData.append('id', orcamentoEditando.id);
        }
        formData.append('ano', ano);
        formData.append('mes', mes);
        formData.append('centro_custo_id', centroCustoId);
        formData.append('categoria_id', categoriaId);
        formData.append('valor_orcado', valorOrcado);
        if (valorRevisado) formData.append('valor_revisado', valorRevisado);
        if (observacoes) formData.append('observacoes', observacoes);

        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/orcamentos.php')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/orcamentos.php`
                : `../../api/financeiro/orcamentos.php`);

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
                SweetAlertConfig.success('Sucesso', result.message || 'Orçamento salvo com sucesso');
            }
            fecharModal();
            await carregarOrcamentos();
        } else {
            throw new Error(result.message || 'Erro ao salvar orçamento');
        }
    } catch (error) {
        console.error('Erro ao salvar orçamento:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao salvar orçamento: ' + error.message);
        }
    }
}

async function excluirOrcamento(id) {
    try {
        const confirmacao = await SweetAlertConfig.confirm(
            'Confirmar Exclusão',
            'Tem certeza que deseja excluir este orçamento? Esta ação não pode ser desfeita.',
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
            ? getApiUrl('financeiro/orcamentos.php')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/orcamentos.php`
                : `../../api/financeiro/orcamentos.php`);

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
                SweetAlertConfig.success('Sucesso', result.message || 'Orçamento excluído com sucesso');
            }
            await carregarOrcamentos();
        } else {
            throw new Error(result.message || 'Erro ao excluir orçamento');
        }
    } catch (error) {
        console.error('Erro ao excluir orçamento:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao excluir orçamento: ' + error.message);
        }
    }
}

let inicializandoOrcamentos = false;
let orcamentosInicializado = false;
const handlersOrcamentos = {
    btnNovoOrcamento: null,
    inputFiltroAno: null,
    selectFiltroMes: null,
    selectFiltroUnidade: null,
    selectFiltroCentroCusto: null,
    selectFiltroCategoria: null
};

async function initOrcamentos() {
    if (inicializandoOrcamentos) return;
    if (orcamentosInicializado) return;
    
    inicializandoOrcamentos = true;
    
    setTimeout(async () => {
        const container = document.getElementById('orcamentos-container') || document.querySelector('.orcamentos-container');
        if (!container) {
            inicializandoOrcamentos = false;
            setTimeout(async () => {
                if (document.getElementById('orcamentos-container') || document.querySelector('.orcamentos-container')) {
                    await initOrcamentos();
                }
            }, 200);
            return;
        }

        inicializarContexto();
        if (isAdminGlobal) {
            await carregarUnidades();
        }
        await Promise.all([carregarCentrosCustos(), carregarCategorias()]);
        await carregarOrcamentos();
        
        const btnNovoOrcamento = document.getElementById('btnNovoOrcamento');
        if (btnNovoOrcamento && !handlersOrcamentos.btnNovoOrcamento) {
            handlersOrcamentos.btnNovoOrcamento = criarOrcamento;
            btnNovoOrcamento.addEventListener('click', handlersOrcamentos.btnNovoOrcamento);
        }

        const inputFiltroAno = document.getElementById('inputFiltroAno');
        if (inputFiltroAno && !handlersOrcamentos.inputFiltroAno) {
            inputFiltroAno.value = new Date().getFullYear();
            handlersOrcamentos.inputFiltroAno = () => carregarOrcamentos();
            inputFiltroAno.addEventListener('change', handlersOrcamentos.inputFiltroAno);
        }

        const selectFiltroMes = document.getElementById('selectFiltroMes');
        if (selectFiltroMes && !handlersOrcamentos.selectFiltroMes) {
            handlersOrcamentos.selectFiltroMes = () => carregarOrcamentos();
            selectFiltroMes.addEventListener('change', handlersOrcamentos.selectFiltroMes);
        }

        const selectFiltroUnidade = document.getElementById('selectFiltroUnidade');
        if (selectFiltroUnidade && !handlersOrcamentos.selectFiltroUnidade) {
            handlersOrcamentos.selectFiltroUnidade = () => carregarOrcamentos();
            selectFiltroUnidade.addEventListener('change', handlersOrcamentos.selectFiltroUnidade);
        }

        const selectFiltroCentroCusto = document.getElementById('selectFiltroCentroCusto');
        if (selectFiltroCentroCusto && !handlersOrcamentos.selectFiltroCentroCusto) {
            handlersOrcamentos.selectFiltroCentroCusto = () => carregarOrcamentos();
            selectFiltroCentroCusto.addEventListener('change', handlersOrcamentos.selectFiltroCentroCusto);
        }

        const selectFiltroCategoria = document.getElementById('selectFiltroCategoria');
        if (selectFiltroCategoria && !handlersOrcamentos.selectFiltroCategoria) {
            handlersOrcamentos.selectFiltroCategoria = () => carregarOrcamentos();
            selectFiltroCategoria.addEventListener('change', handlersOrcamentos.selectFiltroCategoria);
        }
        
        orcamentosInicializado = true;
        inicializandoOrcamentos = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOrcamentos);
} else {
    initOrcamentos();
}

