<?php
// Admin - Pastas de Associados

if (!defined('TEMPLATE_ROUTED')) {
    header('Location: ../../index.php');
    exit;
}

$basePrefix = isset($prefix) ? $prefix : '/';
require_once __DIR__ . '/../../includes/admin_components.php';
?>
<div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <?php
        admin_render_toolbar(
            'Admin - Pastas de Associados',
            'Gestao centralizada de pastas e arquivos dos associados.',
            [
                ['id' => 'reloadPastasAssociados', 'label' => 'Atualizar', 'class' => 'btn-secondary px-4 py-2 text-sm'],
                ['id' => 'novaPastaAssociado', 'label' => 'Nova Pasta', 'class' => 'btn-primary px-4 py-2 text-sm'],
            ]
        );
        ?>

        <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
            Fase 4 ativa: drag-and-drop, menu de contexto, upload com progresso e busca/paginacao server-side.
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <aside class="xl:col-span-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Arvore de pastas</h3>
                    <button type="button" class="text-xs text-gray-600 hover:text-gray-900" id="refreshTreePastasAssociados">
                        Recarregar
                    </button>
                </div>
                <div id="memberFoldersTree" class="min-h-[360px] text-sm text-gray-600">
                    Sem dados carregados.
                </div>
            </aside>

            <section class="xl:col-span-9 rounded-lg border border-gray-200 bg-white p-4">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p id="memberFoldersBreadcrumb" class="text-sm text-gray-600">
                        Pastas de Associados
                    </p>
                    <div class="flex items-center gap-2">
                        <button type="button" class="btn-secondary px-3 py-2 text-xs" id="uploadArquivoAssociado" disabled>
                            Upload
                        </button>
                        <input type="file" id="inputUploadArquivoAssociado" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" multiple>
                        <button type="button" class="btn-secondary px-3 py-2 text-xs" id="voltarPastaAssociado" disabled>
                            Voltar
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <input
                        id="searchPastaAssociado"
                        class="input-primary w-full"
                        placeholder="Buscar pasta ou arquivo nesta visualizacao"
                        disabled>
                </div>

                <div id="memberFoldersDropzone" class="mb-3 rounded border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-4 text-center text-xs text-gray-600">
                    Arraste arquivos aqui para upload na pasta atual ou arraste itens para mover entre pastas.
                </div>

                <div id="memberFoldersUploadProgress" class="mb-3 hidden rounded border border-blue-200 bg-blue-50 p-3">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-800">Uploads em andamento</div>
                    <div id="memberFoldersUploadProgressList" class="space-y-2"></div>
                </div>

                <div id="memberFoldersWorkspace" class="min-h-[300px] rounded border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600">
                    Carregando explorer...
                </div>

                <div class="mt-3 flex items-center justify-between gap-2">
                    <div id="memberFoldersPageMeta" class="text-xs text-gray-600"></div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="memberFoldersPrev" class="btn-secondary px-3 py-1 text-xs">Anterior</button>
                        <button type="button" id="memberFoldersNext" class="btn-secondary px-3 py-1 text-xs">Proximo</button>
                    </div>
                </div>
            </section>
        </div>

        <p id="pastasAssociadosMsg" class="text-sm mt-4"></p>
    </div>
</div>

<div id="memberFoldersContextMenu" class="hidden fixed z-[80] min-w-[180px] rounded border border-gray-200 bg-white p-1 shadow-xl">
    <button type="button" class="w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100" data-cm-action="open">Abrir</button>
    <button type="button" class="w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100" data-cm-action="rename">Renomear</button>
    <button type="button" class="w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100" data-cm-action="cut">Recortar</button>
    <button type="button" class="w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100" data-cm-action="copy">Copiar</button>
    <button type="button" class="w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100" data-cm-action="paste">Colar</button>
    <button type="button" class="w-full rounded px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50" data-cm-action="delete">Excluir</button>
</div>

<script src="<?php echo $basePrefix; ?>assets/js/anateje-api.js"></script>
<script>
(function () {
    const base = (window.LIDERGEST_BASE_URL || '').replace(/\/$/, '');
    const ep = (path) => `${base}${path}`;
    const perms = window.anatejePerms || null;
    const can = (code) => perms && typeof perms.can === 'function' ? perms.can(code) : true;
    const deny = (code) => perms && typeof perms.denyMessage === 'function'
        ? perms.denyMessage(code)
        : 'Acesso negado para esta acao (' + code + ').';

    const treeEl = document.getElementById('memberFoldersTree');
    const workspaceEl = document.getElementById('memberFoldersWorkspace');
    const dropzoneEl = document.getElementById('memberFoldersDropzone');
    const uploadProgressBoxEl = document.getElementById('memberFoldersUploadProgress');
    const uploadProgressListEl = document.getElementById('memberFoldersUploadProgressList');
    const breadcrumbEl = document.getElementById('memberFoldersBreadcrumb');
    const searchEl = document.getElementById('searchPastaAssociado');
    const msgEl = document.getElementById('pastasAssociadosMsg');
    const pageMetaEl = document.getElementById('memberFoldersPageMeta');
    const prevPageBtn = document.getElementById('memberFoldersPrev');
    const nextPageBtn = document.getElementById('memberFoldersNext');
    const contextMenuEl = document.getElementById('memberFoldersContextMenu');
    const reloadBtn = document.getElementById('reloadPastasAssociados');
    const refreshTreeBtn = document.getElementById('refreshTreePastasAssociados');
    const newFolderBtn = document.getElementById('novaPastaAssociado');
    const uploadBtn = document.getElementById('uploadArquivoAssociado');
    const uploadInput = document.getElementById('inputUploadArquivoAssociado');
    const backBtn = document.getElementById('voltarPastaAssociado');

    const state = {
        rootId: 0,
        tree: [],
        currentFolder: null,
        items: { pastas: [], arquivos: [] },
        meta: { files: { page: 1, per_page: 20, total: 0, total_pages: 1, has_prev: false, has_next: false }, q: '' },
        search: '',
        dragItem: null,
        dragOverFolderId: 0,
        clipboard: null,
        contextTarget: null
    };
    let searchTimer = null;
    let uploadProgressSeq = 0;

    function setMsg(text, type) {
        msgEl.textContent = text || '';
        if (!text) {
            msgEl.className = 'text-sm mt-4';
            return;
        }
        msgEl.className = type === 'ok'
            ? 'text-sm mt-4 text-green-700'
            : 'text-sm mt-4 text-red-600';
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function canManageFolder(row) {
        if (!row) return false;
        const type = String(row.tipo || '');
        return type === 'folder';
    }

    function canUploadInFolder(folderId) {
        const id = Number(folderId || 0);
        if (!id) return false;
        const folder = folderById(id) || (state.currentFolder && Number(state.currentFolder.id) === id ? state.currentFolder : null);
        if (!folder) return false;
        const type = String(folder.tipo || '');
        return type === 'member' || type === 'folder';
    }

    function canUploadInCurrentFolder() {
        if (!state.currentFolder) return false;
        return canUploadInFolder(state.currentFolder.id);
    }

    function formatBytes(bytes) {
        const v = Number(bytes || 0);
        if (!v) return '-';
        if (v < 1024) return `${v} B`;
        if (v < 1024 * 1024) return `${(v / 1024).toFixed(1)} KB`;
        return `${(v / (1024 * 1024)).toFixed(1)} MB`;
    }

    function folderById(id) {
        return state.tree.find((row) => Number(row.id) === Number(id)) || null;
    }

    function fileById(id) {
        const files = Array.isArray(state.items.arquivos) ? state.items.arquivos : [];
        return files.find((row) => Number(row.id) === Number(id)) || null;
    }

    function isClipboardCut(itemType, itemId) {
        if (!state.clipboard || state.clipboard.mode !== 'cut') return false;
        return String(state.clipboard.itemType) === String(itemType) && Number(state.clipboard.itemId) === Number(itemId);
    }

    function pathToRoot(folderId) {
        const path = [];
        let cursor = folderById(folderId);
        while (cursor) {
            path.unshift(cursor);
            if (cursor.parent_id === null) break;
            cursor = folderById(cursor.parent_id);
        }
        return path;
    }

    function renderBreadcrumb() {
        if (!state.currentFolder) {
            breadcrumbEl.textContent = 'Pastas de Associados';
            backBtn.disabled = true;
            uploadBtn.disabled = true;
            workspaceEl.removeAttribute('data-drop-folder');
            dropzoneEl.removeAttribute('data-drop-folder');
            return;
        }
        const parts = pathToRoot(state.currentFolder.id);
        breadcrumbEl.innerHTML = parts.map((node) => {
            return `<button type="button" class="hover:underline" data-open-folder="${node.id}">${escapeHtml(node.nome)}</button>`;
        }).join(' / ');
        backBtn.disabled = state.currentFolder.parent_id === null;
        const canUpload = can('admin.pastas_associados.upload') && canUploadInCurrentFolder();
        const canCreate = can('admin.pastas_associados.create') && canUploadInCurrentFolder();
        uploadBtn.disabled = !canUpload;
        newFolderBtn.disabled = !canCreate;
        workspaceEl.setAttribute('data-drop-folder', String(state.currentFolder.id));
        dropzoneEl.setAttribute('data-drop-folder', String(state.currentFolder.id));
    }

    function renderTree() {
        if (!Array.isArray(state.tree) || state.tree.length === 0) {
            treeEl.innerHTML = '<p class="text-sm text-gray-500">Sem pastas cadastradas.</p>';
            return;
        }

        const byParent = {};
        state.tree.forEach((node) => {
            const key = node.parent_id === null ? 'root' : String(node.parent_id);
            if (!byParent[key]) byParent[key] = [];
            byParent[key].push(node);
        });
        Object.keys(byParent).forEach((key) => {
            byParent[key].sort((a, b) => String(a.nome || '').localeCompare(String(b.nome || ''), 'pt-BR'));
        });

        const renderNode = (node, depth) => {
            const children = byParent[String(node.id)] || [];
            const current = state.currentFolder && Number(state.currentFolder.id) === Number(node.id);
            const margin = depth * 12;
            return `
                <div class="space-y-1">
                    <button
                        type="button"
                        class="w-full rounded px-2 py-1 text-left text-sm ${current ? 'bg-indigo-100 text-indigo-900' : 'hover:bg-gray-100 text-gray-700'}"
                        style="margin-left:${margin}px"
                        data-open-folder="${node.id}"
                        data-drop-folder="${node.id}">
                        ${escapeHtml(node.nome)}
                    </button>
                    ${children.map((child) => renderNode(child, depth + 1)).join('')}
                </div>
            `;
        };

        treeEl.innerHTML = (byParent.root || []).map((root) => renderNode(root, 0)).join('');
    }

    function filteredItems() {
        const term = String(state.search || '').toLowerCase();
        const pastas = Array.isArray(state.items.pastas) ? state.items.pastas : [];
        const arquivos = Array.isArray(state.items.arquivos) ? state.items.arquivos : [];
        if (!term) return { pastas, arquivos };

        return {
            pastas: pastas.filter((row) => String(row.nome || '').toLowerCase().includes(term)),
            arquivos: arquivos.filter((row) => String(row.nome_exibicao || row.nome_original || '').toLowerCase().includes(term))
        };
    }

    function renderWorkspace() {
        const data = filteredItems();
        const foldersHtml = data.pastas.map((row) => {
            const canEdit = can('admin.pastas_associados.edit') && canManageFolder(row);
            const canDelete = can('admin.pastas_associados.delete') && canManageFolder(row);
            const dragAttr = canEdit ? 'draggable="true"' : '';
            const dragClass = canEdit ? 'cursor-move' : '';
            const cutClass = isClipboardCut('folder', row.id) ? 'opacity-60 ring-2 ring-amber-300 bg-amber-50' : '';
            return `
                <div class="flex items-center gap-2 rounded border border-gray-200 bg-white px-3 py-2 ${dragClass} ${cutClass}"
                    data-item-type="folder"
                    data-item-id="${row.id}"
                    data-item-member-id="${row.member_id || 0}"
                    data-drop-folder="${row.id}"
                    ${dragAttr}>
                    <button type="button" data-open-folder="${row.id}" class="min-w-0 flex-1 text-left hover:underline">
                        <span class="font-medium text-gray-900">${escapeHtml(row.nome)}</span>
                        <span class="ml-2 text-xs text-gray-500">${escapeHtml(row.tipo || 'folder')}</span>
                    </button>
                    ${canEdit ? `<button type="button" class="text-xs text-indigo-700 hover:underline" data-folder-move="${row.id}">Mover</button>` : ''}
                    ${canEdit ? `<button type="button" class="text-xs text-blue-700 hover:underline" data-folder-rename="${row.id}">Renomear</button>` : ''}
                    ${canDelete ? `<button type="button" class="text-xs text-red-600 hover:underline" data-folder-delete="${row.id}">Excluir</button>` : ''}
                </div>
            `;
        }).join('');

        const filesHtml = data.arquivos.map((row) => {
            const href = escapeHtml(String(row.download_url || '#'));
            const fileName = escapeHtml(row.nome_exibicao || row.nome_original || '');
            const canEdit = can('admin.pastas_associados.edit');
            const canDelete = can('admin.pastas_associados.delete');
            const dragAttr = canEdit ? 'draggable="true"' : '';
            const dragClass = canEdit ? 'cursor-move' : '';
            const cutClass = isClipboardCut('file', row.id) ? 'opacity-60 ring-2 ring-amber-300 bg-amber-50' : '';
            return `
                <div class="rounded border border-gray-200 bg-white px-3 py-2 ${dragClass} ${cutClass}"
                    data-item-type="file"
                    data-item-id="${row.id}"
                    data-item-member-id="${row.member_id || 0}"
                    ${dragAttr}>
                    <div class="flex items-center justify-between gap-3">
                        <a href="${href}" target="_blank" rel="noopener noreferrer" class="min-w-0 flex-1 truncate text-gray-800 hover:underline">
                            ${fileName}
                        </a>
                        <span class="text-xs text-gray-500">${formatBytes(row.tamanho_bytes)}</span>
                    </div>
                    <div class="mt-1 flex items-center gap-3">
                        ${canEdit ? `<button type="button" class="text-xs text-blue-700 hover:underline" data-file-rename="${row.id}">Renomear</button>` : ''}
                        ${canEdit ? `<button type="button" class="text-xs text-indigo-700 hover:underline" data-file-move="${row.id}">Mover</button>` : ''}
                        ${canDelete ? `<button type="button" class="text-xs text-red-600 hover:underline" data-file-delete="${row.id}">Excluir</button>` : ''}
                    </div>
                </div>
            `;
        }).join('');

        if (!foldersHtml && !filesHtml) {
            workspaceEl.innerHTML = '<div class="rounded border border-gray-200 bg-white p-4 text-sm text-gray-500">Pasta vazia.</div>';
            return;
        }

        workspaceEl.innerHTML = `
            <div class="space-y-4">
                ${foldersHtml ? `<div class="space-y-2"><h3 class="text-xs font-semibold uppercase text-gray-500">Pastas</h3>${foldersHtml}</div>` : ''}
                ${filesHtml ? `<div class="space-y-2"><h3 class="text-xs font-semibold uppercase text-gray-500">Arquivos</h3>${filesHtml}</div>` : ''}
            </div>
        `;
    }

    function renderPager() {
        const filesMeta = state.meta && state.meta.files ? state.meta.files : null;
        if (!filesMeta) {
            pageMetaEl.textContent = '';
            prevPageBtn.disabled = true;
            nextPageBtn.disabled = true;
            return;
        }

        const page = Number(filesMeta.page || 1);
        const totalPages = Number(filesMeta.total_pages || 1);
        const total = Number(filesMeta.total || 0);
        const perPage = Number(filesMeta.per_page || 20);
        const start = total === 0 ? 0 : ((page - 1) * perPage) + 1;
        const end = Math.min(total, page * perPage);
        const q = String(state.meta.q || '').trim();
        const qLabel = q ? ` | busca: "${q}"` : '';

        pageMetaEl.textContent = `Arquivos ${start}-${end} de ${total} | pagina ${page}/${totalPages}${qLabel}`;
        prevPageBtn.disabled = !filesMeta.has_prev;
        nextPageBtn.disabled = !filesMeta.has_next;
    }

    function buildItemsQuery(folderId) {
        const params = new URLSearchParams();
        params.set('action', 'admin_items');
        params.set('folder_id', String(folderId));
        params.set('page', String(state.meta.files.page || 1));
        params.set('per_page', String(state.meta.files.per_page || 20));
        if (state.meta.q) {
            params.set('q', String(state.meta.q));
        }
        return params.toString();
    }

    function uploadProgressStart(fileName) {
        uploadProgressSeq += 1;
        const id = 'upload-progress-' + uploadProgressSeq;

        const row = document.createElement('div');
        row.id = id;
        row.className = 'rounded border border-blue-200 bg-white px-2 py-2';
        row.innerHTML = `
            <div class="mb-1 flex items-center justify-between gap-2">
                <span class="truncate text-xs text-gray-800">${escapeHtml(fileName)}</span>
                <span class="text-[11px] text-blue-700" data-progress-status>0%</span>
            </div>
            <div class="h-2 w-full rounded bg-blue-100">
                <div class="h-2 rounded bg-blue-500 transition-all duration-150" style="width:0%" data-progress-bar></div>
            </div>
        `;

        uploadProgressListEl.appendChild(row);
        uploadProgressBoxEl.classList.remove('hidden');
        return id;
    }

    function uploadProgressUpdate(progressId, percent, statusText) {
        const row = document.getElementById(progressId);
        if (!row) return;
        const bar = row.querySelector('[data-progress-bar]');
        const status = row.querySelector('[data-progress-status]');
        const safe = Math.max(0, Math.min(100, Math.round(Number(percent || 0))));
        if (bar) {
            bar.style.width = safe + '%';
        }
        if (status) {
            status.textContent = statusText || (safe + '%');
        }
    }

    function uploadProgressDone(progressId, ok, message) {
        const row = document.getElementById(progressId);
        if (!row) return;
        row.classList.remove('border-blue-200');
        row.classList.add(ok ? 'border-green-200' : 'border-red-200');
        const status = row.querySelector('[data-progress-status]');
        if (status) {
            status.className = ok ? 'text-[11px] text-green-700' : 'text-[11px] text-red-700';
            status.textContent = message || (ok ? 'Concluido' : 'Falhou');
        }
        const bar = row.querySelector('[data-progress-bar]');
        if (bar) {
            bar.classList.remove('bg-blue-500');
            bar.classList.add(ok ? 'bg-green-500' : 'bg-red-500');
        }
    }

    function clearContextMenu() {
        contextMenuEl.classList.add('hidden');
        state.contextTarget = null;
    }

    function setClipboard(mode, itemType, itemId, memberId) {
        state.clipboard = {
            mode: String(mode || ''),
            itemType: String(itemType || ''),
            itemId: Number(itemId || 0),
            memberId: Number(memberId || 0),
        };
    }

    function clearClipboard() {
        state.clipboard = null;
    }

    function canPasteToFolder(folderId) {
        if (!state.clipboard) return false;
        const target = folderById(folderId);
        if (!target) return false;
        const targetType = String(target.tipo || '');
        if (!(targetType === 'member' || targetType === 'folder')) return false;
        if (Number(state.clipboard.memberId || 0) > 0 && Number(target.member_id || 0) !== Number(state.clipboard.memberId || 0)) {
            return false;
        }
        return true;
    }

    function contextTargetFromEventTarget(target) {
        const itemEl = target.closest('[data-item-type][data-item-id]');
        if (itemEl) {
            return {
                itemType: String(itemEl.getAttribute('data-item-type') || ''),
                itemId: Number(itemEl.getAttribute('data-item-id') || 0),
                folderId: Number(itemEl.getAttribute('data-item-type') === 'folder' ? itemEl.getAttribute('data-item-id') : (state.currentFolder && state.currentFolder.id ? state.currentFolder.id : 0)),
                memberId: Number(itemEl.getAttribute('data-item-member-id') || 0)
            };
        }

        const dropTarget = target.closest('[data-drop-folder]');
        if (dropTarget) {
            const folderId = Number(dropTarget.getAttribute('data-drop-folder') || 0);
            const folder = folderById(folderId);
            return {
                itemType: folder ? 'folder' : 'workspace',
                itemId: folderId,
                folderId: folderId,
                memberId: folder ? Number(folder.member_id || 0) : 0
            };
        }

        return {
            itemType: 'workspace',
            itemId: Number(state.currentFolder && state.currentFolder.id ? state.currentFolder.id : 0),
            folderId: Number(state.currentFolder && state.currentFolder.id ? state.currentFolder.id : 0),
            memberId: Number(state.currentFolder && state.currentFolder.member_id ? state.currentFolder.member_id : 0),
        };
    }

    function refreshContextMenuActions() {
        const target = state.contextTarget;
        const isFolder = target && target.itemType === 'folder';
        const isFile = target && target.itemType === 'file';
        const folderRow = isFolder ? folderById(target.itemId) : null;
        const folderManage = isFolder ? canManageFolder(folderRow) : false;
        const folderId = Number(target && target.folderId ? target.folderId : 0);
        const canPaste = folderId > 0 && canPasteToFolder(folderId);

        const openBtn = contextMenuEl.querySelector('[data-cm-action="open"]');
        const renameBtn = contextMenuEl.querySelector('[data-cm-action="rename"]');
        const cutBtn = contextMenuEl.querySelector('[data-cm-action="cut"]');
        const copyBtn = contextMenuEl.querySelector('[data-cm-action="copy"]');
        const pasteBtn = contextMenuEl.querySelector('[data-cm-action="paste"]');
        const deleteBtn = contextMenuEl.querySelector('[data-cm-action="delete"]');

        const setVisible = (el, visible) => {
            if (!el) return;
            el.classList.toggle('hidden', !visible);
        };

        setVisible(openBtn, isFolder || isFile);
        setVisible(renameBtn, isFile || folderManage);
        setVisible(cutBtn, isFile || folderManage);
        setVisible(copyBtn, isFile);
        setVisible(pasteBtn, folderId > 0);
        setVisible(deleteBtn, isFile || folderManage);

        if (renameBtn) {
            renameBtn.disabled = !can('admin.pastas_associados.edit');
        }
        if (cutBtn) {
            cutBtn.disabled = !can('admin.pastas_associados.edit');
        }
        if (copyBtn) {
            copyBtn.disabled = !can('admin.pastas_associados.edit');
        }
        if (pasteBtn) {
            pasteBtn.disabled = !can('admin.pastas_associados.edit') || !canPaste;
        }
        if (deleteBtn) {
            deleteBtn.disabled = !can('admin.pastas_associados.delete');
        }
    }

    function openContextMenu(clientX, clientY, target) {
        state.contextTarget = target;
        refreshContextMenuActions();
        contextMenuEl.classList.remove('hidden');

        const menuRect = contextMenuEl.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const left = Math.max(8, Math.min(clientX, vw - menuRect.width - 8));
        const top = Math.max(8, Math.min(clientY, vh - menuRect.height - 8));
        contextMenuEl.style.left = `${left}px`;
        contextMenuEl.style.top = `${top}px`;
    }

    async function executeContextAction(action) {
        const target = state.contextTarget;
        if (!target) return;

        if (action === 'open') {
            if (target.itemType === 'folder') {
                await openFolder(target.itemId, { resetPage: true });
            } else if (target.itemType === 'file') {
                const file = fileById(target.itemId);
                if (file && file.download_url) {
                    window.open(String(file.download_url), '_blank', 'noopener');
                }
            }
            return;
        }

        if (action === 'rename') {
            if (target.itemType === 'folder') {
                await renameFolder(target.itemId);
            } else if (target.itemType === 'file') {
                await renameFile(target.itemId);
            }
            return;
        }

        if (action === 'delete') {
            if (target.itemType === 'folder') {
                await deleteFolder(target.itemId);
            } else if (target.itemType === 'file') {
                await deleteFile(target.itemId);
            }
            return;
        }

        if (action === 'cut') {
            setClipboard('cut', target.itemType, target.itemId, target.memberId);
            renderWorkspace();
            setMsg('Item recortado. Use Colar no destino.', 'ok');
            return;
        }

        if (action === 'copy') {
            setClipboard('copy', target.itemType, target.itemId, target.memberId);
            renderWorkspace();
            setMsg('Item copiado. Use Colar no destino.', 'ok');
            return;
        }

        if (action === 'paste') {
            if (!state.clipboard) {
                setMsg('Area de transferencia vazia.', 'err');
                return;
            }
            const targetFolderId = Number(target.folderId || 0);
            if (!targetFolderId || !canPasteToFolder(targetFolderId)) {
                setMsg('Destino invalido para colar.', 'err');
                return;
            }

            if (state.clipboard.mode === 'cut') {
                await moveItemToTarget(state.clipboard.itemType, state.clipboard.itemId, targetFolderId);
                clearClipboard();
                renderWorkspace();
                return;
            }

            if (state.clipboard.mode === 'copy') {
                if (state.clipboard.itemType !== 'file') {
                    setMsg('Copia de pasta sera implementada em etapa posterior.', 'err');
                    return;
                }
                await window.anatejeApi(ep('/api/v1/member_folders.php?action=admin_copy_file'), {
                    method: 'POST',
                    body: {
                        file_id: Number(state.clipboard.itemId),
                        target_folder_id: targetFolderId
                    }
                });
                if (state.currentFolder && state.currentFolder.id) {
                    await openFolder(state.currentFolder.id);
                }
                setMsg('Arquivo copiado com sucesso.', 'ok');
            }
        }
    }

    async function createFolder() {
        if (!state.currentFolder || !state.currentFolder.id) {
            setMsg('Selecione uma pasta de destino.', 'err');
            return;
        }
        if (!canUploadInCurrentFolder()) {
            setMsg('Nova pasta permitida apenas dentro de pasta de associado.', 'err');
            return;
        }
        const nome = window.prompt('Nome da nova pasta:');
        if (nome === null) return;
        const finalName = String(nome || '').trim();
        if (!finalName) {
            setMsg('Nome da pasta e obrigatorio.', 'err');
            return;
        }

        await window.anatejeApi(ep('/api/v1/member_folders.php?action=admin_create_folder'), {
            method: 'POST',
            body: {
                parent_id: Number(state.currentFolder.id),
                nome: finalName
            }
        });
        await loadTreeOnly();
        await openFolder(state.currentFolder.id);
        setMsg('Pasta criada com sucesso.', 'ok');
    }

    async function renameFolder(folderId) {
        const row = folderById(folderId);
        const currentName = row ? String(row.nome || '') : '';
        const nome = window.prompt('Novo nome da pasta:', currentName);
        if (nome === null) return;
        const finalName = String(nome || '').trim();
        if (!finalName) {
            setMsg('Nome da pasta e obrigatorio.', 'err');
            return;
        }

        await window.anatejeApi(ep('/api/v1/member_folders.php?action=admin_rename_folder'), {
            method: 'POST',
            body: {
                id: Number(folderId),
                nome: finalName
            }
        });
        await loadTreeOnly();
        if (state.currentFolder && Number(state.currentFolder.id) === Number(folderId)) {
            await openFolder(folderId);
        } else if (state.currentFolder && state.currentFolder.id) {
            await openFolder(state.currentFolder.id);
        }
        setMsg('Pasta renomeada com sucesso.', 'ok');
    }

    async function deleteFolder(folderId) {
        const ok = window.confirm('Confirma excluir esta pasta e seus itens?');
        if (!ok) return;

        await window.anatejeApi(ep('/api/v1/member_folders.php?action=admin_delete_folder'), {
            method: 'POST',
            body: { id: Number(folderId) }
        });

        await loadTreeOnly();
        const currentId = Number(state.currentFolder && state.currentFolder.id ? state.currentFolder.id : 0);
        const currentStillExists = !!folderById(currentId);
        if (currentStillExists && currentId > 0) {
            await openFolder(currentId);
        } else if (state.rootId > 0) {
            await openFolder(state.rootId);
        } else {
            await loadInit();
        }
        setMsg('Pasta excluida com sucesso.', 'ok');
    }

    function moveDestinationOptions(itemType, itemId, sourceMemberId) {
        return state.tree.filter((node) => {
            const type = String(node.tipo || '');
            if (!(type === 'member' || type === 'folder')) return false;
            if (itemType === 'folder' && Number(node.id) === Number(itemId)) return false;
            if (Number(sourceMemberId || 0) > 0 && Number(node.member_id || 0) !== Number(sourceMemberId || 0)) {
                return false;
            }
            return true;
        });
    }

    function askMoveDestination(itemType, itemId, sourceMemberId) {
        const options = moveDestinationOptions(itemType, itemId, sourceMemberId);
        if (!options.length) {
            throw new Error('Nao ha destinos disponiveis para mover este item');
        }

        const preview = options.slice(0, 15).map((row) => `${row.id} - ${row.nome}`).join('\n');
        const msg = [
            'Informe o ID da pasta de destino.',
            '',
            'Opcoes (primeiras 15):',
            preview
        ].join('\n');
        const input = window.prompt(msg);
        if (input === null) return null;

        const id = Number(String(input || '').trim());
        if (!Number.isInteger(id) || id <= 0) {
            throw new Error('ID de destino invalido');
        }
        const allowed = options.some((row) => Number(row.id) === id);
        if (!allowed) {
            throw new Error('Destino nao permitido para este item');
        }
        return id;
    }

    async function moveItem(itemType, itemId) {
        let sourceMemberId = 0;
        if (itemType === 'folder') {
            const folder = folderById(itemId);
            sourceMemberId = Number(folder && folder.member_id ? folder.member_id : 0);
        } else {
            const file = fileById(itemId);
            sourceMemberId = Number(file && file.member_id ? file.member_id : 0);
        }

        const targetId = askMoveDestination(itemType, itemId, sourceMemberId);
        if (targetId === null) return;

        await moveItemToTarget(itemType, itemId, targetId);
    }

    async function moveItemToTarget(itemType, itemId, targetId) {
        if (itemType === 'folder') {
            const folder = folderById(itemId);
            if (folder && Number(folder.parent_id || 0) === Number(targetId || 0)) {
                return;
            }
        } else if (itemType === 'file') {
            const file = fileById(itemId);
            if (file && Number(file.folder_id || 0) === Number(targetId || 0)) {
                return;
            }
        }

        await window.anatejeApi(ep('/api/v1/member_folders.php?action=admin_move_item'), {
            method: 'POST',
            body: {
                item_type: itemType,
                item_id: Number(itemId),
                target_folder_id: Number(targetId)
            }
        });

        await loadTreeOnly();
        if (state.currentFolder && state.currentFolder.id) {
            await openFolder(state.currentFolder.id);
        }
        setMsg('Item movido com sucesso.', 'ok');
    }

    function clearDragHighlight() {
        state.dragOverFolderId = 0;
        document.querySelectorAll('[data-drop-folder]').forEach((el) => {
            el.classList.remove('ring-2', 'ring-indigo-400', 'bg-indigo-50');
        });
        dropzoneEl.classList.remove('border-indigo-400', 'bg-indigo-50');
    }

    function applyDragHighlight(folderId) {
        clearDragHighlight();
        const id = Number(folderId || 0);
        if (!id) return;
        state.dragOverFolderId = id;
        document.querySelectorAll(`[data-drop-folder="${id}"]`).forEach((el) => {
            el.classList.add('ring-2', 'ring-indigo-400', 'bg-indigo-50');
        });
        dropzoneEl.classList.add('border-indigo-400', 'bg-indigo-50');
    }

    function getDropFolderIdFromEvent(event) {
        const target = event.target && event.target.closest
            ? event.target.closest('[data-drop-folder]')
            : null;
        if (!target) return 0;
        return Number(target.getAttribute('data-drop-folder') || 0);
    }

    function getDragTypes(event) {
        const types = event && event.dataTransfer && event.dataTransfer.types
            ? Array.from(event.dataTransfer.types)
            : [];
        return types.map((t) => String(t || '').toLowerCase());
    }

    function isNativeFileDrag(event) {
        const types = getDragTypes(event);
        return types.includes('files');
    }

    async function renameFile(fileId) {
        const row = fileById(fileId);
        const currentName = row ? String(row.nome_exibicao || row.nome_original || '') : '';
        const nome = window.prompt('Novo nome do arquivo:', currentName);
        if (nome === null) return;
        const finalName = String(nome || '').trim();
        if (!finalName) {
            setMsg('Nome do arquivo e obrigatorio.', 'err');
            return;
        }

        await window.anatejeApi(ep('/api/v1/member_folders.php?action=admin_rename_file'), {
            method: 'POST',
            body: {
                id: Number(fileId),
                nome: finalName
            }
        });

        if (state.currentFolder && state.currentFolder.id) {
            await openFolder(state.currentFolder.id);
        }
        setMsg('Arquivo renomeado com sucesso.', 'ok');
    }

    async function deleteFile(fileId) {
        const ok = window.confirm('Confirma excluir este arquivo?');
        if (!ok) return;

        await window.anatejeApi(ep('/api/v1/member_folders.php?action=admin_delete_file'), {
            method: 'POST',
            body: { id: Number(fileId) }
        });

        if (state.currentFolder && state.currentFolder.id) {
            await openFolder(state.currentFolder.id);
        }
        setMsg('Arquivo excluido com sucesso.', 'ok');
    }

    async function uploadFile(file, options = {}) {
        if (!file) return;
        const folderId = Number(options.folderId || (state.currentFolder && state.currentFolder.id ? state.currentFolder.id : 0));
        if (!folderId) {
            setMsg('Selecione uma pasta para upload.', 'err');
            return;
        }
        if (!canUploadInFolder(folderId)) {
            setMsg('Upload permitido apenas em pasta de associado.', 'err');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'admin_upload_file');
        formData.append('folder_id', String(folderId));
        formData.append('arquivo', file);

        const progressId = options.progressId || uploadProgressStart(file.name || 'arquivo');
        uploadProgressUpdate(progressId, 1, 'Iniciando...');

        const data = await new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', ep('/api/v1/member_folders.php?action=admin_upload_file'), true);
            xhr.withCredentials = true;

            const csrf = String(window.LIDERGEST_CSRF_TOKEN || '').trim();
            if (csrf) {
                xhr.setRequestHeader('X-CSRF-Token', csrf);
            }

            xhr.upload.onprogress = (event) => {
                if (!event.lengthComputable) return;
                const percent = event.total > 0 ? (event.loaded / event.total) * 100 : 0;
                uploadProgressUpdate(progressId, percent, Math.round(percent) + '%');
            };

            xhr.onload = () => {
                let payload = null;
                try {
                    payload = JSON.parse(xhr.responseText || '{}');
                } catch (_) {
                    payload = null;
                }
                if (xhr.status >= 200 && xhr.status < 300 && payload && payload.ok === true) {
                    uploadProgressDone(progressId, true, 'Concluido');
                    resolve(payload.data || {});
                    return;
                }
                const message = payload && payload.error && payload.error.message
                    ? payload.error.message
                    : ('Falha no upload (HTTP ' + xhr.status + ')');
                uploadProgressDone(progressId, false, message);
                reject(new Error(message));
            };

            xhr.onerror = () => {
                const message = 'Falha de rede no upload';
                uploadProgressDone(progressId, false, message);
                reject(new Error(message));
            };

            xhr.send(formData);
        });

        if (options.refresh !== false) {
            await openFolder(folderId);
            setMsg('Arquivo enviado com sucesso.', 'ok');
        }

        return data;
    }

    async function uploadFiles(files, options = {}) {
        const list = Array.isArray(files) ? files.filter(Boolean) : [];
        if (!list.length) return;
        const folderId = Number(options.folderId || (state.currentFolder && state.currentFolder.id ? state.currentFolder.id : 0));
        if (!folderId) {
            setMsg('Selecione uma pasta para upload.', 'err');
            return;
        }

        let okCount = 0;
        let failCount = 0;
        const errors = [];

        uploadProgressListEl.innerHTML = '';
        uploadProgressBoxEl.classList.remove('hidden');

        for (const file of list) {
            const progressId = uploadProgressStart(file.name || 'arquivo');
            try {
                await uploadFile(file, { refresh: false, folderId, progressId });
                okCount += 1;
            } catch (err) {
                failCount += 1;
                errors.push(`${file.name}: ${err.message || 'falha no upload'}`);
            }
        }

        await openFolder(folderId);

        if (failCount === 0) {
            setMsg(`Upload concluido (${okCount} arquivo(s)).`, 'ok');
            return;
        }

        const msg = [
            `Upload parcial: ${okCount} enviado(s), ${failCount} com falha.`,
            errors.slice(0, 2).join(' | ')
        ].filter(Boolean).join(' ');
        setMsg(msg, 'err');
    }

    async function loadTreeOnly() {
        const data = await window.anatejeApi(ep('/api/v1/member_folders.php?action=admin_tree'));
        state.rootId = Number(data.root_id || state.rootId || 0);
        state.tree = Array.isArray(data.tree) ? data.tree : [];
        renderTree();
    }

    async function openFolder(folderId, options = {}) {
        const id = Number(folderId || 0);
        if (!id) return;
        const resetPage = !!options.resetPage;
        if (resetPage) {
            state.meta.files.page = 1;
        }
        const query = buildItemsQuery(id);
        const data = await window.anatejeApi(ep('/api/v1/member_folders.php?' + query));
        state.currentFolder = data.pasta_atual || null;
        state.items = {
            pastas: Array.isArray(data.pastas) ? data.pastas : [],
            arquivos: Array.isArray(data.arquivos) ? data.arquivos : []
        };
        if (data.meta && data.meta.files) {
            state.meta = {
                q: String(data.meta.q || state.meta.q || ''),
                files: {
                    page: Number(data.meta.files.page || 1),
                    per_page: Number(data.meta.files.per_page || 20),
                    total: Number(data.meta.files.total || 0),
                    total_pages: Number(data.meta.files.total_pages || 1),
                    has_prev: !!data.meta.files.has_prev,
                    has_next: !!data.meta.files.has_next
                }
            };
        }
        state.search = String(state.meta.q || '');
        searchEl.value = state.search;
        renderBreadcrumb();
        renderWorkspace();
        renderPager();
        renderTree();
    }

    async function loadInit() {
        const params = new URLSearchParams();
        params.set('action', 'admin_init');
        params.set('page', String(state.meta.files.page || 1));
        params.set('per_page', String(state.meta.files.per_page || 20));
        if (state.meta.q) {
            params.set('q', String(state.meta.q));
        }
        const data = await window.anatejeApi(ep('/api/v1/member_folders.php?' + params.toString()));
        const root = data.root || {};
        state.rootId = Number(root.id || 0);
        state.tree = Array.isArray(data.tree) ? data.tree : [];
        const itens = data.itens || {};
        state.currentFolder = itens.pasta_atual || root || null;
        state.items = {
            pastas: Array.isArray(itens.pastas) ? itens.pastas : [],
            arquivos: Array.isArray(itens.arquivos) ? itens.arquivos : []
        };
        if (itens.meta && itens.meta.files) {
            state.meta = {
                q: String(itens.meta.q || state.meta.q || ''),
                files: {
                    page: Number(itens.meta.files.page || 1),
                    per_page: Number(itens.meta.files.per_page || 20),
                    total: Number(itens.meta.files.total || 0),
                    total_pages: Number(itens.meta.files.total_pages || 1),
                    has_prev: !!itens.meta.files.has_prev,
                    has_next: !!itens.meta.files.has_next
                }
            };
        }
        state.search = String(state.meta.q || '');
        searchEl.disabled = false;
        searchEl.value = state.search;
        renderBreadcrumb();
        renderTree();
        renderWorkspace();
        renderPager();
        setMsg('Pastas de associados carregadas.', 'ok');
    }

    function bindEvents() {
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                clearContextMenu();
            }
        });

        document.addEventListener('contextmenu', (event) => {
            const insideScope = event.target.closest('#memberFoldersTree, #memberFoldersWorkspace, #memberFoldersDropzone, #memberFoldersBreadcrumb');
            if (!insideScope) {
                clearContextMenu();
                return;
            }
            event.preventDefault();
            const target = contextTargetFromEventTarget(event.target);
            openContextMenu(event.clientX, event.clientY, target);
        });

        document.addEventListener('click', async (event) => {
            const cmActionBtn = event.target.closest('[data-cm-action]');
            if (cmActionBtn) {
                const action = String(cmActionBtn.getAttribute('data-cm-action') || '');
                try {
                    await executeContextAction(action);
                } catch (err) {
                    setMsg(err.message || 'Falha ao executar acao de contexto', 'err');
                } finally {
                    clearContextMenu();
                }
                return;
            }

            if (!event.target.closest('#memberFoldersContextMenu')) {
                clearContextMenu();
            }

            const folderMoveBtn = event.target.closest('[data-folder-move]');
            if (folderMoveBtn) {
                const id = Number(folderMoveBtn.getAttribute('data-folder-move') || 0);
                if (!id) return;
                try {
                    await moveItem('folder', id);
                } catch (err) {
                    setMsg(err.message || 'Falha ao mover pasta', 'err');
                }
                return;
            }

            const renameBtn = event.target.closest('[data-folder-rename]');
            if (renameBtn) {
                const id = Number(renameBtn.getAttribute('data-folder-rename') || 0);
                if (!id) return;
                try {
                    await renameFolder(id);
                } catch (err) {
                    setMsg(err.message || 'Falha ao renomear pasta', 'err');
                }
                return;
            }

            const deleteBtn = event.target.closest('[data-folder-delete]');
            if (deleteBtn) {
                const id = Number(deleteBtn.getAttribute('data-folder-delete') || 0);
                if (!id) return;
                try {
                    await deleteFolder(id);
                } catch (err) {
                    setMsg(err.message || 'Falha ao excluir pasta', 'err');
                }
                return;
            }

            const fileRenameBtn = event.target.closest('[data-file-rename]');
            if (fileRenameBtn) {
                const id = Number(fileRenameBtn.getAttribute('data-file-rename') || 0);
                if (!id) return;
                try {
                    await renameFile(id);
                } catch (err) {
                    setMsg(err.message || 'Falha ao renomear arquivo', 'err');
                }
                return;
            }

            const fileMoveBtn = event.target.closest('[data-file-move]');
            if (fileMoveBtn) {
                const id = Number(fileMoveBtn.getAttribute('data-file-move') || 0);
                if (!id) return;
                try {
                    await moveItem('file', id);
                } catch (err) {
                    setMsg(err.message || 'Falha ao mover arquivo', 'err');
                }
                return;
            }

            const fileDeleteBtn = event.target.closest('[data-file-delete]');
            if (fileDeleteBtn) {
                const id = Number(fileDeleteBtn.getAttribute('data-file-delete') || 0);
                if (!id) return;
                try {
                    await deleteFile(id);
                } catch (err) {
                    setMsg(err.message || 'Falha ao excluir arquivo', 'err');
                }
                return;
            }

            const openBtn = event.target.closest('[data-open-folder]');
            if (!openBtn) return;
            const id = Number(openBtn.getAttribute('data-open-folder') || 0);
            if (!id) return;
            try {
                await openFolder(id, { resetPage: true });
            } catch (err) {
                setMsg(err.message || 'Falha ao abrir pasta', 'err');
            }
        });

        document.addEventListener('dragstart', (event) => {
            const dragEl = event.target && event.target.closest
                ? event.target.closest('[draggable="true"][data-item-type][data-item-id]')
                : null;
            if (!dragEl || !can('admin.pastas_associados.edit')) {
                return;
            }

            const itemType = String(dragEl.getAttribute('data-item-type') || '').trim();
            const itemId = Number(dragEl.getAttribute('data-item-id') || 0);
            if (!itemId || (itemType !== 'folder' && itemType !== 'file')) {
                return;
            }

            state.dragItem = {
                type: itemType,
                id: itemId
            };

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', `${itemType}:${itemId}`);
            }
        });

        document.addEventListener('dragend', () => {
            state.dragItem = null;
            clearDragHighlight();
        });

        document.addEventListener('dragover', (event) => {
            let folderId = getDropFolderIdFromEvent(event);
            if (!folderId && event.target && event.target.closest && event.target.closest('#memberFoldersDropzone, #memberFoldersWorkspace')) {
                folderId = Number(state.currentFolder && state.currentFolder.id ? state.currentFolder.id : 0);
            }

            if (state.dragItem) {
                if (!folderId || !can('admin.pastas_associados.edit')) {
                    return;
                }
                event.preventDefault();
                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'move';
                }
                applyDragHighlight(folderId);
                return;
            }

            if (!isNativeFileDrag(event)) {
                return;
            }

            event.preventDefault();
            if (!folderId) {
                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'none';
                }
                return;
            }
            if (!can('admin.pastas_associados.upload') || !canUploadInFolder(folderId)) {
                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'none';
                }
                return;
            }
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'copy';
            }
            applyDragHighlight(folderId);
        });

        document.addEventListener('drop', async (event) => {
            let folderId = getDropFolderIdFromEvent(event);
            if (!folderId && event.target && event.target.closest && event.target.closest('#memberFoldersDropzone, #memberFoldersWorkspace')) {
                folderId = Number(state.currentFolder && state.currentFolder.id ? state.currentFolder.id : 0);
            }

            if (state.dragItem) {
                if (!folderId || !can('admin.pastas_associados.edit')) {
                    state.dragItem = null;
                    clearDragHighlight();
                    return;
                }
                event.preventDefault();
                const drag = state.dragItem;
                state.dragItem = null;
                clearDragHighlight();
                try {
                    await moveItemToTarget(drag.type, drag.id, folderId);
                } catch (err) {
                    setMsg(err.message || 'Falha ao mover item', 'err');
                }
                return;
            }

            if (!isNativeFileDrag(event)) {
                clearDragHighlight();
                return;
            }

            const files = event.dataTransfer && event.dataTransfer.files
                ? Array.from(event.dataTransfer.files || [])
                : [];
            if (!files.length) {
                clearDragHighlight();
                return;
            }
            event.preventDefault();

            if (!can('admin.pastas_associados.upload')) {
                clearDragHighlight();
                setMsg(deny('admin.pastas_associados.upload'), 'err');
                return;
            }
            if (!folderId || !canUploadInFolder(folderId)) {
                clearDragHighlight();
                setMsg('Selecione uma pasta de associado para upload.', 'err');
                return;
            }

            clearDragHighlight();
            try {
                await uploadFiles(files, { folderId });
            } catch (err) {
                setMsg(err.message || 'Falha ao enviar arquivos', 'err');
            }
        });

        document.addEventListener('dragleave', (event) => {
            if (!event.relatedTarget) {
                clearDragHighlight();
            }
        });

        reloadBtn.addEventListener('click', async () => {
            try {
                await loadInit();
            } catch (err) {
                setMsg(err.message || 'Falha ao atualizar dados', 'err');
            }
        });

        refreshTreeBtn.addEventListener('click', async () => {
            try {
                await loadTreeOnly();
                if (state.currentFolder && state.currentFolder.id) {
                    await openFolder(state.currentFolder.id);
                }
            } catch (err) {
                setMsg(err.message || 'Falha ao atualizar arvore', 'err');
            }
        });

        backBtn.addEventListener('click', async () => {
            const parentId = Number(state.currentFolder && state.currentFolder.parent_id ? state.currentFolder.parent_id : 0);
            if (!parentId) return;
            try {
                await openFolder(parentId, { resetPage: true });
            } catch (err) {
                setMsg(err.message || 'Falha ao voltar pasta', 'err');
            }
        });

        searchEl.addEventListener('input', () => {
            const value = String(searchEl.value || '').trim();
            state.search = value;
            state.meta.q = value;
            if (searchTimer) {
                clearTimeout(searchTimer);
            }
            searchTimer = setTimeout(async () => {
                if (!state.currentFolder || !state.currentFolder.id) return;
                try {
                    await openFolder(state.currentFolder.id, { resetPage: true });
                } catch (err) {
                    setMsg(err.message || 'Falha na busca de arquivos', 'err');
                }
            }, 250);
        });

        newFolderBtn.addEventListener('click', async () => {
            if (!can('admin.pastas_associados.create')) {
                setMsg(deny('admin.pastas_associados.create'), 'err');
                return;
            }
            try {
                await createFolder();
            } catch (err) {
                setMsg(err.message || 'Falha ao criar pasta', 'err');
            }
        });

        uploadBtn.addEventListener('click', () => {
            if (!can('admin.pastas_associados.upload')) {
                setMsg(deny('admin.pastas_associados.upload'), 'err');
                return;
            }
            if (!canUploadInCurrentFolder()) {
                setMsg('Selecione uma pasta de associado para upload.', 'err');
                return;
            }
            uploadInput.click();
        });

        uploadInput.addEventListener('change', async (event) => {
            const files = event.target.files ? Array.from(event.target.files) : [];
            event.target.value = '';
            if (!files.length) return;
            try {
                await uploadFiles(files);
            } catch (err) {
                setMsg(err.message || 'Falha ao enviar arquivo', 'err');
            }
        });

        prevPageBtn.addEventListener('click', async () => {
            if (!state.currentFolder || !state.currentFolder.id) return;
            if (!state.meta.files.has_prev) return;
            state.meta.files.page = Math.max(1, Number(state.meta.files.page || 1) - 1);
            try {
                await openFolder(state.currentFolder.id);
            } catch (err) {
                setMsg(err.message || 'Falha ao paginar', 'err');
            }
        });

        nextPageBtn.addEventListener('click', async () => {
            if (!state.currentFolder || !state.currentFolder.id) return;
            if (!state.meta.files.has_next) return;
            state.meta.files.page = Number(state.meta.files.page || 1) + 1;
            try {
                await openFolder(state.currentFolder.id);
            } catch (err) {
                setMsg(err.message || 'Falha ao paginar', 'err');
            }
        });
    }

    function applyPermissionState() {
        if (!can('admin.pastas_associados.create')) {
            newFolderBtn.disabled = true;
            newFolderBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
        if (!can('admin.pastas_associados.upload')) {
            uploadBtn.disabled = true;
            uploadBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
        if (!can('admin.pastas_associados.view')) {
            treeEl.innerHTML = '<p class="text-sm text-red-600">Sem permissao para visualizar pastas.</p>';
            workspaceEl.innerHTML = '<div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">Acesso bloqueado.</div>';
            setMsg(deny('admin.pastas_associados.view'), 'err');
            return false;
        }
        return true;
    }

    bindEvents();
    if (!applyPermissionState()) {
        return;
    }

    loadInit().catch((err) => {
        setMsg(err.message || 'Falha ao carregar pastas de associados', 'err');
    });
})();
</script>
