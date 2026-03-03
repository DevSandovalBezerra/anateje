if (typeof window.receitasDespesasData === 'undefined') {
    window.receitasDespesasData = [];
    window.registroEditando = null;
    window.unidadesData = [];
    window.centrosCustosData = [];
    window.categoriasData = [];
    window.contasFinanceirasData = [];
    window.isAdminGlobal = false;
    window.userUnidadeId = null;
}

receitasDespesasData = window.receitasDespesasData;
registroEditando = window.registroEditando;
unidadesData = window.unidadesData;
centrosCustosData = window.centrosCustosData;
categoriasData = window.categoriasData;
contasFinanceirasData = window.contasFinanceirasData;
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

async function carregarContasFinanceiras() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/contas_financeiras.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/contas_financeiras.php?action=listar`
                : `../../api/financeiro/contas_financeiras.php?action=listar`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            contasFinanceirasData = result.data || [];
            preencherSelectContasFinanceiras();
        }
    } catch (error) {
        console.error('Erro ao carregar contas financeiras:', error);
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
    const tipoFiltro = registroEditando ? registroEditando.tipo : document.getElementById('selectTipo')?.value;
    
    categoriasData.forEach(categoria => {
        if (categoria.ativo == 1) {
            if (!tipoFiltro || categoria.tipo === tipoFiltro) {
                const option = document.createElement('option');
                option.value = categoria.id;
                option.textContent = categoria.nome + ' (' + (categoria.tipo === 'receita' ? 'Receita' : 'Despesa') + ')';
                select.appendChild(option);
            }
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

function preencherSelectContasFinanceiras() {
    const select = document.getElementById('selectContaFinanceira');
    if (!select) return;
    
    select.innerHTML = '<option value="">Nenhuma (opcional)</option>';
    contasFinanceirasData.forEach(conta => {
        if (conta.status === 'ativa') {
            const option = document.createElement('option');
            option.value = conta.id;
            option.textContent = conta.nome;
            select.appendChild(option);
        }
    });
}

async function carregarRegistros() {
    try {
        const tipo = document.getElementById('selectFiltroTipo')?.value || '';
        const unidadeId = document.getElementById('selectFiltroUnidade')?.value || '';
        const centroCustoId = document.getElementById('selectFiltroCentroCusto')?.value || '';
        const categoriaId = document.getElementById('selectFiltroCategoria')?.value || '';
        const buscar = document.getElementById('inputBuscar')?.value || '';
        const ativo = document.getElementById('selectFiltroAtivo')?.value || '';
        
        let url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/receitas_despesas.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/receitas_despesas.php?action=listar`
                : `../../api/financeiro/receitas_despesas.php?action=listar`);
        
        const params = new URLSearchParams();
        if (tipo) params.append('tipo', tipo);
        if (unidadeId) params.append('unidade_id', unidadeId);
        if (centroCustoId) params.append('centro_custo_id', centroCustoId);
        if (categoriaId) params.append('categoria_id', categoriaId);
        if (buscar) params.append('buscar', buscar);
        if (ativo !== '') params.append('ativo', ativo);
        
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
            receitasDespesasData = result.data || [];
            renderizarTabela();
        } else {
            throw new Error(result.message || 'Erro ao carregar registros');
        }
    } catch (error) {
        console.error('Erro ao carregar registros:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar registros: ' + error.message);
        }
    }
}

function renderizarTabela() {
    const tbody = document.getElementById('tbodyRegistros');
    if (!tbody) return;

    if (receitasDespesasData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-secondary-dark-gray">
                    Nenhum registro encontrado
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = receitasDespesasData.map(reg => {
        const tipoLabel = reg.tipo === 'receita' ? '<span class="px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800">Receita</span>' : '<span class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800">Despesa</span>';
        const valor = parseFloat(reg.valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const periodicidadeLabels = { 'mensal': 'Mensal', 'trimestral': 'Trimestral', 'anual': 'Anual' };
        const statusLabel = reg.ativo == 1 ? '<span class="px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800">Ativo</span>' : '<span class="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800">Inativo</span>';

        return `
            <tr>
                <td class="px-4 py-2">${tipoLabel}</td>
                <td class="px-4 py-2">
                    <p class="font-medium text-secondary-black">${escapeHtml(reg.nome || '')}</p>
                    ${reg.descricao ? `<p class="text-sm text-secondary-dark-gray">${escapeHtml(reg.descricao)}</p>` : ''}
                </td>
                <td class="px-4 py-2 text-sm font-medium text-secondary-black">${valor}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(reg.centro_nome || '-')}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(reg.categoria_nome || '-')}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${periodicidadeLabels[reg.periodicidade] || reg.periodicidade}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${reg.dia_vencimento || '-'}</td>
                <td class="px-4 py-2">${statusLabel}</td>
                <td class="px-4 py-2">
                    <div class="flex space-x-2">
                        <button onclick="editarRegistro(${reg.id})" class="text-green-600 hover:text-green-700" title="Editar">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        <button onclick="excluirRegistro(${reg.id})" class="text-red-500 hover:text-red-600" title="Excluir">
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

async function criarRegistro() {
    registroEditando = null;
    if (isAdminGlobal) {
        await Promise.all([carregarUnidades(), carregarCentrosCustos(), carregarCategorias(), carregarContasFinanceiras()]);
    } else {
        await Promise.all([carregarCentrosCustos(), carregarCategorias(), carregarContasFinanceiras()]);
    }
    abrirModal();
}

async function editarRegistro(id) {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl(`financeiro/receitas_despesas.php?action=obter&id=${id}`)
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/receitas_despesas.php?action=obter&id=${id}`
                : `../../api/financeiro/receitas_despesas.php?action=obter&id=${id}`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            registroEditando = result.data;
            if (isAdminGlobal) {
                await Promise.all([carregarUnidades(), carregarCentrosCustos(), carregarCategorias(), carregarContasFinanceiras()]);
            } else {
                await Promise.all([carregarCentrosCustos(), carregarCategorias(), carregarContasFinanceiras()]);
            }
            abrirModal();
        } else {
            throw new Error(result.message || 'Erro ao carregar registro');
        }
    } catch (error) {
        console.error('Erro ao carregar registro:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar registro: ' + error.message);
        }
    }
}

function abrirModal() {
    const modal = document.getElementById('modalRegistro');
    if (!modal) {
        criarModal();
        return;
    }

    const form = document.getElementById('formRegistro');
    if (form) {
        form.reset();
    }

    setTimeout(() => {
        if (registroEditando) {
            document.getElementById('modalTitulo').textContent = 'Editar Registro';
            document.getElementById('selectTipo').value = registroEditando.tipo || '';
            document.getElementById('inputNome').value = registroEditando.nome || '';
            document.getElementById('textareaDescricao').value = registroEditando.descricao || '';
            document.getElementById('inputValor').value = registroEditando.valor || '0';
            document.getElementById('selectCentroCusto').value = registroEditando.centro_custo_id || '';
            document.getElementById('selectCategoria').value = registroEditando.categoria_id || '';
            document.getElementById('selectContaFinanceira').value = registroEditando.conta_financeira_id || '';
            document.getElementById('selectPeriodicidade').value = registroEditando.periodicidade || 'mensal';
            document.getElementById('inputDiaVencimento').value = registroEditando.dia_vencimento || 5;
            document.getElementById('selectAtivo').value = registroEditando.ativo || '1';
            document.getElementById('textareaObservacoes').value = registroEditando.observacoes || '';
            if (isAdminGlobal) {
                document.getElementById('selectUnidade').value = registroEditando.unidade_id || '';
            }
            preencherSelectCategorias();
        } else {
            document.getElementById('modalTitulo').textContent = 'Novo Registro';
            document.getElementById('selectTipo').value = '';
            if (isAdminGlobal) {
                document.getElementById('selectUnidade').value = '';
            }
        }
    }, 100);

    modal.classList.remove('hidden');
}

function fecharModal() {
    const modal = document.getElementById('modalRegistro');
    if (modal) {
        modal.classList.add('hidden');
    }
    registroEditando = null;
}

function criarModal() {
    const modalHTML = `
        <div id="modalRegistro" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h2 id="modalTitulo" class="text-xl font-bold text-secondary-black">Novo Registro</h2>
                    <button onclick="fecharModal()" class="text-secondary-dark-gray hover:text-secondary-black">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <form id="formRegistro" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Tipo *</label>
                            <select id="selectTipo" required class="input-primary w-full" onchange="preencherSelectCategorias()">
                                <option value="">Selecione</option>
                                <option value="receita">Receita</option>
                                <option value="despesa">Despesa</option>
                            </select>
                        </div>
                        <div id="selectUnidadeContainer" style="display: ${isAdminGlobal ? 'block' : 'none'};">
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Unidade *</label>
                            <select id="selectUnidade" ${isAdminGlobal ? 'required' : ''} class="input-primary w-full">
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Nome *</label>
                        <input type="text" id="inputNome" required class="input-primary w-full" placeholder="Ex: Aluguel Mensal">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Descrição</label>
                        <textarea id="textareaDescricao" rows="2" class="input-primary w-full" placeholder="Descrição detalhada"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Valor *</label>
                            <input type="number" id="inputValor" step="0.01" required class="input-primary w-full" placeholder="0.00" min="0.01">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Dia de Vencimento *</label>
                            <input type="number" id="inputDiaVencimento" required class="input-primary w-full" placeholder="5" min="1" max="28" value="5">
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
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Conta Financeira</label>
                            <select id="selectContaFinanceira" class="input-primary w-full">
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Periodicidade *</label>
                            <select id="selectPeriodicidade" required class="input-primary w-full">
                                <option value="mensal">Mensal</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Observações</label>
                        <textarea id="textareaObservacoes" rows="2" class="input-primary w-full" placeholder="Observações adicionais"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                        <select id="selectAtivo" class="input-primary w-full">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
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
    
    document.getElementById('formRegistro').addEventListener('submit', async (e) => {
        e.preventDefault();
        await salvarRegistro();
    });
}

async function salvarRegistro() {
    const tipo = document.getElementById('selectTipo').value;
    const nome = document.getElementById('inputNome').value;
    const descricao = document.getElementById('textareaDescricao').value.trim();
    const valor = document.getElementById('inputValor').value;
    const centroCustoId = document.getElementById('selectCentroCusto').value;
    const categoriaId = document.getElementById('selectCategoria').value;
    const contaFinanceiraId = document.getElementById('selectContaFinanceira').value;
    const periodicidade = document.getElementById('selectPeriodicidade').value;
    const diaVencimento = document.getElementById('inputDiaVencimento').value;
    const ativo = document.getElementById('selectAtivo').value;
    const observacoes = document.getElementById('textareaObservacoes').value.trim();

    if (!tipo || !nome || !valor || !centroCustoId || !categoriaId) {
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.warning('Atenção', 'Todos os campos obrigatórios devem ser preenchidos');
        }
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', registroEditando ? 'atualizar' : 'criar');
        if (registroEditando) {
            formData.append('id', registroEditando.id);
        }
        formData.append('tipo', tipo);
        formData.append('nome', nome);
        if (descricao) formData.append('descricao', descricao);
        formData.append('valor', valor);
        formData.append('centro_custo_id', centroCustoId);
        formData.append('categoria_id', categoriaId);
        if (contaFinanceiraId) formData.append('conta_financeira_id', contaFinanceiraId);
        formData.append('periodicidade', periodicidade);
        formData.append('dia_vencimento', diaVencimento);
        formData.append('ativo', ativo);
        if (observacoes) formData.append('observacoes', observacoes);
        if (isAdminGlobal) {
            const unidadeId = document.getElementById('selectUnidade').value;
            if (unidadeId) formData.append('unidade_id', unidadeId);
        }

        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/receitas_despesas.php')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/receitas_despesas.php`
                : `../../api/financeiro/receitas_despesas.php`);

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
                SweetAlertConfig.success('Sucesso', result.message || 'Registro salvo com sucesso');
            }
            fecharModal();
            await carregarRegistros();
        } else {
            throw new Error(result.message || 'Erro ao salvar registro');
        }
    } catch (error) {
        console.error('Erro ao salvar registro:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao salvar registro: ' + error.message);
        }
    }
}

async function excluirRegistro(id) {
    try {
        const confirmacao = await SweetAlertConfig.confirm(
            'Confirmar Exclusão',
            'Tem certeza que deseja excluir este registro? Esta ação não pode ser desfeita.',
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
            ? getApiUrl('financeiro/receitas_despesas.php')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/receitas_despesas.php`
                : `../../api/financeiro/receitas_despesas.php`);

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
                SweetAlertConfig.success('Sucesso', result.message || 'Registro excluído com sucesso');
            }
            await carregarRegistros();
        } else {
            throw new Error(result.message || 'Erro ao excluir registro');
        }
    } catch (error) {
        console.error('Erro ao excluir registro:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao excluir registro: ' + error.message);
        }
    }
}

let inicializandoReceitasDespesas = false;
let receitasDespesasInicializado = false;
const handlersReceitasDespesas = {
    btnNovoRegistro: null,
    selectFiltroTipo: null,
    selectFiltroUnidade: null,
    selectFiltroCentroCusto: null,
    selectFiltroCategoria: null,
    selectFiltroAtivo: null,
    inputBuscar: null,
    timeoutBuscar: null
};

async function initReceitasDespesas() {
    if (inicializandoReceitasDespesas) return;
    if (receitasDespesasInicializado) return;
    
    inicializandoReceitasDespesas = true;
    
    setTimeout(async () => {
        const container = document.getElementById('tbodyRegistros') || document.querySelector('.cadastros-content');
        if (!container) {
            inicializandoReceitasDespesas = false;
            setTimeout(async () => {
                if (document.getElementById('tbodyRegistros') || document.querySelector('.cadastros-content')) {
                    await initReceitasDespesas();
                }
            }, 200);
            return;
        }

        inicializarContexto();
        if (isAdminGlobal) {
            await carregarUnidades();
        }
        await Promise.all([carregarCentrosCustos(), carregarCategorias(), carregarContasFinanceiras()]);
        await carregarRegistros();
        
        const btnNovoRegistro = document.getElementById('btnNovoRegistro');
        if (btnNovoRegistro && !handlersReceitasDespesas.btnNovoRegistro) {
            handlersReceitasDespesas.btnNovoRegistro = criarRegistro;
            btnNovoRegistro.addEventListener('click', handlersReceitasDespesas.btnNovoRegistro);
        }

        const selectFiltroTipo = document.getElementById('selectFiltroTipo');
        if (selectFiltroTipo && !handlersReceitasDespesas.selectFiltroTipo) {
            handlersReceitasDespesas.selectFiltroTipo = () => carregarRegistros();
            selectFiltroTipo.addEventListener('change', handlersReceitasDespesas.selectFiltroTipo);
        }

        const selectFiltroUnidade = document.getElementById('selectFiltroUnidade');
        if (selectFiltroUnidade && !handlersReceitasDespesas.selectFiltroUnidade) {
            handlersReceitasDespesas.selectFiltroUnidade = () => carregarRegistros();
            selectFiltroUnidade.addEventListener('change', handlersReceitasDespesas.selectFiltroUnidade);
        }

        const selectFiltroCentroCusto = document.getElementById('selectFiltroCentroCusto');
        if (selectFiltroCentroCusto && !handlersReceitasDespesas.selectFiltroCentroCusto) {
            handlersReceitasDespesas.selectFiltroCentroCusto = () => carregarRegistros();
            selectFiltroCentroCusto.addEventListener('change', handlersReceitasDespesas.selectFiltroCentroCusto);
        }

        const selectFiltroCategoria = document.getElementById('selectFiltroCategoria');
        if (selectFiltroCategoria && !handlersReceitasDespesas.selectFiltroCategoria) {
            handlersReceitasDespesas.selectFiltroCategoria = () => carregarRegistros();
            selectFiltroCategoria.addEventListener('change', handlersReceitasDespesas.selectFiltroCategoria);
        }

        const selectFiltroAtivo = document.getElementById('selectFiltroAtivo');
        if (selectFiltroAtivo && !handlersReceitasDespesas.selectFiltroAtivo) {
            handlersReceitasDespesas.selectFiltroAtivo = () => carregarRegistros();
            selectFiltroAtivo.addEventListener('change', handlersReceitasDespesas.selectFiltroAtivo);
        }

        const inputBuscar = document.getElementById('inputBuscar');
        if (inputBuscar && !handlersReceitasDespesas.inputBuscar) {
            handlersReceitasDespesas.inputBuscar = () => {
                if (handlersReceitasDespesas.timeoutBuscar) clearTimeout(handlersReceitasDespesas.timeoutBuscar);
                handlersReceitasDespesas.timeoutBuscar = setTimeout(() => carregarRegistros(), 500);
            };
            inputBuscar.addEventListener('input', handlersReceitasDespesas.inputBuscar);
        }
        
        receitasDespesasInicializado = true;
        inicializandoReceitasDespesas = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReceitasDespesas);
} else {
    initReceitasDespesas();
}

