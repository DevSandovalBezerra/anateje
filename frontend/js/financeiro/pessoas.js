if (typeof window.pessoasData === 'undefined') {
    window.pessoasData = [];
    window.registroEditando = null;
}

pessoasData = window.pessoasData;
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

async function carregarPessoas() {
    try {
        const tipo = document.getElementById('selectFiltroTipo')?.value || '';
        const ativo = document.getElementById('selectFiltroAtivo')?.value || '';
        const buscar = document.getElementById('inputBuscar')?.value || '';
        
        const params = new URLSearchParams();
        if (tipo) params.append('tipo', tipo);
        if (ativo) params.append('ativo', ativo);
        if (buscar) params.append('buscar', buscar);
        
        const url = getApiUrl(`financeiro/pessoas.php?action=listar&${params.toString()}`);
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
            pessoasData = result.data || [];
            renderizarTabela();
        } else {
            throw new Error(result.message || 'Erro ao carregar pessoas');
        }
    } catch (error) {
        console.error('Erro ao carregar pessoas:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar pessoas: ' + error.message);
        }
    }
}

function renderizarTabela() {
    const tbody = document.getElementById('tbodyPessoas');
    if (!tbody) return;
    
    if (pessoasData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-secondary-dark-gray">
                    <i data-lucide="users" class="w-8 h-8 mx-auto mb-2"></i>
                    <p>Nenhuma pessoa encontrada</p>
                </td>
            </tr>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }
    
    tbody.innerHTML = pessoasData.map(pessoa => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3">${escapeHtml(pessoa.nome || '')}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full ${classeTipoPessoa(pessoa.tipo)}">
                    ${escapeHtml(labelTipoPessoa(pessoa.tipo))}
                </span>
            </td>
            <td class="px-4 py-3">${formatarDocumento(pessoa.documento || '')}</td>
            <td class="px-4 py-3">${escapeHtml(pessoa.email || '-')}</td>
            <td class="px-4 py-3">${escapeHtml(pessoa.telefone || '-')}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full ${pessoa.ativo == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${pessoa.ativo == 1 ? 'Ativo' : 'Inativo'}
                </span>
            </td>
            <td class="px-4 py-3">
                <div class="flex gap-2">
                    <button onclick="editarPessoa(${pessoa.id})" class="text-green-600 hover:text-green-700" title="Editar">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                    </button>
                    <button onclick="excluirPessoa(${pessoa.id})" class="text-red-500 hover:text-red-600" title="Excluir">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function formatarDocumento(doc) {
    if (!doc) return '-';
    if (doc.length === 11) {
        return doc.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
    if (doc.length === 14) {
        return doc.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    }
    return doc;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function classeTipoPessoa(tipo) {
    if (tipo === 'cliente') return 'bg-blue-100 text-blue-800';
    if (tipo === 'fornecedor') return 'bg-green-100 text-green-800';
    if (tipo === 'colaborador') return 'bg-purple-100 text-purple-800';
    return 'bg-gray-100 text-gray-800';
}

function labelTipoPessoa(tipo) {
    if (tipo === 'cliente') return 'Associado';
    if (tipo === 'fornecedor') return 'Fornecedor';
    if (tipo === 'colaborador') return 'Colaborador';
    if (tipo === 'outro') return 'Outro';
    return tipo || '';
}

async function editarPessoa(id) {
    try {
        const url = getApiUrl(`financeiro/pessoas.php?action=obter&id=${id}`);
        const response = await fetch(url, { credentials: 'include' });
        const result = await response.json();
        
        if (result.success) {
            registroEditando = result.data;
            abrirModal();
        } else {
            throw new Error(result.message || 'Erro ao carregar pessoa');
        }
    } catch (error) {
        console.error('Erro ao carregar pessoa:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao carregar pessoa: ' + error.message);
        }
    }
}

async function excluirPessoa(id) {
    if (typeof SweetAlertConfig === 'undefined') {
        if (!confirm('Tem certeza que deseja excluir esta pessoa?')) return;
    } else {
        const confirmacao = await SweetAlertConfig.confirm(
            'Excluir Pessoa',
            'Tem certeza que deseja excluir esta pessoa? Esta ação não pode ser desfeita.',
            'Excluir',
            'Cancelar'
        );
        if (!confirmacao) return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);
        
        const url = getApiUrl('financeiro/pessoas.php');
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.success('Sucesso', 'Pessoa excluída com sucesso!');
            }
            await carregarPessoas();
        } else {
            throw new Error(result.message || 'Erro ao excluir pessoa');
        }
    } catch (error) {
        console.error('Erro ao excluir pessoa:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao excluir pessoa: ' + error.message);
        }
    }
}

function abrirModal() {
    const pessoa = registroEditando || {};
    
    if (typeof SweetAlertConfig !== 'undefined' && SweetAlertConfig.html) {
        SweetAlertConfig.html({
            title: pessoa.id ? 'Editar Pessoa' : 'Nova Pessoa',
            html: `
                <form id="formPessoa" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nome *</label>
                        <input type="text" id="inputNome" value="${escapeHtml(pessoa.nome || '')}" class="input-primary w-full" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Tipo *</label>
                            <select id="selectTipo" class="input-primary w-full" required>
                                <option value="">Selecione...</option>
                                <option value="cliente" ${pessoa.tipo === 'cliente' ? 'selected' : ''}>Associado</option>
                                <option value="fornecedor" ${pessoa.tipo === 'fornecedor' ? 'selected' : ''}>Fornecedor</option>
                                <option value="colaborador" ${pessoa.tipo === 'colaborador' ? 'selected' : ''}>Colaborador</option>
                                <option value="outro" ${pessoa.tipo === 'outro' ? 'selected' : ''}>Outro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Documento (CPF/CNPJ)</label>
                            <input type="text" id="inputDocumento" value="${escapeHtml(pessoa.documento || '')}" class="input-primary w-full">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <input type="email" id="inputEmail" value="${escapeHtml(pessoa.email || '')}" class="input-primary w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Telefone</label>
                            <input type="text" id="inputTelefone" value="${escapeHtml(pessoa.telefone || '')}" class="input-primary w-full">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Endereço</label>
                        <textarea id="textareaEndereco" class="input-primary w-full" rows="2">${escapeHtml(pessoa.endereco || '')}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Observações</label>
                        <textarea id="textareaObservacoes" class="input-primary w-full" rows="2">${escapeHtml(pessoa.observacoes || '')}</textarea>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="checkAtivo" ${pessoa.ativo != 0 ? 'checked' : ''} class="mr-2">
                            <span>Ativo</span>
                        </label>
                    </div>
                </form>
            `,
            confirmButtonText: pessoa.id ? 'Atualizar' : 'Criar',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                await salvarPessoa(pessoa.id);
            }
        });
    }
}

async function salvarPessoa(id) {
    try {
        const formData = new FormData();
        formData.append('action', id ? 'atualizar' : 'criar');
        if (id) formData.append('id', id);
        formData.append('nome', document.getElementById('inputNome').value);
        formData.append('tipo', document.getElementById('selectTipo').value);
        formData.append('documento', document.getElementById('inputDocumento').value);
        formData.append('email', document.getElementById('inputEmail').value);
        formData.append('telefone', document.getElementById('inputTelefone').value);
        formData.append('endereco', document.getElementById('textareaEndereco').value);
        formData.append('observacoes', document.getElementById('textareaObservacoes').value);
        formData.append('ativo', document.getElementById('checkAtivo').checked ? '1' : '0');
        
        const url = getApiUrl('financeiro/pessoas.php');
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            if (typeof SweetAlertConfig !== 'undefined') {
                SweetAlertConfig.success('Sucesso', id ? 'Pessoa atualizada com sucesso!' : 'Pessoa criada com sucesso!');
            }
            registroEditando = null;
            await carregarPessoas();
        } else {
            throw new Error(result.message || 'Erro ao salvar pessoa');
        }
    } catch (error) {
        console.error('Erro ao salvar pessoa:', error);
        if (typeof SweetAlertConfig !== 'undefined') {
            SweetAlertConfig.error('Erro', 'Erro ao salvar pessoa: ' + error.message);
        }
    }
}

let inicializandoPessoas = false;
let pessoasInicializado = false;
const handlersPessoas = {
    btnNovaPessoa: null,
    btnFiltrar: null,
    inputBuscar: null
};

async function initPessoas() {
    if (inicializandoPessoas) return;
    
    inicializandoPessoas = true;
    
    setTimeout(async () => {
        const container = document.getElementById('tbodyPessoas') || document.querySelector('.cadastros-content');
        if (!container) {
            inicializandoPessoas = false;
            setTimeout(async () => {
                if (document.getElementById('tbodyPessoas') || document.querySelector('.cadastros-content')) {
                    await initPessoas();
                }
            }, 200);
            return;
        }

        const btnNovaPessoa = document.getElementById('btnNovaPessoa');
        if (btnNovaPessoa) {
            if (handlersPessoas.btnNovaPessoa) {
                btnNovaPessoa.removeEventListener('click', handlersPessoas.btnNovaPessoa);
            }
            handlersPessoas.btnNovaPessoa = () => {
                registroEditando = null;
                abrirModal();
            };
            btnNovaPessoa.addEventListener('click', handlersPessoas.btnNovaPessoa);
        }
        
        const btnFiltrar = document.getElementById('btnFiltrar');
        if (btnFiltrar) {
            if (handlersPessoas.btnFiltrar) {
                btnFiltrar.removeEventListener('click', handlersPessoas.btnFiltrar);
            }
            handlersPessoas.btnFiltrar = carregarPessoas;
            btnFiltrar.addEventListener('click', handlersPessoas.btnFiltrar);
        }
        
        const inputBuscar = document.getElementById('inputBuscar');
        if (inputBuscar) {
            if (handlersPessoas.inputBuscar) {
                inputBuscar.removeEventListener('keypress', handlersPessoas.inputBuscar);
            }
            handlersPessoas.inputBuscar = (e) => {
                if (e.key === 'Enter') carregarPessoas();
            };
            inputBuscar.addEventListener('keypress', handlersPessoas.inputBuscar);
        }
        
        await carregarPessoas();
        
        pessoasInicializado = true;
        inicializandoPessoas = false;
    }, 100);
}

document.addEventListener('DOMContentLoaded', () => {
    pessoasInicializado = false;
    initPessoas();
});

document.addEventListener('lidergest:page-ready', (event) => {
    if (event.detail && event.detail.page === 'financeiro/pessoas') {
        pessoasInicializado = false;
        initPessoas();
    }
});



