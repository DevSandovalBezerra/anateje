// Usar window para evitar redeclaração em navegação AJAX
if (typeof window.categoriasData === 'undefined') {
    window.categoriasData = [];
    window.categoriaEditando = null;
    window.unidadesData = [];
    window.isAdminGlobal = false;
    window.userUnidadeId = null;
}

// Criar referências locais sem redeclarar (atribuição sem declaração)
categoriasData = window.categoriasData;
categoriaEditando = window.categoriaEditando;
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
}

async function carregarCategorias() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/categorias_financeiras.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/categorias_financeiras.php?action=listar`
                : `../../api/financeiro/categorias_financeiras.php?action=listar`);
        
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
            categoriasData = result.data || [];
            renderizarTabela();
        } else {
            throw new Error(result.message || 'Erro ao carregar categorias');
        }
    } catch (error) {
        console.error('Erro ao carregar categorias:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar categorias: ' + error.message);
        }
    }
}

function renderizarTabela(filtro = '') {
    const tbody = document.getElementById('tbodyCategorias');
    if (!tbody) return;

    let categoriasFiltradas = categoriasData;
    
    if (filtro) {
        const filtroLower = filtro.toLowerCase();
        categoriasFiltradas = categoriasData.filter(c => 
            c.nome?.toLowerCase().includes(filtroLower) ||
            c.codigo?.toLowerCase().includes(filtroLower)
        );
    }

    const filtroTipo = document.getElementById('selectFiltroTipo')?.value;
    if (filtroTipo) {
        categoriasFiltradas = categoriasFiltradas.filter(c => c.tipo === filtroTipo);
    }

    const filtroAtivo = document.getElementById('selectFiltroAtivo')?.value;
    if (filtroAtivo !== '') {
        categoriasFiltradas = categoriasFiltradas.filter(c => c.ativo == filtroAtivo);
    }

    if (categoriasFiltradas.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-secondary-dark-gray">
                    Nenhuma categoria encontrada
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = categoriasFiltradas.map(categoria => {
        const tipoLabel = categoria.tipo === 'receita' ? 'Receita' : 'Despesa';
        const tipoClass = categoria.tipo === 'receita' ? 'text-green-600' : 'text-red-600';
        const status = categoria.ativo == 1 ? 'Ativa' : 'Inativa';
        const statusClass = categoria.ativo == 1 ? 'text-green-600' : 'text-red-600';
        const unidadeNome = categoria.unidade_nome || 'Geral';
        const parentNome = categoria.parent_nome || '-';
        const codigo = categoria.codigo || '-';

        return `
            <tr>
                <td class="px-4 py-2">
                    <p class="font-medium text-secondary-black">${escapeHtml(categoria.nome || '')}</p>
                </td>
                <td class="px-4 py-2 text-sm ${tipoClass} font-medium">${tipoLabel}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(codigo)}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(parentNome)}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(unidadeNome)}</td>
                <td class="px-4 py-2 text-sm ${statusClass}">${status}</td>
                <td class="px-4 py-2">
                    <div class="flex space-x-2">
                        <button onclick="editarCategoria(${categoria.id})" class="text-green-600 hover:text-green-700" title="Editar">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        <button onclick="excluirCategoria(${categoria.id})" class="text-red-500 hover:text-red-600" title="Excluir">
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

async function criarCategoria() {
    categoriaEditando = null;
    await carregarCategoriasParaSelect();
    abrirModal();
}

async function carregarCategoriasParaSelect() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/categorias_financeiras.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/categorias_financeiras.php?action=listar`
                : `../../api/financeiro/categorias_financeiras.php?action=listar`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) return;
        
        const result = await response.json();
        if (result.success) {
            const select = document.getElementById('selectParentCategoria');
            if (select) {
                select.innerHTML = '<option value="">Nenhuma (Categoria Principal)</option>';
                (result.data || []).forEach(cat => {
                    if (cat.id !== categoriaEditando?.id && cat.ativo == 1) {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.nome;
                        select.appendChild(option);
                    }
                });
            }
        }
    } catch (error) {
        console.error('Erro ao carregar categorias para select:', error);
    }
}

async function editarCategoria(id) {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl(`financeiro/categorias_financeiras.php?action=obter&id=${id}`)
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/categorias_financeiras.php?action=obter&id=${id}`
                : `../../api/financeiro/categorias_financeiras.php?action=obter&id=${id}`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            categoriaEditando = result.data;
            await carregarCategoriasParaSelect();
            abrirModal();
        } else {
            throw new Error(result.message || 'Erro ao carregar categoria');
        }
    } catch (error) {
        console.error('Erro ao carregar categoria:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar categoria: ' + error.message);
        }
    }
}

function abrirModal() {
    const modal = document.getElementById('modalCategoria');
    if (!modal) {
        criarModal();
        return;
    }

    const form = document.getElementById('formCategoria');
    if (form) {
        form.reset();
    }

    setTimeout(() => {
        if (categoriaEditando) {
            document.getElementById('modalTitulo').textContent = 'Editar Categoria';
            document.getElementById('inputNome').value = categoriaEditando.nome || '';
            document.getElementById('selectTipo').value = categoriaEditando.tipo || 'despesa';
            document.getElementById('inputCodigo').value = categoriaEditando.codigo || '';
            document.getElementById('textareaDescricao').value = categoriaEditando.descricao || '';
            document.getElementById('selectParentCategoria').value = categoriaEditando.parent_id || '';
            document.getElementById('selectAtivo').value = categoriaEditando.ativo == 1 ? '1' : '0';
        } else {
            document.getElementById('modalTitulo').textContent = 'Nova Categoria';
        }
    }, 100);

    modal.classList.remove('hidden');
}

function fecharModal() {
    const modal = document.getElementById('modalCategoria');
    if (modal) {
        modal.classList.add('hidden');
    }
    categoriaEditando = null;
}

function criarModal() {
    const modalHTML = `
        <div id="modalCategoria" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h2 id="modalTitulo" class="text-xl font-bold text-secondary-black">Nova Categoria</h2>
                    <button onclick="fecharModal()" class="text-secondary-dark-gray hover:text-secondary-black">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <form id="formCategoria" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Nome *</label>
                        <input type="text" id="inputNome" required class="input-primary w-full" placeholder="Ex: Salários, Mensalidades">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Tipo *</label>
                            <select id="selectTipo" required class="input-primary w-full">
                                <option value="receita">Receita</option>
                                <option value="despesa">Despesa</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Código</label>
                            <input type="text" id="inputCodigo" class="input-primary w-full" placeholder="Ex: 1.1.1">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Categoria Pai</label>
                        <select id="selectParentCategoria" class="input-primary w-full">
                            <option value="">Carregando...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Descrição</label>
                        <textarea id="textareaDescricao" rows="3" class="input-primary w-full" placeholder="Descrição da categoria"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                        <select id="selectAtivo" class="input-primary w-full">
                            <option value="1">Ativa</option>
                            <option value="0">Inativa</option>
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
    
    document.getElementById('formCategoria').addEventListener('submit', async (e) => {
        e.preventDefault();
        await salvarCategoria();
    });
}

async function salvarCategoria() {
    const nome = document.getElementById('inputNome').value.trim();
    const tipo = document.getElementById('selectTipo').value;
    const codigo = document.getElementById('inputCodigo').value.trim();
    const descricao = document.getElementById('textareaDescricao').value.trim();
    const parentId = document.getElementById('selectParentCategoria').value;
    const ativo = document.getElementById('selectAtivo').value;

    if (!nome || !tipo) {
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.warning('Atenção', 'Nome e tipo são obrigatórios');
        }
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', categoriaEditando ? 'atualizar' : 'criar');
        if (categoriaEditando) {
            formData.append('id', categoriaEditando.id);
        }
        formData.append('nome', nome);
        formData.append('tipo', tipo);
        if (codigo) formData.append('codigo', codigo);
        if (descricao) formData.append('descricao', descricao);
        if (parentId) formData.append('parent_id', parentId);
        formData.append('ativo', ativo);

        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/categorias_financeiras.php')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/categorias_financeiras.php`
                : `../../api/financeiro/categorias_financeiras.php`);

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
                SweetAlertConfig.success('Sucesso', result.message || 'Categoria salva com sucesso');
            }
            fecharModal();
            await carregarCategorias();
        } else {
            throw new Error(result.message || 'Erro ao salvar categoria');
        }
    } catch (error) {
        console.error('Erro ao salvar categoria:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao salvar categoria: ' + error.message);
        }
    }
}

async function excluirCategoria(id) {
    try {
        const confirmacao = await SweetAlertConfig.confirm(
            'Confirmar Exclusão',
            'Tem certeza que deseja excluir esta categoria? Esta ação não pode ser desfeita.',
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
            ? getApiUrl('financeiro/categorias_financeiras.php')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/categorias_financeiras.php`
                : `../../api/financeiro/categorias_financeiras.php`);

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
                SweetAlertConfig.success('Sucesso', result.message || 'Categoria excluída com sucesso');
            }
            await carregarCategorias();
        } else {
            throw new Error(result.message || 'Erro ao excluir categoria');
        }
    } catch (error) {
        console.error('Erro ao excluir categoria:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao excluir categoria: ' + error.message);
        }
    }
}

let inicializandoCategoriasFinanceiras = false;
let categoriasFinanceirasInicializado = false;
const handlersCategoriasFinanceiras = {
    btnNovaCategoria: null,
    inputBuscar: null,
    selectFiltroTipo: null,
    selectFiltroAtivo: null
};

async function initCategoriasFinanceiras() {
    if (inicializandoCategoriasFinanceiras) return;
    if (categoriasFinanceirasInicializado) return;
    
    inicializandoCategoriasFinanceiras = true;
    
    setTimeout(async () => {
        const tbody = document.getElementById('tbodyCategorias');
        if (!tbody) {
            inicializandoCategoriasFinanceiras = false;
            setTimeout(async () => {
                if (document.getElementById('tbodyCategorias')) {
                    await initCategoriasFinanceiras();
                }
            }, 200);
            return;
        }

        inicializarContexto();
        await carregarCategorias();
        
        const btnNovaCategoria = document.getElementById('btnNovaCategoria');
        if (btnNovaCategoria && !handlersCategoriasFinanceiras.btnNovaCategoria) {
            handlersCategoriasFinanceiras.btnNovaCategoria = criarCategoria;
            btnNovaCategoria.addEventListener('click', handlersCategoriasFinanceiras.btnNovaCategoria);
        }

        const inputBuscar = document.getElementById('inputBuscar');
        if (inputBuscar && !handlersCategoriasFinanceiras.inputBuscar) {
            handlersCategoriasFinanceiras.inputBuscar = (e) => {
                renderizarTabela(e.target.value);
            };
            inputBuscar.addEventListener('input', handlersCategoriasFinanceiras.inputBuscar);
        }

        const selectFiltroTipo = document.getElementById('selectFiltroTipo');
        if (selectFiltroTipo && !handlersCategoriasFinanceiras.selectFiltroTipo) {
            handlersCategoriasFinanceiras.selectFiltroTipo = () => {
                const filtro = inputBuscar?.value || '';
                renderizarTabela(filtro);
            };
            selectFiltroTipo.addEventListener('change', handlersCategoriasFinanceiras.selectFiltroTipo);
        }

        const selectFiltroAtivo = document.getElementById('selectFiltroAtivo');
        if (selectFiltroAtivo && !handlersCategoriasFinanceiras.selectFiltroAtivo) {
            handlersCategoriasFinanceiras.selectFiltroAtivo = () => {
                const filtro = inputBuscar?.value || '';
                renderizarTabela(filtro);
            };
            selectFiltroAtivo.addEventListener('change', handlersCategoriasFinanceiras.selectFiltroAtivo);
        }
        
        categoriasFinanceirasInicializado = true;
        inicializandoCategoriasFinanceiras = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCategoriasFinanceiras);
} else {
    initCategoriasFinanceiras();
}

