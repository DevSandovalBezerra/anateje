(function () {
if (typeof window.pastasAssociadosExplorerState === 'undefined') {
    window.pastasAssociadosExplorerState = {
        unidadeId: null,
        rootId: null,
        tree: [],
        currentFolder: null,
        currentItems: { pastas: [], arquivos: [] },
        searchTerm: '',
        dragItem: null,
        clipboard: null,
        fileCountByFolder: {},
        contextTarget: null,
        rootThemeByFolderId: {},
        listenersBound: false
    };
}

const explorerState = window.pastasAssociadosExplorerState;

function getExplorerApiUrl(query = '') {
    const suffix = query ? `?${query}` : '';
    return typeof getApiUrl !== 'undefined'
        ? getApiUrl(`v1/member_folders.php${suffix}`)
        : (typeof apiConfig !== 'undefined'
            ? `${apiConfig.getBaseUrl()}/api/v1/member_folders.php${suffix}`
            : `../../api/v1/member_folders.php${suffix}`);
}

function notify(type, title, message) {
    if (window.anatejeUi) {
        if (type === 'error' && typeof window.anatejeUi.error === 'function') return window.anatejeUi.error(title, message);
        if (type === 'warning' && typeof window.anatejeUi.warning === 'function') return window.anatejeUi.warning(title, message);
        if (type === 'success' && typeof window.anatejeUi.success === 'function') return window.anatejeUi.success(title, message, 2000);
        if (typeof window.anatejeUi.info === 'function') return window.anatejeUi.info(title, message);
    }

    if (typeof SweetAlertConfig !== 'undefined') {
        if (type === 'error') return SweetAlertConfig.error(title, message);
        if (type === 'warning') return SweetAlertConfig.warning(title, message);
        return SweetAlertConfig.success(title, message);
    }
    if (typeof console !== 'undefined') {
        console[type === 'error' ? 'error' : 'log']((title ? title + ': ' : '') + message);
    }
    return Promise.resolve();
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function requestGet(action, params = {}) {
    const query = new URLSearchParams({ action, ...params });
    const response = await fetch(getExplorerApiUrl(query.toString()), { credentials: 'include' });
    const payload = await response.json().catch(() => null);
    if (!response.ok) {
        const msg = payload && payload.error && payload.error.message
            ? payload.error.message
            : `Erro HTTP ${response.status}`;
        throw new Error(msg);
    }

    if (payload && typeof payload === 'object' && payload.ok === true) {
        return { success: true, data: payload.data || {} };
    }
    if (payload && typeof payload === 'object' && payload.success === true) {
        return payload;
    }

    const msg = payload && payload.error && payload.error.message
        ? payload.error.message
        : 'Falha na requisicao';
    throw new Error(msg);
}

async function requestPost(action, data, file = null) {
    const formData = new FormData();
    formData.append('action', action);
    Object.keys(data || {}).forEach((key) => {
        formData.append(key, data[key]);
    });

    if (file) {
        formData.append('arquivo', file);
    }

    const headers = {};
    const csrf = String(window.LIDERGEST_CSRF_TOKEN || '').trim();
    if (csrf) {
        headers['X-CSRF-Token'] = csrf;
    }

    const response = await fetch(getExplorerApiUrl(), {
        method: 'POST',
        credentials: 'include',
        headers,
        body: formData
    });

    const payload = await response.json().catch(() => null);
    if (!response.ok) {
        const msg = payload && payload.error && payload.error.message
            ? payload.error.message
            : `Erro HTTP ${response.status}`;
        throw new Error(msg);
    }

    if (payload && typeof payload === 'object' && payload.ok === true) {
        return { success: true, data: payload.data || {} };
    }
    if (payload && typeof payload === 'object' && payload.success === true) {
        return payload;
    }

    const msg = payload && payload.error && payload.error.message
        ? payload.error.message
        : 'Falha na requisicao';
    throw new Error(msg);
}

async function carregarContadoresArquivos() {
    if (!explorerState.unidadeId) {
        explorerState.fileCountByFolder = {};
        return;
    }

    try {
        const result = await requestGet('contadores_arquivos', { unidade_id: explorerState.unidadeId });
        if (result.success && result.data && result.data.counts) {
            explorerState.fileCountByFolder = result.data.counts;
            return;
        }
    } catch (error) {
        // fallback silencioso
    }

    explorerState.fileCountByFolder = {};
}

function formatBytes(bytes) {
    const value = Number(bytes || 0);
    if (!value) return '-';
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}

function formatDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';
    return date.toLocaleString('pt-BR');
}

function getAppBaseUrl() {
    if (typeof apiConfig !== 'undefined' && typeof apiConfig.getBaseUrl === 'function') {
        return String(apiConfig.getBaseUrl() || '').replace(/\/$/, '');
    }

    if (typeof getApiUrl !== 'undefined') {
        const url = String(getApiUrl('v1/member_folders.php') || '');
        const split = url.split('/api/');
        return (split[0] || '').replace(/\/$/, '');
    }

    return '';
}

function getFileIconByExt(ext) {
    const lower = String(ext || '').toLowerCase();
    switch (lower) {
        case 'pdf':
            return 'pdf.png';
        case 'rar':
        case 'zip':
            // Nao existe rar.png no projeto; usa icone mais proximo disponivel
            return 'xml.png';
        case 'doc':
        case 'docx':
            return 'word.png';
        case 'xls':
        case 'xlsx':
            return 'excel.png';
        case 'xml':
            return 'xml.png';
        default:
            return '';
    }
}

function getFileIconHtml(arquivo, sizeClass) {
    const ext = String(arquivo?.ext || '').toLowerCase();
    const iconFile = getFileIconByExt(ext);
    const classes = sizeClass || 'h-5 w-5';
    if (iconFile) {
        const baseUrl = getAppBaseUrl();
        const iconUrl = baseUrl
            ? `${baseUrl}/assets/images/icones/${iconFile}`
            : `assets/images/icones/${iconFile}`;
        return `<img src="${iconUrl}" alt="${escapeHtml(ext || 'arquivo')}" class="${classes} object-contain rounded-sm" loading="lazy">`;
    }

    if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg'].includes(ext) && arquivo?.download_url) {
        return `<img src="${escapeHtml(String(arquivo.download_url))}" alt="${escapeHtml(ext)}" class="${classes} object-cover rounded-sm" loading="lazy">`;
    }

    return `<i data-lucide="file" class="${classes} text-blue-500"></i>`;
}

function getFolderById(id) {
    return explorerState.tree.find((item) => Number(item.id) === Number(id)) || null;
}

function getFileById(id) {
    return (explorerState.currentItems.arquivos || []).find((item) => Number(item.id) === Number(id)) || null;
}

function canUploadInCurrentFolder() {
    const folderType = String(explorerState.currentFolder?.tipo || '');
    return folderType === 'member' || folderType === 'folder';
}

function buildPath(folderId) {
    const path = [];
    let cursor = getFolderById(folderId);

    while (cursor) {
        path.unshift(cursor);
        cursor = cursor.parent_id !== null ? getFolderById(cursor.parent_id) : null;
    }

    return path;
}

function renderBreadcrumb() {
    const el = document.getElementById('explorerBreadcrumb');
    if (!el) return;

    if (!explorerState.currentFolder) {
        el.textContent = 'Sem pasta selecionada';
        return;
    }

    const parts = buildPath(explorerState.currentFolder.id);
    el.innerHTML = parts
        .map((item) => `<button type="button" class="hover:underline" data-breadcrumb-id="${item.id}">${escapeHtml(item.nome)}</button>`)
        .join(' / ');
}

function renderTree() {
    const container = document.getElementById('explorerTree');
    if (!container) return;

    if (!Array.isArray(explorerState.tree) || explorerState.tree.length === 0) {
        container.innerHTML = '<p class="text-sm text-secondary-dark-gray">Sem pastas cadastradas.</p>';
        return;
    }

    const byParent = {};
    explorerState.tree.forEach((folder) => {
        const key = folder.parent_id === null ? 'root' : String(folder.parent_id);
        if (!byParent[key]) byParent[key] = [];
        byParent[key].push(folder);
    });

    Object.keys(byParent).forEach((key) => {
        byParent[key].sort((a, b) => String(a.nome).localeCompare(String(b.nome), 'pt-BR'));
    });

    const renderNode = (folder, depth) => {
        const children = byParent[String(folder.id)] || [];
        const isCurrent = explorerState.currentFolder && Number(explorerState.currentFolder.id) === Number(folder.id);
        const margin = depth * 14;

        return `
            <div class="space-y-1">
                <div class="group flex items-center justify-between rounded-md px-2 py-1 ${isCurrent ? 'bg-primary-light-blue/20' : 'hover:bg-gray-100'}" style="margin-left:${margin}px" data-tree-folder-id="${folder.id}">
                    <button type="button" class="flex min-w-0 flex-1 items-center gap-2 text-left" data-open-folder="${folder.id}">
                        <i data-lucide="folder" class="h-4 w-4 shrink-0 text-amber-500"></i>
                        <span class="truncate text-sm text-secondary-black">${escapeHtml(folder.nome)}</span>
                    </button>
                </div>
                ${children.map((child) => renderNode(child, depth + 1)).join('')}
            </div>
        `;
    };

    const roots = byParent.root || [];
    container.innerHTML = roots.map((root) => renderNode(root, 0)).join('');

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    bindTreeDropTargets();
}

function getFilteredItems() {
    const term = explorerState.searchTerm.toLowerCase();
    const pastas = explorerState.currentItems.pastas || [];
    const arquivos = explorerState.currentItems.arquivos || [];

    if (!term) {
        return { pastas, arquivos };
    }

    return {
        pastas: pastas.filter((item) => String(item.nome || '').toLowerCase().includes(term)),
        arquivos: arquivos.filter((item) => String(item.nome_exibicao || item.nome_original || '').toLowerCase().includes(term))
    };
}

const ROOT_CARD_THEMES = [
    { bg: '#F8F1C7', text: '#1F2937', accent: '#8A6D1A', iconBg: '#ECDf93', icon: 'folder-open' },
    { bg: '#DDEEFE', text: '#1F2937', accent: '#1F5A9A', iconBg: '#BFDDF9', icon: 'folder' },
    { bg: '#F9DFEA', text: '#1F2937', accent: '#9D3C6A', iconBg: '#F0BDD4', icon: 'archive' },
    { bg: '#DDF4E6', text: '#1F2937', accent: '#2C7A4B', iconBg: '#BFE8CF', icon: 'folders' }
];

function getRootCardTheme(index) {
    return ROOT_CARD_THEMES[index % ROOT_CARD_THEMES.length];
}

function buildRootThemeMap() {
    const rootChildren = explorerState.tree
        .filter((item) => Number(item.parent_id) === Number(explorerState.rootId))
        .sort((a, b) => String(a.nome || '').localeCompare(String(b.nome || ''), 'pt-BR'));

    const map = {};
    rootChildren.forEach((folder, index) => {
        map[Number(folder.id)] = getRootCardTheme(index);
    });

    explorerState.rootThemeByFolderId = map;
}

function getTopRootChildId(folderId) {
    const path = buildPath(folderId);
    if (!Array.isArray(path) || path.length < 2) return null;
    return Number(path[1].id);
}

function getThemeForFolder(folderId, fallbackIndex = 0) {
    const topRootId = getTopRootChildId(folderId);
    if (topRootId !== null && explorerState.rootThemeByFolderId[topRootId]) {
        return explorerState.rootThemeByFolderId[topRootId];
    }

    return getRootCardTheme(fallbackIndex);
}

function applyWorkspaceTheme() {
    const workspace = document.getElementById('explorerWorkspace');
    const dropzone = document.getElementById('dropzoneUpload');
    const search = document.getElementById('inputBuscaItens');
    const tableHead = document.querySelector('#explorerTable thead tr');
    const breadcrumb = document.getElementById('explorerBreadcrumb');
    const btnVoltarPai = document.getElementById('btnVoltarPai');
    const btnIrRaiz = document.getElementById('btnIrRaiz');

    if (!workspace) return;

    const isRoot = explorerState.currentFolder && Number(explorerState.currentFolder.id) === Number(explorerState.rootId);
    if (isRoot) {
        workspace.style.borderColor = '';
        workspace.style.boxShadow = '';
        workspace.style.background = '';
        if (dropzone) {
            dropzone.style.borderColor = '';
            dropzone.style.background = '';
        }
        if (search) {
            search.style.borderColor = '';
            search.style.boxShadow = '';
        }
        if (tableHead) {
            tableHead.style.borderBottomColor = '';
        }
        if (breadcrumb) {
            breadcrumb.style.color = '';
        }
        if (btnVoltarPai) {
            btnVoltarPai.style.borderColor = '';
            btnVoltarPai.style.color = '';
            btnVoltarPai.style.background = '';
        }
        if (btnIrRaiz) {
            btnIrRaiz.style.borderColor = '';
            btnIrRaiz.style.color = '';
            btnIrRaiz.style.background = '';
        }
        return;
    }

    const theme = getThemeForFolder(explorerState.currentFolder?.id, 0);
    const accent = theme.accent || '#64748B';
    const soft = theme.bg || '#F8FAFC';

    workspace.style.borderColor = `${accent}55`;
    workspace.style.boxShadow = `inset 0 4px 0 ${accent}55`;
    workspace.style.background = `linear-gradient(0deg, #ffffff 0%, ${soft}35 100%)`;

    if (dropzone) {
        dropzone.style.borderColor = `${accent}55`;
        dropzone.style.background = `${soft}66`;
    }

    if (search) {
        search.style.borderColor = `${accent}55`;
        search.style.boxShadow = `0 0 0 1px ${accent}22`;
    }

    if (tableHead) {
        tableHead.style.borderBottomColor = `${accent}55`;
    }

    if (breadcrumb) {
        breadcrumb.style.color = accent;
    }

    [btnVoltarPai, btnIrRaiz].forEach((button) => {
        if (!button) return;
        button.style.borderColor = `${accent}55`;
        button.style.color = accent;
        button.style.background = `${soft}99`;
    });
}

function renderNavigationButtons() {
    const btnVoltarPai = document.getElementById('btnVoltarPai');
    const btnIrRaiz = document.getElementById('btnIrRaiz');
    if (!btnVoltarPai || !btnIrRaiz) return;

    const currentId = Number(explorerState.currentFolder?.id || 0);
    const isRoot = currentId > 0 && currentId === Number(explorerState.rootId);
    const hasParent = explorerState.currentFolder && explorerState.currentFolder.parent_id !== null;

    btnVoltarPai.classList.toggle('hidden', !hasParent);
    btnIrRaiz.classList.toggle('hidden', isRoot || !explorerState.rootId);
}

function updateUploadControls() {
    const inputUpload = document.getElementById('inputUploadArquivo');
    const uploadLabel = document.querySelector('label[for="inputUploadArquivo"]');
    const dropzone = document.getElementById('dropzoneUpload');
    const enabled = canUploadInCurrentFolder();

    if (inputUpload) {
        inputUpload.disabled = !enabled;
    }

    if (uploadLabel) {
        uploadLabel.classList.toggle('opacity-50', !enabled);
        uploadLabel.classList.toggle('cursor-not-allowed', !enabled);
        uploadLabel.classList.toggle('pointer-events-none', !enabled);
        uploadLabel.title = enabled ? '' : 'Selecione uma pasta de associado para habilitar upload';
    }

    if (dropzone) {
        if (!dropzone.dataset.defaultText) {
            dropzone.dataset.defaultText = dropzone.textContent || '';
        }
        dropzone.classList.toggle('opacity-60', !enabled);
        dropzone.classList.toggle('cursor-not-allowed', !enabled);
        dropzone.textContent = enabled
            ? dropzone.dataset.defaultText
            : 'Selecione uma pasta de associado para habilitar o upload.';
    }
}

function getChildrenCount(folderId) {
    return explorerState.tree.filter((item) => Number(item.parent_id) === Number(folderId)).length;
}

function formatDateCard(value) {
    if (!value) return 'Sem atualizaÃ§Ã£o';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'Sem atualizaÃ§Ã£o';
    return date.toLocaleDateString('pt-BR');
}

function cardClassForFile(arquivo) {
    const isCut = explorerState.clipboard
        && explorerState.clipboard.mode === 'cut'
        && Number(explorerState.clipboard.itemId) === Number(arquivo.id);

    return isCut
        ? 'opacity-60 ring-1 ring-amber-200 bg-amber-50/30'
        : 'bg-white';
}

function renderCards(pastas, arquivos) {
    const cardsContainer = document.getElementById('explorerRootCards');
    if (!cardsContainer) return;
    const foldersHtml = (pastas || []).map((pasta, index) => {
        const theme = getThemeForFolder(pasta.id, index);
        const title = escapeHtml(pasta.nome);
        const attrs = `draggable="true" data-item-type="pasta" data-item-id="${pasta.id}" data-tree-folder-id="${pasta.id}"`;

        return `
            <div class="rounded-2xl border border-slate-200 p-6 shadow-sm transition hover:shadow-md" style="background:${theme.bg};color:${theme.text};min-height:170px;" ${attrs}>
                <div class="flex items-start justify-between gap-4">
                    <button type="button" class="flex min-w-0 flex-1 items-start gap-4 text-left" data-open-folder="${pasta.id}">
                        <div class="rounded-xl p-3" style="background:${theme.iconBg};">
                            <i data-lucide="${theme.icon}" class="h-6 w-6" style="color:${theme.accent};"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium" style="color:${theme.accent};">Pasta</p>
                            <h3 class="truncate text-2xl sm:text-3xl font-semibold leading-tight tracking-tight">${title}</h3>
                        </div>
                    </button>
                    <i data-lucide="arrow-right" class="h-6 w-6 shrink-0" style="color:${theme.accent};"></i>
                </div>
                <div class="mt-6 flex items-center justify-between border-t border-slate-300/60 pt-3">
                    <div class="flex items-center gap-4">
                        <button type="button" class="text-xs font-medium hover:underline" style="color:${theme.accent};" data-rename-folder="${pasta.id}">Renomear</button>
                        <button type="button" class="text-xs font-medium hover:underline" style="color:#DC2626;" data-delete-folder="${pasta.id}">Excluir</button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    const filesHtml = (arquivos || []).map((arquivo) => {
        const name = escapeHtml(arquivo.nome_exibicao || arquivo.nome_original || '');
        const url = escapeHtml(String(arquivo.download_url || '#'));
        
        const isCut = explorerState.clipboard
            && explorerState.clipboard.mode === 'cut'
            && Number(explorerState.clipboard.itemId) === Number(arquivo.id);
            
        const containerClass = isCut 
            ? 'opacity-60 ring-2 ring-amber-400 bg-amber-50' 
            : 'hover:bg-gray-50';

        const attrs = `draggable="true" data-item-type="arquivo" data-item-id="${arquivo.id}" data-file-item-id="${arquivo.id}"`;
        
        return `
            <div class="group relative flex cursor-pointer flex-col items-center gap-3 rounded-xl p-4 transition duration-200 ${containerClass}" ${attrs} title="${name}">
                <a href="${url}" target="_blank" rel="noopener noreferrer" class="flex w-full flex-col items-center gap-3 text-center">
                    <div class="transition-transform duration-200 group-hover:-translate-y-1 group-hover:scale-105">
                        ${getFileIconHtml(arquivo, 'h-24 w-24 object-contain drop-shadow-sm')}
                    </div>
                    <span class="line-clamp-2 w-full overflow-hidden text-ellipsis text-sm font-medium leading-tight text-gray-700 group-hover:text-primary-blue">
                        ${name}
                    </span>
                </a>
            </div>
        `;
    }).join('');

    let html = '';
    
    if (pastas && pastas.length > 0) {
        html += `
            <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                ${foldersHtml}
            </div>
        `;
    }

    if (arquivos && arquivos.length > 0) {
        // Separador visual se houver pastas antes
        if (pastas && pastas.length > 0) {
            html += '<div class="mb-4 border-t border-gray-100"></div>';
        }
        
        html += `
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                ${filesHtml}
            </div>
        `;
    }

    if (!pastas?.length && !arquivos?.length) {
        html = '<div class="col-span-full rounded-lg border border-dashed border-gray-300 bg-gray-50 p-10 text-center text-sm text-secondary-dark-gray">Pasta vazia.</div>';
    }

    cardsContainer.innerHTML = html;
}

function rowClassForFile(arquivo) {
    const isCut = explorerState.clipboard
        && explorerState.clipboard.mode === 'cut'
        && Number(explorerState.clipboard.itemId) === Number(arquivo.id);

    return isCut ? 'border-b border-gray-100 bg-amber-50/40 opacity-70 hover:bg-amber-50/70' : 'border-b border-gray-100 hover:bg-gray-50';
}

function renderTableRows(pastas, arquivos, includeFolders) {
    const tbody = document.getElementById('tbodyExplorerItens');
    if (!tbody) return;

    let rowsPastas = '';
    if (includeFolders) {
        rowsPastas = pastas.map((pasta) => `
            <tr class="border-b border-gray-100 hover:bg-gray-50" draggable="true" data-item-type="pasta" data-item-id="${pasta.id}">
                <td class="px-3 py-2">
                    <button type="button" class="flex items-center gap-2 text-left" data-open-folder="${pasta.id}">
                        <i data-lucide="folder" class="h-4 w-4 text-amber-500"></i>
                        <span>${escapeHtml(pasta.nome)}</span>
                    </button>
                </td>
                <td class="px-3 py-2">Pasta</td>
                <td class="px-3 py-2">-</td>
                <td class="px-3 py-2">${formatDate(pasta.updated_at)}</td>
                <td class="px-3 py-2">
                    <div class="flex items-center gap-2">
                        <button type="button" class="text-xs text-primary-blue hover:underline" data-rename-folder="${pasta.id}">Renomear</button>
                        <button type="button" class="text-xs text-red-600 hover:underline" data-delete-folder="${pasta.id}">Excluir</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    const rowsArquivos = arquivos.map((arquivo) => `
        <tr class="${rowClassForFile(arquivo)}" draggable="true" data-item-type="arquivo" data-item-id="${arquivo.id}" data-file-item-id="${arquivo.id}">
            <td class="px-3 py-2">
                <a href="${escapeHtml(String(arquivo.download_url || '#'))}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-left text-primary-blue hover:underline">
                    ${getFileIconHtml(arquivo)}
                    <span>${escapeHtml(arquivo.nome_exibicao || arquivo.nome_original)}</span>
                </a>
            </td>
            <td class="px-3 py-2">${escapeHtml(String(arquivo.ext || '-').toUpperCase())}</td>
            <td class="px-3 py-2">${formatBytes(arquivo.tamanho_bytes)}</td>
            <td class="px-3 py-2">${formatDate(arquivo.updated_at)}</td>
            <td class="px-3 py-2">
                <div class="flex items-center gap-2">
                    <button type="button" class="text-xs text-primary-blue hover:underline" data-rename-file="${arquivo.id}">Renomear</button>
                    <button type="button" class="text-xs text-red-600 hover:underline" data-delete-file="${arquivo.id}">Excluir</button>
                </div>
            </td>
        </tr>
    `).join('');

    const html = rowsPastas + rowsArquivos;
    if (html.trim() === '') {
        tbody.innerHTML = '<tr><td colspan="5" class="px-3 py-10 text-center text-secondary-dark-gray">Pasta vazia.</td></tr>';
    } else {
        tbody.innerHTML = html;
    }
}

function renderList() {
    const rootCards = document.getElementById('explorerRootCards');
    const table = document.getElementById('explorerTable');
    if (!rootCards || !table) return;
    const data = getFilteredItems();
    buildRootThemeMap();
    const listWrapper = document.getElementById('explorerList');
    if (listWrapper) listWrapper.classList.add('hidden');
    table.classList.add('hidden');
    const tbody = document.getElementById('tbodyExplorerItens');
    if (tbody) tbody.innerHTML = '';

    rootCards.classList.remove('hidden');
    renderCards(data.pastas, data.arquivos);


    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    renderNavigationButtons();
    updateUploadControls();
    applyWorkspaceTheme();
    bindRowDnD();
    bindTreeDropTargets();
}

async function carregarArvore() {
    const result = await requestGet('listar_arvore', { unidade_id: explorerState.unidadeId || '' });
    if (!result.success) throw new Error(result.message || 'Erro ao carregar arvore');
    explorerState.tree = result.data || [];
    renderTree();
}

async function carregarItens(pastaId) {
    const result = await requestGet('listar_itens', { pasta_id: pastaId });
    if (!result.success) throw new Error(result.message || 'Erro ao carregar itens');
    await carregarContadoresArquivos();

    explorerState.currentFolder = result.data.pasta_atual;
    explorerState.currentItems = {
        pastas: result.data.pastas || [],
        arquivos: result.data.arquivos || []
    };

    renderBreadcrumb();
    renderTree();
    renderList();
}

async function carregarInicial() {
    const result = await requestGet('init');
    if (!result.success) throw new Error(result.message || 'Erro ao inicializar Explorer');

    explorerState.unidadeId = result.data.unidade_id;
    await carregarContadoresArquivos();
    explorerState.rootId = result.data.root.id;
    explorerState.tree = result.data.tree || [];
    explorerState.currentFolder = result.data.itens.pasta_atual;
    explorerState.currentItems = {
        pastas: result.data.itens.pastas || [],
        arquivos: result.data.itens.arquivos || []
    };

    renderBreadcrumb();
    renderTree();
    renderList();
}

async function criarPasta() {
    if (!explorerState.currentFolder) return;

    let nome = null;
    if (window.anatejeUi && typeof window.anatejeUi.promptText === 'function') {
        nome = await window.anatejeUi.promptText({
            title: 'Nova pasta',
            text: 'Nome da nova pasta',
            required: true,
            requiredMessage: 'Informe o nome da pasta.'
        });
    }
    if (!nome || !String(nome).trim()) return;

    const result = await requestPost('criar_pasta', {
        parent_id: explorerState.currentFolder.id,
        nome: String(nome).trim()
    });

    if (!result.success) throw new Error(result.message || 'Falha ao criar pasta');

    await carregarArvore();
    await carregarItens(explorerState.currentFolder.id);
}

async function uploadArquivo(file) {
    if (!file || !explorerState.currentFolder) return;
    if (!canUploadInCurrentFolder()) {
        throw new Error('Upload permitido apenas em pasta de associado');
    }

    const result = await requestPost('upload_arquivo', {
        pasta_id: explorerState.currentFolder.id
    }, file);

    if (!result.success) throw new Error(result.message || 'Falha no upload');

    await carregarItens(explorerState.currentFolder.id);
}

async function renomearPasta(id) {
    const pasta = getFolderById(id);
    const nomeAtual = pasta ? pasta.nome : '';
    let novoNome = null;
    if (window.anatejeUi && typeof window.anatejeUi.promptText === 'function') {
        novoNome = await window.anatejeUi.promptText({
            title: 'Renomear pasta',
            text: 'Novo nome da pasta',
            defaultValue: nomeAtual,
            required: true,
            requiredMessage: 'Informe o novo nome.'
        });
    }
    if (!novoNome || !String(novoNome).trim()) return;

    const result = await requestPost('renomear_pasta', { id, nome: String(novoNome).trim() });
    if (!result.success) throw new Error(result.message || 'Falha ao renomear pasta');

    await carregarArvore();
    await carregarItens(explorerState.currentFolder.id);
}

async function renomearArquivo(id) {
    const arquivo = getFileById(id);
    const nomeAtual = arquivo ? (arquivo.nome_exibicao || arquivo.nome_original) : '';
    let novoNome = null;
    if (window.anatejeUi && typeof window.anatejeUi.promptText === 'function') {
        novoNome = await window.anatejeUi.promptText({
            title: 'Renomear arquivo',
            text: 'Novo nome do arquivo',
            defaultValue: nomeAtual,
            required: true,
            requiredMessage: 'Informe o novo nome.'
        });
    }
    if (!novoNome || !String(novoNome).trim()) return;

    const result = await requestPost('renomear_arquivo', { id, nome: String(novoNome).trim() });
    if (!result.success) throw new Error(result.message || 'Falha ao renomear arquivo');

    await carregarItens(explorerState.currentFolder.id);
}

async function excluirItem(tipo, id) {
    let confirmado = false;
    if (window.anatejeUi && typeof window.anatejeUi.confirmAction === 'function') {
        confirmado = await window.anatejeUi.confirmAction({
            title: 'Confirmar exclusao',
            text: 'Deseja enviar este item para a lixeira?',
            confirmText: 'Excluir',
            cancelText: 'Cancelar',
            icon: 'warning',
            danger: true
        });
    }
    if (!confirmado) return;

    const result = await requestPost('excluir_item', { tipo, id });
    if (!result.success) throw new Error(result.message || 'Falha ao excluir item');

    await carregarArvore();
    await carregarItens(explorerState.currentFolder.id);
}

async function moverItem(tipo, id, destinoId) {
    if (!destinoId || !tipo || !id) return;

    const result = await requestPost('mover_item', {
        tipo,
        id,
        novo_parent_id: destinoId
    });

    if (!result.success) throw new Error(result.message || 'Falha ao mover item');

    await carregarArvore();
    await carregarItens(explorerState.currentFolder.id);
}

function setClipboard(mode, fileId) {
    const file = getFileById(fileId);
    if (!file) return;

    explorerState.clipboard = {
        mode,
        itemType: 'arquivo',
        itemId: Number(fileId),
        sourceFolderId: Number(explorerState.currentFolder?.id || 0),
        label: file.nome_exibicao || file.nome_original
    };

    renderList();
}

async function colarClipboardNaPastaAtual() {
    if (!explorerState.clipboard || !explorerState.currentFolder) {
        throw new Error('Nada para colar');
    }

    if (explorerState.clipboard.itemType !== 'arquivo') {
        throw new Error('Somente arquivos sao suportados no menu de contexto');
    }

    if (explorerState.clipboard.mode === 'copy') {
        const copyResult = await requestPost('copiar_arquivo', {
            id: explorerState.clipboard.itemId,
            pasta_destino_id: explorerState.currentFolder.id
        });
        if (!copyResult.success) throw new Error(copyResult.message || 'Falha ao copiar arquivo');
    } else {
        const moveResult = await requestPost('mover_item', {
            tipo: 'arquivo',
            id: explorerState.clipboard.itemId,
            novo_parent_id: explorerState.currentFolder.id
        });
        if (!moveResult.success) throw new Error(moveResult.message || 'Falha ao recortar/colar arquivo');

        explorerState.clipboard = null;
    }

    await carregarArvore();
    await carregarItens(explorerState.currentFolder.id);
}

function closeContextMenu() {
    const menu = document.getElementById('fileContextMenu');
    if (!menu) return;

    menu.classList.add('hidden');
    explorerState.contextTarget = null;
}

function openContextMenu(itemType, itemId, x, y) {
    const menu = document.getElementById('fileContextMenu');
    if (!menu) return;

    explorerState.contextTarget = {
        type: String(itemType || ''),
        id: Number(itemId || 0)
    };

    const pasteButton = menu.querySelector('[data-context-action="paste"]');
    if (pasteButton) {
        const enabled = !!explorerState.clipboard;
        pasteButton.disabled = !enabled;
        pasteButton.classList.toggle('opacity-40', !enabled);
        pasteButton.classList.toggle('cursor-not-allowed', !enabled);
    }

    const isFile = explorerState.contextTarget.type === 'arquivo';
    const copyButton = menu.querySelector('[data-context-action="copy"]');
    const cutButton = menu.querySelector('[data-context-action="cut"]');
    [copyButton, cutButton].forEach((button) => {
        if (!button) return;
        button.disabled = !isFile;
        button.classList.toggle('opacity-40', !isFile);
        button.classList.toggle('cursor-not-allowed', !isFile);
    });

    menu.classList.remove('hidden');

    const maxX = window.innerWidth - menu.offsetWidth - 8;
    const maxY = window.innerHeight - menu.offsetHeight - 8;
    menu.style.left = `${Math.max(8, Math.min(x, maxX))}px`;
    menu.style.top = `${Math.max(8, Math.min(y, maxY))}px`;
}

async function executarContextAction(action) {
    const target = explorerState.contextTarget;
    const targetId = Number(target?.id || 0);
    const targetType = String(target?.type || '');

    if (action === 'copy') {
        if (!targetId || targetType !== 'arquivo') return;
        setClipboard('copy', targetId);
        return;
    }

    if (action === 'cut') {
        if (!targetId || targetType !== 'arquivo') return;
        setClipboard('cut', targetId);
        return;
    }

    if (action === 'paste') {
        await colarClipboardNaPastaAtual();
        notify('success', 'Sucesso', 'Colado com sucesso');
        return;
    }

    if (action === 'rename') {
        if (!targetId) return;
        if (targetType === 'arquivo') {
            await renomearArquivo(targetId);
            return;
        }
        if (targetType === 'pasta') {
            await renomearPasta(targetId);
        }
        return;
    }

    if (action === 'delete') {
        if (!targetId) return;
        if (targetType !== 'arquivo' && targetType !== 'pasta') return;
        await excluirItem(targetType, targetId);
        return;
    }
}

function bindTreeDropTargets() {
    const targets = document.querySelectorAll('[data-tree-folder-id]');

    targets.forEach((target) => {
        target.addEventListener('dragover', (event) => {
            event.preventDefault();
            target.classList.add('ring-1', 'ring-primary-blue');
        });

        target.addEventListener('dragleave', () => {
            target.classList.remove('ring-1', 'ring-primary-blue');
        });

        target.addEventListener('drop', async (event) => {
            event.preventDefault();
            target.classList.remove('ring-1', 'ring-primary-blue');

            if (!explorerState.dragItem) return;

            try {
                await moverItem(explorerState.dragItem.tipo, explorerState.dragItem.id, Number(target.getAttribute('data-tree-folder-id')));
            } catch (error) {
                notify('error', 'Erro', error.message);
            } finally {
                explorerState.dragItem = null;
            }
        });
    });
}

function bindRowDnD() {
    const rows = document.querySelectorAll('[data-item-type][data-item-id]');

    rows.forEach((row) => {
        row.addEventListener('dragstart', () => {
            explorerState.dragItem = {
                tipo: row.getAttribute('data-item-type'),
                id: Number(row.getAttribute('data-item-id'))
            };
        });

        row.addEventListener('dragend', () => {
            explorerState.dragItem = null;
        });
    });
}

function bindEvents() {
    if (explorerState.listenersBound) return;

    document.addEventListener('contextmenu', (event) => {
        const itemEl = event.target.closest('[data-item-type][data-item-id]');
        if (!itemEl) {
            closeContextMenu();
            return;
        }

        event.preventDefault();
        const itemType = itemEl.getAttribute('data-item-type');
        const itemId = Number(itemEl.getAttribute('data-item-id'));
        if (!itemType || !itemId) return;

        openContextMenu(itemType, itemId, event.clientX, event.clientY);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeContextMenu();
        }
    });

    document.addEventListener('click', async (event) => {
        const contextAction = event.target.closest('[data-context-action]');
        if (contextAction) {
            const action = contextAction.getAttribute('data-context-action');
            try {
                await executarContextAction(action);
            } catch (error) {
                notify('error', 'Erro', error.message);
            } finally {
                closeContextMenu();
            }
            return;
        }

        if (!event.target.closest('#fileContextMenu')) {
            closeContextMenu();
        }

        const openFolder = event.target.closest('[data-open-folder]');
        if (openFolder) {
            event.preventDefault();
            const folderId = Number(openFolder.getAttribute('data-open-folder'));
            if (!folderId) return;
            try {
                await carregarItens(folderId);
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
            return;
        }

        const breadcrumb = event.target.closest('[data-breadcrumb-id]');
        if (breadcrumb) {
            event.preventDefault();
            try {
                await carregarItens(Number(breadcrumb.getAttribute('data-breadcrumb-id')));
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
            return;
        }

        const renameFolder = event.target.closest('[data-rename-folder]');
        if (renameFolder) {
            try {
                await renomearPasta(Number(renameFolder.getAttribute('data-rename-folder')));
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
            return;
        }

        const renameFile = event.target.closest('[data-rename-file]');
        if (renameFile) {
            try {
                await renomearArquivo(Number(renameFile.getAttribute('data-rename-file')));
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
            return;
        }

        const deleteFolder = event.target.closest('[data-delete-folder]');
        if (deleteFolder) {
            try {
                await excluirItem('pasta', Number(deleteFolder.getAttribute('data-delete-folder')));
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
            return;
        }

        const deleteFile = event.target.closest('[data-delete-file]');
        if (deleteFile) {
            try {
                await excluirItem('arquivo', Number(deleteFile.getAttribute('data-delete-file')));
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
            return;
        }
    });

    const btnNovaPasta = document.getElementById('btnNovaPasta');
    if (btnNovaPasta) {
        btnNovaPasta.addEventListener('click', async () => {
            try {
                await criarPasta();
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
        });
    }

    const btnVoltarPai = document.getElementById('btnVoltarPai');
    if (btnVoltarPai) {
        btnVoltarPai.addEventListener('click', async () => {
            const parentId = Number(explorerState.currentFolder?.parent_id || 0);
            if (!parentId) return;
            try {
                await carregarItens(parentId);
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
        });
    }

    const btnIrRaiz = document.getElementById('btnIrRaiz');
    if (btnIrRaiz) {
        btnIrRaiz.addEventListener('click', async () => {
            if (!explorerState.rootId) return;
            try {
                await carregarItens(explorerState.rootId);
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
        });
    }

    const btnAtualizarArvore = document.getElementById('btnAtualizarArvore');
    if (btnAtualizarArvore) {
        btnAtualizarArvore.addEventListener('click', async () => {
            try {
                await carregarArvore();
                if (explorerState.currentFolder) {
                    await carregarItens(explorerState.currentFolder.id);
                }
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
        });
    }

    const btnAtualizarArvoreTop = document.getElementById('btnAtualizarArvoreTop');
    if (btnAtualizarArvoreTop) {
        btnAtualizarArvoreTop.addEventListener('click', async () => {
            try {
                await carregarArvore();
                if (explorerState.currentFolder) {
                    await carregarItens(explorerState.currentFolder.id);
                }
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
        });
    }

    const inputBuscaItens = document.getElementById('inputBuscaItens');
    if (inputBuscaItens) {
        inputBuscaItens.addEventListener('input', (event) => {
            explorerState.searchTerm = String(event.target.value || '').trim();
            renderList();
        });
    }

    const inputUploadArquivo = document.getElementById('inputUploadArquivo');
    if (inputUploadArquivo) {
        inputUploadArquivo.addEventListener('change', async (event) => {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            event.target.value = '';
            if (!file) return;

            try {
                await uploadArquivo(file);
                notify('success', 'Sucesso', 'Arquivo enviado com sucesso');
            } catch (error) {
                notify('error', 'Erro', error.message);
            }
        });
    }

    const dropzone = document.getElementById('dropzoneUpload');
    if (dropzone) {
        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                if (!canUploadInCurrentFolder()) {
                    return;
                }
                event.preventDefault();
                dropzone.classList.add('border-primary-blue', 'bg-blue-50');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('border-primary-blue', 'bg-blue-50');
            });
        });

        dropzone.addEventListener('drop', async (event) => {
            if (!canUploadInCurrentFolder()) {
                notify('warning', 'Atenção', 'Selecione uma pasta de associado para enviar arquivos');
                return;
            }
            const files = event.dataTransfer && event.dataTransfer.files ? Array.from(event.dataTransfer.files) : [];
            if (files.length === 0) return;

            for (const file of files) {
                try {
                    await uploadArquivo(file);
                } catch (error) {
                    notify('error', 'Erro', `${file.name}: ${error.message}`);
                }
            }

            notify('success', 'Sucesso', 'Upload finalizado');
        });
    }

    explorerState.listenersBound = true;
}

async function initPastasAssociados() {
    setTimeout(async () => {
        const container = document.getElementById('explorerTree');
        if (!container) return;

        bindEvents();

        try {
            await carregarInicial();
        } catch (error) {
            notify('error', 'Erro', error.message || 'Falha ao carregar Explorer');
        }
    }, 50);
}

document.addEventListener('DOMContentLoaded', initPastasAssociados);

document.addEventListener('lidergest:page-ready', (event) => {
    if (event.detail && event.detail.page === 'admin/pastas_associados') {
        initPastasAssociados();
    }
});

})();

