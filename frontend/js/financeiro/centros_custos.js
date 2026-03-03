// Usar window para evitar redeclaração em navegação AJAX
if (typeof window.centrosData === 'undefined') {
    window.centrosData = [];
    window.centroEditando = null;
    window.unidadesData = [];
    window.usuariosData = [];
    window.isAdminGlobal = false;
    window.userUnidadeId = null;
}

// Criar referências locais sem redeclarar (atribuição sem declaração)
centrosData = window.centrosData;
centroEditando = window.centroEditando;
unidadesData = window.unidadesData;
usuariosData = window.usuariosData;
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

async function carregarUsuarios() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('cadastros/usuarios.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/cadastros/usuarios.php?action=listar`
                : `../../api/cadastros/usuarios.php?action=listar`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            usuariosData = result.data || [];
            preencherSelectResponsaveis();
        }
    } catch (error) {
        console.error('Erro ao carregar usuários:', error);
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

function preencherSelectResponsaveis() {
    const select = document.getElementById('selectResponsavel');
    if (!select) return;
    
    select.innerHTML = '<option value="">Nenhum</option>';
    usuariosData.forEach(usuario => {
        if (usuario.ativo == 1) {
            const option = document.createElement('option');
            option.value = usuario.id;
            option.textContent = usuario.nome || usuario.email;
            select.appendChild(option);
        }
    });
}

async function carregarCentros() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/centros_custos.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/centros_custos.php?action=listar`
                : `../../api/financeiro/centros_custos.php?action=listar`);
        
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
            centrosData = result.data || [];
            renderizarTabela();
        } else {
            throw new Error(result.message || 'Erro ao carregar centros de custo');
        }
    } catch (error) {
        console.error('Erro ao carregar centros de custo:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar centros de custo: ' + error.message);
        }
    }
}

function renderizarTabela(filtro = '') {
    const tbody = document.getElementById('tbodyCentros');
    if (!tbody) return;

    let centrosFiltrados = centrosData;
    
    if (filtro) {
        const filtroLower = filtro.toLowerCase();
        centrosFiltrados = centrosData.filter(c => 
            c.nome?.toLowerCase().includes(filtroLower) ||
            c.descricao?.toLowerCase().includes(filtroLower)
        );
    }

    const filtroStatus = document.getElementById('selectFiltroStatus')?.value;
    if (filtroStatus) {
        centrosFiltrados = centrosFiltrados.filter(c => c.status === filtroStatus);
    }

    const filtroUnidade = document.getElementById('selectFiltroUnidade')?.value;
    if (filtroUnidade) {
        centrosFiltrados = centrosFiltrados.filter(c => c.unidade_id == filtroUnidade);
    }

    if (centrosFiltrados.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-secondary-dark-gray">
                    Nenhum centro de custo encontrado
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = centrosFiltrados.map(centro => {
        const status = centro.status === 'ativo' ? 'Ativo' : 'Inativo';
        const statusClass = centro.status === 'ativo' ? 'text-green-600' : 'text-red-600';
        const unidadeNome = centro.unidade_nome || '-';
        const responsavelNome = centro.responsavel_nome || '-';
        const descricao = centro.descricao || '-';

        return `
            <tr>
                <td class="px-4 py-2">
                    <p class="font-medium text-secondary-black">${escapeHtml(centro.nome || '')}</p>
                </td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(descricao)}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(responsavelNome)}</td>
                <td class="px-4 py-2 text-sm text-secondary-dark-gray">${escapeHtml(unidadeNome)}</td>
                <td class="px-4 py-2 text-sm ${statusClass}">${status}</td>
                <td class="px-4 py-2">
                    <div class="flex space-x-2">
                        <button onclick="editarCentro(${centro.id})" class="text-green-600 hover:text-green-700" title="Editar">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        <button onclick="excluirCentro(${centro.id})" class="text-red-500 hover:text-red-600" title="Excluir">
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

async function criarCentro() {
    centroEditando = null;
    if (isAdminGlobal) {
        await Promise.all([carregarUnidades(), carregarUsuarios()]);
    } else {
        await carregarUsuarios();
    }
    abrirModal();
}

async function editarCentro(id) {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl(`financeiro/centros_custos.php?action=obter&id=${id}`)
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/centros_custos.php?action=obter&id=${id}`
                : `../../api/financeiro/centros_custos.php?action=obter&id=${id}`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Erro HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            centroEditando = result.data;
            if (isAdminGlobal) {
                await Promise.all([carregarUnidades(), carregarUsuarios()]);
            } else {
                await carregarUsuarios();
            }
            abrirModal();
        } else {
            throw new Error(result.message || 'Erro ao carregar centro de custo');
        }
    } catch (error) {
        console.error('Erro ao carregar centro de custo:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar centro de custo: ' + error.message);
        }
    }
}

function abrirModal() {
    const modal = document.getElementById('modalCentro');
    if (!modal) {
        criarModal();
        return;
    }

    const form = document.getElementById('formCentro');
    if (form) {
        form.reset();
    }

    setTimeout(() => {
        if (centroEditando) {
            document.getElementById('modalTitulo').textContent = 'Editar Centro de Custo';
            document.getElementById('inputNome').value = centroEditando.nome || '';
            document.getElementById('textareaDescricao').value = centroEditando.descricao || '';
            document.getElementById('selectResponsavel').value = centroEditando.responsavel_id || '';
            document.getElementById('selectStatus').value = centroEditando.status || 'ativo';
            if (isAdminGlobal && document.getElementById('selectUnidade')) {
                document.getElementById('selectUnidade').value = centroEditando.unidade_id || '';
            }
        } else {
            document.getElementById('modalTitulo').textContent = 'Novo Centro de Custo';
        }
    }, 100);

    modal.classList.remove('hidden');
}

function fecharModal() {
    const modal = document.getElementById('modalCentro');
    if (modal) {
        modal.classList.add('hidden');
    }
    centroEditando = null;
}

function criarModal() {
    const modalHTML = `
        <div id="modalCentro" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h2 id="modalTitulo" class="text-xl font-bold text-secondary-black">Novo Centro de Custo</h2>
                    <button onclick="fecharModal()" class="text-secondary-dark-gray hover:text-secondary-black">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <form id="formCentro" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Nome *</label>
                        <input type="text" id="inputNome" required class="input-primary w-full" placeholder="Ex: Marketing, Operações">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Descrição</label>
                        <textarea id="textareaDescricao" rows="3" class="input-primary w-full" placeholder="Descrição do centro de custo"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        ${isAdminGlobal ? `
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Unidade *</label>
                            <select id="selectUnidade" required class="input-primary w-full">
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                        ` : ''}
                        <div>
                            <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Responsável</label>
                            <select id="selectResponsavel" class="input-primary w-full">
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                        <select id="selectStatus" class="input-primary w-full">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
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
    
    document.getElementById('formCentro').addEventListener('submit', async (e) => {
        e.preventDefault();
        await salvarCentro();
    });
}

async function salvarCentro() {
    const nome = document.getElementById('inputNome').value.trim();
    const descricao = document.getElementById('textareaDescricao').value.trim();
    const responsavelId = document.getElementById('selectResponsavel').value;
    const status = document.getElementById('selectStatus').value;
    const unidadeId = isAdminGlobal ? document.getElementById('selectUnidade')?.value : null;

    if (!nome) {
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.warning('Atenção', 'Nome é obrigatório');
        }
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', centroEditando ? 'atualizar' : 'criar');
        if (centroEditando) {
            formData.append('id', centroEditando.id);
        }
        formData.append('nome', nome);
        if (descricao) formData.append('descricao', descricao);
        if (responsavelId) formData.append('responsavel_id', responsavelId);
        formData.append('status', status);
        if (isAdminGlobal && unidadeId) {
            formData.append('unidade_id', unidadeId);
        }

        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/centros_custos.php')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/centros_custos.php`
                : `../../api/financeiro/centros_custos.php`);

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
                SweetAlertConfig.success('Sucesso', result.message || 'Centro de custo salvo com sucesso');
            }
            fecharModal();
            await carregarCentros();
        } else {
            throw new Error(result.message || 'Erro ao salvar centro de custo');
        }
    } catch (error) {
        console.error('Erro ao salvar centro de custo:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao salvar centro de custo: ' + error.message);
        }
    }
}

async function excluirCentro(id) {
    try {
        const confirmacao = await SweetAlertConfig.confirm(
            'Confirmar Exclusão',
            'Tem certeza que deseja excluir este centro de custo? Esta ação não pode ser desfeita.',
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
            ? getApiUrl('financeiro/centros_custos.php')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/centros_custos.php`
                : `../../api/financeiro/centros_custos.php`);

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
                SweetAlertConfig.success('Sucesso', result.message || 'Centro de custo excluído com sucesso');
            }
            await carregarCentros();
        } else {
            throw new Error(result.message || 'Erro ao excluir centro de custo');
        }
    } catch (error) {
        console.error('Erro ao excluir centro de custo:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao excluir centro de custo: ' + error.message);
        }
    }
}

let inicializandoCentrosCustos = false;
let centrosCustosInicializado = false;
const handlersCentrosCustos = {
    btnNovoCentro: null,
    inputBuscar: null,
    selectFiltroStatus: null,
    selectFiltroUnidade: null
};

async function initCentrosCustos() {
    if (inicializandoCentrosCustos) return;
    if (centrosCustosInicializado) return;
    
    inicializandoCentrosCustos = true;
    
    setTimeout(async () => {
        const tbody = document.getElementById('tbodyCentros');
        if (!tbody) {
            inicializandoCentrosCustos = false;
            setTimeout(async () => {
                if (document.getElementById('tbodyCentros')) {
                    await initCentrosCustos();
                }
            }, 200);
            return;
        }

        inicializarContexto();
        if (isAdminGlobal) {
            await carregarUnidades();
        }
        await carregarCentros();
        
        const btnNovoCentro = document.getElementById('btnNovoCentro');
        if (btnNovoCentro && !handlersCentrosCustos.btnNovoCentro) {
            handlersCentrosCustos.btnNovoCentro = criarCentro;
            btnNovoCentro.addEventListener('click', handlersCentrosCustos.btnNovoCentro);
        }

        const inputBuscar = document.getElementById('inputBuscar');
        if (inputBuscar && !handlersCentrosCustos.inputBuscar) {
            handlersCentrosCustos.inputBuscar = (e) => {
                renderizarTabela(e.target.value);
            };
            inputBuscar.addEventListener('input', handlersCentrosCustos.inputBuscar);
        }

        const selectFiltroStatus = document.getElementById('selectFiltroStatus');
        if (selectFiltroStatus && !handlersCentrosCustos.selectFiltroStatus) {
            handlersCentrosCustos.selectFiltroStatus = () => {
                const filtro = inputBuscar?.value || '';
                renderizarTabela(filtro);
            };
            selectFiltroStatus.addEventListener('change', handlersCentrosCustos.selectFiltroStatus);
        }

        const selectFiltroUnidade = document.getElementById('selectFiltroUnidade');
        if (selectFiltroUnidade && !handlersCentrosCustos.selectFiltroUnidade) {
            handlersCentrosCustos.selectFiltroUnidade = () => {
                const filtro = inputBuscar?.value || '';
                renderizarTabela(filtro);
            };
            selectFiltroUnidade.addEventListener('change', handlersCentrosCustos.selectFiltroUnidade);
        }
        
        centrosCustosInicializado = true;
        inicializandoCentrosCustos = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCentrosCustos);
} else {
    initCentrosCustos();
}

