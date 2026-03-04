if (typeof window.lancamentosData === 'undefined') {
    window.lancamentosData = [];
    window.registroEditando = null;
    window.contasBancariasData = [];
    window.categoriasData = [];
    window.centrosCustosData = [];
    window.pessoasData = [];
    window.lancamentosSelecionados = [];
}

lancamentosData = window.lancamentosData;
registroEditando = window.registroEditando;
contasBancariasData = window.contasBancariasData;
categoriasData = window.categoriasData;
centrosCustosData = window.centrosCustosData;
pessoasData = window.pessoasData;
lancamentosSelecionados = window.lancamentosSelecionados;

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

async function carregarDadosAuxiliares() {
    try {
        const [contas, categorias, centros, pessoas] = await Promise.all([
            fetch(getApiUrl('financeiro/contas_bancarias.php?action=listar&ativo=1'), { credentials: 'include' }).then(r => r.json()),
            fetch(getApiUrl('financeiro/categorias_financeiras.php?action=listar'), { credentials: 'include' }).then(r => r.json()),
            fetch(getApiUrl('financeiro/centros_custos.php?action=listar'), { credentials: 'include' }).then(r => r.json()),
            fetch(getApiUrl('financeiro/pessoas.php?action=listar&ativo=1'), { credentials: 'include' }).then(r => r.json())
        ]);
        
        if (contas.success) {
            contasBancariasData = contas.data || [];
            preencherSelect('selectFiltroConta', contasBancariasData, 'nome_conta', 'id', true);
        }
        
        if (categorias.success) {
            categoriasData = categorias.data || [];
            preencherSelect('selectFiltroCategoria', categoriasData, 'nome', 'id', true);
        }
        
        if (centros.success) {
            centrosCustosData = centros.data || [];
        }
        
        if (pessoas.success) {
            pessoasData = pessoas.data || [];
        }
    } catch (error) {
        console.error('Erro ao carregar dados auxiliares:', error);
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

async function carregarLancamentos() {
    try {
        const tipo = document.getElementById('selectFiltroTipo')?.value || '';
        const status = document.getElementById('selectFiltroStatus')?.value || '';
        const conta = document.getElementById('selectFiltroConta')?.value || '';
        const categoria = document.getElementById('selectFiltroCategoria')?.value || '';
        const dataInicio = document.getElementById('inputDataInicio')?.value || '';
        const dataFim = document.getElementById('inputDataFim')?.value || '';
        const buscar = document.getElementById('inputBuscar')?.value || '';
        
        const params = new URLSearchParams();
        if (tipo) params.append('tipo_semantico', tipo);
        if (status) params.append('status', status);
        if (conta) params.append('conta_bancaria_id', conta);
        if (categoria) params.append('categoria_id', categoria);
        if (dataInicio) params.append('data_inicio', dataInicio);
        if (dataFim) params.append('data_fim', dataFim);
        if (buscar) params.append('buscar', buscar);
        
        const url = getApiUrl(`financeiro/lancamentos.php?action=listar&${params.toString()}`);
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
            lancamentosData = result.data || [];
            renderizarTabela();
            atualizarBotaoAcoesMassa();
        } else {
            throw new Error(result.message || 'Erro ao carregar lançamentos');
        }
    } catch (error) {
        console.error('Erro ao carregar lançamentos:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar lançamentos: ' + error.message);
        }
    }
}

function renderizarTabela() {
    const tbody = document.getElementById('tbodyLancamentos');
    if (!tbody) return;
    
    if (lancamentosData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-secondary-dark-gray">
                    <i data-lucide="file-text" class="w-8 h-8 mx-auto mb-2"></i>
                    <p>Nenhum lançamento encontrado</p>
                </td>
            </tr>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }
    
    tbody.innerHTML = lancamentosData.map(lanc => {
        const tipoSemantico = lanc.tipo_semantico || (lanc.tipo === 'receber' ? 'receita' : (lanc.tipo === 'pagar' ? 'despesa' : ''));
        const isTransferencia = tipoSemantico === 'transferencia';
        
        let tipoLabel = 'Receita';
        let tipoClass = 'text-green-600 bg-green-100';
        if (tipoSemantico === 'despesa') {
            tipoLabel = 'Despesa';
            tipoClass = 'text-red-600 bg-red-100';
        } else if (isTransferencia) {
            tipoLabel = 'Transferência';
            tipoClass = 'text-blue-600 bg-blue-100';
        }
        
        const statusClass = {
            'previsto': 'bg-blue-100 text-blue-800',
            'aberto': 'bg-yellow-100 text-yellow-800',
            'pendente': 'bg-yellow-100 text-yellow-800',
            'parcial': 'bg-orange-100 text-orange-800',
            'quitado': 'bg-green-100 text-green-800',
            'pago': 'bg-green-100 text-green-800',
            'cancelado': 'bg-red-100 text-red-800'
        }[lanc.status] || 'bg-gray-100 text-gray-800';
        
        const valorClass = isTransferencia ? 'text-blue-600' : (tipoSemantico === 'receita' ? 'text-green-600' : 'text-red-600');
        
        let pessoaInfo = escapeHtml(lanc.pessoa_nome || '-');
        if (isTransferencia && lanc.conta_bancaria_nome) {
            pessoaInfo = `Transferência: ${escapeHtml(lanc.conta_bancaria_nome)}`;
        }
        
        return `
            <tr class="hover:bg-gray-50 ${isTransferencia ? 'bg-blue-50' : ''}">
                <td class="px-4 py-3">
                    <input type="checkbox" class="check-lancamento rounded" value="${lanc.id}" onchange="toggleSelecao(${lanc.id})">
                </td>
                <td class="px-4 py-3 font-medium">${escapeHtml(lanc.titulo || lanc.descricao || '')}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded-full ${tipoClass}">
                        ${tipoLabel}
                    </span>
                </td>
                <td class="px-4 py-3">${pessoaInfo}</td>
                <td class="px-4 py-3 font-semibold ${valorClass}">${formatarMoeda(lanc.valor_total || 0)}</td>
                <td class="px-4 py-3">${formatarData(lanc.data_vencimento)}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded-full ${statusClass}">
                        ${escapeHtml(lanc.status || '')}
                    </span>
                </td>
                <td class="px-4 py-3">
                    ${lanc.total_parcelas > 0 ? `${lanc.parcelas_pagas || 0}/${lanc.total_parcelas}` : '-'}
                </td>
                <td class="px-4 py-3">
                    <div class="flex gap-2">
                        ${!isTransferencia ? `
                        <button onclick="editarLancamento(${lanc.id})" class="text-green-600 hover:text-green-700" title="Editar">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        <button onclick="duplicarLancamento(${lanc.id})" class="text-green-600 hover:text-green-800" title="Duplicar">
                            <i data-lucide="copy" class="w-4 h-4"></i>
                        </button>
                        ` : ''}
                        ${!isTransferencia ? `
                        <button onclick="quitarLancamento(${lanc.id})" class="text-purple-600 hover:text-purple-800" title="Quitar">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                        </button>
                        ` : ''}
                        <button onclick="excluirLancamento(${lanc.id})" class="text-red-500 hover:text-red-600" title="Excluir">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function toggleSelecao(id) {
    const index = lancamentosSelecionados.indexOf(id);
    if (index > -1) {
        lancamentosSelecionados.splice(index, 1);
    } else {
        lancamentosSelecionados.push(id);
    }
    atualizarBotaoAcoesMassa();
}

function atualizarBotaoAcoesMassa() {
    const btn = document.getElementById('btnAcoesMassa');
    if (btn) {
        btn.style.display = lancamentosSelecionados.length > 0 ? 'block' : 'none';
    }
}

async function editarLancamento(id) {
    try {
        const url = getApiUrl(`financeiro/lancamentos.php?action=obter&id=${id}`);
        const response = await fetch(url, { credentials: 'include' });
        const result = await response.json();
        
        if (result.success) {
            registroEditando = result.data;
            abrirModal();
        } else {
            throw new Error(result.message || 'Erro ao carregar lançamento');
        }
    } catch (error) {
        console.error('Erro ao carregar lançamento:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar lançamento: ' + error.message);
        }
    }
}

async function duplicarLancamento(id) {
    try {
        const formData = new FormData();
        formData.append('action', 'duplicar');
        formData.append('id', id);
        
        const url = getApiUrl('financeiro/lancamentos.php');
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.success('Sucesso', 'Lançamento duplicado com sucesso!');
            }
            await carregarLancamentos();
        } else {
            throw new Error(result.message || 'Erro ao duplicar lançamento');
        }
    } catch (error) {
        console.error('Erro ao duplicar lançamento:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao duplicar lançamento: ' + error.message);
        }
    }
}

async function quitarLancamento(id) {
    try {
        const formData = new FormData();
        formData.append('action', 'quitar');
        formData.append('id', id);
        formData.append('data_pagamento', new Date().toISOString().split('T')[0]);
        
        const url = getApiUrl('financeiro/lancamentos.php');
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.success('Sucesso', 'Lançamento quitado com sucesso!');
            }
            await carregarLancamentos();
        } else {
            throw new Error(result.message || 'Erro ao quitar lançamento');
        }
    } catch (error) {
        console.error('Erro ao quitar lançamento:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao quitar lançamento: ' + error.message);
        }
    }
}

async function excluirLancamento(id) {
    if (typeof SweetAlertConfig === 'undefined') {
        if (!confirm('Tem certeza que deseja excluir este lançamento?')) return;
    } else {
        const confirmacao = await SweetAlertConfig.confirm(
            'Excluir Lançamento',
            'Tem certeza que deseja excluir este lançamento? Esta ação não pode ser desfeita.',
            'Excluir',
            'Cancelar'
        );
        if (!confirmacao) return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);
        
        const url = getApiUrl('financeiro/lancamentos.php');
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.success('Sucesso', 'Lançamento excluído com sucesso!');
            }
            await carregarLancamentos();
        } else {
            throw new Error(result.message || 'Erro ao excluir lançamento');
        }
    } catch (error) {
        console.error('Erro ao excluir lançamento:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao excluir lançamento: ' + error.message);
        }
    }
}

function abrirModal() {
    const isEdicao = registroEditando !== null;
    const tipoInicial = registroEditando?.tipo_semantico || registroEditando?.tipo || 'receita';
    const tipoMapeado = tipoInicial === 'receber' ? 'receita' : (tipoInicial === 'pagar' ? 'despesa' : tipoInicial);
    const statusInicial = registroEditando?.status || 'aberto';
    
    const html = `
        <div id="modalLancamento" class="modal-lancamento">
            <div class="modal-lancamento-header">
                <h2 class="modal-lancamento-title">${isEdicao ? 'Editar Lançamento' : 'Novo Lançamento'}</h2>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="modal-lancamento-status">
                        <select id="modalStatus" style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 0.375rem; background: var(--bg-primary); color: var(--text-primary); font-size: 0.875rem; cursor: pointer;">
                            <option value="previsto" ${statusInicial === 'previsto' ? 'selected' : ''}>Previsto</option>
                            <option value="aberto" ${statusInicial === 'aberto' ? 'selected' : ''}>Em Aberto</option>
                            <option value="pendente" ${statusInicial === 'pendente' ? 'selected' : ''}>Pendente</option>
                            <option value="parcial" ${statusInicial === 'parcial' ? 'selected' : ''}>Parcial</option>
                            <option value="quitado" ${statusInicial === 'quitado' ? 'selected' : ''}>Quitado</option>
                            <option value="pago" ${statusInicial === 'pago' ? 'selected' : ''}>Pago</option>
                            <option value="cancelado" ${statusInicial === 'cancelado' ? 'selected' : ''}>Cancelado</option>
                        </select>
                    </div>
                    <div class="modal-lancamento-modo">
                        <button type="button" id="btnModoSimples" class="active">Simples</button>
                        <button type="button" id="btnModoCompleto">Completo</button>
                    </div>
                </div>
            </div>
            
            <form id="formLancamento" class="modal-lancamento-form">
                <input type="hidden" id="modalLancamentoId" value="${registroEditando?.id || ''}">
                <input type="hidden" id="modalTipoSemantico" value="${tipoMapeado}">
                <input type="hidden" id="modalModo" value="simples">
                
                <div class="modal-tipo-selector">
                    <button type="button" class="btn-tipo-lancamento ${tipoMapeado === 'receita' ? 'active' : ''}" data-tipo="receita">
                        <i data-lucide="arrow-down-circle" class="w-4 h-4"></i>
                        <span>Receita</span>
                    </button>
                    <button type="button" class="btn-tipo-lancamento ${tipoMapeado === 'despesa' ? 'active' : ''}" data-tipo="despesa">
                        <i data-lucide="arrow-up-circle" class="w-4 h-4"></i>
                        <span>Despesa</span>
                    </button>
                    <button type="button" class="btn-tipo-lancamento ${tipoMapeado === 'transferencia' ? 'active' : ''}" data-tipo="transferencia">
                        <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
                        <span>Transferência</span>
                    </button>
                </div>
                
                <div class="modal-form-container" id="modalFormContainer">
                    <div class="modal-form-column modal-form-left">
                        <div class="form-group">
                            <label>Título <span class="required">*</span></label>
                            <input type="text" id="modalTitulo" value="${escapeHtml(registroEditando?.titulo || '')}" required>
                        </div>
                        
                        <div class="form-group" id="grupoPessoa" style="${tipoMapeado === 'transferencia' ? 'display: none;' : ''}">
                            <label>Responsavel financeiro <span class="required">*</span></label>
                            <div class="btn-group-inline">
                                <button type="button" class="btn-tipo-pessoa active" data-tipo-pessoa="colaborador">Colaborador</button>
                                <button type="button" class="btn-tipo-pessoa" data-tipo-pessoa="cliente">Associado</button>
                                <button type="button" class="btn-tipo-pessoa" data-tipo-pessoa="fornecedor">Fornecedor</button>
                            </div>
                            <select id="modalPessoa">
                                <option value="">Selecione...</option>
                            </select>
                        </div>
                        
                        <div class="form-row" id="grupoCategoriaValor" style="${tipoMapeado === 'transferencia' ? 'display: none;' : ''}">
                            <div class="form-group">
                                <label>Categoria <span class="required">*</span></label>
                                <select id="modalCategoria" required>
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Valor (R$) <span class="required">*</span></label>
                                <input type="number" id="modalValor" step="0.01" min="0.01" value="${registroEditando?.valor_total || ''}" required>
                                <a href="#" id="linkAcrescimoDesconto" class="link-acrescimo">Adicionar acréscimo ou desconto?</a>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Vencimento <span class="required">*</span></label>
                                <input type="date" id="modalDataVencimento" value="${registroEditando?.data_vencimento || ''}" required>
                            </div>
                            <div class="form-group">
                                <label>Competência</label>
                                <input type="text" id="modalCompetencia" placeholder="MM/AAAA" maxlength="7">
                            </div>
                        </div>
                        
                        <div class="form-row" id="grupoCondicao" style="${tipoMapeado === 'transferencia' ? 'display: none;' : ''}">
                            <div class="form-group">
                                <label>Condição</label>
                                <select id="modalCondicao">
                                    <option value="unica">Única</option>
                                    <option value="mensal" selected>Mensal</option>
                                    <option value="quinzenal">Quinzenal</option>
                                    <option value="semanal">Semanal</option>
                                    <option value="bimestral">Bimestral</option>
                                    <option value="trimestral">Trimestral</option>
                                    <option value="semestral">Semestral</option>
                                    <option value="anual">Anual</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>N° de Meses</label>
                                <input type="number" id="modalQtdParcelas" min="1" value="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-form-column modal-form-right" id="colunaDireita" style="display: none;">
                        <div class="form-group">
                            <label>Nº Documento ou NF</label>
                            <input type="text" id="modalNumeroDocumento" value="${escapeHtml(registroEditando?.numero_documento || '')}">
                        </div>
                        
                        <div class="form-group" id="grupoConta" style="${tipoMapeado === 'transferencia' ? 'display: none;' : ''}">
                            <label>Conta <span class="required">*</span></label>
                            <select id="modalContaBancaria" required>
                                <option value="">Selecione...</option>
                            </select>
                        </div>
                        
                        <div class="form-row" id="grupoTransferencia" style="${tipoMapeado !== 'transferencia' ? 'display: none;' : ''}">
                            <div class="form-group">
                                <label>Conta Origem <span class="required">*</span></label>
                                <select id="modalContaOrigem" required>
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Conta Destino <span class="required">*</span></label>
                                <select id="modalContaDestino" required>
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Forma de Pagamento</label>
                                <select id="modalFormaPagamento">
                                    <option value="">Selecione...</option>
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="pix">PIX</option>
                                    <option value="ted">TED</option>
                                    <option value="doc">DOC</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="cartao_credito">Cartão de Crédito</option>
                                    <option value="cartao_debito">Cartão de Débito</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Centro de custo</label>
                                <select id="modalCentroCusto">
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Empresa</label>
                                <select id="modalEmpresa">
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Referente ao associado</label>
                                <select id="modalCliente">
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group" id="grupoDescricao">
                    <label>Descrição</label>
                    <textarea id="modalDescricao" rows="3">${escapeHtml(registroEditando?.descricao || '')}</textarea>
                </div>
                
                <div id="tabelaParcelasContainer" style="display: none; margin-top: 1.5rem;">
                    <div class="parcelamento-table-container">
                        <table class="parcelamento-table">
                            <thead>
                                <tr>
                                    <th>Fatura</th>
                                    <th>Vencimento</th>
                                    <th>Competência</th>
                                    <th>Valor (R$)</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyParcelas"></tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>
    `;
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '',
            html: html,
            width: '900px',
            showCancelButton: true,
            confirmButtonText: 'Salvar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#8B5CF6',
            cancelButtonColor: '#6B7280',
            allowOutsideClick: false,
            didOpen: () => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
                inicializarModalLancamento();
            },
            preConfirm: () => {
                return salvarLancamento();
            }
        });
    } else if (typeof SweetAlertConfig !== 'undefined' && SweetAlertConfig.form) {
        SweetAlertConfig.form({
            title: '',
            html: html,
            confirmButtonText: 'Salvar',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
                inicializarModalLancamento();
            },
            preConfirm: () => {
                return salvarLancamento();
            }
        });
    } else {
        alert('SweetAlert não disponível');
    }
}

function inicializarModalLancamento() {
    preencherSelectsModal();
    configurarEventosModal();
    
    const formContainer = document.getElementById('modalFormContainer');
    if (formContainer) {
        formContainer.classList.add('simples');
    }
    
    const tipoAtual = document.getElementById('modalTipoSemantico')?.value || 'receita';
    if (tipoAtual !== 'transferencia') {
        gerarTabelaParcelas();
    }
    
    if (registroEditando) {
        preencherCamposEdicao();
    }
}

function preencherSelectsModal() {
    const selectConta = document.getElementById('modalContaBancaria');
    const selectContaOrigem = document.getElementById('modalContaOrigem');
    const selectContaDestino = document.getElementById('modalContaDestino');
    const selectCategoria = document.getElementById('modalCategoria');
    const selectCentroCusto = document.getElementById('modalCentroCusto');
    const selectPessoa = document.getElementById('modalPessoa');
    
    if (selectConta || selectContaOrigem || selectContaDestino) {
        contasBancariasData.forEach(conta => {
            const option = document.createElement('option');
            option.value = conta.id;
            option.textContent = conta.nome_conta;
            if (selectConta) selectConta.appendChild(option.cloneNode(true));
            if (selectContaOrigem) selectContaOrigem.appendChild(option.cloneNode(true));
            if (selectContaDestino) selectContaDestino.appendChild(option.cloneNode(true));
        });
    }
    
    if (selectCategoria) {
        categoriasData.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.nome;
            selectCategoria.appendChild(option);
        });
    }
    
    if (selectCentroCusto) {
        centrosCustosData.forEach(cc => {
            const option = document.createElement('option');
            option.value = cc.id;
            option.textContent = cc.nome;
            selectCentroCusto.appendChild(option);
        });
    }
    
    if (selectPessoa) {
        preencherSelectPessoaPorTipo('colaborador');
    }
}

/**
 * Preenche o select "Responsavel financeiro" (modalPessoa) apenas com pessoas do tipo informado.
 * Tipos: colaborador, cliente (associado) e fornecedor.
 */
function preencherSelectPessoaPorTipo(tipoPessoa) {
    const selectPessoa = document.getElementById('modalPessoa');
    if (!selectPessoa) return;
    const valorAtual = selectPessoa.value;
    selectPessoa.innerHTML = '<option value="">Selecione...</option>';
    const filtradas = pessoasData.filter(p => (p.tipo || '').toLowerCase() === (tipoPessoa || '').toLowerCase());
    filtradas.forEach(p => {
        const option = document.createElement('option');
        option.value = p.id;
        option.textContent = p.nome;
        selectPessoa.appendChild(option);
    });
    if (valorAtual && filtradas.some(p => String(p.id) === String(valorAtual))) {
        selectPessoa.value = valorAtual;
    }
}

function configurarEventosModal() {
    const botoesTipo = document.querySelectorAll('.btn-tipo-lancamento');
    botoesTipo.forEach(btn => {
        btn.addEventListener('click', function() {
            botoesTipo.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const tipo = this.dataset.tipo;
            document.getElementById('modalTipoSemantico').value = tipo;
            
            const grupoPessoa = document.getElementById('grupoPessoa');
            const grupoCategoriaValor = document.getElementById('grupoCategoriaValor');
            const grupoCondicao = document.getElementById('grupoCondicao');
            const grupoConta = document.getElementById('grupoConta');
            const grupoTransferencia = document.getElementById('grupoTransferencia');
            
            if (tipo === 'transferencia') {
                if (grupoPessoa) grupoPessoa.style.display = 'none';
                if (grupoCategoriaValor) grupoCategoriaValor.style.display = 'none';
                if (grupoCondicao) grupoCondicao.style.display = 'none';
                if (grupoConta) grupoConta.style.display = 'none';
                if (grupoTransferencia) grupoTransferencia.style.display = 'grid';
                document.getElementById('tabelaParcelasContainer').style.display = 'none';
            } else {
                if (grupoPessoa) grupoPessoa.style.display = 'block';
                if (grupoCategoriaValor) grupoCategoriaValor.style.display = 'grid';
                if (grupoCondicao) grupoCondicao.style.display = 'grid';
                if (grupoConta) grupoConta.style.display = 'block';
                if (grupoTransferencia) grupoTransferencia.style.display = 'none';
                gerarTabelaParcelas();
            }
            
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    });
    
    // Botoes tipo de pessoa (Colaborador / Associado / Fornecedor): alternar active e filtrar select
    const botoesTipoPessoa = document.querySelectorAll('.btn-tipo-pessoa');
    botoesTipoPessoa.forEach(btn => {
        btn.addEventListener('click', function() {
            const tipo = (this.getAttribute('data-tipo-pessoa') || '').toLowerCase();
            botoesTipoPessoa.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            preencherSelectPessoaPorTipo(tipo);
        });
    });
    
    const btnModoSimples = document.getElementById('btnModoSimples');
    const btnModoCompleto = document.getElementById('btnModoCompleto');
    const colunaDireita = document.getElementById('colunaDireita');
    const formContainer = document.getElementById('modalFormContainer');
    
    if (btnModoSimples && btnModoCompleto) {
        btnModoSimples.addEventListener('click', function() {
            btnModoSimples.classList.add('active');
            btnModoCompleto.classList.remove('active');
            document.getElementById('modalModo').value = 'simples';
            if (colunaDireita) colunaDireita.style.display = 'none';
            if (formContainer) formContainer.classList.remove('completo');
            if (formContainer) formContainer.classList.add('simples');
        });
        
        btnModoCompleto.addEventListener('click', function() {
            btnModoCompleto.classList.add('active');
            btnModoSimples.classList.remove('active');
            document.getElementById('modalModo').value = 'completo';
            if (colunaDireita) colunaDireita.style.display = 'flex';
            if (formContainer) formContainer.classList.remove('simples');
            if (formContainer) formContainer.classList.add('completo');
        });
    }
    
    const condicao = document.getElementById('modalCondicao');
    const qtdMeses = document.getElementById('modalQtdParcelas');
    
    if (condicao && qtdMeses) {
        condicao.addEventListener('change', function() {
            if (this.value === 'unica') {
                qtdMeses.value = 1;
                qtdMeses.disabled = true;
                document.getElementById('tabelaParcelasContainer').style.display = 'none';
            } else {
                qtdMeses.disabled = false;
                gerarTabelaParcelas();
            }
        });
        
        qtdMeses.addEventListener('change', gerarTabelaParcelas);
        
        const dataVencimento = document.getElementById('modalDataVencimento');
        if (dataVencimento) {
            dataVencimento.addEventListener('change', gerarTabelaParcelas);
        }
        
        const valor = document.getElementById('modalValor');
        if (valor) {
            valor.addEventListener('change', gerarTabelaParcelas);
        }
    }
    
    const sectionHeaders = document.querySelectorAll('.modal-section-header');
    sectionHeaders.forEach(header => {
        const section = header.closest('.modal-section');
        if (section && !section.classList.contains('always-visible') && section.style.display !== 'none') {
            const content = section.querySelector('.modal-section-content');
            if (content && !content.classList.contains('expanded')) {
                content.classList.add('expanded');
                header.classList.add('active');
                const icon = header.querySelector('i[data-lucide="chevron-down"]');
                if (icon) icon.style.transform = 'rotate(180deg)';
            }
            
            header.addEventListener('click', function() {
                const content = section.querySelector('.modal-section-content');
                const icon = header.querySelector('i[data-lucide="chevron-down"]');
                
                if (content && content.classList.contains('expanded')) {
                    content.classList.remove('expanded');
                    header.classList.remove('active');
                    if (icon) icon.style.transform = 'rotate(0deg)';
                } else if (content) {
                    content.classList.add('expanded');
                    header.classList.add('active');
                    if (icon) icon.style.transform = 'rotate(180deg)';
                }
            });
        }
    });
    
}

function preencherCamposEdicao() {
    if (!registroEditando) return;
    
    if (registroEditando.conta_bancaria_id) {
        const select = document.getElementById('modalContaBancaria');
        if (select) select.value = registroEditando.conta_bancaria_id;
    }
    
    if (registroEditando.categoria_id) {
        const select = document.getElementById('modalCategoria');
        if (select) {
            select.value = registroEditando.categoria_id;
            const input = document.getElementById('modalCategoriaInput');
            if (input) {
                const option = select.options[select.selectedIndex];
                if (option) input.value = option.text;
            }
        }
    }
    
    if (registroEditando.centro_custo_id) {
        const select = document.getElementById('modalCentroCusto');
        if (select) select.value = registroEditando.centro_custo_id;
    }
    
    if (registroEditando.pessoa_id || registroEditando.pessoa_tipo) {
        const tipoPessoa = (registroEditando.pessoa_tipo || 'colaborador').toLowerCase();
        const botoesTipoPessoa = document.querySelectorAll('.btn-tipo-pessoa');
        botoesTipoPessoa.forEach(b => {
            b.classList.toggle('active', (b.getAttribute('data-tipo-pessoa') || '').toLowerCase() === tipoPessoa);
        });
        preencherSelectPessoaPorTipo(tipoPessoa);
        const select = document.getElementById('modalPessoa');
        if (select && registroEditando.pessoa_id) {
            select.value = String(registroEditando.pessoa_id);
        }
    }
    
    if (registroEditando.numero_documento) {
        const input = document.getElementById('modalNumeroDocumento');
        if (input) input.value = registroEditando.numero_documento;
    }
    
    if (registroEditando.forma_pagamento) {
        const select = document.getElementById('modalFormaPagamento');
        if (select) select.value = registroEditando.forma_pagamento;
    }
    
    setTimeout(() => {
        gerarTabelaParcelas();
    }, 100);
}

function gerarTabelaParcelas() {
    const condicao = document.getElementById('modalCondicao')?.value;
    if (!condicao || condicao === 'unica') {
        const container = document.getElementById('tabelaParcelasContainer');
        if (container) container.style.display = 'none';
        return;
    }
    
    const valorTotal = parseFloat(document.getElementById('modalValor')?.value || 0);
    const qtdMeses = parseInt(document.getElementById('modalQtdParcelas')?.value || 1);
    const dataVencimento = document.getElementById('modalDataVencimento')?.value;
    const competencia = document.getElementById('modalCompetencia')?.value || '';
    
    if (!dataVencimento || valorTotal <= 0 || qtdMeses < 1) {
        const container = document.getElementById('tabelaParcelasContainer');
        if (container) container.style.display = 'none';
        return;
    }
    
    const valorParcela = valorTotal / qtdMeses;
    const valorParcelaBase = Math.floor(valorParcela * 100) / 100;
    const diferenca = Math.round((valorTotal - (valorParcelaBase * qtdMeses)) * 100) / 100;
    
    const incrementos = {
        'mensal': 1,
        'quinzenal': 0.5,
        'semanal': 0.25,
        'bimestral': 2,
        'trimestral': 3,
        'semestral': 6,
        'anual': 12
    };
    
    const meses = incrementos[condicao] || 1;
    const data = new Date(dataVencimento + 'T00:00:00');
    
    const tbody = document.getElementById('tbodyParcelas');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    for (let i = 1; i <= qtdMeses; i++) {
        const valorFinal = i === 1 ? valorParcelaBase + diferenca : valorParcelaBase;
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${i}/${qtdMeses}</td>
            <td>${data.toLocaleDateString('pt-BR')}</td>
            <td>${competencia || ''}</td>
            <td>${formatarMoeda(valorFinal)}</td>
        `;
        tbody.appendChild(tr);
        
        const novaData = new Date(data);
        if (meses < 1) {
            novaData.setDate(novaData.getDate() + (meses * 30));
        } else {
            novaData.setMonth(novaData.getMonth() + meses);
        }
        data.setTime(novaData.getTime());
    }
    
    const container = document.getElementById('tabelaParcelasContainer');
    if (container) container.style.display = 'block';
}

async function salvarLancamento() {
    const tipoSemantico = document.getElementById('modalTipoSemantico')?.value;
    const id = document.getElementById('modalLancamentoId')?.value;
    const titulo = document.getElementById('modalTitulo')?.value;
    const descricao = document.getElementById('modalDescricao')?.value;
    const valor = document.getElementById('modalValor')?.value;
    const dataVencimento = document.getElementById('modalDataVencimento')?.value;
    const status = document.getElementById('modalStatus')?.value || 'aberto';
    
    if (!tipoSemantico || !titulo || !valor || !dataVencimento) {
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Preencha todos os campos obrigatórios');
        }
        return false;
    }
    
    const dados = {
        action: id ? 'atualizar' : 'criar',
        tipo_semantico: tipoSemantico,
        titulo: titulo,
        descricao: descricao || titulo,
        valor_total: parseFloat(valor),
        data_vencimento: dataVencimento,
        data_emissao: new Date().toISOString().split('T')[0],
        status: status
    };
    
    if (id) {
        dados.id = parseInt(id);
    }
    
    if (tipoSemantico === 'transferencia') {
        const contaOrigem = document.getElementById('modalContaOrigem')?.value;
        const contaDestino = document.getElementById('modalContaDestino')?.value;
        
        if (!contaOrigem || !contaDestino) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.error('Erro', 'Selecione conta origem e destino');
            }
            return false;
        }
        
        dados.conta_origem_id = parseInt(contaOrigem);
        dados.conta_destino_id = parseInt(contaDestino);
        dados.criar_registro_transferencia = true;
    } else {
        const contaBancaria = document.getElementById('modalContaBancaria')?.value;
        const categoria = document.getElementById('modalCategoria')?.value;
        
        if (!contaBancaria || !categoria) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.error('Erro', 'Selecione conta bancária e categoria');
            }
            return false;
        }
        
        dados.conta_bancaria_id = parseInt(contaBancaria);
        dados.categoria_id = parseInt(categoria);
        
        const centroCusto = document.getElementById('modalCentroCusto')?.value;
        if (centroCusto) dados.centro_custo_id = parseInt(centroCusto);
        
        const pessoa = document.getElementById('modalPessoa')?.value;
        if (pessoa) dados.pessoa_id = parseInt(pessoa);
        
        const numeroDocumento = document.getElementById('modalNumeroDocumento')?.value;
        if (numeroDocumento) dados.numero_documento = numeroDocumento;
        
        const formaPagamento = document.getElementById('modalFormaPagamento')?.value;
        if (formaPagamento) dados.forma_pagamento = formaPagamento;
        
        const competencia = document.getElementById('modalCompetencia')?.value;
        if (competencia) dados.competencia = competencia;
        
        const condicao = document.getElementById('modalCondicao')?.value;
        if (condicao && condicao !== 'unica') {
            dados.condicao = condicao;
            dados.qtd_parcelas = parseInt(document.getElementById('modalQtdParcelas')?.value || 1);
        }
    }
    
    try {
        const url = getApiUrl('financeiro/lancamentos.php');
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dados),
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            await carregarLancamentos();
            return true;
        } else {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.error('Erro', result.message || 'Erro ao salvar lançamento');
            }
            return false;
        }
    } catch (error) {
        console.error('Erro ao salvar lançamento:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao salvar lançamento: ' + error.message);
        }
        return false;
    }
}

// Estado global idempotente para suportar reexecução via navegação AJAX.
if (typeof window.lancamentosInitState === 'undefined') {
    window.lancamentosInitState = {
        inicializando: false,
        inicializado: false,
        handlers: {
            btnNovoLancamento: null,
            btnFiltrar: null,
            inputBuscar: null,
            checkAll: null
        }
    };
}

var lancamentosInitState = window.lancamentosInitState;
var handlers = lancamentosInitState.handlers;

async function initLancamentos() {
    if (lancamentosInitState.inicializando) {
        return;
    }
    
    if (lancamentosInitState.inicializado) {
        return;
    }
    
    lancamentosInitState.inicializando = true;
    
    setTimeout(async () => {
        const container = document.getElementById('tbodyLancamentos') || document.querySelector('.cadastros-content');
        if (!container) {
            lancamentosInitState.inicializando = false;
            setTimeout(async () => {
                if (document.getElementById('tbodyLancamentos') || document.querySelector('.cadastros-content')) {
                    await initLancamentos();
                }
            }, 200);
            return;
        }

        const btnNovoLancamento = document.getElementById('btnNovoLancamento');
        if (btnNovoLancamento && !handlers.btnNovoLancamento) {
            handlers.btnNovoLancamento = () => {
                registroEditando = null;
                abrirModal();
            };
            btnNovoLancamento.addEventListener('click', handlers.btnNovoLancamento);
        }
        
        const btnFiltrar = document.getElementById('btnFiltrar');
        if (btnFiltrar && !handlers.btnFiltrar) {
            handlers.btnFiltrar = carregarLancamentos;
            btnFiltrar.addEventListener('click', handlers.btnFiltrar);
        }
        
        const inputBuscar = document.getElementById('inputBuscar');
        if (inputBuscar && !handlers.inputBuscar) {
            handlers.inputBuscar = (e) => {
                if (e.key === 'Enter') carregarLancamentos();
            };
            inputBuscar.addEventListener('keypress', handlers.inputBuscar);
        }
        
        const checkAll = document.getElementById('checkAll');
        if (checkAll && !handlers.checkAll) {
            handlers.checkAll = function(e) {
                const checkboxes = document.querySelectorAll('.check-lancamento');
                checkboxes.forEach(cb => {
                    cb.checked = e.target.checked;
                    const id = parseInt(cb.value);
                    if (e.target.checked) {
                        if (!lancamentosSelecionados.includes(id)) {
                            lancamentosSelecionados.push(id);
                        }
                    } else {
                        const index = lancamentosSelecionados.indexOf(id);
                        if (index > -1) {
                            lancamentosSelecionados.splice(index, 1);
                        }
                    }
                });
                atualizarBotaoAcoesMassa();
            };
            checkAll.addEventListener('change', handlers.checkAll);
        }
        
        await carregarDadosAuxiliares();
        await carregarLancamentos();
        
        lancamentosInitState.inicializado = true;
        lancamentosInitState.inicializando = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLancamentos);
} else {
    initLancamentos();
}
